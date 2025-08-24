<?php
header("Content-Type: application/json");
include "db.php";

$type  = $_GET['type'] ?? '';
$value = $_GET['value'] ?? '';

$sql = "SELECT * FROM reports WHERE type=? AND value=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $type, $value);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
