<?php

session_start();

require 'config/database.php';
require 'config/activity_log.php';

if (isset($_SESSION['id'])) {

    logActivity(
        $conn,
        $_SESSION['id'],
        $_SESSION['username'],
        $_SESSION['role'],
        "Logged out"
    );

}

session_unset();
session_destroy();

header("Location: login.php");
exit();

?>