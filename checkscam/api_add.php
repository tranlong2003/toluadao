<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$type    = $data['type'] ?? '';
$value   = $data['value'] ?? '';
$title   = $data['title'] ?? '';
$excerpt = $data['excerpt'] ?? '';
$source  = $data['source'] ?? '';
$url     = $data['url'] ?? '';

$sql = "INSERT INTO reports (type,value,title,excerpt,source,url) VALUES (?,?,?,?,?,?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $type, $value, $title, $excerpt, $source, $url);

if ($stmt->execute()) {
    echo json_encode(["success"=>true]);
} else {
    echo json_encode(["success"=>false, "error"=>$conn->error]);
}
?>
