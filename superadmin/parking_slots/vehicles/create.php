<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['id'])) {
    header("Location: /pms/login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header("Location: /pms/access_denied.php");
    exit();
}

require_once  '../../../config/database.php';

date_default_timezone_set('Asia/Manila');

function entryEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Floors for the entry form.
$floors = [];

$floorResult = $conn->query(
    "SELECT DISTINCT Floor FROM parkingslots ORDER BY Floor ASC"
);

while ($row = $floorResult->fetch_assoc()) {
    $floors[] = $row['Floor'];
}

// Today's entries, five records per page.
$today = new DateTimeImmutable('today');
$start = $today->format('Y-m-d H:i:s');
$end = $today->modify('+1 day')->format('Y-m-d H:i:s');

$countStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM parkinglogs
     WHERE TimeIn >= ? AND TimeIn < ?"
);

$countStmt->bind_param('ss', $start, $end);
$countStmt->execute();

$totalEntries = (int) $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$perPage = 5;
$totalPages = max(1, (int) ceil($totalEntries / $perPage));

$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = min($totalPages, max(1, $requestedPage ?: 1));
$offset = ($page - 1) * $perPage;

$entryStmt = $conn->prepare(
    "SELECT
        pl.LogID,
        COALESCE(pl.PlateNumberSnapshot, v.PlateNumber) AS PlateNumber,
        ps.VehicleType,
        ps.Floor,
        ps.SlotNumber,
        pl.TimeIn,
        pl.TimeOut,
        pl.Status
     FROM parkinglogs pl
     LEFT JOIN vehicles v ON v.VehicleID = pl.VehicleID
     LEFT JOIN parkingslots ps ON ps.SlotID = pl.SlotID
     WHERE pl.TimeIn >= ? AND pl.TimeIn < ?
     ORDER BY pl.TimeIn DESC, pl.LogID DESC
     LIMIT ? OFFSET ?"
);

$entryStmt->bind_param('ssii', $start, $end, $perPage, $offset);
$entryStmt->execute();

$recentEntries = $entryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$entryStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vehicle Entry | ParkZen</title>

        <link rel="stylesheet" href="/pms/css/superadmin_dashboard.css?v=2">
        <link rel="stylesheet" href="/pms/css/vehicle_entry.css?v=1">

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


            <a href="/pms/superadmin/parking_slots/vehicles/create.php" class="nav-item active">

                <span class="nav-dot"></span>

                <strong class="nav-text">
                    Vehicle Entry
                </strong>

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

