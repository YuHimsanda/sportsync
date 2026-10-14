<?php
require_once "includes/functions.php";
global $conn;

$q = trim((string) ($_GET["q"] ?? ""));
$searchQuery = "%" . $q . "%";

$sql = "SELECT t.id, t.name, t.status, a.name AS academy_name, s.name AS sport_name, COUNT(tp.player_id) AS player_count
    FROM teams t 
    LEFT JOIN academies a ON a.id = t.academy_id 
    LEFT JOIN sports s ON s.id = t.sport_id
    LEFT JOIN team_players tp ON tp.team_id = t.id 
    WHERE t.status = 'active'
    AND (t.name LIKE ? OR a.name LIKE ? OR s.name LIKE ?) 
    GROUP BY t.id, t.name, t.status, a.name, s.name 
    ORDER BY a.name, t.name 
    LIMIT 16";

$stmt = $conn->prepare($sql);

$stmt->bind_param("sss", $searchQuery, $searchQuery, $searchQuery);
$stmt->execute();

$result = $stmt->get_result();
$teams = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
}

$pageTitle = "Find teams";
include "includes/header.php";
?>
<div class="section-head"><div><h1>Find academy teams</h1><p class="meta">Discover active teams and request to join an academy squad.</p></div></div>
<form class="searchbar" method="get"><input name="q" value="<?= e(
    $q,
) ?>" placeholder="Search team, academy or sport"><button class="button">Search</button></form>
<?php if ($teams): ?><div class="grid"><?php foreach (
    $teams
    as $team
): ?><a class="card card-link" href="team.php?id=<?= (int) $team[
    "id"
] ?>"><span class="tag"><?= e($team["sport_name"]) ?></span><h3><?= e(
    $team["name"],
) ?></h3><p class="meta"><?= e($team["academy_name"]) ?> · <?= (int) $team[
     "player_count"
 ] ?> players</p><span>View team →</span></a><?php endforeach; ?></div><?php else: ?><div class="empty">No active teams found.</div><?php endif; ?>
<?php include "includes/footer.php"; ?>