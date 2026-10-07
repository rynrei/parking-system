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

// Only Admin can access this page
if ($_SESSION['role'] !== 'admin') {
    die("ACCESS DENIED");
}

// Get Staff accounts only
$sql = "SELECT UserID, Username, Role, Active
        FROM users
        WHERE Role = 'staff'
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

    <title>Manage Staff</title>

    <link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

<div class="dashboard">

    <h1>Manage Staff</h1>

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

                    <td>
                        <?php echo $user['UserID']; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($user['Username']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($user['Role']); ?>
                    </td>

                    <td>
                        <?php echo ($user['Active'] == 1)
                            ? 'Active'
                            : 'Deactivated'; ?>
                    </td>

                    <td>

                        <?php if ($user['Active'] == 1): ?>

                            <a href="edit_staff.php?id=<?php echo $user['UserID']; ?>">
                                Edit
                            </a>

                            |

                            <a
                                href="deactivate_staff.php?id=<?php echo $user['UserID']; ?>"
                                onclick="return confirm('Are you sure you want to deactivate this staff account?');"
                            >
                                Deactivate
                            </a>

                        <?php else: ?>

                            Deactivated

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="5">No staff accounts found.</td>
            </tr>

        <?php endif; ?>

    </table>

    <br>

    <a href="dashboard.php">← Back to Dashboard</a>

</div>

</body>

</html>