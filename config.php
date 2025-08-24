<?php
// ====== DB ======
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'checkscam');

// ====== Google Custom Search (tùy chọn) ======
// Tạo key tại: https://console.cloud.google.com/apis/credentials
// Tạo CSE tại: https://programmablesearchengine.google.com/
// Nếu chưa có, để trống 2 dòng dưới => hệ thống chỉ dùng DB nội bộ.
define('GOOGLE_CSE_KEY', '');  // ví dụ: AIza...  (để '' nếu chưa dùng)
define('GOOGLE_CSE_CX',  '');  // ví dụ: a1b2c3d4e5  (để '' nếu chưa dùng)

// Whitelist domain (để lọc bớt rác). Bạn có thể thêm/bớt:
$GLOBALS['TRUSTED_SITES'] = [
  'facebook.com', 'vnexpress.net', 'tuoitre.vn', 'zingnews.vn',
  'voz.vn', 'webtretho.com', 'reddit.com', 'cafef.vn', 'dantri.com.vn',
  'congan.com.vn', 'cand.com.vn'
];
