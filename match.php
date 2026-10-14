<?php
require_once "includes/functions.php";
global $conn;

$id = (int) ($_GET["id"] ?? 0);

$statement = $conn->prepare(
    "SELECT e.*,s.name sport_name,v.name venue_name,a.name academy_name,r.name referee_name FROM events e JOIN sports s 
    ON s.id=e.sport_id JOIN venues v ON v.id=e.venue_id JOIN academies a ON a.id=e.academy_id LEFT JOIN users r
     ON r.id=e.referee_id WHERE e.id=?"
);
$statement->bind_param("i", $id);
$statement->execute();
$result = $statement->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    http_response_code(404);
    $pageTitle = "Match not found";
    include "includes/header.php";
    echo '<div class="empty"><h1>Match not found</h1><a class="button" href="matches.php">Browse matches</a></div>';
    include "includes/footer.php";
    exit();
}

$teamsStmt = $conn->prepare(
    "SELECT t.id,t.name,a.name academy_name,et.team_side FROM event_teams et JOIN teams t 
    ON t.id=et.team_id JOIN academies a ON a.id=t.academy_id WHERE et.event_id=? 
    ORDER BY FIELD(et.team_side,'home','away'),et.team_id"
);
$teamsStmt->bind_param("i", $id);
$teamsStmt->execute();
$teamsResult = $teamsStmt->get_result();
$teams = [];
while ($row = $teamsResult->fetch_assoc()) {
    $teams[] = $row;
}

$playersStmt = $conn->prepare(
    "SELECT DISTINCT u.id,u.name,t.id team_id,t.name team_name,a.name academy_name,et.team_side,
            ps.score_or_runs,ps.stats
     FROM event_teams et
     JOIN teams t ON t.id=et.team_id
     JOIN academies a ON a.id=t.academy_id
     JOIN team_players tp ON tp.team_id=et.team_id
     JOIN users u ON u.id=tp.player_id
     LEFT JOIN player_stats ps ON ps.event_id=et.event_id AND ps.player_id=u.id
     WHERE et.event_id=? ORDER BY et.team_side,u.name"
);
$playersStmt->bind_param("i", $id);
$playersStmt->execute();
$playersResult = $playersStmt->get_result();
$players = [];
while ($row = $playersResult->fetch_assoc()) {
    $players[] = $row;
}

$user = currentUser();
$canSubmit =
    $user &&
    ($user["role"] === "admin" ||
        $user["role"] === "coach" ||
        ($user["role"] === "referee" &&
            (int) $event["referee_id"] === (int) $user["id"]));
$fields = sportStatFields($event["sport_name"]);
$pageTitle = $event["title"];
include "includes/header.php";
?>
<section class="profile-head"><div><span class="tag"><?= e(
    ucfirst($event["event_type"]),
) ?> · <?= e($event["sport_name"]) ?></span><h1><?= e(
    $event["title"],
) ?></h1><p class="meta"><?= e(
    formatDateTime($event["start_datetime"]),
) ?> · <?= e($event["venue_name"]) ?> · Hosted by <?= e(
     $event["academy_name"],
 ) ?></p><p><?= e(
    $event["referee_name"] ?: "Referee not assigned",
) ?></p></div><?php if ($canSubmit): ?><a class="button" href="submit-result.php?id=<?= $id ?>"><?= $event["status"] ===
"completed"
    ? "Edit result"
    : "Submit final result" ?></a><?php endif; ?></section>
<div class="section-head"><h2>Teams and score</h2></div><div class="stats-grid"><?php foreach (
    $teams
    as $teamIndex => $team
):

    $teamSide = $team["team_side"] ?: ($teamIndex === 0 ? "home" : "away");
    $teamScore =
        $teamSide === "away" ? $event["away_score"] : $event["home_score"];
    ?><div class="stat-card"><span class="meta"><?= e(
    $team["academy_name"],
) ?></span><strong><?= e($team["name"]) ?></strong><span>Score: <?= e(
    (string) ($teamScore ?? "-"),
) ?></span></div><?php
endforeach; ?></div>
<div class="section-head"><h2>Team information</h2></div>
<div class="grid">
    <?php foreach ($teams as $team): ?>
        <section class="card">
            <h3><?= e($team["name"]) ?></h3>
            <p class="team-academy"><?= e($team["academy_name"]) ?></p>
            <ul>
                <?php foreach ($players as $player): ?>
                    <?php if ((int) $player["team_id"] === (int) $team["id"]): ?>
                        <?php
                        $playerName = $player["name"];
                        $academyPrefix = $team["academy_name"] . " ";
                        if (strpos($playerName, $academyPrefix) === 0) {
                            $playerName = substr($playerName, strlen($academyPrefix));
                        }
                        ?>
                        <li>
                            <strong><?= e($team["academy_name"]) ?></strong>
                            <?= e($playerName) ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</div>
<div class="section-head"><h2><?= $event["status"] === "completed"
    ? "Player scores for this match"
    : "Players in this match" ?></h2></div><?php if (
    $players
): ?><div class="grid"><?php foreach ($players as $player):
    $playerStats =
        $event["status"] === "completed" && $player["stats"]
            ? json_decode($player["stats"], true)
            : []; ?><a class="card card-link" href="player.php?id=<?= $player[
    "id"
] ?>"><h3><?php
$playerName = $player["name"];
$academyPrefix = $player["academy_name"] . " ";
if (strpos($playerName, $academyPrefix) === 0) {
    $playerName = substr($playerName, strlen($academyPrefix));
}
?><strong><?= e($player["academy_name"]) ?></strong> <?= e($playerName) ?></h3><?php if (
    $event["status"] === "completed"
): ?><strong>Score: <?= e(
    (string) ($player["score_or_runs"] ?? 0),
) ?></strong><?php if ($playerStats): ?><p class="meta"><?php
$shown = 0;
foreach ($playerStats as $statKey => $statValue) {
    if (
        $statValue !== "" &&
        $statValue !== 0 &&
        $statValue !== "0" &&
        $shown < 4
    ) {
        echo e(ucwords(str_replace("_", " ", $statKey))) .
            ": " .
            e((string) $statValue) .
            " · ";
        $shown++;
    }
}
?></p><?php endif;else: ?><span class="meta">View player profile →</span><?php endif; ?></a><?php
endforeach; ?></div><?php else: ?><div class="empty">No players have been assigned to these teams.</div><?php endif; ?>
<?php include "includes/footer.php"; ?>