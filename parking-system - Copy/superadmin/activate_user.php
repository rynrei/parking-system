<?php

session_start();

require '../config/database.php';
require '../config/activity_log.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    die("ACCESS DENIED");
}

if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = intval($_GET['id']);

// Get target user using NEW database columns
$sql = "SELECT UserID, Username, Role, Active
        FROM users
        WHERE UserID = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();

// Do not activate an already active account
if ((int)$user['Active'] === 1) {
    header("Location: manage_users.php");
    exit();
}

// Activate account
$sql = "UPDATE users
        SET Active = 1
        WHERE UserID = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    die("Failed to activate user: " . $stmt->error);
}

// Log action
logActivity(
    $conn,
    $_SESSION['id'],
    $_SESSION['username'],
    $_SESSION['role'],
    "Activated user account: " . $user['Username']
);

header("Location: manage_users.php");
exit();

?>