<?php 
require_once 'inc/config.php';

// Pokud není přihlášený → přesměruj na login
if (!isset($_SESSION['user_id'])) {
    header("Location:	login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vítej!</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <main>
        <section class="form-container" style="text-align:center;">
            <div class="form-header">
                <img src="img/logo.png" alt="logo" class="logo">
                <h2>Vítej zpět, <?= htmlspecialchars($_SESSION['username']) ?>! 🎉</h2>
            </div>

            <p>Jsi úspěšně přihlášený!</p>
            
            <br><br>
            <a href="logout.php" class="submit-button" style="width:auto; padding:12px 24px; text-decoration:none;">
                Odhlásit se
            </a>
        </section>
    </main>
</body>
</html>