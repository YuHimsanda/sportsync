<?php
require_once "includes/functions.php";
global $conn;

$user = requireLogin();

if ($user["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$academy_id = (int) $user["academy_id"];
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staff_id > 0) {

    $deleteQuery = "DELETE FROM users WHERE id = ? AND academy_id = ? AND role IN ('manager', 'coach')";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $staff_id, $academy_id);
    $stmt->execute();
}


header("Location: manage-users.php");
exit();
?>