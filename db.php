<?php
require_once __DIR__ . '/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
  http_response_code(500);
  die(json_encode(['error' => 'DB connect failed']));
}
$conn->set_charset('utf8mb4');

function json_out($arr) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}
