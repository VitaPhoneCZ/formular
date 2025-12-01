<?php 
require_once 'inc/config.php';
require_once 'inc/GoogleAuthenticator.php';

$error = '';
$success = '';
$username = $username ?? '';
$email = $email ?? '';
$step = $_GET['step'] ?? '1'; // Step 1: form, Step 2: verify 2FA

// Handle 2FA verification (step 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $code = trim($_POST['auth_code'] ?? '');
    
    if (empty($code)) {
        $error = 'Zadej ověřovací kód.';
    } elseif (!isset($_SESSION['reg_secret']) || !isset($_SESSION['reg_data'])) {
        $error = 'Platnost registrace vypršela. Začni znovu.';
        unset($_SESSION['reg_secret'], $_SESSION['reg_data']);
        $step = '1';
    } else {
        $secret = $_SESSION['reg_secret'];
        if (GoogleAuthenticator::verifyCode($secret, $code)) {
            // Registration data is valid, create user
            $regData = $_SESSION['reg_data'];
            $hash = password_hash($regData['password'], PASSWORD_DEFAULT);
            $avatarUrl = 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($regData['username']);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, avatar_url, google_auth_secret) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$regData['username'], $regData['email'], $hash, $avatarUrl, $secret]);
            
            // Clear session
            unset($_SESSION['reg_secret'], $_SESSION['reg_data']);
            
            $success = 'Registrace úspěšná! Nyní se můžeš <a href="login.php">přihlásit</a>.';
            $step = '1';
            $username = '';
            $email = '';
        } else {
            $error = 'Neplatný ověřovací kód. Zkus to znovu.';
        }
    }
}

// Handle initial registration form (step 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['verify_code'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm-password'] ?? '';
    $terms    = isset($_POST['terms']);

    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Všechna pole jsou povinná.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Zadej platný e-mail.';
    } elseif ($password !== $confirm) {
        $error = 'Hesla se neshodují.';
    } elseif (strlen($password) < 6) {
        $error = 'Heslo musí mít alespoň 6 znaků.';
    } elseif (!$terms) {
        $error = 'Musíš souhlasit s podmínkami.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Tento uživatel nebo e-mail už existuje.';
        } else {
            // Generate secret and store in session
            $secret = GoogleAuthenticator::generateSecret();
            $_SESSION['reg_secret'] = $secret;
            $_SESSION['reg_data'] = [
                'username' => $username,
                'email' => $email,
                'password' => $password
            ];
            // Redirect to step 2
            header("Location: register.php?step=2");
            exit;
        }
    }
}

