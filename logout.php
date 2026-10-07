<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['id'])) {
    header("Location: /pms/login.php");
    exit();
}

require 'config/database.php';
require 'config/activity_log.php';

date_default_timezone_set('Asia/Manila');

if (isset($_SESSION['id'])) {

    $userID = $_SESSION['id'];
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];


    // ========================================
    // AUTOMATIC TIME OUT FOR STAFF
    // ========================================

    if ($role === 'staff') {

        $timeOut = date('H:i:s');


        // ========================================
        // GET STAFF SHIFT
        // ========================================

        $shiftQuery = $conn->prepare("
            SELECT shift
            FROM users
            WHERE UserID = ?
        ");

        $shiftQuery->bind_param("i", $userID);
        $shiftQuery->execute();

        $shiftResult = $shiftQuery->get_result();


        if ($shiftResult->num_rows > 0) {

            $userData = $shiftResult->fetch_assoc();
            $shift = $userData['shift'];

            $shiftQuery->close();


            // ========================================
            // GET OPEN ATTENDANCE
            // ========================================

            $attendanceQuery = $conn->prepare("
                SELECT
                    AttendanceID,
                    AttendanceDate,
                    TimeIn
                FROM attendance
                WHERE UserID = ?
                AND TimeOut IS NULL
                ORDER BY AttendanceID DESC
                LIMIT 1
            ");

            $attendanceQuery->bind_param("i", $userID);
            $attendanceQuery->execute();

            $attendanceResult = $attendanceQuery->get_result();


            if ($attendanceResult->num_rows > 0) {

                $attendance = $attendanceResult->fetch_assoc();

                $attendanceID = $attendance['AttendanceID'];
                $attendanceDate = $attendance['AttendanceDate'];
                $timeIn = $attendance['TimeIn'];

                $attendanceQuery->close();


                // ========================================
                // CREATE FULL TIMESTAMPS
                // ========================================

                $timeInTimestamp = strtotime(
                    $attendanceDate . ' ' . $timeIn
                );

                $timeOutTimestamp = strtotime(
                    date('Y-m-d') . ' ' . $timeOut
                );


                // ========================================
                // DETERMINE SHIFT END
                // ========================================

                if ($shift === 'Morning') {

                    // 6:00 AM → 6:00 PM

                    $shiftEndTimestamp = strtotime(
                        $attendanceDate . ' 18:00:00'
                    );

                } elseif ($shift === 'Night') {

                    // 6:00 PM → 6:00 AM NEXT DAY

                    $shiftEndTimestamp = strtotime(
                        $attendanceDate . ' 18:00:00 +12 hours'
                    );

                } else {

                    $shiftEndTimestamp = null;
                }


                if ($shiftEndTimestamp !== null) {

                    // ========================================
                    // TOTAL WORKED TIME
                    // ========================================

                    $workedMinutes = (
                        $timeOutTimestamp - $timeInTimestamp
                    ) / 60;


                    // ========================================
                    // DETERMINE STATUS
                    // ========================================

                    if ($workedMinutes < 60) {

                        $newStatus = 'Absent';

                    } elseif ($timeOutTimestamp < $shiftEndTimestamp) {

                        $newStatus = 'Cut Off';

                    } elseif (
                        $timeOutTimestamp <=
                        ($shiftEndTimestamp + (30 * 60))
                    ) {

                        $newStatus = 'Present';

                    } else {

                        $newStatus = 'OT';
                    }


                    // ========================================
                    // UPDATE ATTENDANCE
                    // ========================================

                    $updateAttendance = $conn->prepare("
                        UPDATE attendance
                        SET
                            TimeOut = ?,
                            Status = ?
                        WHERE AttendanceID = ?
                    ");

                    $updateAttendance->bind_param(
                        "ssi",
                        $timeOut,
                        $newStatus,
                        $attendanceID
                    );

                    $updateAttendance->execute();
                    $updateAttendance->close();

                }

            } else {

                $attendanceQuery->close();
            }

        } else {

            $shiftQuery->close();
        }


        // ========================================
        // SET STAFF OFFLINE
        // ========================================

        $offlineQuery = $conn->prepare("
            UPDATE users
            SET LastActivity = NULL
            WHERE UserID = ?
        ");

        $offlineQuery->bind_param(
            "i",
            $userID
        );

        $offlineQuery->execute();
        $offlineQuery->close();

    }


    // ========================================
    // ACTIVITY LOG
    // ========================================

    logActivity(
        $conn,
        $userID,
        $username,
        $role,
        "Logged out"
    );
}


// Remove Remember Me cookie
if (isset($_COOKIE['remember_token'])) {

    setcookie(
        "remember_token",
        "",
        time() - 3600,
        "/"
    );
}

// ========================================
// DESTROY SESSION
// ========================================

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]
    );
}

session_destroy();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Location: /pms/login.php");
exit();

$conn->close();

header("Location: login.php");
exit();

?>