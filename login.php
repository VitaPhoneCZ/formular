<?php 
require_once 'inc/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vyplň prosím všechna pole!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Špatné uživatelské jméno nebo heslo!';
        }
    }
}

// Pokud je už přihlášený → přesměruj na homepage
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
<body>
    <main>
        <section class="form-container">
            <div class="form-header">
                <img src="img/logo.png" alt="logo" class="logo">
                <h2>Přihlášení</h2>
            </div>

            <?php if ($error): ?>
                <p style="color:#ff4d4d; text-align:center; background:#330000; padding:12px; border-radius:4px; margin:16px 0;">
                    <?= htmlspecialchars($error) ?>
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
                    <input type="text" name="username" id="username" placeholder="Zadej uživatelské jméno" class="input-field" required>
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

                <button type="submit" class="submit-button">Přihlásit se</button>
            </form>

            <div class="form-footer">
                <p>Nemáš účet? <a href="register.php">Vytvořit účet</a></p>
            </div>
        </section>
    </main>
</body>
</html>