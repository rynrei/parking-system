<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'staff') {
    die("ACCESS DENIED");
}

date_default_timezone_set('Asia/Manila');

require_once "../config/database.php";

$userID = $_SESSION['id'];
$today = date('Y-m-d');
$timeOut = date('H:i:s');

// Find today's attendance record
$check = $conn->prepare("
    SELECT AttendanceID, TimeOut
    FROM attendance
    WHERE UserID = ?
    AND AttendanceDate = ?
");

$check->bind_param("is", $userID, $today);
$check->execute();

$result = $check->get_result();

if ($result->num_rows == 0) {

    $check->close();
    $conn->close();

    echo "<script>
        alert('You have not punched in today.');
        window.location.href = 'dashboard.php';
    </script>";

    exit();
}

$row = $result->fetch_assoc();
$check->close();

// Prevent punching out twice
if ($row['TimeOut'] !== NULL) {

    $conn->close();

    echo "<script>
        alert('You have already punched out today.');
        window.location.href = 'dashboard.php';
    </script>";

    exit();
}

// Save Time Out
$stmt = $conn->prepare("
    UPDATE attendance
    SET TimeOut = ?
    WHERE AttendanceID = ?
");

$stmt->bind_param("si", $timeOut, $row['AttendanceID']);
$stmt->execute();

$stmt->close();
$conn->close();

echo "<script>
    alert('Time Out recorded successfully!');
    window.location.href = 'dashboard.php';
</script>";