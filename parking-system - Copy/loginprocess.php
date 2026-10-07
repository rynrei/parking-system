<?php

session_start();

require 'config/database.php';
require 'config/activity_log.php';

// Make sure the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo "Username and password are required.";
    exit();
}

// Find an active user
$sql = "SELECT UserID, Username, Password, Role, Active
        FROM users
        WHERE Username = ? AND Active = 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Login query failed: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    // Verify hashed password
    if (password_verify($password, $user['Password'])) {

        // Store login information in session
        $_SESSION['id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['Role'];

        // Record successful login
        logActivity(
            $conn,
            $user['UserID'],
            $user['Username'],
            $user['Role'],
            "Logged in"
        );

        // Redirect based on role
        if ($user['Role'] === 'superadmin') {

            header("Location: superadmin/dashboard.php");

        } elseif ($user['Role'] === 'admin') {

            header("Location: admin/dashboard.php");

        } elseif ($user['Role'] === 'staff') {

            header("Location: staff/dashboard.php");

        } else {

            // Unknown role
            session_unset();
            session_destroy();

            die("Invalid user role.");

        }

        exit();

    } else {

        // Correct username, wrong password
        logActivity(
            $conn,
            $user['UserID'],
            $user['Username'],
            $user['Role'],
            "Failed login attempt"
        );

        echo "Incorrect Password!";
    }

} else {

    // Username does not exist OR account is deactivated
    logActivity(
        $conn,
        NULL,
        $username,
        "unknown",
        "Failed login attempt"
    );

    echo "Invalid username or password.";
}

?>