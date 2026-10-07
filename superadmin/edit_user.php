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

// Only Superadmin can edit users
if ($_SESSION['role'] !== 'superadmin') {
    die("ACCESS DENIED");
}

// Check if UserID was provided
if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = intval($_GET['id']);

// Get user from NEW database structure
$sql = "SELECT UserID, Username, Role, Active
        FROM users
        WHERE UserID = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit User</title>

</head>

<body>

<div class="dashboard">

    <h1>Edit User</h1>

    <hr>

    <form action="edit_user_process.php" method="POST">

        <input
            type="hidden"
            name="id"
            value="<?php echo $user['UserID']; ?>"
        >

        <label>Username</label>
        <br>

        <input
            type="text"
            name="username"
            value="<?php echo htmlspecialchars($user['Username']); ?>"
            required
        >

        <br><br>

        <label>Role</label>
        <br>

        <?php if ($user['Role'] === 'superadmin') { ?>

            <input
                type="text"
                value="superadmin"
                disabled
            >

            <input
                type="hidden"
                name="role"
                value="superadmin"
            >

        <?php } else { ?>

            <select name="role">

                <option
                    value="admin"
                    <?php echo ($user['Role'] === 'admin') ? 'selected' : ''; ?>
                >
                    Admin
                </option>

                <option
                    value="staff"
                    <?php echo ($user['Role'] === 'staff') ? 'selected' : ''; ?>
                >
                    Staff
                </option>

            </select>

        <?php } ?>

        <br><br>

        <label>New Password</label>
        <br>

        <input
            type="password"
            name="password"
            placeholder="Leave blank to keep current password"
        >

        <br><br>

        <button type="submit">
            Update User
        </button>

    </form>

    <br>

    <a href="manage_users.php">
        ← Back to Manage Users
    </a>

</div>

</body>

</html>