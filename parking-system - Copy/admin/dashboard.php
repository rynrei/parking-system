<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require '../config/database.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Only Admins can access this page
if ($_SESSION['role'] != 'admin') {
    die("ACCESS DENIED");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

        <h1>Admin Dashboard</h1>

        <p>
            Welcome,
            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        </p>

        <p>
            Role:
            <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>
        </p>

        <hr>

        <h2>Admin Functions</h2>
    
            <a href="create_staff.php">Add Staff</a>

            <a href="manage_staff.php">Manage Staffs</a>

            <a href="../activity_logs.php">Activity Logs</a>

            <a href="../parking_management.php">Parking Management</a>

            <a href="../logout.php">Logout</a>

</body>

</html>