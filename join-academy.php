<?php
require_once "includes/functions.php";
global $conn; 


$academiesResult = $conn->query("SELECT id,name FROM academies ORDER BY name");
$academies = [];
if ($academiesResult) {
    while ($row = $academiesResult->fetch_assoc()) {
        $academies[] = $row;
    }
}

$sports = loadSports($conn);
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selectedSports = selectedValues("sports");
    if (
        post("first_name") === "" ||
        post("last_name") === "" ||
        !filter_var(post("email"), FILTER_VALIDATE_EMAIL)
    ) {
        $error = "Please provide your name and a valid email.";
    } elseif (!$selectedSports) {
        $error = "Please select at least one sport.";
    }
    
    if (!$error && ($passwordError = validatePasswordConfirmation())) {
        $error = $passwordError;
    } else {
        try {
            $conn->begin_transaction();
            
            $academyId = (int) post("academy_id");
            $academySportCheck = $conn->prepare(
                "SELECT COUNT(*) as total FROM academy_sports WHERE academy_id=? AND sport_id=?"
            );
            
            foreach ($selectedSports as $sportId) {
                $academySportCheck->bind_param("ii", $academyId, $sportId);
                $academySportCheck->execute();
                $checkResult = $academySportCheck->get_result();
                $checkRow = $checkResult->fetch_assoc();
                
                if ((int) $checkRow["total"] === 0) {
                    throw new RuntimeException("Please choose sports offered by the selected academy.");
                }
            }
            
            $name = post("first_name") . " " . post("last_name");
            $firstName = post("first_name");
            $lastName = post("last_name");
            $birthDate = post("birth_date");
            $phone = post("phone");
            $email = post("email");
            $passwordHash = password_hash((string) $_POST["password"], PASSWORD_DEFAULT);
            $role = post("role");
            
            $insertUser = $conn->prepare(
                "INSERT INTO users (academy_id,name,first_name,last_name,birth_date,phone,email,password_hash,role,profile_status) VALUES (?,?,?,?,?,?,?,?,?,'pending')"
            );
         
            $insertUser->bind_param("issssssss", $academyId, $name, $firstName, $lastName, $birthDate, $phone, $email, $passwordHash, $role);
            $insertUser->execute();
            
            $memberId = (int) $conn->insert_id;
            
            $userSportStatement = $conn->prepare(
                "INSERT INTO user_sports (user_id,sport_id) VALUES (?,?)"
            );
            foreach ($selectedSports as $sportId) {
                $userSportStatement->bind_param("ii", $memberId, $sportId);
                $userSportStatement->execute();
            }
            
            $approvalStatement = $conn->prepare(
                "INSERT INTO approvals (academy_id,type,reference_id,requested_by) VALUES (?, 'user_registration', ?, ?)"
            );
            $approvalStatement->bind_param("iii", $academyId, $memberId, $memberId);
            $approvalStatement->execute();
            
            $conn->commit();
            
            flash(
                "success",
                "Your request was submitted. The academy admin must approve your account before you can log in.",
            );
            redirect("login.php");
        } catch (Exception $exception) {
            $conn->rollback();
            if ($exception instanceof RuntimeException) {
                $error = $exception->getMessage();
            } elseif ($exception instanceof mysqli_sql_exception && $exception->getCode() === 1062) {
                $error = "This email is already registered. Please use a different email.";
            } else {
                $error = "Unable to submit your request. Please try again.";
            }
        }
    }
}
$pageTitle = "Join an academy";
include "includes/header.php";
?>
<section class="form-card"><h1>Join an academy</h1><p class="meta">Choose your role and request access. Admin approval keeps every academy secure.</p>
<?php if ($error): ?><div class="flash error"><?= e(
    $error,
) ?></div><?php endif; ?>
<form method="post"><div class="form-grid"><label>First name<input name="first_name" required></label><label>Last name<input name="last_name" required></label><label>Birth date<input name="birth_date" type="date"></label><label>Phone<input name="phone" type="tel"></label><label class="full">Email<input name="email" type="email" required></label><label>Password<?= passwordInput() ?></label><label>Confirm password<?= passwordInput(
    "password_confirmation",
) ?></label><label>Role<select name="role"><option value="athlete">Athlete</option><option value="coach">Coach</option><option value="referee">Referee</option></select></label><label class="full">Academy<select name="academy_id" required><option value="">Select an academy</option><?php foreach (
    $academies
    as $academy
): ?><option value="<?= $academy["id"] ?>"><?= e(
    $academy["name"],
) ?></option><?php endforeach; ?></select></label><fieldset class="full sport-options"><legend>Sports <span class="meta">(select one or more)</span></legend><?php foreach (
    $sports
    as $sport
): ?><label><input type="checkbox" name="sports[]" value="<?= (int) $sport[
    "id"
] ?>" <?= in_array((int) $sport["id"], $selectedSports ?? [], true)
    ? "checked"
    : "" ?>> <?= e(
    $sport["name"],
) ?></label><?php endforeach; ?></fieldset></div><div class="form-actions"><button class="button">Send join request</button></div></form></section>
<?php include "includes/footer.php"; ?>