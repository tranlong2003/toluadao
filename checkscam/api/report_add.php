<?php
require_once __DIR__ . '/../db.php';

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$type    = $payload['type']    ?? '';
$value   = trim($payload['value']   ?? '');
$title   = trim($payload['title']   ?? '');
$excerpt = trim($payload['excerpt'] ?? '');
$source  = trim($payload['source']  ?? '');
$url     = trim($payload['url']     ?? '');

if (!in_array($type, ['phone','bank','link'], true) || $value==='' || $title==='') {
  http_response_code(400);
  json_out(['success'=>false,'msg'=>'Thiếu dữ liệu']);
}

$stmt = $conn->prepare("INSERT INTO reports (type,value,title,excerpt,source,url) VALUES (?,?,?,?,?,?)");
$stmt->bind_param('ssssss', $type,$value,$title,$excerpt,$source,$url);

if ($stmt->execute()) {
  // (Tuỳ chọn) cũng insert vào evidences để xuất hiện ngay
  $stmt2 = $conn->prepare("INSERT IGNORE INTO evidences (type,value,title,excerpt,source,url,published_at) VALUES (?,?,?,?,?,?,NOW())");
  $stmt2->bind_param('ssssss', $type,$value,$title,$excerpt,$source,$url);
  $stmt2->execute();

  json_out(['success'=>true]);
} else {
  json_out(['success'=>false,'msg'=>$conn->error]);
}
