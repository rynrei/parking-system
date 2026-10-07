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

// Only Admin can edit Staff
if ($_SESSION['role'] !== 'admin') {
    die("ACCESS DENIED");
}

// Check if UserID was provided
if (!isset($_GET['id'])) {
    die("Staff ID not provided.");
}

$staff_id = intval($_GET['id']);

// Get Staff only
$sql = "SELECT UserID, Username, Role, Active
        FROM users
        WHERE UserID = ? AND Role = 'staff'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Staff account not found.");
}

$staff = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Staff</title>

    <link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

<div class="dashboard">

    <h1>Edit Staff</h1>

    <hr>

    <form action="edit_staff_process.php" method="POST">

        <input
            type="hidden"
            name="id"
            value="<?php echo $staff['UserID']; ?>"
        >

        <label>Username</label>
        <br>

        <input
            type="text"
            name="username"
            value="<?php echo htmlspecialchars($staff['Username']); ?>"
            required
        >

        <br><br>

        <label>Role</label>
        <br>

        <input
            type="text"
            value="staff"
            disabled
        >

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
            Update Staff
        </button>

    </form>

    <br>

    <a href="manage_staff.php">
        ← Back to Manage Staff
    </a>

</div>

</body>

</html>