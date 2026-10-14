<?php
require_once "includes/functions.php";
global $conn;

$user = requireLogin();
if ($user["role"] === "admin") {
    flash("error", "The admin inbox is disabled.");
    redirect("dashboard.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $messageId = (int) ($_POST["message_id"] ?? 0);
    $userId = (int) $user["id"];
    $academyId = (int) $user["academy_id"];
    
    $updateStmt = $conn->prepare(
        "UPDATE messages SET is_read=1 WHERE id=? AND (recipient_id=? OR (recipient_id IS NULL AND academy_id=?))"
    );
  
    $updateStmt->bind_param("iii", $messageId, $userId, $academyId);
    $updateStmt->execute();
    
    redirect("messages.php");
}

$userId = (int) $user["id"];
$academyId = (int) $user["academy_id"];

$stmt = $conn->prepare(
    "SELECT m.*,u.name sender_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.recipient_id=? OR (m.recipient_id IS NULL AND m.academy_id=?) ORDER BY m.created_at DESC"
);

$stmt->bind_param("ii", $userId, $academyId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

$pageTitle = "Messages";
include "includes/header.php";
?>
<div class="section-head"><div><h1>Messages</h1><p class="meta">Academy announcements, match updates and approval notifications.</p></div><?php if (
    in_array($user["role"], ["admin", "coach"], true)
): ?><a class="button" href="send-message.php">Send a message</a><?php endif; ?></div>
<?php if ($messages): ?><div class="grid"><?php foreach (
    $messages
    as $message
): ?><article class="card <?= !$message["is_read"]
    ? "unread"
    : "" ?>"><span class="tag"><?= $message["recipient_id"]
    ? "Direct message"
    : "Academy announcement" ?></span><h3><?= e(
    $message["subject"],
) ?></h3><p><?= nl2br(e($message["body"])) ?></p><p class="meta">From <?= e(
    $message["sender_name"],
) ?> · <?= e(formatDateTime($message["created_at"])) ?></p><?php if (
    !$message["is_read"]
): ?><form method="post"><input type="hidden" name="message_id" value="<?= $message[
    "id"
] ?>"><button class="button button-small">Mark as read</button></form><?php endif; ?></article><?php endforeach; ?></div><?php else: ?><div class="empty">You have no messages.</div><?php endif; ?>
<?php include "includes/footer.php"; ?>