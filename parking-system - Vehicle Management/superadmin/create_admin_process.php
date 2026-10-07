<?php

session_start();

require '../config/database.php';
require '../config/activity_log.php';

// Only Superadmin can create Admin accounts
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

// Automatically assign Admin role
$role = "admin";
$active = 1;

// Insert Admin
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

    logActivity(
        $conn,
        $_SESSION['id'],
        $_SESSION['username'],
        $_SESSION['role'],
        "Created admin account: " . $username
    );

    header("Location: dashboard.php");
    exit();

} else {

    echo "Error creating Admin account: " . $stmt->error;

}

?>