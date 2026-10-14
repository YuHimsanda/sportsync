<?php
require_once "includes/functions.php";
global $conn;

$user = requireLogin();
$pageTitle = "Edit Staff";

if ($user["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$academy_id = (int) $user["academy_id"];
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staff_id === 0) {
    header("Location: manage-users.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $role = $_POST["role"] ?? "";

    if (empty($name) || empty($email) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        $updateQuery = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ? AND academy_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssii", $name, $email, $role, $staff_id, $academy_id);
        
        if ($stmt->execute()) {
            $success = "Staff updated successfully!";
        } else {
            $error = "Failed to update.";
        }
    }
}

$query = "SELECT id, name, email, role FROM users WHERE id = ? AND academy_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $staff_id, $academy_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-users.php");
    exit();
}

$staffData = $result->fetch_assoc();

include "includes/header.php";
?>

<div class="section-head">
    <h2>Edit Staff Member</h2>
</div>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <?php if ($error): ?>
        <div style="color: #dc3545; margin-bottom: 15px; font-weight: bold;"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="color: #28a745; margin-bottom: 15px; font-weight: bold;"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <p>
            <label class="meta">Full Name</label><br>
            <input type="text" name="name" value="<?= e($staffData['name']) ?>" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;">
        </p>
        <p>
            <label class="meta">Email Address</label><br>
            <input type="email" name="email" value="<?= e($staffData['email']) ?>" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;">
        </p>
       <p>
    <label class="meta">Assign Role</label><br>
    <select name="role" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;">
        <option value="">Select a role...</option>
       
        <option value="coach" <?= $staffData['role'] === 'coach' ? 'selected' : '' ?>>Coach</option>
        
       
        <option value="referee" <?= $staffData['role'] === 'referee' ? 'selected' : '' ?>>Referee</option>
        
        <
        <option value="athlete" <?= $staffData['role'] === 'athlete' ? 'selected' : '' ?>>Athlete</option>
        
    </select>
</p>
        <div style="margin-top: 20px;">
            <button type="submit" class="button">Update User</button>
            <a href="manage-users.php" style="margin-left: 10px; text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>

<?php include "includes/footer.php"; ?>