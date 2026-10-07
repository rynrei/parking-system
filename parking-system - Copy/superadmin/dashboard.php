<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'superadmin') {
    header("Location: ../access_denied.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Superadmin Dashboard</title>
    </head>
<body>
    <h1>Superadmin Dashboard</h1>
    <p>
            Welcome, 
            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        </p>

        <p>
            Role:
            <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>
        </p>
        <hr>
    
    <h2>Superadmin Functions</h2>

            <a href="create_admin.php">Manage Admins</a>

            <a href="manage_users.php">Manage Users</a>

            <a href="create_staff.php">Manage Staff</a>
            
            <a href="activity_logs.php">Activity Logs</a>

            <a href="../parking_management.php">Parking Management</a>

            <a href="../logout.php">Logout</a>


</body>
</html>