<main class="entry-page">
    <header class="entry-topbar">Vehicle Entry</header>

    <div class="entry-content">
        <div class="entry-heading">
            <h1>Vehicle Entry</h1>
            <p>Log incoming vehicles and assign parking slots.</p>
        </div>

        <div class="entry-grid">
            <!-- ENTRY FORM -->
            <section class="entry-card">
                <h2>Log New Entry</h2>
                <p class="entry-description">
                    Submit details for incoming vehicles.
                </p>

                <form
                    id="vehicleEntryForm"
                    action="create_process.php"
                    method="POST"
                >
                    <div class="entry-field">
                        <label for="plate_number">Plate Number *</label>
                        <input
                            type="text"
                            id="plate_number"
                            name="plate_number"
                            placeholder="ABC-1234"
                            required
                        >
                    </div>

                    <div class="entry-form-row">
                        <div class="entry-field">
                            <label for="brand">Brand *</label>
                            <input
                                type="text"
                                id="brand"
                                name="brand"
                                placeholder="Type vehicle brand"
                                autocomplete="off"
                                required
                            >
                            <div
                                id="brandSuggestions"
                                class="suggestions"
                            ></div>
                        </div>

                        <div class="entry-field">
                            <label for="model">Model *</label>
                            <input
                                type="text"
                                id="model"
                                name="model"
                                placeholder="Select a brand first"
                                autocomplete="off"
                                required
                                disabled
                            >
                            <div
                                id="modelSuggestions"
                                class="suggestions"
                            ></div>
                        </div>
                    </div>

                    <div class="entry-field">
                        <label for="color">Color *</label>
                        <input
                            type="text"
                            id="color"
                            name="color"
                            placeholder="Enter vehicle color"
                            required
                        >
                    </div>

                    <div class="entry-field">
                        <label for="number_of_wheels">Number of Wheels *</label>

                        <div class="entry-wheel-options">
                            <button
                                type="button"
                                class="entry-wheel"
                                data-wheels="2"
                                aria-pressed="false"
                            >2 wheels</button>

                            <button
                                type="button"
                                class="entry-wheel"
                                data-wheels="4"
                                aria-pressed="false"
                            >4 wheels</button>

                            <button
                                type="button"
                                class="entry-wheel"
                                data-wheels="6"
                                aria-pressed="false"
                            >6+ wheels</button>
                        </div>

                        <input
                            type="number"
                            id="number_of_wheels"
                            name="number_of_wheels"
                            min="2"
                            max="18"
                            step="1"
                            placeholder="Exact number of wheels"
                            required
                        >

                        <small class="entry-help">
                            For 6+ wheels, enter the exact number below.
                        </small>
                    </div>

                    <div class="entry-field">
                        <label for="floor">Floor *</label>
                        <select id="floor" name="floor" required>
                            <option value="">Select floor</option>

                            <?php foreach ($floors as $floor): ?>
                                <option value="<?= entryEscape($floor) ?>">
                                    Floor <?= entryEscape($floor) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="entry-field">
                        <label for="slot_id">Available Slot *</label>
                        <select
                            id="slot_id"
                            name="slot_id"
                            required
                            disabled
                        >
                            <option value="">Select a floor first</option>
                        </select>
                    </div>

                    <div class="entry-actions">
                        <button type="submit" class="entry-submit">
                            Log Entry
                        </button>

                        <button type="reset" class="entry-clear">
                            Clear
                        </button>
                    </div>
                </form>
            </section>

            <!-- REAL DATABASE ENTRIES -->
            <section class="entry-card entry-recent">
                <h2>Recent Entries Today</h2>
                <p class="entry-description">
                    Today's recorded vehicle check-ins.
                </p>

                <div class="entry-table-scroll">
                    <table class="entry-table">
                        <thead>
                            <tr>
                                <th>Entry ID</th>
                                <th>Plate Number</th>
                                <th>Slot Type</th>
                                <th>Slot</th>
                                <th>Time In</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!$recentEntries): ?>
                                <tr>
                                    <td colspan="6" class="entry-empty">
                                        No vehicle entries today.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentEntries as $entry): ?>
                                    <?php
                                    $active = $entry['TimeOut'] === null;
                                    ?>
                                    <tr>
                                        <td class="entry-id">
                                            ENT-<?= (int) $entry['LogID'] ?>
                                        </td>

                                        <td class="entry-plate">
                                            <?= entryEscape($entry['PlateNumber']) ?>
                                        </td>

                                        <td>
                                            <?= entryEscape($entry['VehicleType'] ?? '—') ?>
                                        </td>

                                        <td class="entry-slot">
                                            <?= entryEscape($entry['SlotNumber'] ?? '—') ?>
                                            <small>
                                                Floor <?= entryEscape($entry['Floor'] ?? '—') ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= entryEscape(
                                                (new DateTimeImmutable($entry['TimeIn']))
                                                    ->format('h:i A')
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="entry-status <?= $active ? 'is-active' : 'is-exited' ?>">
                                                <?= $active ? 'Active' : 'Exited' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="entry-table-footer">
                    <p>
                        <?php if ($totalEntries > 0): ?>
                            Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalEntries) ?>
                            of <?= $totalEntries ?> entries
                        <?php else: ?>
                            0 entries
                        <?php endif; ?>
                    </p>

                    <nav class="entry-pagination" aria-label="Entry pages">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>">Previous</a>
                        <?php endif; ?>

                        <span><?= $page ?> / <?= $totalPages ?></span>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </section>
        </div>
    </div>
</main>


<script>

// =====================================================
// BRAND / MODEL AUTOCOMPLETE
// =====================================================

const brandInput = document.getElementById("brand");
const modelInput = document.getElementById("model");

const brandSuggestions = document.getElementById("brandSuggestions");
const modelSuggestions = document.getElementById("modelSuggestions");


