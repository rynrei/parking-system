<?php

session_start();

require '../config/database.php';
require '../config/activity_log.php';

// Only Admin can edit Staff
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    die("ACCESS DENIED");
}

// Get form data
$id = intval($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($id <= 0 || $username === '') {
    die("Invalid staff information.");
}

// Make sure the account is actually Staff
$sql = "SELECT UserID, Username, Role
        FROM users
        WHERE UserID = ? AND Role = 'staff'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Staff account not found.");
}

$staff = $result->fetch_assoc();

// Check if username is already used by another account
$sql = "SELECT UserID
        FROM users
        WHERE Username = ?
        AND UserID != ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("si", $username, $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die("Username already exists!");
}

// Update username
$sql = "UPDATE users
        SET Username = ?
        WHERE UserID = ? AND Role = 'staff'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("si", $username, $id);

if (!$stmt->execute()) {
    die("Failed to update staff: " . $stmt->error);
}

// Update password only if a new password was entered
if ($password !== '') {

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE users
            SET Password = ?
            WHERE UserID = ? AND Role = 'staff'";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("si", $hashedPassword, $id);

    if (!$stmt->execute()) {
        die("Failed to update password: " . $stmt->error);
    }
}

// Record activity
logActivity(
    $conn,
    $_SESSION['id'],
    $_SESSION['username'],
    $_SESSION['role'],
    "Edited staff account: " . $username
);

// Return to Manage Staff
header("Location: manage_staff.php");
exit();

?>