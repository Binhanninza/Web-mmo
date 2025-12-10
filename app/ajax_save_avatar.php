<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>false, 'msg'=>'Chưa đăng nhập']); exit; }

$user_id = $_SESSION['user_id'];
$url = isset($_POST['avatar_url']) ? trim($_POST['avatar_url']) : '';

if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
    // Lưu link ảnh vào DB
    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->bind_param("si", $url, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => true]);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Lỗi SQL']);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'Link ảnh không hợp lệ']);
}
?>
