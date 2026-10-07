<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once "../config/database.php";

if (!isset($_SESSION['id'])) {
    header("Location: /pms/login.php");
    exit();
}

if ($_SESSION['role'] != 'superadmin') {
    header("Location: /pms/access_denied.php");
    exit();
}

// ========================================
// GET RECENT ACTIVITY LOGS
// ========================================

$recentActivityQuery = "
    SELECT
        ActivityLogID,
        UserID,
        Username,
        Role,
        Action,
        CreatedAt
    FROM activitylogs
    ORDER BY ActivityLogID DESC
    LIMIT 5
";

$recentActivityResult = $conn->query($recentActivityQuery);

if (!$recentActivityResult) {
    die("Failed to get recent activity logs: " . htmlspecialchars($conn->error));
}


$totalEntriesQuery = "
    SELECT COUNT(*) AS total
    FROM parkinglogs
    WHERE DATE(TimeIn) = CURDATE()
";

$totalEntriesResult = $conn->query($totalEntriesQuery);

if (!$totalEntriesResult) {
    die("Failed to get total entries: " . htmlspecialchars($conn->error));
}

$totalEntries = $totalEntriesResult->fetch_assoc()['total'];

$overnightQuery = "
    SELECT COUNT(*) AS total
    FROM parkinglogs
    WHERE TimeOut IS NULL
    AND TimeIn <= NOW() - INTERVAL 24 HOUR
";

$overnightResult = $conn->query($overnightQuery);

if (!$overnightResult) {
    die("Failed to get overnight parking: " . htmlspecialchars($conn->error));
}

$overnightParking = $overnightResult->fetch_assoc()['total'];

$currentParkedQuery = "
    SELECT COUNT(*) AS total
    FROM parkinglogs
    WHERE TimeOut IS NULL
";

$currentParkedResult = $conn->query($currentParkedQuery);

if (!$currentParkedResult) {
    die("Failed to get currently parked vehicles: " . htmlspecialchars($conn->error));
}

$currentParked = $currentParkedResult->fetch_assoc()['total'];

$checkedOutQuery = "
    SELECT COUNT(*) AS total
    FROM parkinglogs
    WHERE TimeOut IS NOT NULL
    AND DATE(TimeOut) = CURDATE()
";

$checkedOutResult = $conn->query($checkedOutQuery);

if (!$checkedOutResult) {
    die("Failed to get checked out vehicles: " . htmlspecialchars($conn->error));
}

$checkedOut = $checkedOutResult->fetch_assoc()['total'];

date_default_timezone_set('Asia/Manila');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Superadmin Dashboard</title>

    <!-- External CSS -->
    <link rel="stylesheet" href="../css/superadmin_dashboard.css?v=5">

</head>

