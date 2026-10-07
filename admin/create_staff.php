<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'admin') {
    die("ACCESS DENIED");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Staff</title>

</head>

<body>

<div class="dashboard">

    <h1>Create Staff Account</h1>

    <p>Admin can create Staff accounts.</p>

    <hr>

    <form action="create_staff_process.php" method="POST">

        <label>Username</label><br>
        <input type="text" name="username" required>

        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>

        <br><br>

        <label>Shift</label><br>
            <select name="shift" required>
                <option value="">Select Shift</option>
                <option value="Morning">Morning</option>
                <option value="Night">Night</option>
            </select>

            <br><br>

        <button type="submit">Create Staff</button>

    </form>

    <br>

    <a href="dashboard.php">← Back to Dashboard</a>

</div>

</body>
</html>