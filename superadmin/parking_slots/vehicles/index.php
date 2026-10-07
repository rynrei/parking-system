<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['id'])) {
    // Database.php is three levels up in the supplied page; login is at that root.
    header('Location: ../../../login.php');
    exit();
}

if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header('Location: ../../../access_denied.php');
    exit();
}

require_once '../../../config/database.php';

function vehicleEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// EXISTS avoids duplicate vehicle rows when a vehicle has several parking visits.
// Only completed visits justify the Checked Out label.
$sql = "
    SELECT v.VehicleID, v.PlateNumber, v.NumberOfWheels,
           v.Brand, v.Model, v.Color,
           CASE
               WHEN EXISTS (
                   SELECT 1 FROM parkinglogs pl
                   WHERE pl.VehicleID = v.VehicleID
                     AND pl.TimeOut IS NULL AND pl.Status = 'Active'
               ) THEN 'Parked'
               WHEN EXISTS (
                   SELECT 1 FROM parkinglogs pl
                   WHERE pl.VehicleID = v.VehicleID AND pl.TimeOut IS NOT NULL
               ) THEN 'Checked Out'
               ELSE 'Registered'
           END AS ParkingStatus
    FROM vehicles v
    ORDER BY v.VehicleID DESC
";
$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    exit('Unable to load vehicles. Please try again.');
}
$totalVehicles = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles | ParkZen</title>
    <link rel="stylesheet" href="../../../css/superadmin_dashboard.css?v=2">
    <link rel="stylesheet" href="../../../css/vehicle_management.css">
</head>
<body>
<aside class="sidebar">
        <!-- Logo -->
        <div class="sidebar-logo">
            <a href="../../dashboard.php" style=" font-size: 16px; font-weight: 700; color: #1f2937; letter-spacing: 1px; text-decoration: none;">PARKZEN</a>
        </div>
        <!-- Navigation -->
        <nav class="sidebar-nav">
            <!-- MAIN -->
            <div class="nav-section-title">
                MAIN
            </div>
            <a href="../../dashboard.php" class="nav-item">
                <span class="nav-dot"></span>
                <span class="nav-text">
                    Dashboard
                </span>
            </a>
            <!-- MANAGE -->
            <div class="nav-section-title">
                MANAGE
            </div>
            <a href="../../create_admin.php" class="nav-item">
                <span class="nav-dot"></span>
                <span class="nav-text">
                    Manage Admins
                </span>
            </a>
            <a href="../../create_staff.php" class="nav-item">
                <span class="nav-dot"></span>
                <span class="nav-text">
                    Manage Staff
                </span>
            </a>
            <a href="../../staff_attendance.php" class="nav-item">
                <span class="nav-dot"></span>
                <span class="nav-text">Staff Attendance</span>
            </a>
            <!-- PARKING -->
            <div class="nav-section-title">
                PARKING
            </div>
            <a href="/pms/superadmin/parking_slots/vehicles/index.php" class="nav-item active" aria-current="page">
                <span class="nav-dot"></span>
                <strong class="nav-text">
                    Manage Vehicles
                </strong>
            </a>
            <a href="#" class="nav-item">
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
            <a href="../../activity_logs.php" class="nav-item">
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
            <a href="../../../logout.php" class="nav-item logout-item">
                <span class="nav-dot logout-dot"></span>
                <span class="nav-text">
                    Logout
                </span>
            </a>
        </nav>
    </aside>
