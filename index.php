<?php 
require_once 'inc/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location:	login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, email, password, avatar_url, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$createdAt = $user['created_at'] ? new DateTime($user['created_at']) : null;
$daysWithUs = $createdAt ? max(1, $createdAt->diff(new DateTime())->days + 1) : null;

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
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body class="dashboard-body">
    <main class="dashboard-shell">
        <header class="dashboard-header">
            <div class="header-left">
                <div class="avatar-frame">
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="avatar-large">
                </div>
                <img src="img/logo.png" alt="logo" class="logo">
                <div>
                    <p class="eyebrow">Dashboard</p>
                    <h1>Ahoj, <?= htmlspecialchars($user['username']) ?> 👋</h1>
                    <p class="subtitle">Všechny důležité věci máš pěkně pohromadě.</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="ghost-button">Odhlásit se</a>
            </div>
        </header>

        <section class="quick-stats">
            <article class="stat-card">
                <p class="stat-label">Dní s námi</p>
                <p class="stat-value"><?= $daysWithUs ?? '—' ?></p>
                <?php if ($createdAt): ?>
                    <p class="stat-detail">Účet vytvořen <?= $createdAt->format('d.m.Y H:i') ?></p>
                <?php else: ?>
                    <p class="stat-detail">Žádné datum registrace k dispozici</p>
                <?php endif; ?>
            </article>
            <article class="stat-card">
                <p class="stat-label">E-mail</p>
                <p class="stat-value"><?= htmlspecialchars($user['email']) ?></p>
                <p class="stat-detail">Slouží pro notifikace i obnovu účtu</p>
            </article>
            <article class="stat-card">
                <p class="stat-label">Zabezpečení</p>
                <p class="status-pill">Aktivní</p>
                <p class="stat-detail">Hashování hesel pomocí bcrypt</p>
            </article>
        </section>

        <section class="dashboard-grid single-card">
            <article class="card profile-card">
                <div class="card-header">
                    <h2>Nastavení profilu</h2>
                    <p>Spravuj své údaje na dedikované stránce s plnou kontrolou.</p>
                </div>

                <ul class="profile-highlights">
                    <li>✅ úprava jména a e-mailu</li>
                    <li>✅ změna hesla s potvrzením</li>
                    <li>✅ vlastní avatar nebo generovaný</li>
                </ul>

                <a href="profile.php" class="outline-button">Otevřít nastavení</a>
                <p class="form-hint">Bezpečnostně chráněno – každá změna vyžaduje potvrzení heslem.</p>
            </article>
        </section>
    </main>
</body>
</html>