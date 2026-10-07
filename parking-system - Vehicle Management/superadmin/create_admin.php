<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'superadmin') {
    die("ACCESS DENIED");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Admin</title>

</head>

<body>

    <h1>Create Admin Account</h1>

    <p>Only Superadmin can create Admin accounts.</p>

    <hr>

    <form action="create_admin_process.php" method="POST">

        <label>Username</label><br>
        <input type="text" name="username" required>

        <br><br>

        <label>Password</label><br>
        <input type="password" name="password" required>

        <br><br>

        <button type="submit">Create Admin</button>

    </form>

    <br>

    <a href="dashboard.php">← Back to Dashboard</a>



</body>
</html>