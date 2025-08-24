<?php
require_once __DIR__ . '/../db.php';

// Ví dụ minh hoạ: giả sử 1 trang có dạng /search?q=... và HTML có .post
// Bạn phải tự sửa URL & selector cho đúng site cho phép crawl.
function fetch_html($url){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_USERPWD=>'',
    CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_TIMEOUT=>15,
    CURLOPT_SSL_VERIFYPEER=>false,
    CURLOPT_SSL_VERIFYHOST=>0,
  ]);
  $html = curl_exec($ch);
  curl_close($ch);
  return $html ?: '';
}

function save_ev($type,$value,$title,$excerpt,$source,$url,$published_at=null){
  global $conn;
  $stmt = $conn->prepare("INSERT IGNORE INTO evidences (type,value,title,excerpt,source,url,published_at) VALUES (?,?,?,?,?,?,?)");
  $stmt->bind_param('sssssss',$type,$value,$title,$excerpt,$source,$url,$published_at);
  $stmt->execute();
}

// Ví dụ crawl theo 1 danh sách value phổ biến (bạn có thể lấy từ history)
$values = [];
$r = $conn->query("SELECT DISTINCT value FROM history ORDER BY id DESC LIMIT 30");
while ($x = $r->fetch_assoc()) $values[] = $x['value'];

foreach ($values as $v) {
  // Ví dụ site giả định:
  $url = "https://example.com/search?q=" . urlencode($v . " lừa đảo");
  $html = fetch_html($url);
  if (!$html) continue;

  // Rất đơn giản: match các link (bạn thay bằng DOM/regex phù hợp)
  if (preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/i', $html, $m, PREG_SET_ORDER)) {
    foreach ($m as $a) {
      $link = html_entity_decode($a[1]);
      $title = strip_tags($a[2]);
      if (!$link || !$title) continue;
      save_ev('phone', $v, $title, '', parse_url($link, PHP_URL_HOST), $link, null);
    }
  }
}

echo "DONE\n";
