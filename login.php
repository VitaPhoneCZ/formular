<?php 
require_once 'inc/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        $error = 'Vyplň prosím všechna pole.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, password, avatar_url FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['avatar_url'] = $user['avatar_url'];
            header("Location: index.php");
            exit;
        }

        $error = 'Špatné uživatelské jméno nebo heslo.';
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

                <p class="form-hint center">Zapomněl jsi heslo? <span class="muted">Reset přidáme v další verzi.</span></p>
            </div>
        </section>
    </main>
</body>
</html>