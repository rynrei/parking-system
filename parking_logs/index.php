<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";

$sql = "
    SELECT 
        pl.LogID,
        pl.VehicleID,
        pl.SlotID,
        pl.UserID,
        pl.TimeIn,
        pl.TimeOut,
        pl.DurationMinutes,
        pl.Status,

        COALESCE(pl.PlateNumberSnapshot, v.PlateNumber) AS PlateNumber,
        COALESCE(pl.BrandSnapshot, v.Brand) AS Brand,
        COALESCE(pl.ModelSnapshot, v.Model) AS Model,
        COALESCE(pl.ColorSnapshot, v.Color) AS Color,
        v.NumberOfWheels,

        ps.Floor,
        ps.SlotNumber,

        u.Username AS RegisteredBy

    FROM parkinglogs pl

    LEFT JOIN vehicles v
        ON pl.VehicleID = v.VehicleID

    LEFT JOIN parkingslots ps
        ON pl.SlotID = ps.SlotID

    LEFT JOIN users u
        ON pl.UserID = u.UserID

    ORDER BY pl.LogID DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Database Error: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Parking Logs</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1400px;
            margin: auto;
        }

        h1 {
            margin-bottom: 20px;
        }

        .back-button {
            display: inline-block;
            padding: 10px 15px;
            background: #555;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #f1f1f1;
        }

        tr:hover {
            background: #f8f8f8;
        }

        .active {
            color: #198754;
            font-weight: bold;
        }

        .completed {
            color: #555;
            font-weight: bold;
        }

        .no-records {
            text-align: center;
            padding: 30px;
            color: #777;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Parking Logs</h1>

    <a href="../parking_slots/index.php" class="back-button">
        ← Back to Parking Slots
    </a>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Log ID</th>

                    <th>Plate Number</th>

                    <th>Vehicle</th>

                    <th>Color</th>

                    <th>Wheels</th>

                    <th>Floor</th>

                    <th>Slot</th>

                    <th>Registered By</th>

                    <th>Time In</th>

                    <th>Time Out</th>

                    <th>Duration</th>

                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $row['LogID']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['PlateNumber'] ?? 'N/A'); ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    trim(
                                        ($row['Brand'] ?? '') . " " . ($row['Model'] ?? '')
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['Color'] ?? 'N/A'); ?>
                            </td>

                            <td>
                                <?php echo $row['NumberOfWheels'] ?? 'N/A'; ?>
                            </td>

                            <td>
                                <?php echo $row['Floor'] ?? 'N/A'; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['SlotNumber'] ?? 'N/A'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['RegisteredBy'] ?? 'N/A'); ?>
                            </td>

                            <td>
                                <?php echo $row['TimeIn']; ?>
                            </td>

                            <td>
                                <?php
                                echo $row['TimeOut'] ?? '---';
                                ?>
                            </td>

                            <td>

                                <?php if ($row['DurationMinutes'] !== null): ?>

                                    <?php echo $row['DurationMinutes']; ?> minutes

                                <?php else: ?>

                                    ---

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($row['Status'] === 'Active'): ?>

                                    <span class="active">
                                        ACTIVE
                                    </span>

                                <?php else: ?>

                                    <span class="completed">
                                        <?php echo htmlspecialchars($row['Status']); ?>
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="12" class="no-records">
                            No parking logs found.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>

