<?php
$pdo = new PDO("mysql:host=localhost;dbname=checkscam;charset=utf8", "root", "");
$keyword = $_GET['keyword'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM reports 
                       WHERE status='approved' 
                       AND (bank_account LIKE ? OR phone LIKE ?)
                       ORDER BY created_at DESC");
$stmt->execute(["%$keyword%", "%$keyword%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