<main class="superadmin-content vehicle-page">
    <header class="topbar">
        <span class="topbar-title">Manage Vehicles</span>
        <span class="welcome">Welcome, <strong><?= vehicleEscape($_SESSION['username'] ?? 'User') ?></strong></span>
    </header>
    <div class="content">
            <div><h1>Manage Vehicles</h1><p class="subtitle">View all vehicles in the system</p></div>
        <section class="vehicle-card" aria-labelledby="vehicles-title">
            <div class="card-heading">
                <h2 id="vehicles-title">Registered Vehicles</h2>
                <div class="controls">
                    <label class="sr-only" for="status-filter">Filter by parking status</label>
                    <label class="sr-only" for="plate-search">Search plate number</label>
                    <input class="search" id="plate-search" type="search" placeholder="Search plate number..." autocomplete="off">
                </div>
            </div>
            <noscript><p class="subtitle">Enable JavaScript to use search, filtering, and pagination. All vehicles are shown below.</p></noscript>
            <div class="table-scroll">
                <table>
                    <caption class="sr-only">Registered vehicles and their current parking status</caption>
                    <thead><tr>
                        <th scope="col">Plate Number</th><th scope="col">Brand</th>
                        <th scope="col">Model</th><th scope="col">Color</th>
                        <th scope="col">Wheels</th><th scope="col">Status</th>
                    </tr></thead>
                    <tbody id="vehicle-body">
                    <?php while ($vehicle = $result->fetch_assoc()): ?>
                        <?php
                        $status = $vehicle['ParkingStatus'];
                        $badgeClass = $status === 'Parked' ? 'parked' : ($status === 'Checked Out' ? 'checked-out' : 'registered');
                        ?>
                        <tr
                            class="vehicle-row"
                            data-plate="<?php echo htmlspecialchars(
                                $vehicle['PlateNumber'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >
                            <td class="plate-number"><?= vehicleEscape($vehicle['PlateNumber']) ?></td>
                            <td><?= vehicleEscape($vehicle['Brand']) ?></td>
                            <td><?= vehicleEscape($vehicle['Model']) ?></td>
                            <td><?= vehicleEscape($vehicle['Color']) ?></td>
                            <td><?= (int)$vehicle['NumberOfWheels'] ?></td>
                            <td><span class="status-badge <?= $badgeClass ?>"><?= vehicleEscape($status) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                        <tr id="empty-row" <?= $totalVehicles > 0 ? 'hidden' : '' ?>><td colspan="6" class="empty" id="empty-message">No vehicles registered.</td></tr>
                    </tbody>
                </table>
            </div>
            <footer class="card-footer">
                <p class="summary" id="vehicle-summary" aria-live="polite">Showing <?= $totalVehicles ?> vehicles</p>
                <nav class="pagination" id="pagination" aria-label="Vehicle table pages"></nav>
            </footer>
        </section>
    </div>
</main>
<script>
(() => {
    const rows = Array.from(document.querySelectorAll('.vehicle-row'));
    const search = document.getElementById('plate-search');
    const statusFilter = document.getElementById('status-filter');
    const emptyRow = document.getElementById('empty-row');
    const summary = document.getElementById('vehicle-summary');
    const pagination = document.getElementById('pagination');
    const pageSize = 5;
    let currentPage = 1;

    function render() {
        const query = search.value.trim().toLowerCase();
        const matching = rows.filter(row => row.dataset.plate.toLowerCase().includes(query)
            && (statusFilter.value === 'all' || row.dataset.status === statusFilter.value));
        const pageCount = Math.max(1, Math.ceil(matching.length / pageSize));
        currentPage = Math.min(currentPage, pageCount);
        rows.forEach(row => { row.hidden = true; });
        const start = (currentPage - 1) * pageSize;
        matching.slice(start, start + pageSize).forEach(row => { row.hidden = false; });
        emptyRow.hidden = matching.length > 0;
        document.getElementById('empty-message').textContent = rows.length === 0
            ? 'No vehicles registered.' : 'No vehicles match your search or filter.';
        summary.textContent = matching.length
            ? `Showing ${start + 1}–${Math.min(start + pageSize, matching.length)} of ${matching.length} vehicles`
                + (matching.length < rows.length ? ` (${rows.length} total)` : '')
            : 'Showing 0 vehicles';
        pagination.replaceChildren();
        if (pageCount <= 1) return;
        function pageButton(label, target, disabled = false, active = false) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'page-button' + (active ? ' current' : '');
            button.textContent = label;
            button.disabled = disabled;
            button.setAttribute('aria-label', /^\d+$/.test(label) ? `Page ${label}` : `${label} page`);
            if (active) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => {
                currentPage = target;
                render();
                const selected = pagination.querySelector('[aria-current="page"]');
                if (selected) selected.focus();
            });
            pagination.append(button);
        }
        pageButton('Previous', currentPage - 1, currentPage === 1);
        const first = Math.max(1, Math.min(currentPage - 2, pageCount - 4));
        for (let page = first; page <= Math.min(pageCount, first + 4); page++) {
            pageButton(String(page), page, false, page === currentPage);
        }
        pageButton('Next', currentPage + 1, currentPage === pageCount);
    }
    search.addEventListener('input', () => { currentPage = 1; render(); });
    statusFilter.addEventListener('change', () => { currentPage = 1; render(); });
    render();
})();
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const search = document.getElementById("plate-search");
    const rows = Array.from(
        document.querySelectorAll(".vehicle-row")
    );

    if (!search || rows.length === 0) return;

    const tbody = rows[0].parentElement;

    let emptyRow = document.getElementById("empty-row");

    if (!emptyRow) {
        emptyRow = document.createElement("tr");
        emptyRow.id = "empty-row";

        const cell = document.createElement("td");
        cell.colSpan = rows[0].cells.length;
        cell.className = "empty";
        cell.textContent = "No matching plate number found.";

        emptyRow.appendChild(cell);
        tbody.appendChild(emptyRow);
    }

    function filterVehicles() {
        const query = search.value.trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach(function (row) {
            const plate = row.dataset.plate.toLowerCase();
            const matches = plate.includes(query);

            row.hidden = !matches;

            if (matches) visibleCount++;
        });

        emptyRow.hidden = visibleCount > 0;

        const summary = document.getElementById("vehicle-summary");

        if (summary) {
            summary.textContent =
                "Showing " + visibleCount +
                " of " + rows.length + " vehicles";
        }
    }

    search.addEventListener("input", filterVehicles);
    filterVehicles();
});
</script>
</body>
</html>
