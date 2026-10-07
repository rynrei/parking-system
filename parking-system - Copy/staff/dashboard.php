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

if ($_SESSION['role'] != 'staff') {
    die("ACCESS DENIED");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Dashboard</title>

    <link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

        <h1>Staff Dashboard</h1>

        <p>
            Welcome,
            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        </p>

        <p>
            Role:
            <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>
        </p>

        <hr>

        <h2>Staff Functions</h2>


            <a href="../parking_management.php">Parking Management</a>

            <a href="../logout.php">Logout</a>


</body>

</html>