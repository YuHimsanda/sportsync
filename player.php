<?php
require_once "includes/functions.php";
global $conn; 

$id = (int) ($_GET["id"] ?? 0);
$statement = $conn->prepare(
    "SELECT u.*,a.name academy_name FROM users u LEFT JOIN academies a ON a.id=u.academy_id WHERE u.id=? AND u.profile_status='active' AND u.role IN ('player','athlete')"
);
$statement->bind_param("i", $id);
$statement->execute();
$player = $statement->get_result()->fetch_assoc();

if (!$player) {
    http_response_code(404);
    $pageTitle = "Player not found";
    include "includes/header.php";
    echo '<div class="empty"><h1>Player not found</h1><p>The profile you requested does not exist.</p><a class="button" href="index.php">Back home</a></div>';
    include "includes/footer.php";
    exit();
}
$statsStatement = $conn->prepare(
    "SELECT s.name sport_name,COUNT(ps.id) matches,ps.stats,e.title,e.start_datetime FROM player_stats ps JOIN events e ON e.id=ps.event_id JOIN sports s ON s.id=e.sport_id WHERE ps.player_id=? GROUP BY ps.id ORDER BY s.name,e.start_datetime DESC"
);
$statsStatement->bind_param("i", $id);
$statsStatement->execute();
$statsResult = $statsStatement->get_result();

$records = [];
while ($row = $statsResult->fetch_assoc()) {
    $records[] = $row;
}

$bySport = [];
foreach ($records as $record) {
    $record["stats"] = json_decode((string) $record["stats"], true) ?: [];
    $bySport[$record["sport_name"]][] = $record;
}
$pageTitle = $player["name"];
include "includes/header.php";
?>
<section class="profile-head"><img class="avatar" src="<?= e(
    $player["profile_photo"] ?: "Media/favcon.png",
) ?>" alt=""><div><span class="tag"><?= e(
    roleLabel($player["role"]),
) ?></span><h1><?= e($player["name"]) ?></h1><p class="meta"><?= e(
    $player["academy_name"] ?: "Independent athlete",
) ?> · Public player profile</p></div></section>
<div class="section-head"><h2>Sport performance</h2></div>
<?php if ($bySport):
    foreach (
        $bySport
        as $sport => $sportRecords
    ): ?><section class="card stat-section"><h2><?= e(
    $sport,
) ?></h2><?php foreach ($sportRecords as $record): ?><h3><?= e(
    $record["title"],
) ?> <span class="meta"><?= e(
     formatDateTime($record["start_datetime"]),
 ) ?></span></h3><div class="stat-list"><?php foreach (
    sportStatFields($sport)
    as $section => $sectionFields
):
    foreach ($sectionFields as $key => $label):
        if (
            array_key_exists($key, $record["stats"])
        ): ?><div><span class="meta"><?= e($label) ?></span><strong><?= e(
    (string) $record["stats"][$key],
) ?></strong></div><?php endif;
    endforeach;
endforeach; ?></div><?php endforeach; ?></section><?php endforeach;
else:
     ?><div class="empty">Match statistics will appear here after a referee or coach submits a result.</div><?php
endif; ?>
<?php include "includes/footer.php"; ?>