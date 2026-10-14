<?php
require_once "includes/functions.php";
global $conn; 

$user = requireRole(["admin", "coach"]);
$error = "";

$academyId = (int) $user["academy_id"];
$userId = (int) $user["id"];

$membersStmt = $conn->prepare(
    "SELECT id,name,role FROM users WHERE academy_id=? AND profile_status='active' AND id<>? ORDER BY name"
);
$membersStmt->bind_param("ii", $academyId, $userId);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();
$members = [];
if ($membersResult) {
    while ($row = $membersResult->fetch_assoc()) {
        $members[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (post("subject") === "" || post("body") === "") {
        $error = "Subject and message are required.";
    } else {
        $recipient = (int) post("recipient_id");
        if ($recipient) {
            $validStmt = $conn->prepare(
                'SELECT COUNT(*) as total FROM users WHERE id=? AND academy_id=? AND profile_status="active"'
            );
            $validStmt->bind_param("ii", $recipient, $academyId);
            $validStmt->execute();
            $validResult = $validStmt->get_result();
            $validRow = $validResult->fetch_assoc();
            
            if ((int) $validRow["total"] === 0) {
                $error = "Invalid message recipient.";
            }
        }
        
        if (!$error) {
            $insertStmt = $conn->prepare(
                "INSERT INTO messages (academy_id,sender_id,recipient_id,subject,body) VALUES (?,?,?,?,?)"
            );
            $recipientParam = $recipient ?: null;
            $subject = post("subject");
            $body = post("body");
            
            // iiiss = integer, integer, integer, string, string
            $insertStmt->bind_param("iiiss", $academyId, $userId, $recipientParam, $subject, $body);
            $insertStmt->execute();
            
            flash("success", "Message sent.");
            redirect("messages.php");
        }
    }
}
$pageTitle = "Send message";
include "includes/header.php";
?>
<section class="form-card"><h1>Send a message</h1><p class="meta">Notify one member or your whole academy.</p><?php if (
    $error
): ?><div class="flash error"><?= e(
    $error,
) ?></div><?php endif; ?><form method="post"><label>Recipient<select name="recipient_id"><option value="">Whole academy</option><?php foreach (
    $members
    as $member
): ?><option value="<?= $member["id"] ?>"><?= e($member["name"]) ?> (<?= e(
     roleLabel($member["role"]),
 ) ?>)</option><?php endforeach; ?></select></label><label>Subject<input name="subject" required></label><label>Message<textarea name="body" required></textarea></label><div class="form-actions"><button class="button">Send message</button><a href="messages.php">Cancel</a></div></form></section>
<?php include "includes/footer.php"; ?>