<?php require_once 'inc/config.php'; 

$error = '';
$success = '';
$username = $username ?? '';
$email = $email ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $avatarUrl = 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($username);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, avatar_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $avatarUrl]);
            $success = 'Registrace úspěšná! Nyní se můžeš <a href="login.php">přihlásit</a>.';
            $username = '';
            $email = '';
        }
    }
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
                    <li>Potvrď podmínky a captcha</li>
                    <li>Hotovo! Přihlas se a pokračuj</li>
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

                    <button type="submit" class="submit-button full-width" disabled>Vytvořit účet</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>