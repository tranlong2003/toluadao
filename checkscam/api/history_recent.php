<?php
require_once __DIR__ . '/../db.php';

$limit = max(1, min(30, intval($_GET['limit'] ?? 10)));
$res = $conn->query("SELECT type, value, created_at FROM history ORDER BY id DESC LIMIT {$limit}");

$out=[];
while ($r = $res->fetch_assoc()) $out[] = $r;
json_out($out);