// BRAND SEARCH
brandInput.addEventListener("input", function () {

    const term = this.value.trim();

    modelInput.value = "";
    modelInput.disabled = true;

    modelSuggestions.style.display = "none";

    if (term.length === 0) {

        brandSuggestions.style.display = "none";

        delete brandInput.dataset.brandid;

        return;
    }

    fetch(
        "brand_search.php?term=" +
        encodeURIComponent(term)
    )

    .then(response => response.json())

    .then(data => {

        brandSuggestions.innerHTML = "";

        if (data.length === 0) {

            brandSuggestions.style.display = "none";

            return;
        }

        data.forEach(brand => {

            const item = document.createElement("div");

            item.className = "suggestion-item";
            item.textContent = brand.BrandName;

            item.addEventListener("click", function () {

                brandInput.value = brand.BrandName;

                brandInput.dataset.brandid = brand.BrandID;

                brandSuggestions.style.display = "none";

                modelInput.disabled = false;

                modelInput.placeholder =
                    "Type vehicle model";

                modelInput.value = "";

            });

            brandSuggestions.appendChild(item);

        });

        brandSuggestions.style.display = "block";

    })

    .catch(error => {
        console.error("Brand search error:", error);
    });

});


// MODEL SEARCH
modelInput.addEventListener("input", function () {

    const term = this.value.trim();

    const brandID = brandInput.dataset.brandid;

    if (!brandID) {
        return;
    }

    if (term.length === 0) {

        modelSuggestions.style.display = "none";

        return;
    }

    fetch(
        "model_search.php?brand_id=" +
        encodeURIComponent(brandID) +
        "&term=" +
        encodeURIComponent(term)
    )

    .then(response => response.json())

    .then(data => {

        modelSuggestions.innerHTML = "";

        if (data.length === 0) {

            modelSuggestions.style.display = "none";

            return;
        }

        data.forEach(model => {

            const item = document.createElement("div");

            item.className = "suggestion-item";
            item.textContent = model.ModelName;

            item.addEventListener("click", function () {

                modelInput.value = model.ModelName;

                modelSuggestions.style.display = "none";

            });

            modelSuggestions.appendChild(item);

        });

        modelSuggestions.style.display = "block";

    })

    .catch(error => {
        console.error("Model search error:", error);
    });

});


// CLOSE BRAND/MODEL SUGGESTIONS
document.addEventListener("click", function (event) {

    if (
        !brandInput.contains(event.target) &&
        !brandSuggestions.contains(event.target)
    ) {

        brandSuggestions.style.display = "none";

    }

    if (
        !modelInput.contains(event.target) &&
        !modelSuggestions.contains(event.target)
    ) {

        modelSuggestions.style.display = "none";

    }

});


// =====================================================
// FLOOR / AVAILABLE SLOT
// =====================================================

const floorSelect = document.getElementById("floor");
const slotSelect = document.getElementById("slot_id");

floorSelect.addEventListener("change", function () {

    const floor = this.value;

    slotSelect.innerHTML =
        '<option value="">Loading slots...</option>';

    slotSelect.disabled = true;

    if (floor === "") {

        slotSelect.innerHTML =
            '<option value="">Select a floor first</option>';

        return;
    }

    fetch(
        "available_slots.php?floor=" +
        encodeURIComponent(floor)
    )

    .then(response => response.json())

    .then(data => {

        slotSelect.innerHTML = "";

        if (data.length === 0) {

            slotSelect.innerHTML =
                '<option value="">No available slots</option>';

            slotSelect.disabled = true;

            return;
        }

        const defaultOption = document.createElement("option");

        defaultOption.value = "";
        defaultOption.textContent = "Select available slot";

        slotSelect.appendChild(defaultOption);

        data.forEach(slot => {

            const option = document.createElement("option");

            option.value = slot.SlotID;

            option.textContent =
                slot.SlotNumber;

            slotSelect.appendChild(option);

        });

        slotSelect.disabled = false;

    })

    .catch(error => {

        console.error("Slot search error:", error);

        slotSelect.innerHTML =
            '<option value="">Failed to load slots</option>';

        slotSelect.disabled = true;

    });

});

</script>

<script>
const entryForm = document.getElementById("vehicleEntryForm");
const wheelsInput = document.getElementById("number_of_wheels");
const wheelButtons = document.querySelectorAll(".entry-wheel");

function updateWheelSelection() {
    const wheels = Number(wheelsInput.value);

    wheelButtons.forEach(button => {
        const option = Number(button.dataset.wheels);
        const selected = option === 6
            ? wheels >= 6
            : wheels === option;

        button.classList.toggle("is-selected", selected);
        button.setAttribute("aria-pressed", String(selected));
    });
}

