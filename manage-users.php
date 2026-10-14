<?php
require_once "includes/functions.php";
global $conn;

$user = requireLogin();
$pageTitle = "Manage Staff";

if ($user["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$academy_id = (int) $user["academy_id"];
$query = "SELECT id, name, email, role FROM users WHERE academy_id = ? AND role IN ('coach', 'referee', 'athlete')";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $academy_id);
$stmt->execute();
$result = $stmt->get_result();

include "includes/header.php";
?>

<div class="section-head" style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Academy Staff</h2>

</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="grid">
        <?php while ($staff = $result->fetch_assoc()): ?>
            <div class="card">
                <span class="tag"><?= e(ucfirst($staff['role'])) ?></span>
                <h3 style="margin-top: 10px;"><?= e($staff['name']) ?></h3>
                <p class="meta"><?= e($staff['email']) ?></p>
                
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <a href="edit-user.php?id=<?= $staff['id'] ?>" class="button" style="padding: 5px 12px; font-size: 0.9em;">Edit</a>
                    <a href="delete-user.php?id=<?= $staff['id'] ?>" class="button" style="background-color: #dc3545; padding: 5px 12px; font-size: 0.9em;" onclick="return confirm('Delete this user?');">Delete</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="empty">No staff members found in your academy.</div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>