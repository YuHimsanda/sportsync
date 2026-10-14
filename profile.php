<?php
require_once "includes/functions.php";
global $conn; 

$user = requireLogin();
$error = "";

$allSports = loadSports($conn);
$academySports = [];
$userId = (int) $user["id"];
$academyId = (int) $user["academy_id"];

if ($user["academy_id"]) {
    $academySportsStatement = $conn->prepare(
        "SELECT s.id, s.name
         FROM academy_sports AS a
         JOIN sports AS s ON s.id = a.sport_id
         WHERE a.academy_id = ?
         ORDER BY s.name"
    );
    $academySportsStatement->bind_param("i", $academyId);
    $academySportsStatement->execute();
    $academyResult = $academySportsStatement->get_result();
    while ($row = $academyResult->fetch_assoc()) {
        $academySports[] = $row;
    }
}

$selectedAcademySports = [];
foreach ($academySports as $sport) {
    $selectedAcademySports[] = (int) $sport["id"];
}

$userSportsStatement = $conn->prepare(
    "SELECT sport_id FROM user_sports WHERE user_id = ?"
);
$userSportsStatement->bind_param("i", $userId);
$userSportsStatement->execute();
$userSportsResult = $userSportsStatement->get_result();
$selectedSports = [];

while ($row = $userSportsResult->fetch_assoc()) {
    $selectedSports[] = (int) $row["sport_id"];
}

$uploadDirectory = __DIR__ . "/uploads";