wheelButtons.forEach(button => {
    button.addEventListener("click", () => {
        wheelsInput.value = button.dataset.wheels;
        updateWheelSelection();

        if (button.dataset.wheels === "6") {
            wheelsInput.focus();
        }
    });
});

wheelsInput.addEventListener("input", updateWheelSelection);

entryForm.addEventListener("reset", () => {
    // Run after the browser resets the form fields.
    setTimeout(() => {
        delete brandInput.dataset.brandid;

        modelInput.disabled = true;
        modelInput.placeholder = "Select a brand first";

        brandSuggestions.replaceChildren();
        modelSuggestions.replaceChildren();

        brandSuggestions.style.display = "none";
        modelSuggestions.style.display = "none";

        slotSelect.replaceChildren(
            new Option("Select a floor first", "")
        );
        slotSelect.disabled = true;

        updateWheelSelection();
    }, 0);
});
</script>

<script>
const form = document.getElementById("vehicleEntryForm");
const brandInput = document.getElementById("brand");
const modelInput = document.getElementById("model");
const brandSuggestions = document.getElementById("brandSuggestions");
const modelSuggestions = document.getElementById("modelSuggestions");
const floorSelect = document.getElementById("floor");
const slotSelect = document.getElementById("slot_id");
const wheelsInput = document.getElementById("number_of_wheels");
const wheelButtons = document.querySelectorAll(".entry-wheel");

const endpointBase = "/pms/superadmin/parking_slots/vehicles/";

let brandRequest = 0;
let modelRequest = 0;
let slotRequest = 0;

// Visible messages for loading errors.
const message = document.createElement("p");
message.setAttribute("role", "alert");
message.style.cssText =
    "color:#b91c1c;font-size:12px;margin:12px 0;line-height:1.5;";
form.prepend(message);

function showError(text) {
    message.textContent = text;
}

async function getRows(filename, params) {
    const url = new URL(endpointBase + filename, window.location.origin);
    url.search = new URLSearchParams(params).toString();

    const response = await fetch(url, {
        cache: "no-store",
        credentials: "same-origin"
    });

    if (!response.ok) {
        throw new Error(filename + " returned HTTP " + response.status);
    }

    if (response.redirected) {
        throw new Error(filename + " redirected. Try logging in again.");
    }

    let data;

    try {
        data = await response.json();
    } catch {
        throw new Error(
            filename + " did not return JSON. Check that PHP file for errors."
        );
    }

    if (!Array.isArray(data)) {
        throw new Error(filename + " returned an unexpected response.");
    }

    return data;
}

function hideSuggestions(box) {
    box.replaceChildren();
    box.style.display = "none";
}

function addSuggestion(box, label, onSelect) {
    const item = document.createElement("button");
    item.type = "button";
    item.className = "suggestion-item";
    item.textContent = label;
    item.style.cssText =
        "display:block;width:100%;border:0;background:none;text-align:left;";
    item.addEventListener("click", onSelect);
    box.appendChild(item);
}

// BRAND SEARCH
brandInput.addEventListener("input", async () => {
    const request = ++brandRequest;
    ++modelRequest;

    delete brandInput.dataset.brandid;
    brandInput.setCustomValidity("");

    modelInput.value = "";
    modelInput.disabled = true;
    modelInput.placeholder = "Select a brand first";

    hideSuggestions(brandSuggestions);
    hideSuggestions(modelSuggestions);
    showError("");

    const term = brandInput.value.trim();
    if (!term) return;

    try {
        const brands = await getRows("brand_search.php", { term });
        if (request !== brandRequest) return;

        brands.forEach(brand => {
            addSuggestion(brandSuggestions, brand.BrandName, () => {
                ++brandRequest;
                ++modelRequest;

                brandInput.value = brand.BrandName;
                brandInput.dataset.brandid = brand.BrandID;
                brandInput.setCustomValidity("");

                hideSuggestions(brandSuggestions);
                hideSuggestions(modelSuggestions);

                modelInput.value = "";
                modelInput.disabled = false;
                modelInput.placeholder = "Type vehicle model";
                modelInput.focus();
            });
        });

        if (brands.length) {
            brandSuggestions.style.display = "block";
        }
    } catch (error) {
        if (request === brandRequest) showError(error.message);
    }
});

