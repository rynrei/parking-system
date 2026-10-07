<?php

require_once "../config/database.php";

$json_file = __DIR__ . "/vehicles.json";

if (!file_exists($json_file)) {
    die("vehicles.json was not found.");
}

$json_data = file_get_contents($json_file);
$data = json_decode($json_data, true);

if ($data === null) {
    die("Failed to read vehicles.json.");
}

if (!isset($data['makes'])) {
    die("Invalid JSON structure: 'makes' not found.");
}

$conn->begin_transaction();

try {

    // Prepare statements
    $brand_stmt = $conn->prepare("
        INSERT IGNORE INTO vehicle_brands (BrandName)
        VALUES (?)
    ");

    $brand_id_stmt = $conn->prepare("
        SELECT BrandID
        FROM vehicle_brands
        WHERE BrandName = ?
        LIMIT 1
    ");

    $model_stmt = $conn->prepare("
        INSERT IGNORE INTO vehicle_models (BrandID, ModelName)
        VALUES (?, ?)
    ");

    $brand_count = 0;
    $model_count = 0;

    foreach ($data['makes'] as $make) {

        if (!isset($make['name'])) {
            continue;
        }

        $brand_name = trim($make['name']);

        if ($brand_name === '') {
            continue;
        }

        // Insert brand
        $brand_stmt->bind_param("s", $brand_name);
        $brand_stmt->execute();

        // Get BrandID
        $brand_id_stmt->bind_param("s", $brand_name);
        $brand_id_stmt->execute();

        $result = $brand_id_stmt->get_result();
        $brand = $result->fetch_assoc();

        if (!$brand) {
            continue;
        }

        $brand_id = $brand['BrandID'];
        $brand_count++;

        // Insert models
        if (isset($make['models']) && is_array($make['models'])) {

            foreach ($make['models'] as $model) {

                if (!isset($model['name'])) {
                    continue;
                }

                $model_name = trim($model['name']);

                if ($model_name === '') {
                    continue;
                }

                $model_stmt->bind_param(
                    "is",
                    $brand_id,
                    $model_name
                );

                // Correct parameter types
                $model_stmt->bind_param(
                    "is",
                    $brand_id,
                    $model_name
                );

                $model_stmt->execute();

                if ($model_stmt->affected_rows > 0) {
                    $model_count++;
                }
            }
        }
    }

    $conn->commit();

    echo "<h2>Import completed successfully!</h2>";
    echo "<p>Brands processed: " . $brand_count . "</p>";
    echo "<p>Models inserted: " . $model_count . "</p>";
    echo "<p>You can now check the tables in phpMyAdmin.</p>";

} catch (Exception $e) {

    $conn->rollback();

    echo "<h2>Import failed.</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>