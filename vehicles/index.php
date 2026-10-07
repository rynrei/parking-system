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

// Get all vehicles
$sql = "SELECT VehicleID, PlateNumber, NumberOfWheels, Brand, Model, Color
        FROM vehicles
        ORDER BY VehicleID DESC";

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

    <title>Vehicle Management</title>

    <link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>

<div class="dashboard">

    <h1>Vehicle Management</h1>

    <p>
        Welcome,
        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
    </p>

    <hr>

    <a href="create.php">
        Register New Vehicle
    </a>

    <br><br>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">

        <tr>
            <th>ID</th>
            <th>Plate Number</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Wheels</th>
            <th>Color</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($vehicle = $result->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo $vehicle['VehicleID']; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($vehicle['PlateNumber']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($vehicle['Brand']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($vehicle['Model']); ?>
                    </td>

                    <td>
                        <?php echo $vehicle['NumberOfWheels']; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($vehicle['Color']); ?>
                    </td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="6">
                    No vehicles registered.
                </td>
            </tr>

        <?php endif; ?> 

    </table>

    <br>

    <a href="../superadmin/dashboard.php">
        ← Back
    </a>

</div>

</body>

</html>