<?php

require_once "../config/database.php";

header("Content-Type: application/json");

$floor = isset($_GET['floor']) ? (int) $_GET['floor'] : 0;

if ($floor <= 0) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("
    SELECT SlotID, SlotNumber
    FROM parkingslots
    WHERE Floor = ?
      AND Status = 'Available'
    ORDER BY SlotNumber ASC
");

$stmt->bind_param("i", $floor);
$stmt->execute();

$result = $stmt->get_result();

$slots = [];

while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

$stmt->close();

echo json_encode($slots);
?>