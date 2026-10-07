<?php

session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";


// Get existing floors from parking slots
$floors = [];

$result = $conn->query("
    SELECT DISTINCT Floor
    FROM parkingslots
    ORDER BY Floor ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $floors[] = $row['Floor'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register Vehicle</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f4f4f4;
            padding: 40px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            margin-bottom: 25px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #333;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #555;
        }

        .suggestions {
            position: absolute;
            width: 100%;
            background: white;
            border: 1px solid #ccc;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .suggestion-item {
            padding: 10px;
            cursor: pointer;
        }

        .suggestion-item:hover {
            background: #f0f0f0;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Register Vehicle</h2>

    <form action="create_process.php" method="POST">

        <!-- Plate Number -->
        <div class="form-group">

            <label for="plate_number">
                Plate Number
            </label>

            <input
                type="text"
                id="plate_number"
                name="plate_number"
                placeholder="Enter plate number"
                required
            >

        </div>


        <!-- Brand -->
        <div class="form-group">

            <label for="brand">
                Brand
            </label>

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
                class="suggestions">
            </div>

        </div>


        <!-- Model -->
        <div class="form-group">

            <label for="model">
                Model
            </label>

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
                class="suggestions">
            </div>

        </div>


        <!-- Number of Wheels -->
        <div class="form-group">

            <label for="number_of_wheels">
                Number of Wheels
            </label>

            <input
                type="number"
                id="number_of_wheels"
                name="number_of_wheels"
                min="2"
                max="18"
                placeholder="Enter number of wheels"
                required
            >

        </div>


        <!-- Color -->
        <div class="form-group">

            <label for="color">
                Color
            </label>

            <input
                type="text"
                id="color"
                name="color"
                placeholder="Enter vehicle color"
                required
            >

        </div>


        <!-- Floor -->
        <div class="form-group">

            <label for="floor">
                Floor
            </label>

            <select
                id="floor"
                name="floor"
                required
            >

                <option value="">
                    Select floor
                </option>

                <?php foreach ($floors as $floor): ?>

                    <option value="<?= htmlspecialchars($floor) ?>">
                        Floor <?= htmlspecialchars($floor) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- Available Slot -->
        <div class="form-group">

            <label for="slot_id">
                Available Slot
            </label>

            <select
                id="slot_id"
                name="slot_id"
                required
                disabled
            >

                <option value="">
                    Select a floor first
                </option>

            </select>

        </div>


        <button type="submit">
            Register Vehicle
        </button>

    </form>

</div>


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

</body>
</html>