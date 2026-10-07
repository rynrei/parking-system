<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require "../config/database.php";

// Only Superadmin can view activity logs
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    die("ACCESS DENIED");
}

// Get activity logs from the NEW database
$sql = "SELECT ActivityLogID, UserID, Username, Role, Action, CreatedAt
        FROM activitylogs
        ORDER BY ActivityLogID DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . $conn->error);
}

// Escape database values before displaying them
function logEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Choose a badge based on the recorded action
function logCategory($action)
{
    $action = strtolower((string) $action);

    if (preg_match('/logout|logged out|log out/', $action)) {
        return ['Logout', 'neutral'];
    }

    if (preg_match('/login|logged in|log in/', $action)) {
        return ['Login', 'green'];
    }

    if (preg_match('/delet|remov|deactivat/', $action)) {
        return ['Removed / Disabled', 'red'];
    }

    if (preg_match('/creat|added|register/', $action)) {
        return ['Created / Added', 'green'];
    }

    if (preg_match('/updat|edit|chang/', $action)) {
        return ['Updated', 'blue'];
    }

    if (preg_match('/payment|paid|checkout|exit/', $action)) {
        return ['Payment / Exit', 'orange'];
    }

    if (preg_match('/report|generat|export/', $action)) {
        return ['Generated', 'orange'];
    }

    return ['Activity', 'neutral'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activity Logs</title>

    <link rel="stylesheet" href="../css/superadmin_dashboard.css?v=2">
    <link rel="stylesheet" href="../css/activity_logs.css?v=4">

</head>

<body>

    <div class="dashboard">

    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <!-- Logo -->

        <div class="sidebar-logo">
            <a href="dashboard.php" style=" font-size: 16px; font-weight: 700; color: #1f2937; letter-spacing: 1px; text-decoration: none;">PARKZEN</a>
        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">

            <!-- MAIN -->

            <div class="nav-section-title">
                MAIN
            </div>

            <a href="dashboard.php"  class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Dashboard
                </span>

            </a>


            <!-- MANAGE -->

            <div class="nav-section-title">
                MANAGE
            </div>

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


            <a href="staff_attendance.php" class="nav-item">
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


            <!-- ANALYTICS -->

            <div class="nav-section-title">
                ANALYTICS
            </div>


            <a href="#" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Reports
                </span>

            </a>


            <a href="activity_logs.php"  class="nav-item active">

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

            <a href="../logout.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Logout
                </span>

            </a>
        </nav>

    </aside>

        <main class="al-main">

            <header class="al-topbar">
                <span>Activity Logs</span>

                <a href="dashboard.php" class="al-back">
                    ← Back to Dashboard
                </a>
            </header>

            <div class="al-content">

                <div class="al-page-heading">
                    <h1>Activity Logs</h1>
                    <p>Track system activities and user actions.</p>
                </div>

                <section class="al-card" aria-labelledby="al-title">

                    <div class="al-toolbar">

                        <h2 id="al-title">System Activity Log</h2>

                        <div class="al-filters">

                            <input
                                type="search"
                                id="al-search"
                                placeholder="Search logs..."
                                aria-label="Search activity logs"
                            >

                            <select id="al-action" aria-label="Filter by action">
                                <option value="">All Actions</option>
                                <option>Login</option>
                                <option>Logout</option>
                                <option>Created / Added</option>
                                <option>Updated</option>
                                <option>Removed / Disabled</option>
                                <option>Payment / Exit</option>
                                <option>Generated</option>
                                <option>Activity</option>
                            </select>

                            <select id="al-role" aria-label="Filter by role">
                                <option value="">All Roles</option>
                                <option value="superadmin">Superadmin</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                            </select>

                            <input
                                type="date"
                                id="al-date"
                                aria-label="Filter by date"
                            >

                        </div>

                    </div>

                    <div class="al-table-wrap">

                        <table class="al-table">

                            <thead>
                                <tr>
                                    <th scope="col">Log ID</th>
                                    <th scope="col">Timestamp</th>
                                    <th scope="col">User / Role</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Details</th>
                                </tr>
                            </thead>

                            <tbody id="al-rows">

                                <?php while ($log = $result->fetch_assoc()): ?>

                                    <?php
                                    [$category, $badgeColor] =
                                        logCategory($log['Action']);

                                    $timestamp = strtotime(
                                        (string) $log['CreatedAt']
                                    );

                                    $dateKey = $timestamp !== false
                                        ? date('Y-m-d', $timestamp)
                                        : '';

                                    $role = strtolower(
                                        (string) $log['Role']
                                    );

                                    $roleLabel = $role === 'superadmin'
                                        ? 'Super Admin'
                                        : ucfirst($role);
                                    ?>

                                    <tr
                                        class="al-row"
                                        data-category="<?= logEscape($category) ?>"
                                        data-role="<?= logEscape($role) ?>"
                                        data-date="<?= logEscape($dateKey) ?>"
                                    >

                                        <td>
                                            <span class="al-log-id">
                                                LOG-<?= logEscape($log['ActivityLogID']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($timestamp !== false): ?>
                                                <span class="al-time">
                                                    <?= date('h:i:s A', $timestamp) ?>
                                                </span>

                                                <span class="al-date">
                                                    <?= date('M d, Y', $timestamp) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="al-time">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="al-username">
                                                <?= logEscape($log['Username']) ?>
                                            </span>

                                            <span class="al-user-role">
                                                <?= logEscape($roleLabel) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="al-badge al-badge-<?= logEscape($badgeColor) ?>">
                                                <?= logEscape($category) ?>
                                            </span>
                                        </td>

                                        <td class="al-details">
                                            <?= logEscape($log['Action']) ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                                <tr id="al-empty" hidden>
                                    <td colspan="5" class="al-empty">
                                        No activity logs found.
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                    <footer class="al-footer">

                        <p id="al-count" aria-live="polite"></p>

                        <nav class="al-pagination" aria-label="Activity log pages">
                            <button type="button" id="al-prev">
                                ← Previous
                            </button>

                            <span id="al-page"></span>

                            <button type="button" id="al-next">
                                Next →
                            </button>
                        </nav>

                    </footer>

                </section>

            </div>

        </main>

    </div>

    <script>
        const logRows = Array.from(
            document.querySelectorAll(".al-row")
        );

        const searchInput = document.getElementById("al-search");
        const actionFilter = document.getElementById("al-action");
        const roleFilter = document.getElementById("al-role");
        const dateFilter = document.getElementById("al-date");

        const previousButton = document.getElementById("al-prev");
        const nextButton = document.getElementById("al-next");

        const pageSize = 8;
        let currentPage = 1;

        function renderLogs() {
            const search = searchInput.value.trim().toLowerCase();

            const matchingRows = logRows.filter(row => {
                return (
                    row.textContent.toLowerCase().includes(search) &&
                    (!actionFilter.value ||
                        row.dataset.category === actionFilter.value) &&
                    (!roleFilter.value ||
                        row.dataset.role === roleFilter.value) &&
                    (!dateFilter.value ||
                        row.dataset.date === dateFilter.value)
                );
            });

            const totalPages = Math.max(
                1,
                Math.ceil(matchingRows.length / pageSize)
            );

            currentPage = Math.min(currentPage, totalPages);

            logRows.forEach(row => {
                row.hidden = true;
            });

            const start = (currentPage - 1) * pageSize;
            const visibleRows = matchingRows.slice(
                start,
                start + pageSize
            );

            visibleRows.forEach(row => {
                row.hidden = false;
            });

            document.getElementById("al-empty").hidden =
                matchingRows.length > 0;

            document.getElementById("al-count").textContent =
                matchingRows.length
                    ? `Showing ${start + 1}–${start + visibleRows.length} of ${matchingRows.length} logs`
                    : "Showing 0 logs";

            document.getElementById("al-page").textContent =
                `${currentPage} / ${totalPages}`;

            previousButton.disabled = currentPage === 1;
            nextButton.disabled = currentPage === totalPages;
        }

        [searchInput, actionFilter, roleFilter, dateFilter]
            .forEach(control => {
                control.addEventListener("input", () => {
                    currentPage = 1;
                    renderLogs();
                });
            });

        previousButton.addEventListener("click", () => {
            currentPage--;
            renderLogs();
        });

        nextButton.addEventListener("click", () => {
            currentPage++;
            renderLogs();
        });

        renderLogs();
    </script>

</body>

</html>