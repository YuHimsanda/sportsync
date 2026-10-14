<?php
require_once "includes/functions.php";
global $conn; // MySQLi connection එක

$user = requireRole(["admin", "coach"]);
$sports = loadSports($conn);
$selectedSportId = (int) ($_POST["sport_id"] ?? ($_GET["sport_id"] ?? 0));
$error = "";
$academyId = (int) $user["academy_id"];
$userId = (int) $user["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = post("name");
    $sportId = (int) post("sport_id");
    $coachId = (int) post("coach_id") ?: null;
    
    if ($name === "" || !$sportId) {
        $error = "Team name and sport are required.";
    } elseif ($coachId) {
        $coachCheck = $conn->prepare(
            "SELECT COUNT(*) as total FROM users u JOIN user_sports us ON us.user_id=u.id WHERE u.id=? AND u.academy_id=? AND u.role='coach' AND u.profile_status='active' AND us.sport_id=?"
        );
        $coachCheck->bind_param("iii", $coachId, $academyId, $sportId);
        $coachCheck->execute();
        $coachResult = $coachCheck->get_result();
        $coachRow = $coachResult->fetch_assoc();
        
        if ((int) $coachRow["total"] === 0) {
            $error = "Please assign a coach registered for the selected sport.";
        }
    }
    
    if (!$error) {
        try {
            $conn->begin_transaction();
            
            $status = $user["role"] === "admin" ? "active" : "pending";
            
            $insertTeam = $conn->prepare(
                "INSERT INTO teams (academy_id,name,sport_id,coach_id,status) VALUES (?,?,?,?,?)"
            );
            // isiis = int, string, int, int (or null), string
            $insertTeam->bind_param("isiis", $academyId, $name, $sportId, $coachId, $status);
            $insertTeam->execute();
            
            $teamId = (int) $conn->insert_id;
            
            $insertPlayer = $conn->prepare(
                "INSERT INTO team_players (team_id,player_id,sport_role) VALUES (?,?,?)"
            );
            foreach (selectedValues("player_ids") as $playerId) {
                $role = $_POST["player_role"][$playerId] ?? null;
                $insertPlayer->bind_param("iis", $teamId, $playerId, $role);
                $insertPlayer->execute();
            }
            
            if ($status === "pending") {
                $insertApproval = $conn->prepare(
                    "INSERT INTO approvals (academy_id,type,reference_id,requested_by) VALUES (?, 'team_creation', ?, ?)"
                );
                $insertApproval->bind_param("iii", $academyId, $teamId, $userId);
                $insertApproval->execute();
            }
            
            if ($coachId) {
                $insertMessage = $conn->prepare(
                    "INSERT INTO messages (academy_id,sender_id,recipient_id,subject,body) VALUES (?,?,?,?,?)"
                );
                $subject = "Team assignment";
                $body = "You have been assigned as coach of " . $name . ".";
                $insertMessage->bind_param("iiiss", $academyId, $userId, $coachId, $subject, $body);
                $insertMessage->execute();
            }
            
            $conn->commit();
            flash(
                "success",
                $status === "pending"
                    ? "Team submitted for admin approval."
                    : "Team created successfully.",
            );
            redirect("team.php?id=" . $teamId);
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            if ($exception->getCode() === 1062) {
                $error = "Unable to create team. The team name may already exist.";
            } else {
                $error = "An error occurred while creating the team.";
            }
        }
    }
}

// Members ලබා ගැනීම
$membersStmt = $conn->prepare(
    "SELECT u.id,u.name,u.role FROM users u JOIN user_sports us ON us.user_id=u.id WHERE u.academy_id=? AND us.sport_id=? AND u.role IN ('player','athlete') AND u.profile_status='active' ORDER BY u.name"
);
$membersStmt->bind_param("ii", $academyId, $selectedSportId);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();
$members = [];
if ($membersResult) {
    while ($row = $membersResult->fetch_assoc()) {
        $members[] = $row;
    }
}

// Coaches ලබා ගැනීම
$coachesStmt = $conn->prepare(
    "SELECT u.id,u.name FROM users u JOIN user_sports us ON us.user_id=u.id WHERE u.academy_id=? AND us.sport_id=? AND u.role='coach' AND u.profile_status='active' ORDER BY u.name"
);
$coachesStmt->bind_param("ii", $academyId, $selectedSportId);
$coachesStmt->execute();
$coachesResult = $coachesStmt->get_result();
$coaches = [];
if ($coachesResult) {
    while ($row = $coachesResult->fetch_assoc()) {
        $coaches[] = $row;
    }
}

$pageTitle = "Create team";
include "includes/header.php";
?>
<section class="form-card"><h1>Create a team</h1><p class="meta"><?= $user[
    "role"
] === "coach"
    ? "Coach-created teams require admin approval."
    : "Create and manage an active academy team." ?></p><?php if (
    $error
): ?><div class="flash error"><?= e(
    $error,
) ?></div><?php endif; ?><form method="post"><div class="form-grid"><label>Team name<input name="name" required></label><label>Sport<select name="sport_id" required onchange="window.location='create-team.php?sport_id='+this.value"><?php foreach (
    $sports
    as $sport
): ?><option value="<?= $sport["id"] ?>" <?= $selectedSportId ===
(int) $sport["id"]
    ? "selected"
    : "" ?>><?= e(
    $sport["name"],
) ?></option><?php endforeach; ?></select></label><label class="full">Coach<select name="coach_id"><option value="">Assign later</option><?php foreach (
    $coaches
    as $coach
): ?><option value="<?= $coach["id"] ?>"><?= e(
    $coach["name"],
) ?></option><?php endforeach; ?></select></label><div class="full"><strong>Select <?= $selectedSportId
    ? e(
        $sports[array_search($selectedSportId, array_column($sports, "id"))][
            "name"
        ] ?? "sport",
    )
    : "a sport" ?> players</strong><?php if (
     !$selectedSportId
 ): ?><div class="empty">Select a sport first to see only players and coaches registered for that sport.</div><?php else: ?><div class="table-wrap"><table><tr><th>Player</th><th>Sport role</th><th>Add</th></tr><?php foreach (
    $members
    as $member
): ?><tr><td><?= e(
    $member["name"],
) ?></td><td><input name="player_role[<?= $member[
    "id"
] ?>]" placeholder="e.g. Captain / Forward"></td><td><input type="checkbox" name="player_ids[]" value="<?= $member[
    "id"
] ?>"></td></tr><?php endforeach; ?></table></div><?php endif; ?></div></div><div class="form-actions"><button class="button">Create team</button></div></form></section>
<?php include "includes/footer.php"; ?>