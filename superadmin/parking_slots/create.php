<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parking Slot</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f4f4f4;
            padding: 40px;
        }

        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #333;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #555;
        }

        .back {
            display: block;
            margin-top: 15px;
            text-align: center;
            text-decoration: none;
            color: #333;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Add Parking Slot</h2>

    <form action="create_process.php" method="POST">

        <div class="form-group">
            <label for="floor">Floor</label>
            <input
                type="number"
                id="floor"
                name="floor"
                min="1"
                required
            >
        </div>

        <div class="form-group">
            <label for="slot_number">Slot Number</label>
            <input
                type="text"
                id="slot_number"
                name="slot_number"
                maxlength="10"
                placeholder="Example: A01"
                required
            >
        </div>

        <div class="form-group">
            <label for="vehicle_type">Vehicle Type</label>
            <select id="vehicle_type" name="vehicle_type" required>
                <option value="">Select vehicle type</option>
                <option value="2-wheel">2-wheel</option>
                <option value="4-wheel">4-wheel</option>
            </select>
        </div>

        <button type="submit">Add Slot</button>

    </form>

    <a href="index.php" class="back">← Back to Parking Slots</a>

</div>

</body>
</html>