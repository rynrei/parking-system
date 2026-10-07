<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require '../config/database.php';

// Only Superadmin can view activity logs
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    die("ACCESS DENIED");
}

// Get activity logs from the NEW database
$sql = "SELECT ActivityLogID, UserID, Username, Role, Action, CreatedAt
        FROM activitylogs
        ORDER BY ActivityLogID DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activity Logs</title>

    <link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

<div class="dashboard">

    <h1>Activity Logs</h1>

    <p>
        Welcome,
        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
    </p>

    <hr>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">

        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Action</th>
            <th>Date & Time</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($log = $result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo $log['ActivityLogID']; ?>
                    </td>

                    <td>
                        <?php
                        echo $log['UserID'] !== null
                            ? $log['UserID']
                            : '-';
                        ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($log['Username']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($log['Role']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($log['Action']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($log['CreatedAt']); ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="6">No activity logs found.</td>
            </tr>

        <?php endif; ?>

    </table>

    <br>

    <a href="dashboard.php">
        ← Back to Dashboard
    </a>

</div>

</body>

</html>