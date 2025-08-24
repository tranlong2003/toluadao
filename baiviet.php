<?php
$pdo = new PDO("mysql:host=localhost;dbname=checkscam;charset=utf8", "root", "");
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id=? AND status='approved'");
$stmt->execute([$id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$report){ die("<h2 style='color:red;text-align:center'>❌ Bài tố cáo không tồn tại hoặc chưa được duyệt!</h2>"); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chi tiết tố cáo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card shadow p-4">
      <h2 class="text-danger">🚨 Chi tiết tố cáo</h2>
      <p><b>Tên scammer:</b> <?= htmlspecialchars($report['scammer_name']) ?></p>
      <p><b>Số tài khoản:</b> <?= htmlspecialchars($report['bank_account']) ?> (<?= htmlspecialchars($report['bank_code']) ?>)</p>
      <p><b>SĐT:</b> <?= htmlspecialchars($report['phone']) ?></p>
      <p><b>Số tiền:</b> <?= htmlspecialchars($report['amount']) ?> VND</p>
      <p><b>Danh mục:</b> <?= htmlspecialchars($report['category']) ?></p>
      <p><b>Nội dung tố cáo:</b><br><?= nl2br(htmlspecialchars($report['description'])) ?></p>
      <p><b>Người tố cáo:</b> <?= htmlspecialchars($report['reporter_name']) ?> (<?= htmlspecialchars($report['reporter_phone']) ?>)</p>
      <p><small class="text-muted">Ngày gửi: <?= $report['created_at'] ?></small></p>
    </div>
  </div>
</body>
</html>
