<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'config/database.php';

if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header("Location: /access_denied.php");
    exit();
}

date_default_timezone_set('Asia/Manila');

function reportRows($conn, $sql, $types = '', $params = [])
{
    $stmt = $conn->prepare($sql);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function reportEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$today = new DateTimeImmutable('today');
$tomorrow = $today->modify('+1 day');

$todayStart = $today->format('Y-m-d H:i:s');
$todayEnd = $tomorrow->format('Y-m-d H:i:s');
$weekStart = $today->modify('-6 days')->format('Y-m-d H:i:s');

$monthStart = $today->modify('first day of this month')->setTime(0, 0);
$summaryStart = $monthStart->modify('-5 months')->format('Y-m-d H:i:s');

try {
    // Today's entries.
    $entryStats = reportRows(
        $conn,
        "SELECT COUNT(*) AS total
         FROM parkinglogs
         WHERE TimeIn >= ? AND TimeIn < ?",
        'ss',
        [$todayStart, $todayEnd]
    )[0];

    // Revenue comes from completed payments.
    $revenueStats = reportRows(
        $conn,
        "SELECT COALESCE(SUM(Amount), 0) AS total
         FROM payments
         WHERE Status = 'Paid'
           AND DateTimePaid >= ? AND DateTimePaid < ?",
        'ss',
        [$todayStart, $todayEnd]
    )[0];

    // Average duration of vehicles that exited today.
    $durationStats = reportRows(
        $conn,
        "SELECT AVG(TIMESTAMPDIFF(SECOND, TimeIn, TimeOut) / 3600.0) AS hours
         FROM parkinglogs
         WHERE TimeOut >= ? AND TimeOut < ?
           AND TimeOut >= TimeIn",
        'ss',
        [$todayStart, $todayEnd]
    )[0];

    // Count occupied slots once, even if duplicate open logs exist.
    $slotStats = reportRows(
        $conn,
        "SELECT COUNT(*) AS capacity,
                COALESCE(SUM(
                    EXISTS (
                        SELECT 1 FROM parkinglogs pl
                        WHERE pl.SlotID = ps.SlotID
                          AND pl.TimeOut IS NULL
                          AND pl.TimeIn <= ?
                    )
                ), 0) AS occupied
         FROM parkingslots ps",
        's',
        [(new DateTimeImmutable())->format('Y-m-d H:i:s')]
    )[0];

    // Revenue for the last seven calendar days.
    $dailyRevenue = reportRows(
        $conn,
        "SELECT DATE(DateTimePaid) AS day, SUM(Amount) AS revenue
         FROM payments
         WHERE Status = 'Paid'
           AND DateTimePaid >= ? AND DateTimePaid < ?
         GROUP BY DATE(DateTimePaid)",
        'ss',
        [$weekStart, $todayEnd]
    );

    // Hourly arrivals for the same seven-day period.
    $hourlyEntries = reportRows(
        $conn,
        "SELECT HOUR(TimeIn) AS hour, COUNT(*) AS total
         FROM parkinglogs
         WHERE TimeIn >= ? AND TimeIn < ?
         GROUP BY HOUR(TimeIn)",
        'ss',
        [$weekStart, $todayEnd]
    );

    // Separate monthly queries prevent payments from duplicating entries.
    $monthlyEntries = reportRows(
        $conn,
        "SELECT DATE_FORMAT(TimeIn, '%Y-%m') AS month, COUNT(*) AS entries
         FROM parkinglogs
         WHERE TimeIn >= ? AND TimeIn < ?
         GROUP BY DATE_FORMAT(TimeIn, '%Y-%m')",
        'ss',
        [$summaryStart, $todayEnd]
    );

    $monthlyExits = reportRows(
        $conn,
        "SELECT DATE_FORMAT(TimeOut, '%Y-%m') AS month,
                COUNT(*) AS exits,
                AVG(TIMESTAMPDIFF(SECOND, TimeIn, TimeOut) / 3600.0) AS hours
         FROM parkinglogs
         WHERE TimeOut >= ? AND TimeOut < ?
           AND TimeOut >= TimeIn
         GROUP BY DATE_FORMAT(TimeOut, '%Y-%m')",
        'ss',
        [$summaryStart, $todayEnd]
    );

    $monthlyRevenue = reportRows(
        $conn,
        "SELECT DATE_FORMAT(DateTimePaid, '%Y-%m') AS month,
                SUM(Amount) AS revenue
         FROM payments
         WHERE Status = 'Paid'
           AND DateTimePaid >= ? AND DateTimePaid < ?
         GROUP BY DATE_FORMAT(DateTimePaid, '%Y-%m')",
        'ss',
        [$summaryStart, $todayEnd]
    );
} catch (Throwable $error) {
    error_log('Reports error: ' . $error->getMessage());
    http_response_code(500);
    exit('Unable to load reports. Check the Apache error log for details.');
}

$todayEntries = (int) $entryStats['total'];
$todayRevenue = (float) $revenueStats['total'];
$averageHours = $durationStats['hours'];

$capacity = (int) $slotStats['capacity'];
$occupied = (int) $slotStats['occupied'];
$occupancy = $capacity > 0 ? ($occupied / $capacity) * 100 : null;

// Fill missing days with zero revenue.
$revenueByDay = array_column($dailyRevenue, 'revenue', 'day');
$revenueDays = [];

for ($i = 6; $i >= 0; $i--) {
    $day = $today->modify("-$i days");

    $revenueDays[] = [
        'label' => $day->format('D'),
        'date' => $day->format('M j'),
        'amount' => (float) ($revenueByDay[$day->format('Y-m-d')] ?? 0)
    ];
}

$maxRevenue = max(1, max(array_column($revenueDays, 'amount')));

// Four-hour groups cover the whole day.
// Percentages represent each group's share of arrivals, not occupancy.
$peakGroups = [
    ['label' => '12 AM – 4 AM', 'total' => 0],
    ['label' => '4 AM – 8 AM', 'total' => 0],
    ['label' => '8 AM – 12 PM', 'total' => 0],
    ['label' => '12 PM – 4 PM', 'total' => 0],
    ['label' => '4 PM – 8 PM', 'total' => 0],
    ['label' => '8 PM – 12 AM', 'total' => 0]
];

foreach ($hourlyEntries as $row) {
    $group = intdiv((int) $row['hour'], 4);
    $peakGroups[$group]['total'] += (int) $row['total'];
}

$totalArrivals = array_sum(array_column($peakGroups, 'total'));
$highestArrivals = max(array_column($peakGroups, 'total'));

// Create the last six months, including months without records.
$entriesByMonth = array_column($monthlyEntries, 'entries', 'month');
$exitsByMonth = array_column($monthlyExits, null, 'month');
$revenueByMonth = array_column($monthlyRevenue, 'revenue', 'month');
$monthlySummary = [];

for ($i = 0; $i < 6; $i++) {
    $month = $monthStart->modify("-$i months");
    $key = $month->format('Y-m');

    $monthlySummary[] = [
        'month' => $month->format('F Y'),
        'entries' => (int) ($entriesByMonth[$key] ?? 0),
        'exits' => (int) ($exitsByMonth[$key]['exits'] ?? 0),
        'revenue' => (float) ($revenueByMonth[$key] ?? 0),
        'hours' => $exitsByMonth[$key]['hours'] ?? null
    ];
}

// Export the same monthly figures displayed below.
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="parkzen-monthly-report.csv"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Month', 'Total Entries', 'Total Exits',
        'Revenue (PHP)', 'Average Duration (Hours)'
    ]);

    foreach ($monthlySummary as $row) {
        fputcsv($output, [
            $row['month'],
            $row['entries'],
            $row['exits'],
            number_format($row['revenue'], 2, '.', ''),
            $row['hours'] === null
                ? ''
                : number_format((float) $row['hours'], 2, '.', '')
        ]);
    }

    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | ParkZen</title>

    <link rel="stylesheet" href="/pms/css/superadmin_dashboard.css?v=2">
    <link rel="stylesheet" href="/pms/css/reports.css?v=1">
