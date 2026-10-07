<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require '../config/database.php';

if (!isset($_SESSION['id'])) {
    header("Location: /pms/login.php");
    exit();
}

if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
    header("Location: /pms/access_denied.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../css/admin_dashboard.css?v=2">

</head>

<body>

        <aside class="sidebar admin-sidebar">

    <div class="sidebar-logo">
        <a href="/pms/admin/dashboard.php" class="parkzen-brand">
            <span class="pz-badge">PZ</span>
            <span class="parkzen-name">PARKZEN</span>
        </a>
    </div>

        <nav class="sidebar-nav">

            <div class="nav-section-title">
                MAIN
            </div>

            <a href="/pms/superadmin/dashboard.php" class="nav-item active">

                <span class="nav-dot"></span>

                <strong class="nav-text">
                    Dashboard
                </strong>

            </a>


            <!-- MANAGE -->

            <div class="nav-section-title">
                MANAGE
            </div>

            <a href="/pms/superadmin/create_admin.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Manage Admins
                </span>

            </a>


            <a href="/pms/superadmin/create_staff.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Manage Staff
                </span>

            </a>


            <a href="/pms/superadmin/staff_attendance.php" class="nav-item">
                <span class="nav-dot"></span>
                <span class="nav-text">Staff Attendance</span>
            </a>


            <!-- PARKING -->

            <div class="nav-section-title">
                PARKING
            </div>


            <a href="/pms/superadmin/parking_slots/vehicles/index.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Manage Vehicles
                </span>

            </a>


            <a href="/pms/superadmin/parking_slots/index.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Parking Slots
                </span>

            </a>


            <a href="/pms/parking_logs/index.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Parking Logs
                </span>

            </a>


            <a href="/pms/superadmin/parking_slots/vehicles/create.php" class="nav-item">

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


            <!-- ANALYTICS -->

            <div class="nav-section-title">
                ANALYTICS
            </div>


            <a href="/pms/reports.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Reports
                </span>

            </a>


            <a href="/pms/superadmin/activity_logs.php" class="nav-item">

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

                <div class="sidebar-footer">

                <div class="sidebar-role">ADMIN PANEL</div>

        <a
            href="/pms/logout.php"
            class="nav-item logout-item"
            onclick="openLogoutModal(event)"
        >
            <span></span>
            <span class="nav-text">Logout</span>
        </a>
    </div>

</aside>

</body>

</html>