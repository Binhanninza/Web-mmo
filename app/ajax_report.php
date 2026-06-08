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

if (!function_exists('is_valid_csrf_token') || !is_valid_csrf_token()) {
    http_response_code(403);
    echo json_encode(['status' => false, 'msg' => 'Lỗi bảo mật CSRF: Token không hợp lệ!']); exit;
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
    $delete_stmt = $conn->prepare("DELETE FROM mission_keys WHERE user_id = ? AND mission_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $mid);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // 3. TRỪ 3 ĐIỂM UY TÍN (PHẠT)
    $rep_stmt = $conn->prepare("UPDATE users SET reputation = GREATEST(0, reputation - 3) WHERE id = ?");
    $rep_stmt->bind_param("i", $user_id);
    $rep_stmt->execute();
    $rep_stmt->close();
    
    // 4. SET THỜI GIAN PHẠT (30s)
    $_SESSION['cancel_cooldown'] = time() + 30;

    echo json_encode(['status' => true, 'msg' => 'Đã hủy nhiệm vụ! Bạn bị trừ 3 điểm Uy tín.']);
} else {
    echo json_encode(['status' => false, 'msg' => 'Lỗi hệ thống: ' . $conn->error]);
}
?>
