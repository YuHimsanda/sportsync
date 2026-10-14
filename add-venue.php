<?php
require_once "includes/functions.php";
global $conn;

$user = requireRole(["admin"]);
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (post("name") === "" || post("location") === "") {
        $error = "Venue name and location are required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO venues (academy_id, name, location) VALUES (?, ?, ?)");
            $academy_id = (int) $user["academy_id"];
            $name = post("name");
            $location = post("location");
            
      
            $stmt->bind_param("iss", $academy_id, $name, $location);
            $stmt->execute();

            flash("success", "Venue added.");
            redirect("dashboard.php");
        } catch (mysqli_sql_exception $exception) {
        
            if ($exception->getCode() === 1062) {
                $error = "That venue name already exists for your academy.";
            } else {
                $error = "An error occurred while adding the venue.";
            }
        }
    }
}
$pageTitle = "Add venue";
include "includes/header.php";
?>

<section class="form-card">
    <h1>Add a venue</h1>
    <p class="meta">Add a venue before scheduling your first match.</p>

    <?php if ($error): ?>
        <div class="flash error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>
            Venue name
            <input name="name" required placeholder="e.g. Main Ground">
        </label>

        <label>
            Location
            <input name="location" required placeholder="e.g. Colombo">
        </label>

        <div class="form-actions">
            <button class="button">Add venue</button>
            <a href="dashboard.php">Cancel</a>
        </div>
    </form>
</section>

<?php include "includes/footer.php"; ?>