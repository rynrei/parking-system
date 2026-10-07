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
$timeIn = date('H:i:s');


// ========================================
// GET STAFF SHIFT
// ========================================

$userQuery = $conn->prepare("
    SELECT shift
    FROM users
    WHERE UserID = ?
");

$userQuery->bind_param("i", $userID);
$userQuery->execute();

$userResult = $userQuery->get_result();

if ($userResult->num_rows === 0) {

    $userQuery->close();
    $conn->close();

    die("User account not found.");
}

$userData = $userResult->fetch_assoc();
$shift = $userData['shift'];

$userQuery->close();


// ========================================
// CHECK IF ALREADY PUNCHED IN
// ========================================

$check = $conn->prepare("
    SELECT AttendanceID
    FROM attendance
    WHERE UserID = ?
    AND AttendanceDate = ?
");

$check->bind_param("is", $userID, $today);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {

    $check->close();
    $conn->close();

    echo "<script>
        alert('You have already punched in today.');
        window.location.href = 'dashboard.php';
    </script>";

    exit();
}

$check->close();


// ========================================
// DETERMINE EXPECTED TIME
// ========================================

if ($shift === 'Morning') {

    $expectedTime = '06:00:00';

} elseif ($shift === 'Night') {

    $expectedTime = '18:00:00';

} else {

    $conn->close();

    die("Invalid shift assigned to this account.");
}


// ========================================
// CALCULATE MINUTES LATE
// ========================================

$currentTimestamp = strtotime($today . ' ' . $timeIn);
$expectedTimestamp = strtotime($today . ' ' . $expectedTime);

$minutesLate = ($currentTimestamp - $expectedTimestamp) / 60;


// ========================================
// DETERMINE PUNCH-IN STATUS
// ========================================

// 6:00 - 6:10 = Present
// 6:11 onwards = Late

if ($minutesLate <= 10) {

    $status = 'Present';

} else {

    $status = 'Late';

}


// ========================================
// CREATE ATTENDANCE RECORD
// ========================================

$stmt = $conn->prepare("
    INSERT INTO attendance
    (UserID, AttendanceDate, TimeIn, Status)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    "isss",
    $userID,
    $today,
    $timeIn,
    $status
);

$stmt->execute();

$stmt->close();
$conn->close();


// ========================================
// SUCCESS MESSAGE
// ========================================

echo "<script>
    alert('Time In recorded successfully! Status: $status');
    window.location.href = 'dashboard.php';
</script>";

?>