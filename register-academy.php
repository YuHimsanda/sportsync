<?php
require_once "includes/functions.php";
$error = "";
global $conn; 
$sports = loadSports($conn); // includes/functions.php හි loadSports() $conn සඳහා වෙනස් කළ යුතුයි

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $required = ["first_name", "last_name", "academy_name", "academy_address", "email", "password"];
    foreach ($required as $field) {
        if (post($field) === "") {
            $error = "Please complete all required fields.";
        }
    }
    $selectedSports = selectedValues("sports");
    if (!$selectedSports) {
        $error = "Please select at least one sport.";
    }
    if (!$error && !filter_var(post("email"), FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    if (!$error && ($passwordError = validatePasswordConfirmation())) {
        $error = $passwordError;
    }
    
    if (!$error) {
        try {
          
            $conn->begin_transaction();
            
            $academy_name = post("academy_name");
            $academy_address = post("academy_address");
            
            $stmt1 = $conn->prepare("INSERT INTO academies (name,address) VALUES (?,?)");
            $stmt1->bind_param("ss", $academy_name, $academy_address);
            $stmt1->execute();
            
            $academyId = (int) $conn->insert_id;
            
            $sportStatement = $conn->prepare("INSERT INTO academy_sports (academy_id,sport_id) VALUES (?,?)");
            foreach ($selectedSports as $sportId) {
                $sportStatement->bind_param("ii", $academyId, $sportId); 
                $sportStatement->execute();
            }
            
            $name = post("first_name") . " " . post("last_name");
            $first_name = post("first_name");
            $last_name = post("last_name");
            $email = post("email");
            $phone = post("phone");
            $password_hash = password_hash((string) $_POST["password"], PASSWORD_DEFAULT);
            $role = 'admin';
            $status = 'active';

            $stmt2 = $conn->prepare("INSERT INTO users (academy_id,name,first_name,last_name,email,phone,password_hash,role,profile_status) VALUES (?,?,?,?,?,?,?,?,?)");
          
            $stmt2->bind_param("issssssss", $academyId, $name, $first_name, $last_name, $email, $phone, $password_hash, $role, $status);
            $stmt2->execute();
            
            
            $conn->commit();
            
            flash("success", "Academy registered successfully. You can now log in.");
            redirect("login.php");
            
        } catch (mysqli_sql_exception $exception) {
            
            $conn->rollback();
            $error = ($exception->getCode() === 1062) // 1062 යනු MySQL duplicate entry error code එකයි
                    ? "That academy name or email is already registered. Please use a different one."
                    : "Unable to register the academy. Please try again.";
        }
    }
}
$pageTitle = "Register an academy";
include "includes/header.php";
?>

<section class="form-card"><h1>Register an academy</h1><p class="meta">Create the academy admin account and start building your sports community.</p>
<?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><div class="form-grid">
<label>First name<input name="first_name" required value="<?= e(post("first_name")) ?>"></label><label>Last name<input name="last_name" required value="<?= e(post("last_name")) ?>"></label>
<label>Academy name<input name="academy_name" required></label><label>Admin mobile number<input name="phone" type="tel"></label>
<label class="full">Academy address<textarea name="academy_address" required></textarea></label><label>Admin email<input name="email" type="email" required></label><label>Password<?= passwordInput() ?></label><label>Confirm password<?= passwordInput("password_confirmation") ?></label>
<fieldset class="full sport-options"><legend>Sports offered <span class="meta">(select one or more)</span></legend>
<?php foreach ($sports as $sport): ?>
    <label><input type="checkbox" name="sports[]" value="<?= (int) $sport["id"] ?>" <?= in_array((int) $sport["id"], array_map("intval", (array) ($_POST["sports"] ?? [])), true) ? "checked" : "" ?>> <?= e($sport["name"]) ?></label>
<?php endforeach; ?>
</fieldset>
</div><div class="form-actions"><button class="button">Register academy</button><a href="login.php">Already registered?</a></div></form></section>
<?php include "includes/footer.php"; ?>