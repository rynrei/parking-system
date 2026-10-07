<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require '../config/database.php';

// Check if logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Only Superadmin can access this page
if ($_SESSION['role'] !== 'superadmin') {
    die("ACCESS DENIED");
}

// Get all users from the NEW database structure
$sql = "SELECT UserID, Username, Role, Active
        FROM users
        ORDER BY UserID ASC";

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

    <title>Manage Users</title>

    <link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

<div class="dashboard">

    <h1>Manage Users</h1>

    <p>
        Welcome,
        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
    </p>

    <hr>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">

        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($user = $result->fetch_assoc()): ?>

                <tr>

                    <!-- User ID -->
                    <td>
                        <?php echo $user['UserID']; ?>
                    </td>

                    <!-- Username -->
                    <td>
                        <?php echo htmlspecialchars($user['Username']); ?>
                    </td>

                    <!-- Role -->
                    <td>
                        <?php echo htmlspecialchars($user['Role']); ?>
                    </td>

                    <!-- Status -->
                    <td>
                        <?php
                        echo ($user['Active'] == 1)
                            ? "Active"
                            : "Deactivated";
                        ?>
                    </td>

                    <!-- Actions -->
                    <td>

                        <?php if ($user['UserID'] != $_SESSION['id']): ?>

                            <a href="edit_user.php?id=<?php echo $user['UserID']; ?>">
                                Edit
                            </a>

                            |

                            <?php if ($user['Active'] == 1): ?>

                                <a
                                    href="deactivate_user.php?id=<?php echo $user['UserID']; ?>"
                                    onclick="return confirm('Are you sure you want to deactivate this account?');"
                                >
                                    Deactivate
                                </a>

                            <?php else: ?>

                                <a
                                    href="activate_user.php?id=<?php echo $user['UserID']; ?>"
                                    onclick="return confirm('Are you sure you want to activate this account?');"
                                >
                                    Activate
                                </a>

                            <?php endif; ?>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="5">No users found.</td>
            </tr>

        <?php endif; ?>

    </table>

    <br>

    <a href="dashboard.php">← Back to Dashboard</a>

</div>

</body>

</html>