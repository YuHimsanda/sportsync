<?php
require_once "includes/functions.php";
global $conn;

$query = trim((string) ($_GET["q"] ?? ""));
$searchQuery = "%" . $query . "%";

$statement = $conn->prepare(
    "SELECT u.id,u.name,u.profile_photo,u.role,a.name academy_name,GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') sports
    FROM users u LEFT JOIN academies a ON a.id=u.academy_id LEFT JOIN team_players tp ON tp.player_id=u.id
    LEFT JOIN teams t ON t.id=tp.team_id LEFT JOIN sports s ON s.id=t.sport_id
    WHERE u.role IN ('player','athlete') AND u.profile_status='active' AND u.name LIKE ?
    GROUP BY u.id ORDER BY u.name LIMIT 16"
);
$statement->bind_param("s", $searchQuery);
$statement->execute();
$result = $statement->get_result();

$players = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
}

$pageTitle = "Search players";
include "includes/header.php";
?>
<div class="section-head"><div><h1>Find a player</h1><p class="meta">Search public athlete profiles across SportSync academies.</p></div></div>
<form class="searchbar" method="get"><input name="q" value="<?= e(
    $query,
) ?>" placeholder="Enter a player name"><button class="button">Search</button></form>
<?php if ($players): ?><div class="grid"><?php foreach (
    $players
    as $player
): ?><article class="card"><h3><a href="player.php?id=<?= $player[
    "id"
] ?>"><?= e($player["name"]) ?></a></h3><p class="meta"><?= e(
    $player["academy_name"] ?: "Independent athlete",
) ?></p><p><?= e(
    $player["sports"] ?: "Sport profile pending",
) ?></p><a href="player.php?id=<?= $player[
    "id"
] ?>">View profile →</a></article><?php endforeach; ?></div>
<?php else: ?><div class="empty">No players found. Try another name.</div><?php endif; ?>
<?php include "includes/footer.php"; ?>