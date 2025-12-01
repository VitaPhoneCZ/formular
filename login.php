<?php 
require_once 'inc/config.php';
require_once 'inc/GoogleAuthenticator.php';

$error = '';
$require2FA = false;
$loginUserId = null;

// Handle 2FA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_2fa'])) {
    $code = trim($_POST['auth_code'] ?? '');
    $userId = $_SESSION['pending_user_id'] ?? null;
    
    if (empty($code)) {
        $error = 'Zadej ověřovací kód.';
        $require2FA = true;
    } elseif (!$userId) {
        $error = 'Platnost přihlášení vypršela. Začni znovu.';
        unset($_SESSION['pending_user_id']);
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, avatar_url, google_auth_secret FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['google_auth_secret']) {
            if (GoogleAuthenticator::verifyCode($user['google_auth_secret'], $code)) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['avatar_url'] = $user['avatar_url'];
                unset($_SESSION['pending_user_id']);
                header("Location: index.php");
                exit;
            } else {
                $error = 'Neplatný ověřovací kód. Zkus to znovu.';
                $require2FA = true;
                $loginUserId = $userId;
            }
        } else {
            $error = 'Uživatel nemá nastavenou 2FA.';
            unset($_SESSION['pending_user_id']);
        }
    }
}

// Handle initial login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['verify_2fa'])) {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        $error = 'Vyplň prosím všechna pole.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, password, avatar_url, google_auth_secret FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if user has 2FA enabled
            if ($user['google_auth_secret']) {
                // Require 2FA code
                $_SESSION['pending_user_id'] = $user['id'];
                $require2FA = true;
                $loginUserId = $user['id'];
            } else {
                // No 2FA, login directly
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['avatar_url'] = $user['avatar_url'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = 'Špatné uživatelské jméno nebo heslo.';
        }
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body class="auth-body">
    <main class="auth-wrapper">
        <section class="auth-card">
            <div class="auth-info">
                <div class="tag">Modern Auth</div>
                <h1>Vítej zpět!</h1>
                <p>Pokračuj v tom, co máš rozpracované. Tvůj dashboard už čeká.</p>
                <ul class="auth-highlights">
                    <li>✅ Temný cyberpunkový design</li>
                    <li>✅ Hashovaná hesla & bezpečné přihlášení</li>
                    <li>✅ Vše připraveno pro další funkce</li>
                </ul>
                <a href="register.php" class="ghost-button">Nemáš účet? Registruj se</a>
            </div>

            <div class="form-panel">
                <div class="form-header compact">
                    <img src="img/logo.png" alt="logo" class="logo">
                    <div>
                        <p class="eyebrow">Login</p>
                        <h2>Přihlášení</h2>
                    </div>
                </div>

                <?php if ($error): ?>
                    <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <?php if ($require2FA): ?>
                    <!-- 2FA Verification Step -->
                    <div class="auth-form">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem;">Dvoufázové ověření</h3>
                            <p style="margin-bottom: 1.5rem; color: #888;">Zadej 6místný kód z aplikace Google Authenticator</p>
                        </div>

                        <form method="POST" class="auth-form">
                            <input type="hidden" name="verify_2fa" value="1">
                            <label class="floating-input">
                                <span>Ověřovací kód (6 číslic)</span>
                                <input type="text" name="auth_code" placeholder="123456" maxlength="6" pattern="[0-9]{6}" required autocomplete="off" style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;">
                            </label>
                            <button type="submit" class="submit-button full-width">Ověřit a přihlásit</button>
                        </form>
                        <p class="form-hint center" style="margin-top: 1rem;">
                            <a href="login.php" style="color: #888;">← Zpět na přihlášení</a>
                        </p>
                    </div>
                <?php else: ?>
                    <!-- Initial Login Form -->
                    <form method="POST" class="auth-form">
                        <label class="floating-input">
                            <span>Uživatelské jméno nebo e-mail</span>
                            <input type="text" name="identity" placeholder="např. cyberwolf nebo me@cyber.dev" required>
                        </label>

                        <label class="floating-input">
                            <span>Heslo</span>
                            <input type="password" name="password" placeholder="••••••••" required>
                        </label>

                        <button type="submit" class="submit-button full-width">Pokračovat</button>
                    </form>
                <?php endif; ?>

                <p class="form-hint center">Zapomněl jsi heslo? <span class="muted">Reset přidáme v další verzi.</span></p>
            </div>
        </section>
    </main>
</body>
</html>