// Get QR code URL if in step 2
$qrCodeUrl = '';
$secret = '';
if ($step === '2') {
    if (!isset($_SESSION['reg_secret']) || !isset($_SESSION['reg_data'])) {
        // Session expired or invalid, redirect to step 1
        header("Location: register.php");
        exit;
    }
    $secret = $_SESSION['reg_secret'];
    $usernameForQR = $_SESSION['reg_data']['username'] ?? 'User';
    $qrCodeUrl = GoogleAuthenticator::getQRCodeUrl($usernameForQR, $secret);
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vytvořit účet</title>
    <link rel="stylesheet" href="style/style.css">
    <script src="script/script.js" defer></script>
</head>
<body class="auth-body">
    <main class="auth-wrapper">
        <section class="auth-card">
            <div class="auth-info">
                <div class="tag">Start here</div>
                <h1>Nový účet během minuty</h1>
                <p>Moderní rozhraní, zabezpečené přihlášení a krásný dashboard hned po registraci.</p>
                <div class="pill-group">
                    <span class="pill">Hashovaná hesla</span>
                    <span class="pill">Rychlá registrace</span>
                    <span class="pill">Responzivní UI</span>
                </div>
                <ol class="steps">
                    <li>Vyplň uživatelské jméno a heslo</li>
                    <li>Naskenuj QR kód do Google Authenticator</li>
                    <li>Zadej ověřovací kód a dokonči registraci</li>
                </ol>
                <a href="login.php" class="ghost-button">Mám účet → Přihlásit</a>
            </div>

            <div class="form-panel">
                <div class="form-header compact">
                    <img src="img/logo.png" alt="logo" class="logo">
                    <div>
                        <p class="eyebrow">Signup</p>
                        <h2>Vytvořit účet</h2>
                    </div>
                </div>

                <?php if ($error): ?>
                    <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <?php if ($success): ?>
                    <p class="alert alert-success"><?= $success ?></p>
                <?php endif; ?>

                <?php if ($step === '2'): ?>
                    <!-- Step 2: Verify 2FA Code -->
                    <div class="auth-form">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem;">Nastav Google Authenticator</h3>
                            <p style="margin-bottom: 1.5rem; color: #888;">Naskenuj tento QR kód do aplikace Google Authenticator (nebo podobné aplikace)</p>
                            <div style="display: inline-block; padding: 1rem; background: #fff; border-radius: 8px; margin-bottom: 1rem;">
                                <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code" style="max-width: 250px; height: auto;">
                            </div>
                            <p style="font-size: 0.9rem; color: #aaa; margin-top: 1rem;">
                                <strong>Secret:</strong> <code style="background: #222; padding: 0.25rem 0.5rem; border-radius: 4px; font-family: monospace;"><?= htmlspecialchars($secret) ?></code>
                            </p>
                            <p style="font-size: 0.85rem; color: #888; margin-top: 1rem;">Pokud nemůžeš naskenovat QR kód, zadej secret ručně.</p>
                        </div>

                        <form method="POST" class="auth-form">
                            <input type="hidden" name="verify_code" value="1">
                            <label class="floating-input">
                                <span>Ověřovací kód (6 číslic)</span>
                                <input type="text" name="auth_code" placeholder="123456" maxlength="6" pattern="[0-9]{6}" required autocomplete="off" style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;">
                            </label>
                            <p class="form-hint center">Zadej 6místný kód z aplikace Google Authenticator</p>
                            <button type="submit" class="submit-button full-width">Dokončit registraci</button>
                        </form>
                        <p class="form-hint center" style="margin-top: 1rem;">
                            <a href="register.php" style="color: #888;">← Zpět na formulář</a>
                        </p>
                    </div>
                <?php else: ?>
                    <!-- Step 1: Registration Form -->
                    <form method="POST" class="auth-form">
                    <label class="floating-input">
                        <span>Uživatelské jméno</span>
                        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="např. cybernova" required>
                    </label>

                    <label class="floating-input">
                        <span>E-mail</span>
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="např. me@cybernova.dev" required>
                    </label>

                    <label class="floating-input">
                        <span>Heslo</span>
                        <input type="password" name="password" placeholder="min. 6 znaků" required>
                    </label>

                    <label class="floating-input">
                        <span>Potvrď heslo</span>
                        <input type="password" name="confirm-password" placeholder="zopakuj heslo" required>
                    </label>

                    <label class="terms">
                        <input type="checkbox" id="terms" name="terms" required>
                        Souhlasím s podmínkami a zásadami ochrany osobních údajů
                    </label>

                    <div class="captcha auth-captcha">
                        <label class="cf-challenge" id="fakeCaptchaLabel">
                            <input type="checkbox" id="fakeCaptcha">
                            <span class="checkmark">
                                <svg class="check-icon" viewBox="0 0 24 24">
                                    <path d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <span class="cf-text">Nejsem robot</span>
                        </label>
                        <div class="cloudflare">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/Cloudflare_Logo.svg/1024px-Cloudflare_Logo.svg.png" 
                                 alt="Cloudflare" class="cloudflare-logo">
                            <small>Soukromí • Podmínky</small>
                        </div>
                    </div>

                    <button type="submit" class="submit-button full-width" disabled>Pokračovat k 2FA</button>
                </form>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>