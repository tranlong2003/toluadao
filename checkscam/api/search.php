<?php
require_once __DIR__ . '/../db.php';

$type  = $_GET['type']  ?? '';
$value = trim($_GET['value'] ?? '');

if (!in_array($type, ['phone','bank','link'], true) || $value === '') {
  http_response_code(400);
  json_out(['error' => 'Bad request']);
}

// === Ghi lịch sử (đơn giản, không chặn trùng) ===
$h = $conn->prepare("INSERT INTO history (type, value) VALUES (?,?)");
$h->bind_param('ss', $type, $value);
$h->execute();

// === Lấy dữ liệu nội bộ: reports + evidences ===
$rows = [];

// 1) Reports (do user gửi)
$stmt = $conn->prepare("SELECT title, excerpt, source, url, created_at AS published_at FROM reports WHERE type=? AND value=? ORDER BY created_at DESC LIMIT 200");
$stmt->bind_param('ss', $type, $value);
$stmt->execute();
$r = $stmt->get_result();
while ($x = $r->fetch_assoc()) { $x['origin']='report'; $rows[]=$x; }

// 2) Evidences (crawler/API ngoài đã lưu)
$stmt2 = $conn->prepare("SELECT title, excerpt, source, url, published_at FROM evidences WHERE type=? AND value=? ORDER BY COALESCE(published_at, created_at) DESC LIMIT 300");
$stmt2->bind_param('ss', $type, $value);
$stmt2->execute();
$r2 = $stmt2->get_result();
while ($x = $r2->fetch_assoc()) { $x['origin']='evidence'; $rows[]=$x; }

// === (Tùy chọn) Google CSE ===
function domain_of($url) {
  $h = parse_url($url, PHP_URL_HOST) ?: '';
  return preg_replace('/^www\./', '', strtolower($h));
}

if (GOOGLE_CSE_KEY && GOOGLE_CSE_CX) {
  $q = urlencode($value . ' lừa đảo OR scam OR cảnh báo');
  $url = "https://www.googleapis.com/customsearch/v1?key=" . urlencode(GOOGLE_CSE_KEY)
       . "&cx=" . urlencode(GOOGLE_CSE_CX) . "&num=10&q=" . $q;

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
  ]);
  $raw = curl_exec($ch);
  curl_close($ch);

  $ok = @json_decode($raw, true);
  if (!empty($ok['items'])) {
    foreach ($ok['items'] as $it) {
      $u = $it['link'] ?? '';
      $d = domain_of($u);
      if (!$u || !in_array($d, $GLOBALS['TRUSTED_SITES'], true)) continue;
      $rows[] = [
        'title' => $it['title'] ?? '(không tiêu đề)',
        'excerpt' => $it['snippet'] ?? '',
        'source' => $d,
        'url' => $u,
        'published_at' => null,
        'origin' => 'google'
      ];
    }
  }
}

// === Chống trùng (ưu tiên theo thứ tự: evidences > reports > google) ===
$seen = [];
$out  = [];
foreach ($rows as $item) {
  $key = strtolower(($item['url'] ?: $item['title']));
  if (isset($seen[$key])) continue;
  $seen[$key] = 1;
  $out[] = $item;
}

// === Sắp xếp: có ngày trước, mới nhất lên đầu; sau đó theo origin ===
usort($out, function($a,$b){
  $da = strtotime($a['published_at'] ?? '') ?: 0;
  $db = strtotime($b['published_at'] ?? '') ?: 0;
  if ($da !== $db) return $db <=> $da;
  $prio = ['evidence'=>3,'report'=>2,'google'=>1];
  return ($prio[$b['origin']] ?? 0) <=> ($prio[$a['origin']] ?? 0);
});

// === Tính thống kê mức độ ===
$stats = [
  'total' => count($out),
  'from_reports' => count(array_filter($out, fn($x)=>$x['origin']==='report')),
  'from_external' => count(array_filter($out, fn($x)=>$x['origin']!=='report')),
  'last' => $out[0]['published_at'] ?? null
];

json_out([
  'stats' => $stats,
  'evidences' => $out
]);
