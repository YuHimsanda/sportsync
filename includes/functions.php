<?php

require_once __DIR__ . "/../config/config.php";

function e($value) {
    if ($value === null) return "";
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: " . $path);
    exit();
}

function flash($type, $message) {
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
}

function consumeFlash() {
    if (isset($_SESSION["flash"])) {
        $flash = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        return $flash;
    }
    return null;
}

function currentUser() {
    return $_SESSION["user"] ?? null;
}

function requireLogin() {
    $user = currentUser();
    if (!$user) {
        flash("error", "Please log in to continue.");
        redirect("login.php");
    }
    return $user;
}

function requireRole($roles) {
    $user = requireLogin();
    if (!in_array($user["role"], $roles, true)) {
        flash("error", "You do not have permission to view that page.");
        redirect("index.php");
    }
    return $user;
}

function post($key) {
    return trim((string) ($_POST[$key] ?? ""));
}

function submittedPassword() {
    return (string) ($_POST["password"] ?? "");
}

function validatePasswordConfirmation() {
    $password = submittedPassword();
    $confirmation = (string) ($_POST["password_confirmation"] ?? "");
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if ($password !== $confirmation) {
        return "Passwords do not match.";
    }
    return null;
}

function passwordInput($name = "password", $autocomplete = "new-password", $required = true) {
    $req = $required ? "required" : "";
    return '<span class="password-field">
        <input type="password" name="' . e($name) . '" ' . $req . ' minlength="8" autocomplete="' . e($autocomplete) . '">
        <button type="button" class="password-toggle" aria-label="Show password">Show</button>
    </span>';
}

function roleLabel($role) {
    if ($role === "player") return "Athlete";
    return ucfirst($role);
}

function formatDateTime($value) {
    if (!$value) return "TBA";
    return date("M j, Y · g:i A", strtotime($value));
}


function loadSports($conn) {
    $result = $conn->query("SELECT id, name FROM sports ORDER BY name");
    $sports = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sports[] = $row;
        }
    }
    return $sports;
}

function sportStatFields($sport) {
    $fields = [];
    $sport = strtolower((string)$sport);
    
    if ($sport === "cricket") {
        $fields = [
            "Batting" => ["runs" => "Runs", "balls_faced" => "Balls faced", "fours" => "Fours", "sixes" => "Sixes"],
            "Bowling" => ["overs" => "Overs", "maidens" => "Maidens", "runs_conceded" => "Runs conceded", "wickets" => "Wickets", "bbi" => "Best bowling"],
            "Fielding" => ["catches" => "Catches", "run_outs" => "Run outs", "stumpings" => "Stumpings"]
        ];
    } elseif ($sport === "football") {
        $fields = [
            "Attacking" => ["goals" => "Goals", "assists" => "Assists", "shots_on_target" => "Shots on target"],
            "Defensive" => ["tackles" => "Tackles", "interceptions" => "Interceptions", "clearances" => "Clearances"],
            "Goalkeeping" => ["saves" => "Saves", "clean_sheet" => "Clean sheet (1=Yes, 0=No)"],
            "Discipline" => ["yellow_cards" => "Yellow cards", "red_cards" => "Red cards"]
        ];
    } elseif ($sport === "basketball") {
        $fields = [
            "Scoring" => ["points" => "Points", "three_pointers" => "3-Pointers made", "free_throws" => "Free throws made"],
            "Playmaking" => ["assists" => "Assists", "offensive_rebounds" => "Offensive rebounds", "defensive_rebounds" => "Defensive rebounds"],
            "Defensive" => ["steals" => "Steals", "blocks" => "Blocks", "turnovers" => "Turnovers"]
        ];
    } elseif ($sport === "rugby") {
        $fields = [
            "Scoring" => ["tries" => "Tries", "conversions" => "Conversions", "penalties" => "Penalties", "drop_goals" => "Drop goals"],
            "Play" => ["tackles_made" => "Tackles made", "linebreaks" => "Linebreaks", "handling_errors" => "Handling errors"],
            "Discipline" => ["yellow_cards" => "Yellow cards", "red_cards" => "Red cards"]
        ];
    } elseif ($sport === "hockey") {
        $fields = [
            "Attacking" => ["goals" => "Goals", "assists" => "Assists", "penalty_corners_won" => "Penalty corners won"],
            "Defensive" => ["tackles" => "Tackles", "interceptions" => "Interceptions", "saves" => "Saves (Goalkeeper)"],
            "Discipline" => ["green_cards" => "Green cards", "yellow_cards" => "Yellow cards", "red_cards" => "Red cards"]
        ];
    }
    
    return $fields;
}

function selectedValues($key) {
    $values = (array) ($_POST[$key] ?? []);
    $clean = [];
    foreach ($values as $value) {
        $intVal = (int) $value;
        if ($intVal > 0 && !in_array($intVal, $clean, true)) {
            $clean[] = $intVal;
        }
    }
    return $clean;
}