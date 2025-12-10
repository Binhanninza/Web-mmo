<?php
// Tắt báo lỗi rác
error_reporting(0); 
ini_set('display_errors', 0);

require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');
ob_clean();

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>false, 'msg'=>'Vui lòng đăng nhập!']); exit; }
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Check CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => false, 'msg' => 'Lỗi bảo mật! Reload trang.']); exit;
    }

    $code = strtoupper(trim($conn->real_escape_string($_POST['code'])));
    if (empty($code)) { echo json_encode(['status'=>false, 'msg'=>'Chưa nhập mã!']); exit; }

    // 2. Tìm mã & Check cơ bản
    $stmt = $conn->prepare("SELECT * FROM giftcodes WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) { echo json_encode(['status'=>false, 'msg'=>'Mã quà tặng không tồn tại!']); exit; }
    $gift = $res->fetch_assoc();

    // 3. Check thời hạn
    $now = date('Y-m-d H:i:s');
    if ($now < $gift['start_time']) { echo json_encode(['status'=>false, 'msg'=>'Mã này chưa đến giờ mở!']); exit; }
    if ($now > $gift['end_time']) { echo json_encode(['status'=>false, 'msg'=>'Mã này đã hết hạn!']); exit; }

    // 4. Check Số lượng (QUAN TRỌNG)
    $count_used = $conn->query("SELECT COUNT(*) FROM giftcode_history WHERE giftcode_id = {$gift['id']}")->fetch_row()[0];
    if ($count_used >= $gift['usage_limit']) {
        echo json_encode(['status'=>false, 'msg'=>'Rất tiếc! Mã này đã hết lượt sử dụng.']); exit;
    }

    // 5. TRANSACTION & XỬ LÝ (FIX LỖI DUPLICATE)
    $conn->begin_transaction();
    try {
        // Cố gắng Insert
        $insert = $conn->prepare("INSERT INTO giftcode_history (user_id, giftcode_id, received_at) VALUES (?, ?, NOW())");
        $insert->bind_param("ii", $uid, $gift['id']);
        
        // Thực thi lệnh insert
        $insert->execute(); 
        
        // (Nếu dòng trên lỗi Duplicate, nó sẽ nhảy thẳng xuống catch bên dưới)

        // Nếu qua được thì cộng thưởng
        $type = $gift['type'];
        $val = $gift['value'];
        
        if ($type == 'money') {
            $conn->query("UPDATE users SET money = money + $val WHERE id = $uid");
            $u_money = $conn->query("SELECT money FROM users WHERE id=$uid")->fetch_assoc()['money'];
            $conn->query("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description) VALUES ($uid, 'giftcode', $val, $u_money - $val, $u_money, 'Giftcode: $code')");
        } elseif ($type == 'reputation') {
            $conn->query("UPDATE users SET reputation = LEAST(100, reputation + $val) WHERE id = $uid");
        } elseif ($type == 'exp') {
            $conn->query("UPDATE users SET exp = exp + $val WHERE id = $uid");
        }

        $conn->commit();
        
        echo json_encode([
            'status' => true,
            'msg' => 'Nhập mã thành công!',
            'reward_text' => "+".number_format($val)." ".strtoupper($type),
            'letter' => nl2br(htmlspecialchars($gift['message']))
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        
        $error_msg = $e->getMessage();
        
        // --- CHỐT CHẶN BẮT LỖI TRÙNG LẶP ---
        // Kiểm tra mã lỗi 1062 hoặc nội dung chuỗi có chữ "Duplicate"
        if ($e->getCode() == 1062 || strpos($error_msg, 'Duplicate entry') !== false) {
            $final_msg = "Bạn đã nhập mã này rồi! Đừng tham lam nha.";
        } else {
            $final_msg = "Lỗi hệ thống: " . $error_msg;
        }
        
        echo json_encode(['status' => false, 'msg' => $final_msg]);
    }
}
?>
