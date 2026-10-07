<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";


// ========================================
// GET ALL PARKING SLOTS
// ========================================

$sql = "
    SELECT
        ps.SlotID,
        ps.Floor,
        ps.SlotNumber,
        ps.VehicleType,
        ps.Status,

        v.PlateNumber,
        v.Brand,
        v.Model,
        v.NumberOfWheels,
        v.Color

    FROM parkingslots ps

    LEFT JOIN parkinglogs pl
        ON pl.SlotID = ps.SlotID
        AND pl.TimeOut IS NULL

    LEFT JOIN vehicles v
        ON v.VehicleID = pl.VehicleID

    ORDER BY
        ps.Floor ASC,
        ps.SlotNumber ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Failed to load parking slots: " . htmlspecialchars($conn->error));
}


// ========================================
// GROUP SLOTS BY FLOOR
// ========================================

$slots_by_floor = [];

while ($row = $result->fetch_assoc()) {

    $floor = $row['Floor'];

    if (!isset($slots_by_floor[$floor])) {
        $slots_by_floor[$floor] = [];
    }

    $slots_by_floor[$floor][] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Parking Slot Management</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f4f4f4;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        h1 {
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-box {
            width: 18px;
            height: 18px;
            border-radius: 4px;
        }

        .green {
            background: #28a745;
        }

        .red {
            background: #dc3545;
        }

        .floor-section {
            margin-bottom: 40px;
        }

        .floor-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 18px;
        }

        .slot {
            color: white;
            border-radius: 10px;
            padding: 20px;
            min-height: 180px;

            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        }

        .slot.available {
            background: #28a745;
        }

        .slot.unavailable {
            background: #dc3545;
        }

        .slot-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .slot-status {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .vehicle-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px;
            border-radius: 7px;
            font-size: 13px;
            line-height: 1.6;
        }

        .vehicle-info strong {
            font-weight: bold;
        }

        .vehicle-type {
            margin-top: 8px;
            font-size: 12px;
            opacity: 0.9;
        }

        .no-slots {
            background: white;
            padding: 25px;
            border-radius: 8px;
            color: #666;
        }
        .register-btn {
            display: inline-block;
            background-color: #2196F3;
            color: white !important;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: bold;
            font-size: 16px;
            white-space: nowrap;
            margin-left: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            width: 100%;
        }
        .register-btn:hover {
            background-color: #1976D2;
        }
    </style>

</head>

<body>

<div class="page-header">

    <h1>Parking Slot Management</h1>

    <p class="subtitle">
        View the current status of all parking slots.
    </p>

    <a href="../vehicles/create.php" class="register-btn">
    Register Vehicle
    </a>

    

,</div>
    <div class="legend">

        <div class="legend-item">
            <div class="legend-box green"></div>
            <span>Available</span>
        </div>

        <div class="legend-item">
            <div class="legend-box red"></div>
            <span>Occupied</span>
        </div>

    </div>


    <?php if (empty($slots_by_floor)): ?>

        <div class="no-slots">
            No parking slots found.
        </div>

    <?php else: ?>


        <?php foreach ($slots_by_floor as $floor => $slots): ?>

            <div class="floor-section">

                <div class="floor-title">
                    Floor <?= htmlspecialchars($floor) ?>
                </div>

                <div class="slot-grid">

                    <?php foreach ($slots as $slot): ?>

                        <?php

                        $status = strtolower(trim($slot['Status']));

                        if ($status === 'available') {
                            $slot_class = 'available';
                        } else {
                            $slot_class = 'unavailable';
                        }

                        ?>

                        <div class="slot <?= $slot_class ?>">

                            <div class="slot-number">
                                <?= htmlspecialchars($slot['SlotNumber']) ?>
                            </div>

                            <div class="slot-status">
                                <?php if (strtolower(trim($slot['Status'])) === 'available'): ?>
                                    Available
                                <?php else: ?>
                                    Occupied
                                <?php endif; ?>
                            </div>


                            <?php if ($slot['PlateNumber'] !== null): ?>

                                <div class="vehicle-info">

                                    <div>
                                        <strong>Plate:</strong>
                                        <?= htmlspecialchars($slot['PlateNumber']) ?>
                                    </div>

                                    <div>
                                        <strong>Brand:</strong>
                                        <?= htmlspecialchars($slot['Brand']) ?>
                                    </div>

                                    <div>
                                        <strong>Model:</strong>
                                        <?= htmlspecialchars($slot['Model']) ?>
                                    </div>

                                    <div>
                                        <strong>Wheels:</strong>
                                        <?= htmlspecialchars($slot['NumberOfWheels']) ?>
                                    </div>

                                    <div>
                                        <strong>Color:</strong>
                                        <?= htmlspecialchars($slot['Color']) ?>
                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endforeach; ?>


    <?php endif; ?>

</div>

</body>
</html>