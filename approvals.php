<?php
require_once "includes/functions.php";
global $conn;

$user = requireRole(["admin"]);
$error = "";

$academyId = (int) $user["academy_id"];
$userId = (int) $user["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $approvalId = (int) ($_POST["approval_id"] ?? 0);
    $decision = post("decision");
    
    $statement = $conn->prepare(
        "SELECT * FROM approvals WHERE id=? AND academy_id=? AND status='pending'"
    );
    $statement->bind_param("ii", $approvalId, $academyId);
    $statement->execute();
    $result = $statement->get_result();
    $approval = $result->fetch_assoc();
    
    if ($approval && in_array($decision, ["approved", "rejected"], true)) {
        try {
            $conn->begin_transaction();
            
            $updateApproval = $conn->prepare(
                "UPDATE approvals SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?"
            );
         
            $updateApproval->bind_param("sii", $decision, $userId, $approvalId);
            $updateApproval->execute();
            
            $message = "Your " . str_replace("_", " ", $approval["type"]) . " request was " . $decision . ".";
            $referenceId = (int) $approval["reference_id"];
            $decisionStatus = $decision === "approved" ? "active" : "rejected";
            
            if ($approval["type"] === "user_registration") {
                $updateUser = $conn->prepare("UPDATE users SET profile_status=? WHERE id=?");
                $updateUser->bind_param("si", $decisionStatus, $referenceId);
                $updateUser->execute();
            } elseif ($approval["type"] === "team_creation") {
                $updateTeam = $conn->prepare("UPDATE teams SET status=? WHERE id=?");
                $updateTeam->bind_param("si", $decisionStatus, $referenceId);
                $updateTeam->execute();
            } elseif ($approval["type"] === "team_join_request") {
                $requestStatement = $conn->prepare(
                    "SELECT tjr.*,t.sport_id FROM team_join_requests tjr JOIN teams t ON t.id=tjr.team_id WHERE tjr.id=? AND tjr.academy_id=?"
                );
                $requestStatement->bind_param("ii", $referenceId, $academyId);
                $requestStatement->execute();
                $requestResult = $requestStatement->get_result();
                $request = $requestResult->fetch_assoc();
                
                if (!$request) {
                    throw new RuntimeException("The team join request no longer exists.");
                }
                
                $requestId = (int) $request["id"];
                $updateJoin = $conn->prepare(
                    "UPDATE team_join_requests SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?"
                );
                $updateJoin->bind_param("sii", $decision, $userId, $requestId);
                $updateJoin->execute();
                
                if ($decision === "approved") {
                    $sportCheck = $conn->prepare(
                        'SELECT COUNT(*) as total FROM user_sports WHERE user_id=? AND sport_id=? AND EXISTS (SELECT 1 FROM users WHERE id=? AND profile_status="active")'
                    );
                    $playerId = (int) $request["player_id"];
                    $sportId = (int) $request["sport_id"];
                    $sportCheck->bind_param("iii", $playerId, $sportId, $playerId);
                    $sportCheck->execute();
                    $sportResult = $sportCheck->get_result();
                    $sportRow = $sportResult->fetch_assoc();
                    
                    if ((int) $sportRow["total"] === 0) {
                        throw new RuntimeException("The player is not active for this team sport.");
                    }
                    
                    $insertPlayer = $conn->prepare(
                        "INSERT IGNORE INTO team_players (team_id,player_id,sport_role) VALUES (?,?,?)"
                    );
                    $teamId = (int) $request["team_id"];
                    $nullRole = null;
                    $insertPlayer->bind_param("iis", $teamId, $playerId, $nullRole);
                    $insertPlayer->execute();
                }
            }
            
            $insertMessage = $conn->prepare(
                "INSERT INTO messages (academy_id,sender_id,recipient_id,subject,body) VALUES (?,?,?,?,?)"
            );
            $requestedBy = (int) $approval["requested_by"];
            $subject = "Approval update";
            $insertMessage->bind_param("iiiss", $academyId, $userId, $requestedBy, $subject, $message);
            $insertMessage->execute();
            
            $conn->commit();
            flash("success", "Approval updated and notification sent.");
            redirect("approvals.php");
        } catch (Exception $exception) {
            $conn->rollback();
            $error = $exception->getMessage();
        }
    }
}

$pendingStmt = $conn->prepare("SELECT ap.*,u.name requester,u.role requester_role,
    CASE WHEN ap.type='user_registration' THEN target.name WHEN ap.type='team_join_request' THEN joiner.name ELSE team_target.name END target_name,
    CASE WHEN ap.type='user_registration' THEN target.role WHEN ap.type='team_join_request' THEN joiner.role ELSE NULL END target_role
    FROM approvals ap JOIN users u ON u.id=ap.requested_by
    LEFT JOIN users target ON ap.type='user_registration' AND target.id=ap.reference_id
    LEFT JOIN team_join_requests tjr ON ap.type='team_join_request' AND tjr.id=ap.reference_id
    LEFT JOIN users joiner ON joiner.id=tjr.player_id
    LEFT JOIN teams team_target ON ap.type='team_creation' AND team_target.id=ap.reference_id
    WHERE ap.academy_id=? AND ap.status='pending' ORDER BY ap.created_at");
$pendingStmt->bind_param("i", $academyId);
$pendingStmt->execute();
$pendingResult = $pendingStmt->get_result();
$pending = [];
if ($pendingResult) {
    while ($row = $pendingResult->fetch_assoc()) {
        $pending[] = $row;
    }
}

$pageTitle = "Approvals";
include "includes/header.php";
?>
<div class="section-head"><div><h1>Admin approvals</h1><p class="meta">Review membership, team and cross-academy player requests.</p></div></div>
<?php if ($error): ?><div class="flash error"><?= e(
    $error,
) ?></div><?php endif; ?>
<?php if (
    $pending
): ?><div class="table-wrap"><table><tr><th>Request</th><th>User / team</th><th>Requested by</th><th>Created</th><th>Action</th></tr><?php foreach (
    $pending
    as $item
): ?><tr><td><?= e(
    ucwords(str_replace("_", " ", $item["type"])),
) ?></td><td><?=
e($item["target_name"] ?: $item["requester"])
?><?php if ($item["target_role"]): ?><br><span class="meta"><?= e(
    roleLabel($item["target_role"]),
) ?></span><?php endif;
?></td><td><?= e($item["requester"]) ?> <span class="meta">(<?= e(
     roleLabel($item["requester_role"]),
 ) ?>)</span></td><td><?= e(
    formatDateTime($item["created_at"]),
) ?></td><td><form method="post" class="form-actions"><input type="hidden" name="approval_id" value="<?= $item[
    "id"
] ?>"><button class="button button-small" name="decision" value="approved">Approve</button><button class="button button-small button-outline" name="decision" value="rejected">Reject</button></form></td></tr><?php endforeach; ?></table></div><?php else: ?><div class="empty">There are no pending approvals.</div><?php endif; ?>
<?php include "includes/footer.php"; ?>