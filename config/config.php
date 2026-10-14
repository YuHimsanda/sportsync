<?php
session_start();
date_default_timezone_set("Asia/Colombo");

define('APP_NAME', 'SportSync');
define('APP_TAGLINE', 'Grow your game. Manage your academy.');

$host = "127.0.0.1";
$database = "sportsync_db";
$username = "root";
$password = "";

// MySQLi OOP Connection 
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>