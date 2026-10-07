<?php

require_once "../config/database.php";

header("Content-Type: application/json");

$brand_id = isset($_GET['brand_id']) ? (int) $_GET['brand_id'] : 0;
$term = isset($_GET['term']) ? trim($_GET['term']) : "";

if ($brand_id <= 0 || $term === "") {
    echo json_encode([]);
    exit;
}

$search = $term . "%";

$stmt = $conn->prepare("
    SELECT ModelID, ModelName
    FROM vehicle_models
    WHERE BrandID = ?
      AND ModelName LIKE ?
    ORDER BY ModelName ASC
    LIMIT 20
");

$stmt->bind_param("is", $brand_id, $search);
$stmt->execute();

$result = $stmt->get_result();

$models = [];

while ($row = $result->fetch_assoc()) {
    $models[] = $row;
}

echo json_encode($models);
?>