<body>

    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">
            <a href="/pms/superadmin/dashboard.php" class="parkzen-brand">
                <span class="pz-badge">PZ</span>
                <span class="parkzen-name">PARKZEN</span>
            </a>
        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">

            <!-- MAIN -->

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
         MAIN CONTENT
    ========================== -->

    <main class="superadmin-content">


    <!-- =========================
         HEADER
    ========================== -->

    <header class="page-header">

        <div>

            <span class="page-label">
                Dashboard
            </span>

            <h1>
                Superadmin Dashboard
            </h1>

        </div>


        <div class="header-actions">

            <div class="profile-wrapper">

                <div class="profile-button">

                    <?php
                    echo strtoupper(
                        substr($_SESSION['username'], 0, 1)
                    );
                    ?>

                </div>


                <div class="profile-card">

                    <div class="profile-avatar">

                        <?php
                        echo strtoupper(
                            substr($_SESSION['username'], 0, 1)
                        );
                        ?>

                    </div>


                    <div class="profile-details">

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $_SESSION['username']
                            );
                            ?>
                        </strong>

                        <span>
                            Superadmin
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </header>


    <!-- =========================
         STATISTICS
    ========================== -->

    <section class="stats">

        <div class="stat-card">

            <span class="stat-label">
                Total Entries
            </span>

            <div>
                <strong class="stat-number"><?php echo $totalEntries; ?></strong>
            </div>

            <small>
                Today's entries
            </small>

        </div>

        <div class="stat-card">
            <span class="stat-label">Overnight Parking</span>
            <strong class="stat-number"><?php echo $overnightParking; ?></strong>
            <small>Exceeded 24 hours</small>
        </div>

        <div class="stat-card">
            <span class="stat-label">Currently Parked</span>
            <strong class="stat-number"><?php echo $currentParked; ?></strong>
            <small>Active vehicles</small>
        </div>


        <div class="stat-card">
            <span class="stat-label">Checked Out</span>
            <strong class="stat-number"><?php echo $checkedOut; ?></strong>
            <small>Today's vehicles</small>
        </div>

    </section>


    <!-- =========================
         SERVER / SYSTEM AREA
    ========================== -->

    <section class="server-time">

             <div class="time-info">
                    <span>Server Time</span>
                    <strong id="serverTime">--:--:-- --</strong>
                    <small id="serverDate">Loading...</small>
                </div>

        <div class="divider"></div>


        <div class="dashboard-actions">

            <a href="/pms/superadmin/parking_slots/index.php">
                Parking Management
            </a>

            <a href="activity_logs.php" class="secondary">
                Activity Logs
            </a>

        </div>

    </section>


    <!-- =========================
         DASHBOARD LOGS
    ========================== -->

    <section class="dashboard-logs">
    <div class="section-header">
        <h2>Recent Activity</h2>

        <a href="activity_logs.php">
            View All
        </a>
    </div>

    <div class="activity-table">
        
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Date & Time</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($recentActivityResult->num_rows > 0): ?>

                    <?php while ($activity = $recentActivityResult->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($activity['Username']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($activity['Role']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($activity['Action']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($activity['CreatedAt']); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4">
                            No recent activity.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

</section>

</main>

<script src="script.js"></script>
<script>
    // Hide the old page before the browser saves its snapshot.
    window.addEventListener("pagehide", function () {
        document.documentElement.style.visibility = "hidden";
    });

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            // Ask PHP to check the session again.
            window.location.reload();
            return;
        }

        document.documentElement.style.visibility = "visible";
    });
</script>

<!-- Logout confirmation -->
<dialog
    id="logout-modal"
    class="logout-modal"
    aria-labelledby="logout-title"
    aria-describedby="logout-description"
>
    <div class="logout-modal-content">
        <div class="logout-modal-icon" aria-hidden="true">↪</div>

        <h2 id="logout-title">Log out of ParkZen?</h2>

        <p id="logout-description">
            Are you sure you want to end your session?
        </p>

        <div class="logout-modal-actions">
            <button
                type="button"
                class="logout-cancel"
                onclick="closeLogoutModal()"
                autofocus
            >
                Cancel
            </button>

            <button
                type="button"
                class="logout-confirm"
                onclick="confirmLogout()"
            >
                Logout
            </button>
        </div>
    </div>
</dialog>

<script>
    const logoutModal = document.getElementById("logout-modal");
    let logoutDestination = "";

    function openLogoutModal(event) {
        event.preventDefault();

        // Use the sidebar link's existing logout address.
        logoutDestination = event.currentTarget.href;

        if (!logoutModal.open) {
            logoutModal.showModal();
        }
    }

    function closeLogoutModal() {
        logoutModal.close();
    }

    function confirmLogout() {
        if (logoutDestination) {
            window.location.assign(logoutDestination);
        }
    }

    // Close when clicking outside the modal.
    logoutModal.addEventListener("click", (event) => {
        const bounds = logoutModal.getBoundingClientRect();

        const clickedOutside =
            event.clientX < bounds.left ||
            event.clientX > bounds.right ||
            event.clientY < bounds.top ||
            event.clientY > bounds.bottom;

        if (clickedOutside) {
            closeLogoutModal();
        }
    });
</script>

</body>



</html>