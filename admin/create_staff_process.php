<?php

session_start();

require '../config/database.php';
require '../config/activity_log.php';

// Make sure the user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Only Admin can create Staff
if ($_SESSION['role'] !== 'admin') {
    die("ACCESS DENIED");
}

// Get form data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$shift = $_POST['shift'] ?? '';

if ($username === '' || $password === '' || $shift === '') {
    die("Username, password, and shift are required.");
}

if (!in_array($shift, ['Morning', 'Night'])) {
    die("Invalid shift selected.");
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

// Admin-created accounts are always Staff
$role = "staff";
$active = 1;

// Insert Staff
$sql = "INSERT INTO users (Username, Password, Role, Active, shift)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "sssis",
    $username,
    $hashedPassword,
    $role,
    $active,
    $shift
);

if ($stmt->execute()) {

    // Log the action
    logActivity(
        $conn,
        $_SESSION['id'],
        $_SESSION['username'],
        $_SESSION['role'],
        "Created staff account: " . $username
    );

    // Return to Admin dashboard
    header("Location: dashboard.php");
    exit();

} else {

    die("Error creating Staff account: " . $stmt->error);

}

?>