</head>

<body>

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

            <a href="dashboard.php" class="nav-item">

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


            <a href="/pms/reports.php" class="nav-item active">

                <span class="nav-dot"></span>

                <strong class="nav-text">
                    Reports
                </strong>

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

            <a href="../logout.php" class="nav-item">

                <span class="nav-dot"></span>

                <span class="nav-text">
                    Logout
                </span>

            </a>
        </nav>

    </aside>

    <!-- MAIN CONTENT -->

    <main class="reports-page">
    <header class="reports-topbar">Reports</header>

    <div class="reports-content">
        <div class="reports-heading">
            <h1>Reports</h1>
            <p>View parking analytics and generate reports.</p>
        </div>

        <section class="reports-stats" aria-label="Today's statistics">
            <article class="report-stat">
                <h2>Today's Revenue</h2>
                <strong class="report-gold">
                    ₱<?= number_format($todayRevenue, 2) ?>
                </strong>
                <p>Completed payments today</p>
            </article>

            <article class="report-stat">
                <h2>Today's Entries</h2>
                <strong><?= number_format($todayEntries) ?></strong>
                <p>Vehicles entered today</p>
            </article>

            <article class="report-stat">
                <h2>Average Duration</h2>
                <strong>
                    <?= $averageHours === null
                        ? 'N/A'
                        : number_format((float) $averageHours, 1) . ' hrs' ?>
                </strong>
                <p>Vehicles that exited today</p>
            </article>

            <article class="report-stat">
                <h2>Current Occupancy</h2>
                <strong>
                    <?= $occupancy === null
                        ? 'N/A'
                        : number_format($occupancy, 1) . '%' ?>
                </strong>
                <p><?= $occupied ?> of <?= $capacity ?> slots occupied</p>
            </article>
        </section>

        <div class="reports-chart-grid">
            <section class="report-panel">
                <h2>Revenue Overview</h2>
                <p class="report-description">
                    Daily earnings for the last seven days
                </p>

                <div class="report-bars" aria-label="Daily revenue">
                    <?php foreach ($revenueDays as $day): ?>
                        <div class="report-bar-column">
                            <div class="report-bar-space">
                                <div
                                    class="report-bar"
                                    style="height: <?= ($day['amount'] / $maxRevenue) * 100 ?>%;"
                                    title="<?= reportEscape($day['date']) ?>: ₱<?= number_format($day['amount'], 2) ?>"
                                ></div>
                            </div>

                            <span class="report-bar-value">
                                ₱<?= number_format($day['amount'], 0) ?>
                            </span>

                            <strong><?= reportEscape($day['label']) ?></strong>
                            <small><?= reportEscape($day['date']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="report-panel">
                <h2>Peak Hours Analysis</h2>
                <p class="report-description">
                    Share of arrivals over the last seven days
                </p>

                <div class="report-peak-list">
                    <?php foreach ($peakGroups as $group): ?>
                        <?php
                        $share = $totalArrivals > 0
                            ? ($group['total'] / $totalArrivals) * 100
                            : 0;

                        $isPeak = $group['total'] > 0
                            && $group['total'] === $highestArrivals;
                        ?>

                        <div class="report-peak-row">
                            <span><?= reportEscape($group['label']) ?></span>

                            <div class="report-peak-track">
                                <div
                                    class="report-peak-fill <?= $isPeak ? 'is-peak' : '' ?>"
                                    style="width: <?= $share ?>%;"
                                ></div>
                            </div>

                            <strong><?= number_format($share, 0) ?>%</strong>
                            <small><?= $group['total'] ?> entries</small>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalArrivals === 0): ?>
                    <p class="report-empty">No arrivals recorded in this period.</p>
                <?php endif; ?>
            </section>
        </div>

        <section class="report-panel report-monthly">
            <div class="report-panel-heading">
                <div>
                    <h2>Monthly Performance Summary</h2>
                    <p class="report-description">
                        Last six months · current month through today
                    </p>
                </div>

                <a href="reports.php?export=csv" class="report-export">
                    Export CSV
                </a>
            </div>

            <div class="report-table-scroll">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th scope="col">Month</th>
                            <th scope="col">Total Entries</th>
                            <th scope="col">Total Exits</th>
                            <th scope="col">Revenue</th>
                            <th scope="col">Avg. Duration</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($monthlySummary as $row): ?>
                            <tr>
                                <th scope="row">
                                    <?= reportEscape($row['month']) ?>
                                </th>
                                <td><?= number_format($row['entries']) ?></td>
                                <td><?= number_format($row['exits']) ?></td>
                                <td class="report-gold">
                                    ₱<?= number_format($row['revenue'], 2) ?>
                                </td>
                                <td>
                                    <?= $row['hours'] === null
                                        ? 'N/A'
                                        : number_format((float) $row['hours'], 1) . ' hrs' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="report-footnote">
                Entries use entry dates; exits and average duration use exit
                dates; revenue uses payment dates. All dates are Philippine time.
            </p>
        </section>
    </div>
</main>

</body>
</html>