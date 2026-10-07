<?php

session_start();

require '../config/database.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'superadmin') {
    die("ACCESS DENIED");
}


// ========================================
// GET STAFF ATTENDANCE
// ========================================

// Default date is today
$selectedDate = isset($_GET['date']) && !empty($_GET['date'])
    ? $_GET['date']
    : date('Y-m-d');

$attendanceQuery = "
    SELECT
        a.AttendanceID,
        u.Username,
        u.shift,
        a.AttendanceDate,
        a.TimeIn,
        a.TimeOut,
        a.Status
    FROM attendance a
    INNER JOIN users u
        ON a.UserID = u.UserID
    WHERE u.Role = 'staff'
    AND a.AttendanceDate = ?
    ORDER BY a.AttendanceID DESC
";

$stmt = $conn->prepare($attendanceQuery);
$stmt->bind_param("s", $selectedDate);
$stmt->execute();

$attendanceResult = $stmt->get_result();

if (!$attendanceResult) {
    die("Database error: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Attendance</title>

    <!-- Superadmin dashboard design -->
    <link rel="stylesheet" href="../css/superadmin_dashboard.css?v=2">

    <!-- Manage page design -->
    <link rel="stylesheet" href="../css/staff_attendance.css">

</head>

<body>

<div class="dashboard">


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <a href="dashboard.php"
               style="font-size: 16px; font-weight: 700; color: #1f2937; letter-spacing: 1px; text-decoration: none;">
                PARKZEN
            </a>

        </div>


        <nav class="sidebar-nav">

            <div class="nav-section-title">MAIN</div>

            <a href="dashboard.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Dashboard
                </span>

            </a>


            <div class="nav-section-title">MANAGE</div>

            <a href="create_admin.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Manage Admins
                </span>

            </a>


            <a href="create_staff.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Manage Staff
                </span>

            </a>


            <a href="staff_attendance.php" class="nav-item active">

                <span class="nav-dot"></span>

                <strong class="nav-text">
                    Staff Attendance
                </strong>

            </a>


            <div class="nav-section-title">PARKING</div>

            <a href="../vehicles/index.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Manage Vehicles
                </span>

            </a>


            <a href="../parking_slots/index.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Parking Slots
                </span>

            </a>


            <a href="#" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Parking Logs
                </span>

            </a>


            <a href="#" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Vehicle Entry
                </span>

            </a>


            <a href="#" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Vehicle Exit
                </span>

            </a>


            <div class="nav-section-title">ANALYTICS</div>

            <a href="#" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Reports
                </span>

            </a>


            <a href="activity_logs.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Activity Logs
                </span>

            </a>


            <a href="#" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Payments
                </span>

            </a>

        </nav>

    </aside>


    <!-- =========================
         RIGHT SIDE
    ========================== -->

    <main class="superadmin-content">


        <!-- TOP HEADER -->

        <div class="manage-header">

            <h1>
                Staff Attendance
            </h1>

        </div>


        <!-- PAGE CONTENT -->

        <div class="manage-page">


            <div class="manage-title">

                <p>
                    View staff attendance records
                </p>

            </div>


            <!-- =========================
                 ATTENDANCE CARD
            ========================== -->

                <div class="attendance-filter">

                    <form method="GET" action="staff_attendance.php">

                        <div class="date-input-group">

                            <label for="attendance-date">
                                Select Date
                            </label>

                            <input
                                type="date"
                                id="attendance-date"
                                name="date"
                                value="<?php echo htmlspecialchars($selectedDate); ?>"
                            >

                        </div>

                        <button type="submit">
                            Filter
                        </button>

                    </form>

                </div>

            <div class="attendance-card">

                    <div class="attendance-card-header">
                        <h3>Attendance Records</h3>
                        <p>Staff attendance history</p>
                    </div>

                    <div class="attendance-table">

                        <div class="attendance-table-header">
                            <span>Username</span>
                            <span>Shift</span>
                            <span>Date</span>
                            <span>Time In</span>
                            <span>Time Out</span>
                            <span>Status</span>
                        </div>

        <?php if ($attendanceResult->num_rows > 0): ?>

            <?php while ($attendance = $attendanceResult->fetch_assoc()): ?>

                <div class="attendance-row">

                    <div>
                        <?php echo htmlspecialchars($attendance['Username']); ?>
                    </div>

                    <div>
                        <?php echo htmlspecialchars($attendance['shift']); ?>
                    </div>

                    <div>
                        <?php echo htmlspecialchars($attendance['AttendanceDate']); ?>
                    </div>

                    <div>
                        <?php echo htmlspecialchars($attendance['TimeIn']); ?>
                    </div>

                    <div>
                        <?php echo $attendance['TimeOut']
                            ? htmlspecialchars($attendance['TimeOut'])
                            : '--'; ?>
                    </div>

                    <div>
                        <span class="attendance-status 
                            <?php echo strtolower(str_replace(' ', '-', $attendance['Status'])); ?>">
                            <?php echo htmlspecialchars($attendance['Status']); ?>
                        </span>
                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="attendance-empty">
                No attendance records found.
            </div>

        <?php endif; ?>

    </div>

</div>


        </div>


    </main>


</div>

</body>

</html>