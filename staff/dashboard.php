<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if (!in_array($_SESSION['role'] ?? '', ['staff', 'admin', 'superadmin'], true)) {
    header("Location: /pms/access_denied.php");
    exit();
}

// Database connection
require_once "../config/database.php";

// Get logged-in user's username
$userQuery = $conn->prepare("
    SELECT Username
    FROM users
    WHERE UserID = ?
");

$userQuery->bind_param("i", $_SESSION['id']);
$userQuery->execute();

$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();

$username = $userData['Username'];

$userQuery->close();

// Set timezone
date_default_timezone_set('Asia/Manila');

// Update staff's last activity
$stmt = $conn->prepare("
    UPDATE users
    SET LastActivity = NOW()
    WHERE UserID = ?
");

$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->close();

// Count staff currently online
$onlineQuery = $conn->query("
    SELECT COUNT(*) AS online_count
    FROM users
    WHERE Role = 'staff'
      AND Active = 1
      AND LastActivity >= NOW() - INTERVAL 2 MINUTE
");

$onlineStaff = $onlineQuery->fetch_assoc()['online_count'];

// Get today's attendance records
$today = date('Y-m-d');

$attendanceQuery = $conn->prepare("
    SELECT 
        a.AttendanceID,
        u.Username,
        a.AttendanceDate,
        a.TimeIn,
        a.TimeOut,
        a.Status
    FROM attendance a
    INNER JOIN users u ON a.UserID = u.UserID
    WHERE a.AttendanceDate = ?
    ORDER BY a.TimeIn DESC
");

$attendanceQuery->bind_param("s", $today);
$attendanceQuery->execute();

$attendanceResult = $attendanceQuery->get_result();

$attendanceCount = $attendanceResult->num_rows;

// Count staff currently clocked in
$activeQuery = $conn->query("
    SELECT COUNT(*) AS active_count
    FROM attendance
    WHERE AttendanceDate = '$today'
      AND TimeIn IS NOT NULL
      AND TimeOut IS NULL
");

$activeStaff = $activeQuery->fetch_assoc()['active_count'];

// Count staff who are late
$lateQuery = $conn->query("
    SELECT COUNT(*) AS late_count
    FROM attendance
    WHERE AttendanceDate = '$today'
      AND TimeIn > '08:00:00'
");

$lateStaff = $lateQuery->fetch_assoc()['late_count'];

// Check if current staff is still timed in
$activeAttendanceQuery = $conn->prepare("
    SELECT AttendanceID
    FROM attendance
    WHERE UserID = ?
      AND AttendanceDate = ?
      AND TimeIn IS NOT NULL
      AND TimeOut IS NULL
    LIMIT 1
");

$activeAttendanceQuery->bind_param(
    "is",
    $_SESSION['id'],
    $today
);

$activeAttendanceQuery->execute();

$activeAttendanceResult = $activeAttendanceQuery->get_result();

$isTimedIn = $activeAttendanceResult->num_rows > 0;

$activeAttendanceQuery->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Attendance Terminal</title>

    <script src="script.js" defer></script>

    <link rel="stylesheet" href="../css/staff_dashboard.css">

</head>


<body>

<div class="dashboard">


    <!-- ==================== SIDEBAR ==================== -->

    <aside class="sidebar">


        <!-- BRAND -->

        <div class="brand">

            <div class="brand-logo">
                PZ
            </div>

            <div class="brand-text">

                <span>
                    Staff Terminal
                </span>

                <strong>
                    ParkZen
                </strong>

            </div>

        </div>


        <!-- SIDEBAR MENU -->

        <div class="sidebar-menu">


            <!-- ATTENDANCE -->

            <a href="dashboard.php" class="menu-item active">

                <span class="menu-dot"></span>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- PARKING MANAGEMENT -->

            <a href="../parking_slots/index.php" class="menu-item">

                <span class="menu-dot"></span>

                <span>
                    Parking Management
                </span>

            </a>


        </div>


        <!-- LOGOUT -->

        <a
            href="../logout.php"
            class="menu-item logout-item"
            onclick="openLogoutModal(event);"
        >

            <span class="menu-dot logout-dot"></span>

            <span>
                Logout
            </span>

        </a>


    </aside>

    <!-- ==================== SIDEBAR END ==================== -->


    <!-- ==================== MAIN CONTENT ==================== -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="page-header">


            <div>

                <span class="page-label">
                    Attendance
                </span>

                <h1>
                    Staff Attendance Terminal
                </h1>

            </div>


            <div class="header-actions">


                <!-- THEME BUTTON -->

                <button
                    class="mode-button"
                    id="themeToggle"
                >

                    <span></span>

                    <span id="themeText">
                        Light Mode
                    </span>

                </button>


                <!-- PROFILE -->

                <div class="profile-wrapper">


                    <div class="profile-button">

                        <?php
                        echo strtoupper(
                            substr($username, 0, 1)
                        );
                        ?>

                    </div>


                    <div class="profile-card">


                        <div class="profile-avatar">

                            <?php
                            echo strtoupper(
                                substr($username, 0, 1)
                            );
                            ?>

                        </div>


                        <div class="profile-details">

                            <strong>

                                <?php
                                echo htmlspecialchars($username);
                                ?>

                            </strong>

                            <span>
                                Staff
                            </span>

                        </div>


                    </div>


                </div>


            </div>


        </header>


        <!-- ==================== STATISTICS ==================== -->

        <section class="stats">


            <!-- PRESENT -->

            <div class="stat-card">

                <span class="stat-label">
                    Present
                </span>

                <strong class="stat-number">
                    <?php echo $onlineStaff; ?>
                </strong>

                <small>
                    On-site staff
                </small>

            </div>


            <!-- ACTIVE -->

            <div class="stat-card">

                <span class="stat-label">
                    Active
                </span>

                <strong class="stat-number">
                    <?php echo $activeStaff; ?>
                </strong>

                <small>
                    Currently clocked in
                </small>

            </div>


            <!-- LATE -->

            <div class="stat-card">

                <span class="stat-label">
                    Late
                </span>

                <strong class="stat-number">
                    <?php echo $lateStaff; ?>
                </strong>

                <small>
                    Needs review
                </small>

            </div>


        </section>


        <!-- ==================== SERVER TIME ==================== -->

        <section class="server-time">


            <div class="time-info">

                <span>
                    Current Server Time
                </span>

                <strong id="serverTime">
                    <?php echo date('h:i:s A'); ?>
                </strong>

                <small id="serverDate">
                    <?php echo date('l, F j, Y'); ?>
                </small>

            </div>


            <div class="divider"></div>


            <div class="attendance-buttons">


                <!-- PUNCH IN -->

                <form
                    action="punch_in.php"
                    method="POST"
                >

                    <button
                        type="submit"
                        class="punch-in"
                    >

                        → &nbsp; Punch Time In

                    </button>

                </form>


                <!-- PUNCH OUT -->

                <form
                    action="punch_out.php"
                    method="POST"
                >

                    <button
                        type="submit"
                        class="punch-out"
                    >

                        → &nbsp; Punch Time Out

                    </button>

                </form>


            </div>


        </section>


        <!-- ==================== ATTENDANCE LOGS ==================== -->

        <section class="attendance-logs">


            <!-- LOG HEADER -->

            <div class="logs-header">

                <strong>
                    Today's Attendance Logs
                </strong>

                <span>
                    <?php echo $attendanceCount; ?> records
                </span>

            </div>


            <!-- TABLE HEADER -->

            <div class="table-header">

                <span>
                    Staff Name
                </span>

                <span>
                    Date
                </span>

                <span>
                    Time In
                </span>

                <span>
                    Time Out
                </span>

                <span>
                    Status
                </span>

            </div>


            <!-- ATTENDANCE ROWS -->

            <?php while ($row = $attendanceResult->fetch_assoc()): ?>

                <div class="attendance-row">


                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $row['Username']
                        );
                        ?>

                    </strong>


                    <span>

                        <?php
                        echo date(
                            'M d, Y',
                            strtotime(
                                $row['AttendanceDate']
                            )
                        );
                        ?>

                    </span>


                    <span>

                        <?php

                        echo $row['TimeIn']
                            ? date(
                                'h:i A',
                                strtotime(
                                    $row['TimeIn']
                                )
                            )
                            : '--:--';

                        ?>

                    </span>


                    <span>

                        <?php

                        echo $row['TimeOut']
                            ? date(
                                'h:i A',
                                strtotime(
                                    $row['TimeOut']
                                )
                            )
                            : '--:--';

                        ?>

                    </span>


                    <span
                        class="status <?php
                            echo strtolower(
                                str_replace(
                                    ' ',
                                    '-',
                                    $row['Status']
                                )
                            );
                        ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $row['Status']
                        );
                        ?>

                    </span>


                </div>

            <?php endwhile; ?>


            <?php $attendanceQuery->close(); ?>


        </section>


    </main>

    <!-- ==================== MAIN CONTENT END ==================== -->


</div>

<?php if ($isTimedIn): ?>

<!-- =========================
     LOGOUT MODAL
========================= -->

<div class="logout-modal" id="logoutModal">

    <div class="logout-modal-box">

        <div class="logout-modal-icon">
            !
        </div>

        <h2>You're Still Timed In</h2>

        <p>
            You are currently timed in.
            Logging out will automatically time you out
            and record your attendance.
        </p>

        <div class="logout-modal-actions">

            <button
                type="button"
                class="logout-cancel"
                onclick="closeLogoutModal();"
            >
                Cancel
            </button>

            <button
                type="button"
                class="logout-confirm"
                onclick="confirmLogout();"
            >
                Logout Anyway
            </button>

        </div>

    </div>

</div>

<?php endif; ?>

<script>

function openLogoutModal(event) {

    event.preventDefault();

    const modal = document.getElementById("logoutModal");

    if (modal) {
        modal.classList.add("show");
    } else {
        window.location.href = "../logout.php";
    }

}


function closeLogoutModal() {

    const modal = document.getElementById("logoutModal");

    if (modal) {
        modal.classList.remove("show");
    }

}


function confirmLogout() {

    window.location.href = "../logout.php";

}

</script>

</body>

</html>