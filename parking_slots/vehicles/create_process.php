<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";
require_once "../config/activity_log.php";


// ========================================
// GET FORM DATA
// ========================================

$plate_number = strtoupper(trim($_POST['plate_number'] ?? ''));
$brand        = trim($_POST['brand'] ?? '');
$model        = trim($_POST['model'] ?? '');
$wheels       = trim($_POST['number_of_wheels'] ?? '');
$color        = trim($_POST['color'] ?? '');
$floor        = trim($_POST['floor'] ?? '');
$slot_id      = trim($_POST['slot_id'] ?? '');


// ========================================
// BASIC VALIDATION
// ========================================

if (
    $plate_number === '' ||
    $brand === '' ||
    $model === '' ||
    $wheels === '' ||
    $color === '' ||
    $floor === '' ||
    $slot_id === ''
) {
    die("All vehicle and parking fields are required.");
}


// ========================================
// VALIDATE WHEELS
// ========================================

if (!filter_var($wheels, FILTER_VALIDATE_INT)) {
    die("Number of wheels must be a whole number.");
}

$wheels = (int)$wheels;

if ($wheels < 2 || $wheels > 18) {
    die("Number of wheels must be between 2 and 18.");
}


// ========================================
// VALIDATE FLOOR
// ========================================

if (!filter_var($floor, FILTER_VALIDATE_INT) || (int)$floor < 1) {
    die("Invalid floor.");
}

$floor = (int)$floor;


// ========================================
// VALIDATE SLOT ID
// ========================================

if (!filter_var($slot_id, FILTER_VALIDATE_INT) || (int)$slot_id <= 0) {
    die("Invalid parking slot.");
}

$slot_id = (int)$slot_id;


// ========================================
// START TRANSACTION
// ========================================

$conn->begin_transaction();

try {

    // ========================================
    // CHECK DUPLICATE PLATE
    // ========================================

    $stmt = $conn->prepare("
        SELECT VehicleID
        FROM vehicles
        WHERE PlateNumber = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $plate_number);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        throw new Exception(
            "Plate number " .
            htmlspecialchars($plate_number) .
            " is already registered."
        );
    }

    $stmt->close();


    // ========================================
    // VALIDATE BRAND
    // ========================================

    $stmt = $conn->prepare("
        SELECT BrandID, BrandName
        FROM vehicle_brands
        WHERE BrandName = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $brand);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Invalid vehicle brand.");
    }

    $brand_data = $result->fetch_assoc();

    $brand_id   = (int)$brand_data['BrandID'];
    $brand_name = $brand_data['BrandName'];

    $stmt->close();


    // ========================================
    // VALIDATE MODEL FOR SELECTED BRAND
    // ========================================

    $stmt = $conn->prepare("
        SELECT ModelName
        FROM vehicle_models
        WHERE ModelName = ?
          AND BrandID = ?
        LIMIT 1
    ");

    $stmt->bind_param("si", $model, $brand_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception(
            "Invalid model for the selected brand."
        );
    }

    $model_data = $result->fetch_assoc();

    $model_name = $model_data['ModelName'];

    $stmt->close();


    // ========================================
    // CHECK SELECTED SLOT
    // ========================================

    $stmt = $conn->prepare("
        SELECT SlotID, SlotNumber
        FROM parkingslots
        WHERE SlotID = ?
          AND Floor = ?
          AND Status = 'Available'
        FOR UPDATE
    ");

    $stmt->bind_param("ii", $slot_id, $floor);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception(
            "The selected parking slot is no longer available."
        );
    }

    $slot_data = $result->fetch_assoc();

    $slot_number = $slot_data['SlotNumber'];

    $stmt->close();


    // ========================================
    // INSERT VEHICLE
    // ========================================

    $stmt = $conn->prepare("
        INSERT INTO vehicles
        (
            PlateNumber,
            NumberOfWheels,
            Brand,
            Model,
            Color
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sisss",
        $plate_number,
        $wheels,
        $brand_name,
        $model_name,
        $color
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "Failed to register vehicle: " .
            $stmt->error
        );
    }

    $vehicle_id = $stmt->insert_id;

    $stmt->close();


    // ========================================
    // CREATE PARKING LOG
    // ========================================

    $user_id = $_SESSION['id'];
    $parking_status = "Active";

    $stmt = $conn->prepare("
        INSERT INTO parkinglogs
        (
            VehicleID,
            SlotID,
            UserID,
            TimeIn,
            TimeOut,
            DurationMinutes,
            Status
        )
        VALUES (?, ?, ?, NOW(), NULL, NULL, ?)
    ");

    $stmt->bind_param(
        "iiis",
        $vehicle_id,
        $slot_id,
        $user_id,
        $parking_status
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "Failed to create parking log: " .
            $stmt->error
        );
    }

    $stmt->close();


    // ========================================
    // MARK SLOT AS UNAVAILABLE
    // ========================================

    $stmt = $conn->prepare("
        UPDATE parkingslots
        SET Status = 'Unavailable'
        WHERE SlotID = ?
          AND Status = 'Available'
    ");

    $stmt->bind_param("i", $slot_id);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception(
            "Failed to update parking slot status."
        );
    }

    $stmt->close();


    // ========================================
    // ACTIVITY LOG
    // ========================================

    $action =
        "Registered vehicle " .
        $plate_number .
        " and assigned parking slot " .
        $slot_number .
        " on Floor " .
        $floor;

    logActivity(
        $conn,
        $_SESSION['id'],
        $_SESSION['username'],
        $_SESSION['role'],
        $action
    );


    // ========================================
    // COMPLETE TRANSACTION
    // ========================================

    $conn->commit();


    // ========================================
    // SUCCESS
    // ========================================

    header("Location: ../parking_slots/index.php");
    exit();

} catch (Exception $e) {

    $conn->rollback();

    die(
        htmlspecialchars($e->getMessage())
    );
}

?>