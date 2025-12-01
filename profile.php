<?php 
require_once 'inc/config.php';
require_once 'inc/GoogleAuthenticator.php';

if (!isset($_SESSION['user_id'])) {
    header("Location:	login.php");
    exit;
}

$profileError = '';
$profileSuccess = '';

$stmt = $pdo->prepare("SELECT id, username, email, password, avatar_url, created_at, google_auth_secret FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$avatarUrl = $user['avatar_url'] ?: 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($user['username']);
$require2FA = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newUsername = trim($_POST['new_username'] ?? '');
    $newEmail = trim($_POST['new_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $newAvatar = trim($_POST['avatar_url'] ?? '');
    $changes = 0;

    if (empty($currentPassword) || !password_verify($currentPassword, $user['password'])) {
        $profileError = 'Nejprve potvrď své aktuální heslo.';
    } else {
        if ($newUsername && $newUsername !== $user['username']) {
            if (strlen($newUsername) < 3) {
                $profileError = 'Uživatelské jméno musí mít alespoň 3 znaky.';
            } else {
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $check->execute([$newUsername, $user['id']]);
                if ($check->fetch()) {
                    $profileError = 'Toto uživatelské jméno už někdo používá.';
                } else {
                    $updateUsername = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                    $updateUsername->execute([$newUsername, $user['id']]);
                    $user['username'] = $newUsername;
                    $_SESSION['username'] = $newUsername;
                    $changes++;
                }
            }
        }

        if (!$profileError && $newEmail && $newEmail !== $user['email']) {
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $profileError = 'Zadej platný e-mail.';
            } else {
                $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $checkEmail->execute([$newEmail, $user['id']]);
                if ($checkEmail->fetch()) {
                    $profileError = 'Tento e-mail už někdo používá.';
                } else {
                    $updateEmail = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $updateEmail->execute([$newEmail, $user['id']]);
                    $user['email'] = $newEmail;
                    $_SESSION['email'] = $newEmail;
                    $changes++;
                }
            }
        }

        if (!$profileError && $newPassword) {
            if (strlen($newPassword) < 6) {
                $profileError = 'Nové heslo musí mít alespoň 6 znaků.';
            } elseif ($newPassword !== $confirmPassword) {
                $profileError = 'Nové heslo a potvrzení se neshodují.';
            } elseif (!$user['google_auth_secret']) {
                $profileError = 'Pro změnu hesla musíš mít nastavenou 2FA.';
            } else {
                // Check if 2FA code is provided
                $authCode = trim($_POST['auth_code_2fa'] ?? '');
                if (empty($authCode)) {
                    $profileError = 'Pro změnu hesla je vyžadován ověřovací kód z Google Authenticator.';
                    $require2FA = true;
                } elseif (!GoogleAuthenticator::verifyCode($user['google_auth_secret'], $authCode)) {
                    $profileError = 'Neplatný ověřovací kód. Zkus to znovu.';
                    $require2FA = true;
                } else {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updatePassword = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updatePassword->execute([$hash, $user['id']]);
                    $changes++;
                }
            }
        }

        if (!$profileError) {
            if ($newAvatar !== '') {
                if ($newAvatar !== $user['avatar_url']) {
                    if (!filter_var($newAvatar, FILTER_VALIDATE_URL)) {
                        $profileError = 'Avatar musí být platná URL adresa.';
                    } else {
                        $updateAvatar = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                        $updateAvatar->execute([$newAvatar, $user['id']]);
                        $user['avatar_url'] = $newAvatar;
                        $_SESSION['avatar_url'] = $newAvatar;
                        $avatarUrl = $newAvatar;
                        $changes++;
                    }
                }
            } elseif ($user['avatar_url']) {
                $resetAvatar = $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
                $resetAvatar->execute([$user['id']]);
                $user['avatar_url'] = null;
                $_SESSION['avatar_url'] = null;
                $avatarUrl = 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($user['username']);
                $changes++;
            }
        }

        if (!$profileError && $changes === 0) {
            $profileError = 'Neprovedl(a) jsi žádnou změnu.';
        } elseif (!$profileError && $changes > 0) {
            $profileSuccess = 'Profil byl úspěšně aktualizován.';
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            $avatarUrl = $user['avatar_url'] ?: 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($user['username']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nastavení profilu</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body class="dashboard-body">
    <main class="dashboard-shell profile-shell">
        <header class="profile-header">
            <div class="header-left">
                <div class="avatar-frame">
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="avatar-large">
                </div>
                <div>
                    <p class="eyebrow">Profil</p>
                    <h1>Nastavení účtu</h1>
                    <p class="subtitle">Uprav svoje údaje, avatar i heslo z jednoho místa.</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php" class="ghost-button">← Zpět na dashboard</a>
            </div>
        </header>

        <section class="profile-grid">
            <article class="card profile-card settings-card">
                <div class="card-header">
                    <h2>Osobní údaje</h2>
                    <p>Veškeré změny jsou aplikovány okamžitě po potvrzení.</p>
                </div>

                <?php if ($profileError): ?>
                    <p class="alert alert-error"><?= htmlspecialchars($profileError) ?></p>
                <?php endif; ?>

                <?php if ($profileSuccess): ?>
                    <p class="alert alert-success"><?= htmlspecialchars($profileSuccess) ?></p>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="floating-label">
                            <label for="new_username">Nové uživatelské jméno</label>
                            <input type="text" id="new_username" name="new_username" placeholder="<?= htmlspecialchars($user['username']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="floating-label">
                            <label for="new_email">E-mail</label>
                            <input type="email" id="new_email" name="new_email" placeholder="<?= htmlspecialchars($user['email']) ?>">
                        </div>
                    </div>

                    <div class="form-row two-columns">
                        <div class="floating-label">
                            <label for="new_password">Nové heslo</label>
                            <input type="password" id="new_password" name="new_password" placeholder="********">
                        </div>
                        <div class="floating-label">
                            <label for="confirm_password">Potvrzení hesla</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="********">
                        </div>
                    </div>

                    <?php if ($require2FA || (isset($_POST['new_password']) && !empty($_POST['new_password']))): ?>
                    <div class="form-row">
                        <div class="floating-label">
                            <label for="auth_code_2fa">Ověřovací kód z Google Authenticator (6 číslic) *</label>
                            <input type="text" id="auth_code_2fa" name="auth_code_2fa" placeholder="123456" maxlength="6" pattern="[0-9]{6}" style="text-align: center; font-size: 1.2rem; letter-spacing: 0.3rem;" autocomplete="off">
                        </div>
                        <p class="form-hint">* Povinné pro změnu hesla. Zadej 6místný kód z aplikace Google Authenticator.</p>
                    </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="floating-label">
                            <label for="avatar_url">Avatar URL</label>
                            <input type="url" id="avatar_url" name="avatar_url" placeholder="https://..." value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>">
                        </div>
                        <p class="form-hint">Nech prázdné pro výchozí generovaný avatar.</p>
                        <div class="avatar-preview">
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Aktuální avatar">
                            <small>Aktuální náhled</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="floating-label">
                            <label for="current_password">Aktuální heslo *</label>
                            <input type="password" id="current_password" name="current_password" placeholder="****************" required>
                        </div>
                        <p class="form-hint">* Bezpečnostně vyžadováno pro jakoukoliv změnu.</p>
                    </div>

                    <button type="submit" class="submit-button full-width">Uložit změny</button>
                </form>
            </article>

            <article class="card checklist-card tips-card">
                <div class="card-header">
                    <h2>Tipy pro bezpečí</h2>
                    <p>Drž svůj účet v bezpečí díky těmto doporučením.</p>
                </div>
                <ul class="checklist">
                    <li>
                        <span>Pravidelně měň heslo</span>
                        <small>Zvlášť po změně zařízení nebo úniku dat.</small>
                    </li>
                    <li>
                        <span>Kontroluj e-mail</span>
                        <small>Aktivní adresa je klíčová pro obnovu účtu.</small>
                    </li>
                    <li>
                        <span>Pečuj o avatar</span>
                        <small>Aktualizovaný avatar pomůže identifikaci.</small>
                    </li>
                    <li>
                        <span>Skutečná 2FA</span>
                        <small>Jakmile bude dostupná, hned ji zapni.</small>
                    </li>
                </ul>
            </article>
        </section>
    </main>
</body>
</html>

