<?php
    header('Content-Type: application/json');
$mysqli = new mysqli("localhost", "root", "", "checkscam"); // sửa user/pass/db nếu cần

$action = $_GET['action'] ?? '';

if ($action == 'list') {
    $rs = $mysqli->query("SELECT * FROM reports ORDER BY id DESC");
    $data = [];
    while ($row = $rs->fetch_assoc()) $data[] = $row;
    echo json_encode($data);
}
elseif ($action == 'get') {
    $id = intval($_GET['id']);
    $rs = $mysqli->query("SELECT * FROM reports WHERE id=$id");
    echo json_encode($rs->fetch_assoc());
}
elseif ($action == 'add') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $mysqli->prepare("INSERT INTO reports (reporter_name, scammer_name, bank_account, phone, content, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $data['reporter_name'], $data['scammer_name'], $data['bank_account'], $data['phone'], $data['content'], $data['status']);
    $stmt->execute();
    echo json_encode(['success'=>true]);
}
elseif ($action == 'update') {
    $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
    $status = $_GET['status'] ?? null;
    if ($status) {
        $stmt = $mysqli->prepare("UPDATE reports SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        // Nếu duyệt, có thể gửi thông báo cho admin ở đây (ví dụ gửi email hoặc log)
        echo json_encode(['success'=>true]);
    } else {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $mysqli->prepare("UPDATE reports SET reporter_name=?, scammer_name=?, bank_account=?, phone=?, content=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssi", $data['reporter_name'], $data['scammer_name'], $data['bank_account'], $data['phone'], $data['content'], $data['status'], $data['id']);
        $stmt->execute();
        echo json_encode(['success'=>true]);
    }
}
elseif ($action == 'delete') {
    $id = intval($_GET['id']);
    $mysqli->query("DELETE FROM reports WHERE id=$id");
    echo json_encode(['success'=>true]);
}
else {
    echo json_encode(['error'=>'No action']);
}
?>
