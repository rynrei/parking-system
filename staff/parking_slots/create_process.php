<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";
require_once "../config/activity_log.php";


// =========================
// GET FORM DATA
// =========================

$floor        = trim($_POST['floor'] ?? '');
$slot_number  = strtoupper(trim($_POST['slot_number'] ?? ''));
$vehicle_type = trim($_POST['vehicle_type'] ?? '');


// =========================
// BASIC VALIDATION
// =========================

if ($floor === '' || $slot_number === '' || $vehicle_type === '') {
    die("All parking slot fields are required.");
}


// Floor must be a positive integer
if (!filter_var($floor, FILTER_VALIDATE_INT) || (int)$floor < 1) {
    die("Floor must be a valid positive number.");
}

$floor = (int)$floor;


// =========================
// VALIDATE VEHICLE TYPE
// =========================

$allowed_types = ['2-wheel', '4-wheel'];

if (!in_array($vehicle_type, $allowed_types, true)) {
    die("Invalid vehicle type.");
}


// =========================
// CHECK DUPLICATE SLOT
// =========================

$stmt = $conn->prepare("
    SELECT SlotID
    FROM parkingslots
    WHERE Floor = ?
      AND SlotNumber = ?
    LIMIT 1
");

$stmt->bind_param("is", $floor, $slot_number);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    die(
        "Slot " .
        htmlspecialchars($slot_number) .
        " already exists on Floor " .
        htmlspecialchars($floor) .
        "."
    );
}

$stmt->close();


// =========================
// INSERT SLOT
// =========================

$status = "Available";

$stmt = $conn->prepare("
    INSERT INTO parkingslots
    (
        Floor,
        SlotNumber,
        VehicleType,
        Status
    )
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    "isss",
    $floor,
    $slot_number,
    $vehicle_type,
    $status
);

if (!$stmt->execute()) {
    die(
        "Failed to create parking slot: " .
        htmlspecialchars($stmt->error)
    );
}

$slot_id = $stmt->insert_id;

$stmt->close();


// =========================
// ACTIVITY LOG
// =========================

$action = "Created parking slot: Floor " .
          $floor .
          " - " .
          $slot_number;

logActivity(
    $conn,
    $_SESSION['id'],
    $_SESSION['username'],
    $_SESSION['role'],
    $action
);


// =========================
// SUCCESS
// =========================

header("Location: index.php");
exit();

?>