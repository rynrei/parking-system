<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../../config/database.php";

function slotEscape($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// ========================================
// GET PARKING SLOTS AND CURRENT VEHICLES
// ========================================

$sql = "
    SELECT
        ps.SlotID,
        ps.Floor,
        ps.SlotNumber,
        ps.VehicleType,
        ps.Status,

        v.PlateNumber,
        v.Brand,
        v.Model,
        v.NumberOfWheels,
        v.Color

    FROM parkingslots ps

    LEFT JOIN parkinglogs pl
        ON pl.SlotID = ps.SlotID
        AND pl.TimeOut IS NULL

    LEFT JOIN vehicles v
        ON v.VehicleID = pl.VehicleID

    ORDER BY
        ps.Floor ASC,
        ps.SlotNumber ASC,
        ps.SlotID ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Failed to load parking slots.");
}

// ========================================
// GROUP EXISTING SLOTS BY FLOOR
// ========================================

$slots_by_floor = [];
$seenSlotIDs = [];

while ($row = $result->fetch_assoc()) {
    // Count and show each physical slot once.
    $slotID = (string) $row['SlotID'];

    if (isset($seenSlotIDs[$slotID])) {
        continue;
    }

    $seenSlotIDs[$slotID] = true;

    $floor = trim((string) $row['Floor']);
    $slots_by_floor[$floor][] = $row;
}

uksort($slots_by_floor, function ($a, $b) {
    return strnatcasecmp((string) $a, (string) $b);
});

$floorPrefixes = [
    '1' => 'A',
    '2' => 'B',
    '3' => 'C'
];

$totals = [
    'total' => 0,
    'available' => 0,
    'occupied' => 0,
    'overdue' => 0
];

foreach ($slots_by_floor as $floor => &$slots) {
    // Natural order: 1, 2, 3 ... 10, 11.
    usort($slots, function ($a, $b) {
        $comparison = strnatcasecmp(
            (string) $a['SlotNumber'],
            (string) $b['SlotNumber']
        );

        return $comparison !== 0
            ? $comparison
            : ((int) $a['SlotID'] <=> (int) $b['SlotID']);
    });

    foreach ($slots as $index => &$slot) {
        $status = strtolower(trim((string) $slot['Status']));

        if ($status === 'available') {
            $slot['DisplayStatus'] = 'Available';
            $slot['StatusClass'] = 'available';
        } elseif (in_array($status, ['overdue', 'expired'], true)) {
            $slot['DisplayStatus'] = ucfirst($status);
            $slot['StatusClass'] = 'overdue';
        } else {
            $slot['DisplayStatus'] = 'Occupied';
            $slot['StatusClass'] = 'occupied';
        }

        $prefix = $floorPrefixes[(string) $floor] ?? null;

        $slot['DisplayNumber'] = $prefix !== null
            ? $prefix . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)
            : $slot['SlotNumber'];

        $totals['total']++;
        $totals[$slot['StatusClass']]++;
    }

    unset($slot);
}

unset($slots);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Parking Slots | PARKZEN</title>

    <!-- Copy the dashboard's existing sidebar CSS link here. -->
    <link rel="stylesheet" href="/pms/css/superadmin_dashboard.css?v=2">
    <link rel="stylesheet" href="/pms/css/parking_slots.css">

</head>

<body>

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


            <a href="/pms/superadmin/parking_slots/index.php" class="nav-item active">

                <span class="nav-dot"></span>

                <strong class="nav-text">
                    Parking Slots
                </strong>

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

            <a href="../logout.php" class="nav-item logout-item">

                <span class="nav-dot logout-dot"></span>

                <span class="nav-text">
                    Logout
                </span>

            </a>
        </nav>

    </aside>

