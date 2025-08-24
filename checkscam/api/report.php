<?php
header('Content-Type: application/json');

// Kết nối DB
$pdo = new PDO("mysql:host=localhost;dbname=checkscam;charset=utf8","root","");

// Lấy dữ liệu JSON
$data = json_decode(file_get_contents("php://input"), true);

$stk = trim($data['stk']);
$content = trim($data['content']);

if($stk == "" || $content == ""){
  echo json_encode(["success"=>false, "msg"=>"Thiếu dữ liệu"]);
  exit;
}

// Lưu vào DB
$stmt = $pdo->prepare("INSERT INTO reports (stk, content, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$stk, $content]);

echo json_encode(["success"=>true]);
