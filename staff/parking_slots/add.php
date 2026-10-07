<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $floor = intval($_POST['floor']);
    $start_slot = intval($_POST['start_slot']);
    $number_of_slots = intval($_POST['number_of_slots']);

    if ($floor < 1 || $floor > 3) {
        $message = "Floor must be between 1 and 3.";
        $message_type = "error";
    } elseif ($start_slot < 1) {
        $message = "Starting slot must be 1 or higher.";
        $message_type = "error";
    } elseif ($number_of_slots < 1) {
        $message = "Number of slots must be at least 1.";
        $message_type = "error";
    } else {

        $success_count = 0;
        $duplicate_count = 0;

        for ($i = 0; $i < $number_of_slots; $i++) {

            $slot_number = $start_slot + $i;

            // Example: A01, A02, A03...
            $slot_name = "A" . str_pad($slot_number, 2, "0", STR_PAD_LEFT);

            // Check if slot already exists
            $check = $conn->prepare(
                "SELECT SlotID FROM parkingslots
                 WHERE Floor = ? AND SlotNumber = ?"
            );

            $check->bind_param("is", $floor, $slot_name);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $duplicate_count++;
                $check->close();
                continue;
            }

            $check->close();

            // Insert new slot
            $stmt = $conn->prepare(
                "INSERT INTO parkingslots
                (Floor, SlotNumber, VehicleType, Status)
                VALUES (?, ?, 'general', 'available')"
            );

            $stmt->bind_param("is", $floor, $slot_name);

            if ($stmt->execute()) {
                $success_count++;
            }

            $stmt->close();
        }

        if ($success_count > 0) {
            $message = $success_count . " slot(s) added successfully.";

            if ($duplicate_count > 0) {
                $message .= " " . $duplicate_count . " slot(s) already existed.";
            }

            $message_type = "success";
        } else {
            $message = "No new slots were added. The slots may already exist.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Parking Slots</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 40px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        select,
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #007bff;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Add Parking Slots</h1>

    <?php if ($message != ""): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label for="floor">Select Floor</label>

        <select name="floor" id="floor" required>
            <option value="">-- Select Floor --</option>
            <option value="1">Floor 1</option>
            <option value="2">Floor 2</option>
            <option value="3">Floor 3</option>
        </select>


        <label for="start_slot">Starting Slot Number</label>

        <input
            type="number"
            name="start_slot"
            id="start_slot"
            min="1"
            placeholder="Example: 1"
            required
        >


        <label for="number_of_slots">Number of Slots</label>

        <input
            type="number"
            name="number_of_slots"
            id="number_of_slots"
            min="1"
            placeholder="Example: 10"
            required
        >


        <button type="submit">
            Add Parking Slots
        </button>

    </form>

    <a href="index.php" class="back">
        ← Back to Parking Slots
    </a>

</div>

</body>
</html>