<main class="parking-page" id="parkingPage">

    <header class="parking-topbar">
        <span>Parking Slots</span>
    </header>

    <div class="parking-content">

        <div class="parking-heading">
            <div>
                <h1>Parking Slots</h1>
                <p>View and manage current slot availability.</p>
            </div>
        </div>

        <div class="parking-summary">

            <div class="parking-stat">
                <span class="parking-stat-label">Total Spaces</span>
                <strong id="slotTotal">
                    <?= $totals['total'] ?>
                </strong>
            </div>

            <div class="parking-stat parking-stat-available">
                <span class="parking-stat-label">
                    Available
                    <i aria-hidden="true"></i>
                </span>

                <strong id="slotAvailable">
                    <?= $totals['available'] ?>
                </strong>
            </div>

            <div class="parking-stat parking-stat-occupied">
                <span class="parking-stat-label">
                    Occupied
                    <i aria-hidden="true"></i>
                </span>

                <strong id="slotOccupied">
                    <?= $totals['occupied'] ?>
                </strong>
            </div>

            <div class="parking-stat parking-stat-overdue">
                <span class="parking-stat-label">
                    Overdue / Expired
                    <i aria-hidden="true"></i>
                </span>

                <strong id="slotOverdue">
                    <?= $totals['overdue'] ?>
                </strong>
            </div>

        </div>

        <div class="parking-toolbar">

            <div class="parking-legend" aria-label="Slot status colors">
                <span>
                    <i class="available" aria-hidden="true"></i>
                    Available
                </span>

                <span>
                    <i class="occupied" aria-hidden="true"></i>
                    Occupied
                </span>

                <span>
                    <i class="overdue" aria-hidden="true"></i>
                    Overdue / Expired
                </span>
            </div>

            <div class="parking-filter">
                <label for="floorFilter">Floor</label>

                <select id="floorFilter">
                    <option value="all">All Floors</option>

                    <?php foreach ($slots_by_floor as $floor => $slots): ?>
                        <option value="<?= slotEscape($floor) ?>">
                            Floor <?= slotEscape($floor) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <p class="parking-filter-note" id="floorSummary" aria-live="polite">
            Showing all floors · <?= $totals['total'] ?> spaces
        </p>

        <?php if (empty($slots_by_floor)): ?>

            <div class="parking-empty">
                No parking slots found.
            </div>

        <?php else: ?>

            <?php foreach ($slots_by_floor as $floor => $slots): ?>

                <section
                    class="parking-floor"
                    data-floor="<?= slotEscape($floor) ?>"
                >

                    <div class="parking-floor-heading">
                        <div>
                            <h2>Floor <?= slotEscape($floor) ?></h2>

                            <p>
                                Vehicle slots
                                <?= slotEscape($slots[0]['DisplayNumber']) ?>
                                to
                                <?= slotEscape(
                                    $slots[count($slots) - 1]['DisplayNumber']
                                ) ?>
                            </p>
                        </div>

                        <span class="parking-floor-count">
                            <?= count($slots) ?> spaces
                        </span>
                    </div>

                    <div class="parking-slot-grid">

                        <?php foreach ($slots as $slot): ?>

                            <article
                                class="parking-slot <?= slotEscape($slot['StatusClass']) ?>"
                                data-slot-id="<?= slotEscape($slot['SlotID']) ?>"
                                data-status="<?= slotEscape($slot['StatusClass']) ?>"

                                <?php if ($slot['PlateNumber'] !== null): ?>
                                    role="button"
                                    tabindex="0"
                                    aria-label="View vehicle in slot <?= slotEscape($slot['DisplayNumber']) ?>"
                                    aria-haspopup="dialog"

                                    data-number="<?= slotEscape($slot['DisplayNumber']) ?>"
                                    data-floor="<?= slotEscape($floor) ?>"
                                    data-label="<?= slotEscape($slot['DisplayStatus']) ?>"
                                    data-plate="<?= slotEscape($slot['PlateNumber']) ?>"
                                    data-brand="<?= slotEscape($slot['Brand']) ?>"
                                    data-model="<?= slotEscape($slot['Model']) ?>"
                                    data-color="<?= slotEscape($slot['Color']) ?>"
                                    data-wheels="<?= slotEscape($slot['NumberOfWheels']) ?>"
                                <?php endif; ?>
                            >

                                <div class="parking-slot-top">
                                    <h3>
                                        <?= slotEscape($slot['DisplayNumber']) ?>
                                    </h3>

                                </div>

                                <?php if ($slot['PlateNumber'] !== null): ?>

                                    <?php if ($slot['PlateNumber'] !== null): ?>

                                <?php endif; ?>

                                <?php endif; ?>

                                <div class="parking-slot-bottom">

                                    <span class="parking-slot-status">
                                        <?= slotEscape($slot['DisplayStatus']) ?>
                                    </span>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

