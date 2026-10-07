<?php

session_start();

require '../config/database.php';
require '../config/activity_log.php';

// Only Admin can deactivate Staff
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    die("ACCESS DENIED");
}

// Check if UserID was provided
if (!isset($_GET['id'])) {
    die("Staff ID not provided.");
}

$staff_id = intval($_GET['id']);

if ($staff_id <= 0) {
    die("Invalid Staff ID.");
}

// Make sure the account is actually Staff
$sql = "SELECT UserID, Username, Role, Active
        FROM users
        WHERE UserID = ? AND Role = 'staff'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Staff account not found.");
}

$staff = $result->fetch_assoc();

// Do not deactivate an already deactivated account
if ((int)$staff['Active'] === 0) {
    header("Location: manage_staff.php");
    exit();
}

// Deactivate Staff
$sql = "UPDATE users
        SET Active = 0
        WHERE UserID = ? AND Role = 'staff'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $staff_id);

if (!$stmt->execute()) {
    die("Failed to deactivate staff: " . $stmt->error);
}

// Record activity
logActivity(
    $conn,
    $_SESSION['id'],
    $_SESSION['username'],
    $_SESSION['role'],
    "Deactivated staff account: " . $staff['Username']
);

// Return to Manage Staff
header("Location: manage_staff.php");
exit();

?>