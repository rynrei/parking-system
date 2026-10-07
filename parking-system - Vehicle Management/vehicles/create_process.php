<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";


// =========================
// GET FORM DATA
// =========================

$plate_number = strtoupper(trim($_POST['plate_number'] ?? ''));
$brand        = trim($_POST['brand'] ?? '');
$model        = trim($_POST['model'] ?? '');
$wheels       = trim($_POST['number_of_wheels'] ?? '');
$color        = trim($_POST['color'] ?? '');


// =========================
// BASIC VALIDATION
// =========================

if (
    $plate_number === '' ||
    $brand === '' ||
    $model === '' ||
    $wheels === '' ||
    $color === ''
) {
    die("All vehicle fields are required.");
}


// =========================
// VALIDATE NUMBER OF WHEELS
// =========================

if (!filter_var($wheels, FILTER_VALIDATE_INT)) {
    die("Number of wheels must be a whole number.");
}

$wheels = (int)$wheels;

if ($wheels < 2 || $wheels > 18) {
    die("Number of wheels must be between 2 and 18.");
}


// =========================
// CHECK DUPLICATE PLATE
// =========================

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
    die("Plate number " . htmlspecialchars($plate_number) . " is already registered.");
}

$stmt->close();


// =========================
// VALIDATE BRAND
// =========================

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
    die("Invalid vehicle brand.");
}

$brand_data = $result->fetch_assoc();

$brand_id = $brand_data['BrandID'];
$brand_name = $brand_data['BrandName'];

$stmt->close();


// =========================
// VALIDATE MODEL
// =========================

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
    die("Invalid model for the selected brand.");
}

$model_data = $result->fetch_assoc();

$model_name = $model_data['ModelName'];

$stmt->close();


// =========================
// INSERT VEHICLE
// =========================

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
    die("Failed to register vehicle: " . htmlspecialchars($stmt->error));
}

$vehicle_id = $stmt->insert_id;

$stmt->close();


// =========================
// ACTIVITY LOG
// =========================

$user_id  = $_SESSION['id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'];

$action = "Registered vehicle: " . $plate_number;

$log_stmt = $conn->prepare("
    INSERT INTO activitylogs
    (
        UserID,
        Username,
        Role,
        Action,
        CreatedAt
    )
    VALUES (?, ?, ?, ?, NOW())
");

$log_stmt->bind_param(
    "isss",
    $user_id,
    $username,
    $role,
    $action
);

$log_stmt->execute();
$log_stmt->close();


// =========================
// SUCCESS
// =========================

header("Location: index.php");
exit();

?>