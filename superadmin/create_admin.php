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


$adminQuery = "
    SELECT
        UserID,
        Username,
        Role,
        Active
    FROM users
    WHERE Role = 'admin'
    ORDER BY UserID DESC
";

$adminResult = $conn->query($adminQuery);

if (!$adminResult) {
    die("Database error: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage admins</title>

    <!-- Superadmin dashboard design -->
    <link rel="stylesheet" href="../css/superadmin_dashboard.css?v=3">

    <!-- Manage admins design -->
    <link rel="stylesheet" href="../css/create_admin.css">

</head>

<body>

<div class="dashboard">

    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <!-- Logo -->

        <div class="sidebar-logo">
            <a href="/pms/superadmin/dashboard.php" style=" font-size: 16px; font-weight: 700; color: #1f2937; letter-spacing: 1px; text-decoration: none;">PARKZEN</a>
        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">

            <!-- MAIN -->

            <div class="nav-section-title">
                MAIN
            </div>

            <a href="/pms/superadmin/dashboard.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Dashboard
                </span>

            </a>


            <!-- MANAGE -->

            <div class="nav-section-title">
                MANAGE
            </div>

            <a href="/pms/superadmin/create_admin.php" class="nav-item active">

                <span class="nav-dot"></span>

                <strong class="nav-text">
                    Manage Admins
                </strong>

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

                <div class="sidebar-role">SUPERADMIN PANEL</div>

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


    <!-- =========================
         RIGHT SIDE
    ========================== -->

    <main class="superadmin-content">

        <!-- TOP HEADER -->

        <div class="manage-header">

            <h1>Manage Admins</h1>

        </div>


        <!-- PAGE CONTENT -->

        <div class="manage-page">

            <div class="manage-title">

                <h2>Manage Admins</h2>

                <p>
                    Create and manage administrator accounts
                </p>

            </div>


            <!-- =========================
                 ADMIN CONTENT GRID
            ========================== -->

            <div class="admin-content-grid">


                <!-- =====================
                     ADD ADMIN CARD
                ====================== -->

                <div class="admin-card add-admin-card">

                    <div class="admin-card-header">

                        <h3>Add New Admin</h3>

                        <p>
                            Create a new administrator account
                        </p>

                    </div>


                    <form action="create_admin_process.php" method="POST">

    <div class="form-row">

        <div class="form-group">
            <label for="username">Username</label>

            <input
                type="text"
                id="username"
                name="username"
                placeholder="e.g. kien.admin"
                required
            >
        </div>

        <div class="form-group">
            <label for="shift">Shift</label>

            <select id="shift" name="shift" required>
                <option value="" selected disabled>Select shift</option>
                <option value="Morning">Morning</option>
                <option value="Night">Night</option>
            </select>
        </div>

    </div>

    <div class="form-row">

        <div class="form-group">
            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter password"
                required
            >
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Re-enter password"
                required
            >
        </div>

    </div>

    <button type="submit" class="create-admin-btn">
        Create Admin
    </button>

</form>

                </div>


                <!-- =====================
                     REGISTERED ADMINS
                ====================== -->

                <div class="admin-card registered-admin-card">

                    <div class="admin-card-header">

                        <h3>Registered Administrators</h3>

                    </div>


                    <!-- TABLE HEADER -->

                    <div class="admin-table-header">

                        <span>Username</span>

                        <span>Role</span>

                        <span>Status</span>

                    </div>


                    <div class="admin-divider"></div>


                        <?php if ($adminResult->num_rows > 0): ?>

                            <?php while ($admin = $adminResult->fetch_assoc()): ?>

                                <div class="admin-row">

                                    <div class="admin-username">
                                        <?php echo htmlspecialchars($admin['Username']); ?>
                                    </div>

                                    <div class="admin-role">
                                        <?php echo htmlspecialchars(ucfirst($admin['Role'])); ?>
                                    </div>

                                    <div>
                                        <?php if ($admin['Active'] == 1): ?>

                                            <span class="status active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="status inactive">
                                                Inactive
                                            </span>

                                        <?php endif; ?>
                                    </div>

                                </div>

                                <?php if ($adminResult->num_rows > 1): ?>
                                    <div class="admin-divider"></div>
                                <?php endif; ?>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <div class="no-admins">
                                No registered administrators yet.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>