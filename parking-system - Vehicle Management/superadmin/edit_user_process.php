<?php

session_start();

require '../config/database.php';
require '../config/activity_log.php';

// Check login
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Only Superadmin
if ($_SESSION['role'] !== 'superadmin') {
    die("ACCESS DENIED");
}

// Get form data
$id = intval($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';

if ($id <= 0 || $username === '') {
    die("Invalid user information.");
}

// Only allow valid roles
if (!in_array($role, ['admin', 'staff', 'superadmin'], true)) {
    die("Invalid role.");
}

// Get the current user
$sql = "SELECT UserID, Username, Role
        FROM users
        WHERE UserID = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$currentUser = $result->fetch_assoc();

// Check duplicate username
$sql = "SELECT UserID
        FROM users
        WHERE Username = ?
        AND UserID != ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $username, $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die("Username already exists!");
}

// Update username and role
$sql = "UPDATE users
        SET Username = ?, Role = ?
        WHERE UserID = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("ssi", $username, $role, $id);

if (!$stmt->execute()) {
    die("Failed to update user: " . $stmt->error);
}

// Update password only if supplied
if ($password !== '') {

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $sql = "UPDATE users
            SET Password = ?
            WHERE UserID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashedPassword, $id);

    if (!$stmt->execute()) {
        die("Failed to update password: " . $stmt->error);
    }
}

// Log the action
logActivity(
    $conn,
    $_SESSION['id'],
    $_SESSION['username'],
    $_SESSION['role'],
    "Edited user account: " . $username
);

// Return to Manage Users
header("Location: manage_users.php");
exit();

?>