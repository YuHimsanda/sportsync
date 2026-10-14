<?php
require_once __DIR__ . "/functions.php";
$pageTitle = $pageTitle ?? APP_NAME;
$user = currentUser();
$flash = consumeFlash();
$unreadMessages = 0;
if ($user && $user["academy_id"]) {
    $unreadStatement = $conn->prepare(
        "SELECT COUNT(*) FROM messages WHERE is_read=0 AND (recipient_id=? OR (recipient_id IS NULL AND academy_id=?))",
    );
    $userId = (int) $user["id"];
    $academyId = (int) $user["academy_id"];
    $unreadStatement->bind_param("ii", $userId, $academyId);
    $unreadStatement->execute();
    $res = $unreadStatement->get_result();
    $row = $res->fetch_row();
    $unreadMessages = (int) ($row[0] ?? 0);
    $unreadStatement->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= APP_NAME ?></title>
    <link rel="icon" href="Media/favcon.png">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php">
        <img src="Media/Logo.png" alt="SportSync">
    </a>
    <nav class="main-nav">
        <a href="players.php">Players</a>
        <a href="matches.php">Matches</a>
        <a href="teams.php">Teams</a>
        <?php if ($user): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">Profile</a>
            <?php if ($user["role"] !== "admin"): ?>
                <a href="messages.php">Messages<?= $unreadMessages
                    ? " (" . $unreadMessages . ")"
                    : "" ?></a>
            <?php endif; ?>
            <a class="button button-small" href="logout.php">Log out</a>
        <?php else: ?>
            <a class="button button-small button-outline" href="login.php">Log in</a>
        <?php endif; ?>
    </nav>
</header>
<?php if ($flash): ?>
    <div class="flash <?= e($flash["type"]) ?>"><?= e(
    $flash["message"],
) ?></div>
<?php endif; ?>
<main class="page-shell">
