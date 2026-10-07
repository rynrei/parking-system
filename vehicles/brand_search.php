<?php

require_once "../config/database.php";

header("Content-Type: application/json");

$term = isset($_GET['term']) ? trim($_GET['term']) : "";

if ($term === "") {
    echo json_encode([]);
    exit;
}

$search = $term . "%";

$stmt = $conn->prepare("
    SELECT BrandID, BrandName
    FROM vehicle_brands
    WHERE BrandName LIKE ?
    ORDER BY BrandName ASC
    LIMIT 20
");

$stmt->bind_param("s", $search);
$stmt->execute();

$result = $stmt->get_result();

$brands = [];

while ($row = $result->fetch_assoc()) {
    $brands[] = $row;
}

echo json_encode($brands);