<dialog class="parking-details-panel" id="parkingDetailsPanel"
        aria-labelledby="parkingDetailsTitle">

    <div class="parking-panel-header">
        <div>
            <span class="parking-panel-caption">VEHICLE DETAILS</span>
            <h2 id="parkingDetailsTitle">Slot details</h2>
        </div>

        <button type="button" class="parking-panel-close"
                id="closeParkingPanel" aria-label="Close vehicle details"
                autofocus>
            &times;
        </button>
    </div>

    <div class="parking-panel-body">
        <div class="parking-panel-location">
            <span id="panelFloor"></span>
            <span id="panelStatus"></span>
        </div>

        <div class="parking-panel-plate">
            <span>PLATE NUMBER</span>
            <strong id="panelPlate"></strong>
        </div>

        <dl class="parking-panel-fields">
            <div>
                <dt>Brand</dt>
                <dd id="panelBrand"></dd>
            </div>

            <div>
                <dt>Model</dt>
                <dd id="panelModel"></dd>
            </div>

            <div>
                <dt>Color</dt>
                <dd id="panelColor"></dd>
            </div>

            <div>
                <dt>Number of wheels</dt>
                <dd id="panelWheels"></dd>
            </div>
        </dl>
    </div>
</dialog>

<script>
(() => {
    const page = document.getElementById('parkingPage');
    const panel = document.getElementById('parkingDetailsPanel');
    const closeButton = document.getElementById('closeParkingPanel');

    const fields = {
        plate: document.getElementById('panelPlate'),
        brand: document.getElementById('panelBrand'),
        model: document.getElementById('panelModel'),
        color: document.getElementById('panelColor'),
        wheels: document.getElementById('panelWheels')
    };

    function openPanel(slot) {
        document.getElementById('parkingDetailsTitle').textContent =
            `Slot ${slot.dataset.number}`;

        document.getElementById('panelFloor').textContent =
            `Floor ${slot.dataset.floor}`;

        document.getElementById('panelStatus').textContent =
            slot.dataset.label;

        Object.entries(fields).forEach(([key, element]) => {
            element.textContent = slot.dataset[key] || '—';
        });

        if (!panel.open) {
            panel.showModal();
        }
    }

    page.addEventListener('click', event => {
        const slot = event.target.closest('.parking-slot[data-plate]');

        if (slot) {
            openPanel(slot);
        }
    });

    page.addEventListener('keydown', event => {
        const slot = event.target.closest('.parking-slot[data-plate]');

        if (slot && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            openPanel(slot);
        }
    });

    closeButton.addEventListener('click', () => panel.close());

    // Close when clicking the shaded area outside the panel.
    panel.addEventListener('click', event => {
        const bounds = panel.getBoundingClientRect();

        if (
            event.target === panel &&
            (
                event.clientX < bounds.left ||
                event.clientX > bounds.right ||
                event.clientY < bounds.top ||
                event.clientY > bounds.bottom
            )
        ) {
            panel.close();
        }
    });
})();
</script>

<script>
(() => {
    const page = document.getElementById('parkingPage');
    const filter = page.querySelector('#floorFilter');
    const floors = [...page.querySelectorAll('.parking-floor')];

    const counters = {
        total: page.querySelector('#slotTotal'),
        available: page.querySelector('#slotAvailable'),
        occupied: page.querySelector('#slotOccupied'),
        overdue: page.querySelector('#slotOverdue')
    };

    const summary = page.querySelector('#floorSummary');

    function applyFloorFilter() {
        const selectedFloor = filter.value;

        const counts = {
            total: 0,
            available: 0,
            occupied: 0,
            overdue: 0
        };

        floors.forEach(floor => {
            const visible =
                selectedFloor === 'all' ||
                floor.dataset.floor === selectedFloor;

            floor.hidden = !visible;

            if (!visible) return;

            floor.querySelectorAll('.parking-slot').forEach(slot => {
                counts.total++;

                const status = slot.dataset.status;

                if (Object.hasOwn(counts, status)) {
                    counts[status]++;
                }
            });
        });

        Object.keys(counters).forEach(key => {
            counters[key].textContent = counts[key];
        });

        const label = selectedFloor === 'all'
            ? 'all floors'
            : `Floor ${selectedFloor}`;

        summary.textContent =
            `Showing ${label} · ${counts.total} spaces`;
    }

    filter.addEventListener('change', applyFloorFilter);
    applyFloorFilter();
})();
</script>

</body>
</html>