<?php require_once 'inc/config.php'; 

// Zpracování formuláře
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm-password'] ?? '';
    $terms    = isset($_POST['terms']);

    if (empty($username) || empty($password) || empty($confirm)) {
        $error = 'Všechna pole jsou povinná!';
    } elseif ($password !== $confirm) {
        $error = 'Hesla se neshodují!';
    } elseif (strlen($password) < 6) {
        $error = 'Heslo musí mít alespoň 6 znaků!';
    } elseif (!$terms) {
        $error = 'Musíte souhlasit s podmínkami!';
    } else {
        // Kontrola, jestli uživatel už existuje
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Tento uživatel už existuje!';
        } else {
            // Registrace
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
            
            $success = 'Registrace úspěšná! Nyní se můžeš <a href="login.php" style="color:#ff4d4d;">přihlásit</a>.';
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
<body>
    <main>
        <section class="form-container">
            <div class="form-header">
                <img src="img/logo.png" alt="logo" class="logo">
                <h2>Vytvořit účet</h2>
            </div>

            <?php if ($error): ?>
                <p style="color:#ff4d4d; text-align:center; background:#330000; padding:12px; border-radius:4px; margin:16px 0;">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p style="color:#00cc00; text-align:center; background:#003300; padding:12px; border-radius:4px; margin:16px 0;">
                    <?= $success ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Uživatelské jméno
                    </label>
                    <input type="text" name="username" id="username" placeholder="Zadej uživatelské jméno" class="input-field" value="<?= htmlspecialchars($username ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Heslo
                    </label>
                    <input type="password" name="password" id="password" placeholder="Zadej heslo" class="input-field" required>
                </div>

                <div class="form-group">
                    <label for="confirm-password">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Potvrď heslo
                    </label>
                    <input type="password" name="confirm-password" id="confirm-password" placeholder="Zopakuj heslo" class="input-field" required>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="terms" name="terms" class="checkbox" required>
                    <label for="terms">Souhlasím s podmínkami služby a zásadami ochrany osobních údajů</label>
                </div>

                <div class="captcha">
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
                        <div>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/Cloudflare_Logo.svg/1024px-Cloudflare_Logo.svg.png" 
                                 alt="Cloudflare" class="cloudflare-logo">
                        </div>
                        <div><small style="color:#808080;">Soukromí - Podmínky</small></div>
                    </div>
                </div>

                <button type="submit" class="submit-button" disabled>Vytvořit účet</button>
            </form>

            <div class="form-footer">
                <p>Máš už účet? <a href="login.php">Přihlásit se</a></p>
            </div>
        </section>
    </main>
</body>
</html>