<?php

session_start();

require '../config/database.php';
require '../config/activity_log.php';

// Only Superadmin can create Staff
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    die("ACCESS DENIED");
}

// Get form data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    die("Username and password are required.");
}

// Check if username already exists
$sql = "SELECT UserID FROM users WHERE Username = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die("Username already exists!");
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Automatically assign Staff role
$role = "staff";
$active = 1;

// Insert Staff account
$sql = "INSERT INTO users (Username, Password, Role, Active)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "sssi",
    $username,
    $hashedPassword,
    $role,
    $active
);

if ($stmt->execute()) {

    // Log activity
    logActivity(
        $conn,
        $_SESSION['id'],
        $_SESSION['username'],
        $_SESSION['role'],
        "Created staff account: " . $username
    );

    // Return to Superadmin dashboard
    header("Location: dashboard.php");
    exit();

} else {

    die("Error creating Staff account: " . $stmt->error);

}

?>