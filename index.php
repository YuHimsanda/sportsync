<?php
require_once "includes/functions.php";
global $conn;

$pageTitle = "Home";


$upcomingQuery = "SELECT e.*, s.name sport_name, v.name venue_name, a.name academy_name,
    GROUP_CONCAT(t.name ORDER BY t.id SEPARATOR ' vs ') teams
    FROM events e JOIN sports s ON s.id=e.sport_id JOIN venues v ON v.id=e.venue_id
    JOIN academies a ON a.id=e.academy_id LEFT JOIN event_teams et ON et.event_id=e.id
    LEFT JOIN teams t ON t.id=et.team_id WHERE e.start_datetime >= NOW()
    GROUP BY e.id ORDER BY e.start_datetime LIMIT 3";

$upcomingResult = $conn->query($upcomingQuery);
$upcoming = [];
if ($upcomingResult) {
    while ($row = $upcomingResult->fetch_assoc()) {
        $upcoming[] = $row;
    }
}

$recentQuery = "SELECT e.*, s.name sport_name, v.name venue_name, a.name academy_name,
    GROUP_CONCAT(t.name ORDER BY t.id SEPARATOR ' vs ') teams
    FROM events e JOIN sports s ON s.id=e.sport_id JOIN venues v ON v.id=e.venue_id
    JOIN academies a ON a.id=e.academy_id LEFT JOIN event_teams et ON et.event_id=e.id
    LEFT JOIN teams t ON t.id=et.team_id WHERE e.status='completed'
    GROUP BY e.id ORDER BY e.start_datetime DESC LIMIT 5";

$recentResult = $conn->query($recentQuery);
$recent = [];
if ($recentResult) {
    while ($row = $recentResult->fetch_assoc()) {
        $recent[] = $row;
    }
}

include "includes/header.php";
?>
<section class="hero">
    <div>
        <h1>Grow your game.<br>Manage your academy.</h1>
        <p>SportSync brings players, coaches, referees and academy admins together to organise teams, matches and meaningful performance records in one clear place.</p>
        <div class="hero-actions">
            <a class="button" href="register-academy.php">Register an Academy</a>
            <a class="button button-outline" style="color:#fff" href="join-academy.php">Join an Academy</a>
        </div>
    </div>
    <div class="hero-card"><h2>One connected sports community</h2><p>Discover academies, find players, follow upcoming fixtures and keep every result ready for the next challenge.</p><strong>Cricket · Football · Basketball · Hockey · Rugby</strong></div>
</section>
<div class="section-head"><h2>Upcoming matches</h2><a href="matches.php">View all matches →</a></div>
<?php if ($upcoming): ?><div class="grid"><?php foreach (
    $upcoming
    as $match
): ?>
<a class="card card-link match-card" href="match.php?id=<?= (int) $match[
    "id"
] ?>"><span class="tag"><?= e(ucfirst($match["event_type"])) ?></span><h3><?= e(
    $match["teams"] ?: $match["academy_name"],
) ?></h3><p class="meta"><?= e($match["sport_name"]) ?> · <?= e(
     $match["academy_name"],
 ) ?></p><p><?= e(formatDateTime($match["start_datetime"])) ?><br><?= e(
    $match["venue_name"],
) ?></p></a>
<?php endforeach; ?></div><?php else: ?><div class="empty">No upcoming matches have been scheduled yet.</div><?php endif; ?>
<div class="section-head"><h2>Recent official matches</h2><a href="matches.php?type=official">Browse results →</a></div>
<?php if ($recent): ?><div class="grid"><?php foreach ($recent as $match): ?>
<a class="card card-link" href="match.php?id=<?= (int) $match[
    "id"
] ?>"><span class="tag"><?= e($match["sport_name"]) ?></span><h3><?= e(
    $match["teams"] ?: $match["academy_name"],
) ?></h3><p class="meta"><?= e(
    formatDateTime($match["start_datetime"]),
) ?></p><strong><?= e((string) ($match["home_score"] ?? "-")) ?> — <?= e(
     (string) ($match["away_score"] ?? "-"),
 ) ?></strong></a>
<?php endforeach; ?></div><?php else: ?><div class="empty">Completed official match results will appear here.</div><?php endif; ?>
<?php include "includes/footer.php"; ?>