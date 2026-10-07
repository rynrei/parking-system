<?php

date_default_timezone_set('Asia/Manila');

$host = "localhost";
$username = "root";
$password = "";
$database = "parking_management_system";

$checkTime = date('Y-m-d H:i:s');

$status = 'UP';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {

    $status = 'DOWN';

} else {

    $testQuery = $conn->query("SELECT 1");

    if (!$testQuery) {
        $status = 'DOWN';
    }
}


/*
|--------------------------------------------------------------------------
| SAVE MONITORING RESULT TO FILE
|--------------------------------------------------------------------------
*/

$logFile = __DIR__ . "/uptime.log";

$logEntry = $checkTime . " | " . $status . PHP_EOL;

file_put_contents(
    $logFile,
    $logEntry,
    FILE_APPEND | LOCK_EX
);


/*
|--------------------------------------------------------------------------
| SAVE UP RESULT TO DATABASE
|--------------------------------------------------------------------------
*/

if ($status === 'UP') {

    $stmt = $conn->prepare("
        INSERT INTO system_uptime (CheckTime, Status)
        VALUES (?, ?)
    ");

    $stmt->bind_param("ss", $checkTime, $status);
    $stmt->execute();
    $stmt->close();

    $conn->close();
}


/*
|--------------------------------------------------------------------------
| RETURN RESULT
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json');

echo json_encode([
    'status' => $status,
    'time' => $checkTime
]);

?>