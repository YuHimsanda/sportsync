<?php
require_once "includes/functions.php";
global $conn;

$query = $_GET["q"] ?? "";
$type = $_GET["type"] ?? "";
$searchQuery = "%" . $query . "%";

$sql = "SELECT e.*,s.name sport_name,v.name venue_name,a.name academy_name,GROUP_CONCAT(t.name ORDER BY t.id SEPARATOR ' vs ')
 teams FROM events e JOIN sports s ON s.id=e.sport_id JOIN venues v ON v.id=e.venue_id JOIN academies a ON a.id=e.academy_id 
 LEFT JOIN event_teams et ON et.event_id=e.id LEFT JOIN teams t ON t.id=et.team_id WHERE (a.name LIKE ? OR e.title LIKE ? OR t.name LIKE ?)";

if ($type) {
    $sql .= " AND e.event_type=?";
}
$sql .= " GROUP BY e.id ORDER BY e.start_datetime DESC";

$stmt = $conn->prepare($sql);

if ($type) {
   
    $stmt->bind_param("ssss", $searchQuery, $searchQuery, $searchQuery, $type);
} else {
    
    $stmt->bind_param("sss", $searchQuery, $searchQuery, $searchQuery);
}

$stmt->execute();
$result = $stmt->get_result();
$matches = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row;
    }
}

$pageTitle = "Matches";
include "includes/header.php";
?>
<div class="section-head"><div><h1>Matches</h1><p class="meta">Search fixtures and results from registered academies.</p></div></div>
<form class="searchbar" method="get"><input name="q" value="<?= e(
    $query,
) ?>" placeholder="Search academy, team or match"><select name="type"><option value="">All match types</option><option value="official" <?= $type ===
"official"
    ? "selected"
    : "" ?>>Official</option><option value="friendly" <?= $type === "friendly"
    ? "selected"
    : "" ?>>Friendly</option><option value="practice" <?= $type === "practice"
    ? "selected"
    : "" ?>>Practice</option></select><button class="button">Search</button></form>
<?php if ($matches): ?><div class="grid"><?php foreach (
    $matches
    as $match
): ?><a class="card card-link match-card" href="match.php?id=<?= $match[
    "id"
] ?>"><span class="tag"><?= e(ucfirst($match["event_type"])) ?> · <?= e(
     $match["sport_name"],
 ) ?></span><h3><?= e($match["title"]) ?></h3><p><strong><?= e(
    $match["teams"] ?: $match["academy_name"],
) ?></strong></p><p class="meta"><?= e(
    formatDateTime($match["start_datetime"]),
) ?><br><?= e($match["venue_name"]) ?> · Hosted by <?= e(
     $match["academy_name"],
 ) ?></p><?php if ($match["status"] === "completed"): ?><strong>Score: <?= e(
    (string) ($match["home_score"] ?? "-"),
) ?> — <?= e(
     (string) ($match["away_score"] ?? "-"),
 ) ?></strong><?php endif; ?></a><?php endforeach; ?></div><?php else: ?><div class="empty">No matches found.</div><?php endif; ?>
<?php include "includes/footer.php"; ?>