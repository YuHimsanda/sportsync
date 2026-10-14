<?php
require_once "includes/functions.php";
global $conn; 

$id = (int) ($_GET["id"] ?? 0);
$statement = $conn->prepare(
    "SELECT t.*,a.name academy_name,s.name sport_name,c.name coach_name FROM teams t JOIN academies a ON a.id=t.academy_id JOIN sports s ON s.id=t.sport_id LEFT JOIN users c ON c.id=t.coach_id WHERE t.id=? AND t.status='active'"
);
$statement->bind_param("i", $id);
$statement->execute();
$team = $statement->get_result()->fetch_assoc();

if (!$team) {
    http_response_code(404);
    $pageTitle = "Team not found";
    include "includes/header.php";
    echo '<div class="empty"><h1>Team not found</h1><a class="button" href="teams.php">Browse teams</a></div>';
    include "includes/footer.php";
    exit();
}
$user = currentUser();
$canManageMembers =
    $user &&
    (int) $user["academy_id"] === (int) $team["academy_id"] &&
    ($user["role"] === "admin" ||
        ($user["role"] === "coach" &&
            (int) $user["id"] === (int) $team["coach_id"]));
$error = "";
$teamAcademyId = (int) $team["academy_id"];
$teamSportId = (int) $team["sport_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && $canManageMembers) {
    $memberIds = selectedValues("player_ids");
    $validMembersStmt = $conn->prepare(
        "SELECT u.id
         FROM users u
         JOIN user_sports us ON us.user_id = u.id
         WHERE u.academy_id = ? AND u.profile_status = 'active'
           AND u.role IN ('player','athlete') AND us.sport_id = ?"
    );
    $validMembersStmt->bind_param("ii", $teamAcademyId, $teamSportId);
    $validMembersStmt->execute();
    $validMembersResult = $validMembersStmt->get_result();
    
    $validMemberIds = [];
    while ($row = $validMembersResult->fetch_assoc()) {
        $validMemberIds[] = (int) $row["id"];
    }
    
    $memberIds = array_values(array_intersect($memberIds, $validMemberIds));

    try {
        $conn->begin_transaction();
        
        $oldMembersStmt = $conn->prepare(
            "SELECT player_id, sport_role FROM team_players WHERE team_id = ?"
        );
        $oldMembersStmt->bind_param("i", $id);
        $oldMembersStmt->execute();
        $oldMembersResult = $oldMembersStmt->get_result();
        
        $oldRoles = [];
        while ($oldMember = $oldMembersResult->fetch_assoc()) {
            $oldRoles[(int) $oldMember["player_id"]] = $oldMember["sport_role"];
        }
        
        $deleteStmt = $conn->prepare("DELETE FROM team_players WHERE team_id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        
        $insertMember = $conn->prepare(
            "INSERT INTO team_players (team_id, player_id, sport_role) VALUES (?, ?, ?)"
        );
        foreach ($memberIds as $memberId) {
            $memId = (int) $memberId;
            $role = trim((string) ($_POST["player_role"][$memId] ?? ($oldRoles[$memId] ?? "")));
            $roleParam = $role ?: null;
            $insertMember->bind_param("iis", $id, $memId, $roleParam);
            $insertMember->execute();
        }
        $conn->commit();
        flash("success", "Team members updated.");
        redirect("team.php?id=" . $id);
    } catch (Exception $exception) {
        $conn->rollback();
        $error = "Unable to update the team members.";
    }
}

$membersStmt = $conn->prepare(
    "SELECT u.id,u.name,tp.sport_role FROM team_players tp JOIN users u ON u.id=tp.player_id WHERE tp.team_id=? AND u.profile_status='active' ORDER BY u.name"
);
$membersStmt->bind_param("i", $id);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();
$members = [];
while ($row = $membersResult->fetch_assoc()) {
    $members[] = $row;
}

$requestStatus = null;
if ($user && in_array($user["role"], ["player", "athlete"], true)) {
    $userId = (int) $user["id"];
    
    $checkStmt = $conn->prepare(
        "SELECT id,status FROM team_join_requests WHERE team_id=? AND player_id=?"
    );
    $checkStmt->bind_param("ii", $id, $userId);
    $checkStmt->execute();
    $request = $checkStmt->get_result()->fetch_assoc();
    
    $requestStatus = $request["status"] ?? null;
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        $requestStatus !== "approved"
    ) {
        $sportCheck = $conn->prepare(
            "SELECT COUNT(*) as total FROM user_sports WHERE user_id=? AND sport_id=?"
        );
        $sportCheck->bind_param("ii", $userId, $teamSportId);
        $sportCheck->execute();
        $sportResult = $sportCheck->get_result()->fetch_assoc();
        
        if ((int) $sportResult["total"] === 0) {
            flash("error", "You are not registered for this team sport.");
            redirect("team.php?id=" . $id);
        }
        
        $conn->begin_transaction();
        if ($request) {
            $updateReqStmt = $conn->prepare(
                "UPDATE team_join_requests SET status='pending',reviewed_by=NULL,reviewed_at=NULL WHERE id=?"
            );
            $reqId = (int) $request["id"];
            $updateReqStmt->bind_param("i", $reqId);
            $updateReqStmt->execute();
            $requestId = $reqId;
        } else {
            $insertReqStmt = $conn->prepare(
                "INSERT INTO team_join_requests (academy_id,team_id,player_id) VALUES (?,?,?)"
            );
            $insertReqStmt->bind_param("iii", $teamAcademyId, $id, $userId);
            $insertReqStmt->execute();
            $requestId = (int) $conn->insert_id;
        }
        
        $insertApproval = $conn->prepare(
            "INSERT INTO approvals (academy_id,type,reference_id,requested_by) VALUES (?, 'team_join_request', ?, ?)"
        );
        $insertApproval->bind_param("iii", $teamAcademyId, $requestId, $userId);
        $insertApproval->execute();
        
        $conn->commit();
        $requestStatus = "pending";
        flash(
            "success",
            "Your team join request was sent to the academy admin.",
        );
    }
}
$pageTitle = $team["name"];
include "includes/header.php";
?>
<section class="profile-head"><div><span class="tag"><?= e(
    $team["sport_name"],
) ?></span><h1><?= e($team["name"]) ?></h1><p class="meta"><?= e(
    $team["academy_name"],
) ?> · Coach: <?= e(
     $team["coach_name"] ?: "Not assigned",
 ) ?></p></div><?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?><?php if (
    $user &&
    in_array($user["role"], ["player", "athlete"], true) &&
    $requestStatus !== "approved"
): ?><form method="post"><button class="button"><?= $requestStatus ===
"rejected"
    ? "Request again"
    : "Request to join" ?></button></form><?php elseif (
    $requestStatus
): ?><span class="tag"><?= e(
    ucfirst($requestStatus),
) ?> request</span><?php endif; ?></section>
<?php if ($canManageMembers): ?>
    <?php
    $availableMembersStmt = $conn->prepare(
        "SELECT u.id, u.name
         FROM users u
         JOIN user_sports us ON us.user_id = u.id
         WHERE u.academy_id = ? AND u.profile_status = 'active'
           AND u.role IN ('player','athlete') AND us.sport_id = ?
         ORDER BY u.name"
    );
    $availableMembersStmt->bind_param("ii", $teamAcademyId, $teamSportId);
    $availableMembersStmt->execute();
    $availableMembersResult = $availableMembersStmt->get_result();
    $availableMembers = [];
    while ($row = $availableMembersResult->fetch_assoc()) {
        $availableMembers[] = $row;
    }
    ?>
    <section class="form-card">
        <h2>Edit team members</h2>
        <p class="meta">Add or remove active players registered for <?= e($team["sport_name"]) ?>.</p>
        <form method="post">
            <div class="sport-options">
                <?php foreach ($availableMembers as $availableMember): ?>
                    <?php
                    $memberId = (int) $availableMember["id"];
                    $memberRecord = null;
                    foreach ($members as $currentMember) {
                        if ((int) $currentMember["id"] === $memberId) {
                            $memberRecord = $currentMember;
                            break;
                        }
                    }
                    ?>
                    <label>
                        <input type="checkbox" name="player_ids[]" value="<?= $memberId ?>" <?= $memberRecord ? "checked" : "" ?>>
                        <?= e($availableMember["name"]) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-actions"><button class="button" type="submit">Save team members</button></div>
        </form>
    </section>
<?php endif; ?>
<div class="section-head"><h2>Team players</h2></div><?php if (
    $members
): ?><div class="grid"><?php foreach (
    $members
    as $member
): ?><a class="card card-link" href="player.php?id=<?= $member[
    "id"
] ?>"><h3><?= e($member["name"]) ?></h3><p class="meta"><?= e(
    $member["sport_role"] ?: "Team member",
) ?></p></a><?php endforeach; ?></div><?php else: ?><div class="empty">No players have been assigned.</div><?php endif; ?>
<?php include "includes/footer.php"; ?>