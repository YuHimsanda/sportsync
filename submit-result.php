<?php
require_once "includes/functions.php";
global $conn; // MySQLi connection එක

$user = requireLogin();
$id = (int) ($_GET["id"] ?? 0);

$statement = $conn->prepare(
    "SELECT e.*,s.name sport_name FROM events e JOIN sports s ON s.id=e.sport_id WHERE e.id=?"
);
$statement->bind_param("i", $id);
$statement->execute();
$result = $statement->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    redirect("matches.php");
}
$canSubmit =
    $user["role"] === "admin" ||
    $user["role"] === "coach" ||
    ($user["role"] === "referee" &&
        (int) $event["referee_id"] === (int) $user["id"]);
if (!$canSubmit) {
    flash("error", "You are not authorised to submit this result.");
    redirect("match.php?id=" . $id);
}

$playersStmt = $conn->prepare(
    "SELECT DISTINCT u.id,u.name FROM event_teams et JOIN team_players tp ON tp.team_id=et.team_id JOIN users u ON u.id=tp.player_id WHERE et.event_id=? ORDER BY u.name"
);
$playersStmt->bind_param("i", $id);
$playersStmt->execute();
$playersResult = $playersStmt->get_result();
$players = [];
while ($row = $playersResult->fetch_assoc()) {
    $players[] = $row;
}

$fields = sportStatFields($event["sport_name"]);
$existingStats = [];

$existingStatement = $conn->prepare(
    "SELECT player_id, stats FROM player_stats WHERE event_id = ?"
);
$existingStatement->bind_param("i", $id);
$existingStatement->execute();
$existingResult = $existingStatement->get_result();

while ($existing = $existingResult->fetch_assoc()) {
    $existingStats[(int) $existing["player_id"]] =
        json_decode((string) $existing["stats"], true) ?: [];
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $conn->begin_transaction();
        
        $updateStmt = $conn->prepare(
            'UPDATE events SET home_score=?,away_score=?,status="completed" WHERE id=?'
        );
        $homeScore = (int) ($_POST["home_score"] ?? 0);
        $awayScore = (int) ($_POST["away_score"] ?? 0);
        $updateStmt->bind_param("iii", $homeScore, $awayScore, $id);
        $updateStmt->execute();
        
        $upsert = $conn->prepare(
            "INSERT INTO player_stats (event_id,player_id,score_or_runs,stats,submitted_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE score_or_runs=VALUES(score_or_runs),stats=VALUES(stats),submitted_by=VALUES(submitted_by)"
        );
        
        $userId = (int) $user["id"];
        
        foreach ($players as $player) {
            $stats = [];
            foreach ($fields as $section => $sectionFields) {
                foreach ($sectionFields as $key => $label) {
                    $value = $_POST["stats"][$player["id"]][$key] ?? "";
                    $stats[$key] =
                        $key === "bbi" ? trim((string) $value) : (float) $value;
                }
            }
            $score =
                $stats["runs"] ??
                ($stats["goals"] ??
                    ($stats["points"] ?? ($stats["total_points"] ?? 0)));
                    
            $statsJson = json_encode($stats);
            $playerId = (int) $player["id"];
            
            // iidsi = integer, integer, double, string, integer
            $upsert->bind_param("iidsi", $id, $playerId, $score, $statsJson, $userId);
            $upsert->execute();
        }
        $conn->commit();
        flash("success", "Final result and player statistics saved.");
        redirect("match.php?id=" . $id);
    } catch (Exception $exception) {
        $conn->rollback();
        $error = "Unable to save the result. Please check the values and try again.";
    }
}
$pageTitle = "Submit result";
include "includes/header.php";
?>
<section class="form-card"><h1>Submit final result</h1><p class="meta"><?= e(
    $event["title"],
) ?> · <?= e($event["sport_name"]) ?></p><?php if (
    $error
): ?><div class="flash error"><?= e(
    $error,
) ?></div><?php endif; ?><form method="post"><div class="form-grid"><label>Home score<input type="number" name="home_score" min="0" value="<?= e((string) ($event["home_score"] ?? 0)) ?>" required></label><label>Away score<input type="number" name="away_score" min="0" value="<?= e((string) ($event["away_score"] ?? 0)) ?>" required></label></div>
<?php foreach ($players as $player): ?><div class="stat-section"><h3><?= e(
    $player["name"],
) ?></h3><div class="form-grid"><?php foreach (
    $fields
    as $section => $sectionFields
): ?><div class="full"><strong><?= e($section) ?></strong></div><?php foreach (
    $sectionFields
    as $key => $label
): ?>
    <label>
        <?= e($label) ?>
        <input
            type="<?= $key === "bbi" ? "text" : "number" ?>"
            name="stats[<?= $player["id"] ?>][<?= e($key) ?>]"
            value="<?= e((string) ($existingStats[(int) $player["id"]][$key] ?? ($key === "bbi" ? "" : 0))) ?>"
            <?= $key === "bbi" ? 'placeholder="4/22"' : 'step="0.01" min="0"' ?>
        >
    </label>
<?php endforeach; endforeach; ?></div></div><?php endforeach; ?><div class="form-actions"><button class="button">Save final result</button><a href="match.php?id=<?= $id ?>">Cancel</a></div></form></section>
<?php include "includes/footer.php"; ?>