<?php
require_once "includes/functions.php";
global $conn;

$user = requireRole(["admin", "coach"]);
$sports = loadSports($conn);
$selectedSportId = (int) ($_POST["sport_id"] ?? ($_GET["sport_id"] ?? 0));
$error = "";

$academyId = (int) $user["academy_id"];
$userId = (int) $user["id"];

$venuesStatement = $conn->prepare(
    "SELECT id, name FROM venues WHERE academy_id = ? ORDER BY name"
);
$venuesStatement->bind_param("i", $academyId);
$venuesStatement->execute();
$venuesResult = $venuesStatement->get_result();
$venues = [];
while ($row = $venuesResult->fetch_assoc()) {
    $venues[] = $row;
}

$teams = [];
$referees = [];
if ($selectedSportId) {
    $teamsStatement = $conn->prepare(
        'SELECT t.id, t.name, a.name academy_name FROM teams t
         JOIN academies a ON a.id=t.academy_id
         WHERE t.sport_id = ? AND t.status = "active"
         ORDER BY a.name,t.name'
    );
    $teamsStatement->bind_param("i", $selectedSportId);
    $teamsStatement->execute();
    $teamsResult = $teamsStatement->get_result();
    while ($row = $teamsResult->fetch_assoc()) {
        $teams[] = $row;
    }

    $refereesStatement = $conn->prepare(
        "SELECT DISTINCT u.id, u.name
         FROM users u
         JOIN user_sports us ON us.user_id = u.id
         WHERE u.academy_id = ? AND us.sport_id = ?
           AND u.role = 'referee' AND u.profile_status = 'active'
         ORDER BY u.name"
    );
    $refereesStatement->bind_param("ii", $academyId, $selectedSportId);
    $refereesStatement->execute();
    $refereesResult = $refereesStatement->get_result();
    while ($row = $refereesResult->fetch_assoc()) {
        $referees[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $teamIds = selectedValues("team_ids");
    $startTimestamp = strtotime(post("start_datetime"));

    if (
        post("title") === "" ||
        !$startTimestamp ||
        !post("venue_id") ||
        !$selectedSportId
    ) {
        $error = "Title, sport, time and venue are required.";
    } elseif (count($teamIds) !== 2) {
        $error = "Select exactly two teams for this match.";
    } else {
        $start = date("Y-m-d H:i:s", $startTimestamp);
        $end = date("Y-m-d H:i:s", strtotime("+2 hours", $startTimestamp));
        $venueId = (int) post("venue_id");
        
        $conflict = $conn->prepare(
            'SELECT COUNT(*) as total FROM events
             WHERE venue_id = ? AND status <> "cancelled"
               AND start_datetime < DATE_ADD(?, INTERVAL 2 HOUR)
               AND end_datetime > DATE_SUB(?, INTERVAL 2 HOUR)'
        );
        $conflict->bind_param("iss", $venueId, $start, $start);
        $conflict->execute();
        $conflictResult = $conflict->get_result()->fetch_assoc();

        $validTeams = $conn->prepare(
            'SELECT COUNT(*) as total FROM teams
             WHERE sport_id = ? AND status = "active"
               AND id IN (?, ?)'
        );
        $teamId1 = (int) $teamIds[0];
        $teamId2 = (int) $teamIds[1];
        $validTeams->bind_param("iii", $selectedSportId, $teamId1, $teamId2);
        $validTeams->execute();
        $validTeamsResult = $validTeams->get_result()->fetch_assoc();

        if ((int) $conflictResult["total"] > 0) {
            $error = "This venue has a conflicting match within the required two-hour buffer.";
        } elseif ((int) $validTeamsResult["total"] !== 2) {
            $error = "Both selected teams must be active teams for the selected sport.";
        } else {
            $venueCheck = $conn->prepare(
                "SELECT COUNT(*) as total FROM venues WHERE id=? AND academy_id=?"
            );
            $venueCheck->bind_param("ii", $venueId, $academyId);
            $venueCheck->execute();
            $venueCheckResult = $venueCheck->get_result()->fetch_assoc();
            
            $refereeId = (int) post("referee_id") ?: null;
            $refereeCheck = true;
            if ($refereeId) {
                $refereeStatement = $conn->prepare(
                    "SELECT COUNT(*) as total FROM users u
                     JOIN user_sports us ON us.user_id=u.id
                     WHERE u.id=? AND u.academy_id=? AND u.role='referee'
                       AND u.profile_status='active' AND us.sport_id=?"
                );
                $refereeStatement->bind_param("iii", $refereeId, $academyId, $selectedSportId);
                $refereeStatement->execute();
                $refereeStmtResult = $refereeStatement->get_result()->fetch_assoc();
                $refereeCheck = (int) $refereeStmtResult["total"] === 1;
            }

         
            $participantConflict = $conn->prepare(
                'SELECT COUNT(*) as total FROM (
                    SELECT tp_existing.player_id AS participant_id
                    FROM events existing
                    JOIN event_teams existing_et ON existing_et.event_id = existing.id
                    JOIN team_players tp_existing ON tp_existing.team_id = existing_et.team_id
                    JOIN team_players tp_new ON tp_new.player_id = tp_existing.player_id
                    WHERE existing.status <> "cancelled"
                      AND DATE(existing.start_datetime) = DATE(?)
                      AND tp_new.team_id IN (?, ?)
                    UNION
                    SELECT t_existing.coach_id AS participant_id
                    FROM events existing
                    JOIN event_teams existing_et ON existing_et.event_id = existing.id
                    JOIN teams t_existing ON t_existing.id = existing_et.team_id
                    JOIN teams t_new ON t_new.coach_id = t_existing.coach_id
                    WHERE existing.status <> "cancelled"
                      AND DATE(existing.start_datetime) = DATE(?)
                      AND t_new.id IN (?, ?)
                      AND t_new.coach_id IS NOT NULL
                ) conflicts'
            );
            
            $participantConflict->bind_param("siisii", 
                $start, $teamId1, $teamId2, 
                $start, $teamId1, $teamId2
            );
            $participantConflict->execute();
            $participantConflictResult = $participantConflict->get_result()->fetch_assoc();

            if ((int) $venueCheckResult["total"] !== 1) {
                $error = "Please select a venue from your academy.";
            } elseif (!$refereeCheck) {
                $error = "Please select an active referee registered for the selected sport.";
            } elseif ((int) $participantConflictResult["total"] > 0) {
                $error = "A player or coach is already assigned to another match on this date.";
            }
        }

        if (!$error) {
            try {
                $conn->begin_transaction();
                
                $title = post("title");
                $eventType = post("event_type");
                $statusStr = 'scheduled';
                
                $eventStatement = $conn->prepare(
                    "INSERT INTO events
                        (academy_id, title, event_type, sport_id, venue_id,
                         start_datetime, end_datetime, status, created_by, referee_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $eventStatement->bind_param("isssisssii", 
                    $academyId, $title, $eventType, $selectedSportId, $venueId, 
                    $start, $end, $statusStr, $userId, $refereeId
                );
                $eventStatement->execute();
                
                $eventId = (int) $conn->insert_id;
                
                $eventTeamStatement = $conn->prepare(
                    "INSERT INTO event_teams (event_id, team_id, team_side) VALUES (?, ?, ?)"
                );
                $homeStr = "home";
                $awayStr = "away";
                $eventTeamStatement->bind_param("iis", $eventId, $teamId1, $homeStr);
                $eventTeamStatement->execute();
                
                $eventTeamStatement->bind_param("iis", $eventId, $teamId2, $awayStr);
                $eventTeamStatement->execute();

                $recipients = [];
                $playerStatement = $conn->prepare(
                    'SELECT DISTINCT tp.player_id
                     FROM team_players tp
                     WHERE tp.team_id IN (?, ?)'
                );
                $playerStatement->bind_param("ii", $teamId1, $teamId2);
                $playerStatement->execute();
                $playerResult = $playerStatement->get_result();
                while ($row = $playerResult->fetch_assoc()) {
                    $recipients[] = $row["player_id"];
                }

                $coachStatement = $conn->prepare(
                    "SELECT coach_id FROM teams WHERE id IN (?, ?) AND coach_id IS NOT NULL"
                );
                $coachStatement->bind_param("ii", $teamId1, $teamId2);
                $coachStatement->execute();
                $coachResult = $coachStatement->get_result();
                while ($row = $coachResult->fetch_assoc()) {
                    $recipients[] = $row["coach_id"];
                }
                $recipients = array_unique($recipients);

                $messageStatement = $conn->prepare(
                    'INSERT INTO messages
                        (academy_id, sender_id, recipient_id, subject, body)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $msgSubject = "New match scheduled";
                $msgBody = $title . " is scheduled for " . formatDateTime($start) . ".";
                
                foreach ($recipients as $recipientId) {
                    $rec = (int) $recipientId;
                    $messageStatement->bind_param("iiiss", $academyId, $userId, $rec, $msgSubject, $msgBody);
                    $messageStatement->execute();
                }
                
                $conn->commit();
                flash(
                    "success",
                    "Match scheduled successfully and notifications sent.",
                );
                redirect("dashboard.php");
            } catch (Exception $exception) {
                $conn->rollback();
                $error = "The match could not be scheduled. Please try again.";
            }
        }
    }
}

$pageTitle = "Create match";
include "includes/header.php";
?>
<section class="form-card">
    <h1>Create a match</h1>
    <p class="meta">Choose a sport first so only its teams and referees are available.</p>
    <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-grid">
            <label class="full">Match title
                <input name="title" required placeholder="e.g. Saturday Academy Cricket" value="<?= e(post("title")) ?>">
            </label>
            <label>Match type
                <select name="event_type">
                    <option value="official">Official</option>
                    <option value="friendly">Friendly</option>
                    <option value="practice">Practice</option>
                </select>
            </label>
            <label>Sport
                <select name="sport_id" required onchange="window.location='create-match.php?sport_id='+this.value">
                    <option value="">Select sport</option>
                    <?php foreach ($sports as $sport): ?>
                        <option value="<?= (int) $sport["id"] ?>" <?= $selectedSportId === (int) $sport["id"] ? "selected" : "" ?>>
                            <?= e($sport["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Date and time
                <input type="datetime-local" name="start_datetime" required value="<?= e(post("start_datetime")) ?>">
            </label>
            <label>Venue
                <select name="venue_id" required>
                    <option value="">Select venue</option>
                    <?php foreach ($venues as $venue): ?>
                        <option value="<?= (int) $venue["id"] ?>"><?= e($venue["name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Referee
                <select name="referee_id">
                    <option value="">Not assigned</option>
                    <?php foreach ($referees as $referee): ?>
                        <option value="<?= (int) $referee["id"] ?>"><?= e($referee["name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="full">
                <strong>Select exactly two teams</strong>
                <?php if (!$selectedSportId): ?>
                    <div class="empty">Select a sport first to see its teams.</div>
                <?php elseif ($teams): ?>
                    <div class="table-wrap">
                        <table>
                            <tr><th>Team</th><th>Select</th></tr>
                            <?php foreach ($teams as $team): ?>
                                <tr>
                                    <td><?= e($team["name"]) ?> <span class="meta">(<?= e($team["academy_name"]) ?>)</span></td>
                                    <td><input type="checkbox" name="team_ids[]" value="<?= (int) $team["id"] ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty">No active teams exist for this sport yet.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-actions">
            <button class="button">Schedule match</button>
            <a href="dashboard.php">Cancel</a>
        </div>
    </form>
</section>
<?php include "includes/footer.php"; ?>