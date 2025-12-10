<?php
// Tắt báo lỗi ra màn hình để tránh hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/database.php';
header('Content-Type: application/json');

// Xóa bộ đệm đầu ra (Quan trọng)
ob_clean();

if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['status'=>false, 'msg'=>'Vui lòng đăng nhập!']); exit; 
}

$user_id = $_SESSION['user_id'];
$mid = isset($_POST['mission_id']) ? (int)$_POST['mission_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if (empty($reason)) { 
    echo json_encode(['status'=>false, 'msg'=>'Chưa chọn lý do!']); exit; 
}

// 1. LƯU BÁO CÁO
$stmt = $conn->prepare("INSERT INTO reports (user_id, mission_id, reason, note) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $user_id, $mid, $reason, $note);

if ($stmt->execute()) {
    // 2. HỦY JOB (XÓA KEY ĐANG LÀM)
    $conn->query("DELETE FROM mission_keys WHERE user_id = $user_id AND mission_id = $mid");
    
    // 3. TRỪ 3 ĐIỂM UY TÍN (PHẠT)
    $conn->query("UPDATE users SET reputation = GREATEST(0, reputation - 3) WHERE id = $user_id");
    
    // 4. SET THỜI GIAN PHẠT (30s)
    $_SESSION['cancel_cooldown'] = time() + 30;

    echo json_encode(['status' => true, 'msg' => 'Đã hủy nhiệm vụ! Bạn bị trừ 3 điểm Uy tín.']);
} else {
    echo json_encode(['status' => false, 'msg' => 'Lỗi hệ thống: ' . $conn->error]);
}
?>
