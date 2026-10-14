<?php
require_once "includes/functions.php";
$_SESSION = [];
session_destroy();
redirect("index.php");