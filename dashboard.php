<?php
require_once "includes/functions.php";
global $conn; // MySQLi connection එක

$user = requireLogin();
$pageTitle = "Dashboard";

$academy_id = (int) $user["academy_id"];

$upcomingQuery = "SELECT e.id,e.title,e.start_datetime,e.event_type,s.name sport_name,v.name venue_name FROM events e JOIN sports s ON s.id=e.sport_id JOIN venues v ON v.id=e.venue_id WHERE e.academy_id=? AND e.start_datetime>=NOW() ORDER BY e.start_datetime LIMIT 5";
$stmt = $conn->prepare($upcomingQuery);
$stmt->bind_param("i", $academy_id);
$stmt->execute();
$eventsResult = $stmt->get_result();
$events = [];
if ($eventsResult) {
    while ($row = $eventsResult->fetch_assoc()) {
        $events[] = $row;
    }
}


$teamCount = 0;
if ($academy_id) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM teams WHERE academy_id=?");
    $countStmt->bind_param("i", $academy_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    $teamCount = (int) $countRow["total"];
}
include "includes/header.php";
?>
<div class="profile-head dashboard-profile">
    <img class="avatar" src="<?= e($user["profile_photo"] ?: "Media/favcon.png") ?>" alt="Profile picture">
    <div>
        <h1>Hello, <?= e($user["first_name"] ?: $user["name"]) ?>.</h1>
        <p class="meta"><?= e(roleLabel($user["role"])) ?> · <?= e($user["academy_name"] ?? "SportSync") ?></p>
        <a href="profile.php">Edit profile and sports →</a>
    </div>
    <?php if (in_array($user["role"], ["admin", "coach"], true)): ?>
        <a class="button" href="create-match.php">Create a match</a>
    <?php endif; ?>
</div>

<div class="stats-grid dashboard-stats">
    <div class="stat-card">
        <span class="meta">Role</span>
        <strong><?= e(roleLabel($user["role"])) ?></strong>
    </div>
    <div class="stat-card">
        <span class="meta">Academy</span>
        <strong><?= e((string) ($user["academy_name"] ?? "—")) ?></strong>
    </div>
    <div class="stat-card">
        <span class="meta">Teams</span>
        <strong><?= $teamCount ?></strong>
    </div>
</div>

<div class="section-head">
    <h2>Upcoming academy matches</h2>
</div>

<?php if ($events): ?>
    <div class="grid">
        <?php foreach ($events as $event): ?>
            <a class="card card-link match-card" href="match.php?id=<?= (int) $event["id"] ?>">
                <span class="tag"><?= e(ucfirst($event["event_type"])) ?></span>
                <h3><?= e($event["title"]) ?></h3>
                <p><?= e($event["sport_name"]) ?> · <?= e($event["venue_name"]) ?></p>
                <p class="meta"><?= e(formatDateTime($event["start_datetime"])) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty">No upcoming matches for your academy.</div>
<?php endif; ?>

<div class="section-head">
    <h2>Management</h2>
</div>
<div class="grid">
    <a class="card" href="profile.php">
        <h3>My profile</h3>
        <p class="meta">Update your details and profile picture.</p>
    </a>
    <a class="card" href="teams.php">
        <h3>Find teams</h3>
        <p class="meta">Browse active academy teams.</p>
    </a>
    
    <?php if (in_array($user["role"], ["admin", "coach"], true)): ?>
        <a class="card" href="create-team.php">
            <h3>Create a team</h3>
            <p class="meta">Assign a sport, coach and players.</p>
        </a>
    <?php endif; ?>

    <?php if ($user["role"] === "admin"): ?>
        <a class="card" href="send-message.php">
            <h3>Send a message</h3>
            <p class="meta">Notify one academy member or the whole academy.</p>
        </a>
        <a class="card" href="approvals.php">
            <h3>Approvals</h3>
            <p class="meta">Review pending member and team requests.</p>
        </a>
        <a class="card" href="add-venue.php">
            <h3>Add a venue</h3>
            <p class="meta">Keep your academy venues ready for scheduling.</p>
        </a>
        
       
        <a class="card" href="manage-users.php">
            <h3>Manage staff</h3>
            <p class="meta">View, edit, or remove your academy staff.</p>
        </a>
        
    <?php endif; ?>
</div>
<!-- 
<?php if ($user["role"] !== "admin"): ?>
    <div class="section-head">
        <h2>Messages</h2>
        <a href="messages.php">Open inbox →</a>
    </div>
<?php endif; ?> -->

<?php include "includes/footer.php"; ?>