// MODEL SEARCH
modelInput.addEventListener("input", async () => {
    const request = ++modelRequest;
    hideSuggestions(modelSuggestions);
    showError("");

    const term = modelInput.value.trim();
    const brandID = brandInput.dataset.brandid;

    if (!term || !brandID) return;

    try {
        const models = await getRows("model_search.php", {
            brand_id: brandID,
            term
        });

        if (request !== modelRequest) return;

        models.forEach(model => {
            addSuggestion(modelSuggestions, model.ModelName, () => {
                ++modelRequest;
                modelInput.value = model.ModelName;
                hideSuggestions(modelSuggestions);
            });
        });

        if (models.length) {
            modelSuggestions.style.display = "block";
        }
    } catch (error) {
        if (request === modelRequest) showError(error.message);
    }
});

// AVAILABLE SLOTS
floorSelect.addEventListener("change", async () => {
    const request = ++slotRequest;
    const floor = floorSelect.value;

    slotSelect.disabled = true;
    slotSelect.replaceChildren(
        new Option(floor ? "Loading slots..." : "Select a floor first", "")
    );
    showError("");

    if (!floor) return;

    try {
        const slots = await getRows("available_slots.php", { floor });
        if (request !== slotRequest) return;

        slotSelect.replaceChildren(
            new Option(
                slots.length ? "Select available slot" : "No available slots",
                ""
            )
        );

        slots.forEach(slot => {
            slotSelect.add(new Option(slot.SlotNumber, slot.SlotID));
        });

        slotSelect.disabled = slots.length === 0;
    } catch (error) {
        if (request !== slotRequest) return;

        slotSelect.replaceChildren(
            new Option("Failed to load slots", "")
        );
        showError(error.message);
    }
});

// WHEEL BUTTONS
function updateWheels() {
    const wheels = Number(wheelsInput.value);

    wheelButtons.forEach(button => {
        const option = Number(button.dataset.wheels);
        const selected = option === 6 ? wheels >= 6 : wheels === option;

        button.classList.toggle("is-selected", selected);
        button.setAttribute("aria-pressed", String(selected));
    });
}

wheelButtons.forEach(button => {
    button.addEventListener("click", () => {
        wheelsInput.value = button.dataset.wheels;
        updateWheels();

        if (button.dataset.wheels === "6") {
            wheelsInput.focus();
        }
    });
});

wheelsInput.addEventListener("input", updateWheels);

// CLOSE SUGGESTIONS
document.addEventListener("click", event => {
    if (
        !brandInput.contains(event.target) &&
        !brandSuggestions.contains(event.target)
    ) {
        brandSuggestions.style.display = "none";
    }

    if (
        !modelInput.contains(event.target) &&
        !modelSuggestions.contains(event.target)
    ) {
        modelSuggestions.style.display = "none";
    }
});

// SUBMIT TO YOUR ORIGINAL HANDLER
form.action = endpointBase + "create_process.php";
form.method = "POST";

form.addEventListener("submit", event => {
    if (!brandInput.dataset.brandid) {
        event.preventDefault();
        showError("Choose a brand from the suggestions first.");
        brandInput.focus();
        return;
    }

    if (modelInput.disabled || !modelInput.value.trim()) {
        event.preventDefault();
        showError("Choose a brand, then enter the vehicle model.");
        return;
    }

    if (slotSelect.disabled || !slotSelect.value) {
        event.preventDefault();
        showError("Select a floor and an available parking slot first.");
        return;
    }

    showError("");
});

// CLEAR FORM AND CANCEL OLD REQUEST RESULTS
form.addEventListener("reset", () => {
    ++brandRequest;
    ++modelRequest;
    ++slotRequest;

    setTimeout(() => {
        delete brandInput.dataset.brandid;
        brandInput.setCustomValidity("");

        modelInput.disabled = true;
        modelInput.placeholder = "Select a brand first";

        hideSuggestions(brandSuggestions);
        hideSuggestions(modelSuggestions);

        slotSelect.disabled = true;
        slotSelect.replaceChildren(
            new Option("Select a floor first", "")
        );

        showError("");
        updateWheels();
    }, 0);
});

updateWheels();

const colorInput = document.getElementById("color");

colorInput.addEventListener("input", function () {
    const start = this.selectionStart;
    const end = this.selectionEnd;

    this.value = this.value.toUpperCase();
    this.setSelectionRange(start, end);
});

</script>

</body>
</html>