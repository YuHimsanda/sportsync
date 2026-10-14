<?php
require_once "includes/functions.php";
if (currentUser()) {
    redirect("dashboard.php");
}
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    global $conn; 
    
  
    $statement = $conn->prepare(
        "SELECT u.*, a.name academy_name FROM users u LEFT JOIN academies a ON a.id=u.academy_id WHERE u.email=? AND u.profile_status='active' LIMIT 1"
    );
    
    $email = post("email");
  
    $statement->bind_param("s", $email); 
    $statement->execute();
    

    $result = $statement->get_result();
    $user = $result->fetch_assoc();

    if (
        $user &&
        password_verify(
            (string) ($_POST["password"] ?? ""),
            $user["password_hash"],
        )
    ) {
        $_SESSION["user"] = $user;
        redirect("dashboard.php");
    }
    $error = "Invalid email or password, or your account is still awaiting approval.";
}
$pageTitle = "Log in";
include "includes/header.php";
?>
<section class="form-card"><h1>Welcome back</h1><p class="meta">Log in to your SportSync workspace.</p>
<?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><label>Email<input type="email" name="email" required autocomplete="email"></label><label>Password<?= passwordInput("password","current-password") ?></label><div class="form-actions"><button class="button">Log in</button><a href="join-academy.php">Create a member account</a></div></form></section>
<?php include "includes/footer.php"; ?>