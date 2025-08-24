<?php
// filepath: c:\xampp\htdocs\checkscam\api\stats.php
header('Content-Type: application/json');

// Kết nối DB
$pdo = new PDO("mysql:host=localhost;dbname=antiscam;charset=utf8","root","");

// Lấy IP user
$ip = $_SERVER['REMOTE_ADDR'];
$time = time();

// Cập nhật / chèn IP
$pdo->prepare("REPLACE INTO user_online (ip, last_active) VALUES (?, ?)")->execute([$ip, $time]);

// Tổng số user (unique IP)
$totalUsers = $pdo->query("SELECT COUNT(DISTINCT ip) FROM user_online")->fetchColumn();

// Online trong 5 phút gần nhất
$activeUsers = $pdo->prepare("SELECT COUNT(*) FROM user_online WHERE last_active >= ?");
$activeUsers->execute([$time - 300]); // 5 phút
$activeUsers = $activeUsers->fetchColumn();

// Trả JSON
echo json_encode([
  "total" => $totalUsers,
  "online" => $activeUsers
]);
