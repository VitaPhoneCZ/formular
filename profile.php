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

// Helper function to get avatar URL (handles uploaded files and URLs)
function getAvatarUrl($avatarUrl, $username) {
    if (empty($avatarUrl)) {
        return 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($username);
    }
    // If it's a relative path (uploaded file), prepend the base path
    if (strpos($avatarUrl, 'http') !== 0 && strpos($avatarUrl, '/') === 0) {
        return $avatarUrl;
    }
    // If it's a relative path without leading slash
    if (strpos($avatarUrl, 'http') !== 0 && strpos($avatarUrl, 'uploads/') === 0) {
        return '/' . $avatarUrl;
    }
    // Otherwise it's a full URL (DiceBear or external)
    return $avatarUrl;
}

$avatarUrl = getAvatarUrl($user['avatar_url'], $user['username']);
$require2FA = false;

// Configuration for file uploads
define('UPLOAD_DIR', __DIR__ . '/uploads/avatars/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newUsername = trim($_POST['new_username'] ?? '');
    $newEmail = trim($_POST['new_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $newAvatar = trim($_POST['avatar_url'] ?? '');
    $removeAvatar = isset($_POST['remove_avatar']);
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

        // Handle avatar upload
        if (!$profileError && isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar_upload'];
            
            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                $profileError = 'Soubor je příliš velký. Maximální velikost je 5MB.';
            } elseif (!in_array($file['type'], ALLOWED_TYPES)) {
                $profileError = 'Povolené formáty jsou pouze JPG, PNG a GIF.';
            } else {
                // Validate file extension
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $profileError = 'Neplatná přípona souboru. Povolené jsou: jpg, png, gif.';
                } else {
                    // Validate image by checking if it's actually an image
                    $imageInfo = @getimagesize($file['tmp_name']);
                    if ($imageInfo === false) {
                        $profileError = 'Nahraný soubor není platný obrázek.';
                    } else {
                        // Delete old uploaded avatar if exists
                        if ($user['avatar_url'] && strpos($user['avatar_url'], 'uploads/avatars/') !== false) {
                            $oldPath = __DIR__ . '/' . $user['avatar_url'];
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                        
                        // Generate unique filename with collision protection
                        $maxAttempts = 10;
                        $attempt = 0;
                        $filename = '';
                        $targetPath = '';
                        
                        do {
                            // Create unique filename: avatar_{user_id}_{timestamp}_{random}.{ext}
                            $random = bin2hex(random_bytes(4)); // 8 character random string
                            $filename = 'avatar_' . $user['id'] . '_' . time() . '_' . $random . '.' . $ext;
                            $targetPath = UPLOAD_DIR . $filename;
                            $attempt++;
                            
                            // If file exists (very unlikely but possible), try again
                            if (file_exists($targetPath) && $attempt < $maxAttempts) {
                                usleep(1000); // Wait 1ms before retry
                                continue;
                            }
                            
                            // If we've tried too many times, use hash-based name
                            if ($attempt >= $maxAttempts) {
                                $fileHash = hash_file('md5', $file['tmp_name']);
                                $filename = 'avatar_' . $user['id'] . '_' . substr($fileHash, 0, 8) . '_' . time() . '.' . $ext;
                                $targetPath = UPLOAD_DIR . $filename;
                            }
                            
                            break;
                        } while (true);
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            // Verify file was actually written
                            if (!file_exists($targetPath) || filesize($targetPath) === 0) {
                                $profileError = 'Chyba při ukládání souboru. Zkus to znovu.';
                                @unlink($targetPath); // Clean up empty file
                            } else {
                                $relativePath = 'uploads/avatars/' . $filename;
                                $updateAvatar = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                                $updateAvatar->execute([$relativePath, $user['id']]);
                                $user['avatar_url'] = $relativePath;
                                $_SESSION['avatar_url'] = $relativePath;
                                $avatarUrl = getAvatarUrl($relativePath, $user['username']);
                                $changes++;
                            }
                        } else {
                            // Check specific error reasons
                            if (!is_writable(UPLOAD_DIR)) {
                                $profileError = 'Složka pro nahrávání není zapisovatelná. Kontaktuj administrátora.';
                            } elseif (file_exists($targetPath)) {
                                $profileError = 'Soubor s tímto názvem již existuje. Zkus to znovu.';
                            } else {
                                $profileError = 'Chyba při nahrávání souboru. Zkus to znovu.';
                            }
                        }
                    }
                }
            }
        }
        
        // Handle avatar URL (if no file upload)
        if (!$profileError && !isset($_FILES['avatar_upload']['error']) && $newAvatar !== '') {
            if ($newAvatar !== $user['avatar_url']) {
                if (!filter_var($newAvatar, FILTER_VALIDATE_URL)) {
                    $profileError = 'Avatar musí být platná URL adresa.';
                } else {
                    // Delete old uploaded avatar if exists
                    if ($user['avatar_url'] && strpos($user['avatar_url'], 'uploads/avatars/') !== false) {
                        $oldPath = __DIR__ . '/' . $user['avatar_url'];
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    
                    $updateAvatar = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                    $updateAvatar->execute([$newAvatar, $user['id']]);
                    $user['avatar_url'] = $newAvatar;
                    $_SESSION['avatar_url'] = $newAvatar;
                    $avatarUrl = getAvatarUrl($newAvatar, $user['username']);
                    $changes++;
                }
            }
        }
        
        // Handle avatar removal
        if (!$profileError && $removeAvatar) {
            // Delete old uploaded avatar if exists
            if ($user['avatar_url'] && strpos($user['avatar_url'], 'uploads/avatars/') !== false) {
                $oldPath = __DIR__ . '/' . $user['avatar_url'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            
            $resetAvatar = $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
            $resetAvatar->execute([$user['id']]);
            $user['avatar_url'] = null;
            $_SESSION['avatar_url'] = null;
            $avatarUrl = getAvatarUrl(null, $user['username']);
            $changes++;
        }

        if (!$profileError && $changes === 0) {
            $profileError = 'Neprovedl(a) jsi žádnou změnu.';
        } elseif (!$profileError && $changes > 0) {
            $profileSuccess = 'Profil byl úspěšně aktualizován.';
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            $avatarUrl = getAvatarUrl($user['avatar_url'], $user['username']);
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
    <script src="script/script.js" defer></script>
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

                <form method="POST" enctype="multipart/form-data" class="profile-form">
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
                            <label for="avatar_upload">Nahrát profilový obrázek</label>
                            <input type="file" id="avatar_upload" name="avatar_upload" accept="image/jpeg,image/jpg,image/png,image/gif">
                        </div>
                        <p class="form-hint">Povolené formáty: JPG, PNG, GIF. Maximální velikost: 5MB.</p>
                    </div>

                    <div class="form-row">
                        <div class="floating-label">
                            <label for="avatar_url">Nebo zadej URL adresu</label>
                            <input type="url" id="avatar_url" name="avatar_url" placeholder="https://..." value="<?= htmlspecialchars((strpos($user['avatar_url'] ?? '', 'http') === 0) ? $user['avatar_url'] : '') ?>">
                        </div>
                        <p class="form-hint">Nebo zadej URL adresu obrázku. Pokud necháš prázdné, použije se výchozí generovaný avatar.</p>
                    </div>

                    <?php if ($user['avatar_url']): ?>
                    <div class="form-row">
                        <label class="terms">
                            <input type="checkbox" id="remove_avatar" name="remove_avatar" value="1">
                            Odstranit aktuální avatar a použít výchozí
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="avatar-preview">
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Aktuální avatar" id="avatar-preview-img">
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