if (!is_dir($uploadDirectory)) {
    if (!mkdir($uploadDirectory, 0755, true)) {
        $error = "The profile picture directory could not be prepared.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = post("first_name");
    $lastName = post("last_name");
    $phone = post("phone");
    $newPassword = submittedPassword();
    $newSports = selectedValues("sports");
    $newAcademySports = selectedValues("academy_sports");
    $profilePhoto = $user["profile_photo"];

    if ($firstName === "" || $lastName === "") {
        $error = "First name and last name are required.";
    } elseif ($user["role"] !== "admin" && count($newSports) === 0) {
        $error = "Please select at least one sport.";
    } elseif ($user["role"] === "admin" && count($newAcademySports) === 0) {
        $error = "Please select at least one academy sport.";
    } elseif ($newPassword !== "") {
        $error = validatePasswordConfirmation();
    }

    if ($error === "" && $user["role"] !== "admin") {
        foreach ($newSports as $sportId) {
            if (!in_array($sportId, $selectedAcademySports, true)) {
                $error = "Please select only sports offered by your academy.";
                break;
            }
        }
    }

    if ($error === "" && $user["role"] === "admin") {
        $validSportIds = [];
        foreach ($allSports as $sport) {
            $validSportIds[] = (int) $sport["id"];
        }
        foreach ($newAcademySports as $sportId) {
            if (!in_array($sportId, $validSportIds, true)) {
                $error = "Please select valid academy sports.";
                break;
            }
        }
        if (count($newSports) > 0) {
            foreach ($newSports as $sportId) {
                if (!in_array($sportId, $newAcademySports, true)) {
                    $error = "Your sports must be selected from the academy sports.";
                    break;
                }
            }
        }
    }

    if (
        $error === "" &&
        isset($_FILES["profile_photo"]) &&
        $_FILES["profile_photo"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES["profile_photo"]["error"] !== UPLOAD_ERR_OK) {
            $error = "The profile picture could not be uploaded.";
        } elseif ($_FILES["profile_photo"]["size"] > 2 * 1024 * 1024) {
            $error = "Profile picture must be smaller than 2 MB.";
        } else {
            $fileName = basename($_FILES["profile_photo"]["name"]);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ["jpg", "jpeg", "png", "webp"];
            $imageInformation = getimagesize($_FILES["profile_photo"]["tmp_name"]);

            if (!in_array($fileExtension, $allowedExtensions, true)) {
                $error = "Please upload a JPG, PNG or WebP image.";
            } elseif ($imageInformation === false) {
                $error = "The selected file is not a valid image.";
            } else {
                $newFileName = "profile_" . $user["id"] . "_" . time() . "." . $fileExtension;
                $newFilePath = $uploadDirectory . "/" . $newFileName;

                if (!move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $newFilePath)) {
                    $error = "The profile picture could not be saved.";
                } else {
                    $profilePhoto = "uploads/" . $newFileName;
                }
            }
        }
    }

    if ($error === "") {
        try {
            $conn->begin_transaction();
            $passwordHash = null;

            if ($newPassword !== "") {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            if ($user["role"] === "admin") {
                $deleteAcademySports = $conn->prepare("DELETE FROM academy_sports WHERE academy_id = ?");
                $deleteAcademySports->bind_param("i", $academyId);
                $deleteAcademySports->execute();

                $insertAcademySport = $conn->prepare("INSERT INTO academy_sports (academy_id, sport_id) VALUES (?, ?)");
                foreach ($newAcademySports as $sportId) {
                    $insertAcademySport->bind_param("ii", $academyId, $sportId);
                    $insertAcademySport->execute();
                }
            }

            $fullName = $firstName . " " . $lastName;
            if ($passwordHash === null) {
                $updateUser = $conn->prepare(
                    "UPDATE users SET name = ?, first_name = ?, last_name = ?, phone = ?, profile_photo = ? WHERE id = ?"
                );
                $updateUser->bind_param("sssssi", $fullName, $firstName, $lastName, $phone, $profilePhoto, $userId);
                $updateUser->execute();
            } else {
                $updateUser = $conn->prepare(
                    "UPDATE users SET name = ?, first_name = ?, last_name = ?, phone = ?, profile_photo = ?, password_hash = ? WHERE id = ?"
                );
                $updateUser->bind_param("ssssssi", $fullName, $firstName, $lastName, $phone, $profilePhoto, $passwordHash, $userId);
                $updateUser->execute();
            }

            if ($user["role"] !== "admin") {
                $deleteUserSports = $conn->prepare("DELETE FROM user_sports WHERE user_id = ?");
                $deleteUserSports->bind_param("i", $userId);
                $deleteUserSports->execute();

                $insertUserSport = $conn->prepare("INSERT INTO user_sports (user_id, sport_id) VALUES (?, ?)");
                foreach ($newSports as $sportId) {
                    $insertUserSport->bind_param("ii", $userId, $sportId);
                    $insertUserSport->execute();
                }
            }

            $conn->commit();

            $_SESSION["user"]["name"] = $fullName;
            $_SESSION["user"]["first_name"] = $firstName;
            $_SESSION["user"]["last_name"] = $lastName;
            $_SESSION["user"]["phone"] = $phone;
            $_SESSION["user"]["profile_photo"] = $profilePhoto;

            flash("success", "Your profile and sports were updated.");
            redirect("profile.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Could not update profile details.";
        }
    }

    $selectedSports = $newSports;
    if ($user["role"] === "admin") {
        $selectedAcademySports = $newAcademySports;
    }
}

$pageTitle = "My profile";
include "includes/header.php";
?>
<section class="form-card">
    <h1>My profile</h1>
    <p class="meta">Update your details, sports and profile picture.</p>

    <?php if ($error !== ""): ?>
        <div class="flash error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="profile-head">
        <img class="avatar" src="<?= e($user["profile_photo"] ?: "Media/favcon.png") ?>" alt="Current profile picture">
        <div>
            <strong><?= e($user["name"]) ?></strong>
            <p class="meta">JPG, PNG or WebP · maximum 2 MB</p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
            <label>First name<input name="first_name" required value="<?= e($user["first_name"] ?: $user["name"]) ?>"></label>
            <label>Last name<input name="last_name" required value="<?= e($user["last_name"]) ?>"></label>
            <label>Phone<input name="phone" value="<?= e($user["phone"]) ?>"></label>
            <label>Profile picture<input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"></label>

            <?php if ($user["role"] === "admin"): ?>
                <fieldset class="full sport-options">
                    <legend>Academy sports</legend>
                    <?php foreach ($allSports as $sport): ?>
                        <label>
                            <input type="checkbox" name="academy_sports[]" value="<?= (int) $sport["id"] ?>" <?= in_array((int) $sport["id"], $selectedAcademySports, true) ? "checked" : "" ?>>
                            <?= e($sport["name"]) ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endif; ?>

            <?php if ($user["role"] !== "admin"): ?>
                <fieldset class="full sport-options">
                    <legend>My sports</legend>
                    <?php if (count($academySports) > 0): ?>
                        <?php foreach ($academySports as $sport): ?>
                            <label>
                                <input type="checkbox" name="sports[]" value="<?= (int) $sport["id"] ?>" <?= in_array((int) $sport["id"], $selectedSports, true) ? "checked" : "" ?>>
                                <?= e($sport["name"]) ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="meta">Your academy has not configured any sports yet.</span>
                    <?php endif; ?>
                </fieldset>
            <?php endif; ?>

            <label>Password <span class="meta">(leave blank to keep current password)</span><?= passwordInput("password", "new-password", false) ?></label>
            <label>Confirm password
                <span class="password-field">
                    <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-label="Show password">Show</button>
                </span>
            </label>
        </div>
        <div class="form-actions"><button class="button" type="submit">Save profile</button></div>
    </form>
</section>
<?php include "includes/footer.php"; ?>