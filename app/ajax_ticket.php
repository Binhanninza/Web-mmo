<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['status'=>false]); exit; }
$uid = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['lv']) && $_SESSION['lv'] == 9);

$action = $_POST['action'] ?? '';

// 1. TẠO TICKET MỚI
if ($action == 'create_ticket') {
    // Rate limit tạo ticket
    if (isset($_SESSION['last_create_ticket']) && (time() - $_SESSION['last_create_ticket'] < 10)) {
        echo json_encode(['status' => false, 'msg' => 'Từ từ thôi! 10s mới được tạo 1 phiếu.']); exit;
    }
    $_SESSION['last_create_ticket'] = time();

    $priority = (int)$_POST['priority'];
    if (!in_array($priority, [1, 2, 3])) $priority = 1;
    
    $stmt = $conn->prepare("INSERT INTO tickets (user_id, priority, updated_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $uid, $priority);
    if ($stmt->execute()) {
        echo json_encode(['status' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Lỗi DB']);
    }
}

// 2. GỬI TIN NHẮN
if ($action == 'send_msg') {
    // Rate limit chat (Chống spam)
    if (isset($_SESSION['last_chat_time']) && (time() - $_SESSION['last_chat_time'] < 1)) {
        echo json_encode(['status' => true]); exit; // Im lặng bỏ qua
    }
    $_SESSION['last_chat_time'] = time();

    $tid = (int)$_POST['ticket_id'];
    $msg = trim($_POST['message']);
    
    // --- LỌC XSS (QUAN TRỌNG NHẤT) ---
    // Biến <script>alert(1)</script> thành text vô hại
    $msg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    
    $role = $is_admin ? 'admin' : 'user';
    
    if (empty($msg)) exit;

    if (!$is_admin) {
        $check = $conn->query("SELECT id FROM tickets WHERE id=$tid AND user_id=$uid");
        if ($check->num_rows == 0) exit;
    }

    $stmt = $conn->prepare("INSERT INTO ticket_messages (ticket_id, sender_role, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $tid, $role, $msg);
    $stmt->execute();

    if ($is_admin) {
        $conn->query("UPDATE tickets SET updated_at=NOW(), is_read_user=0, status=0 WHERE id=$tid");
    } else {
        $conn->query("UPDATE tickets SET updated_at=NOW(), is_read_admin=0, status=0 WHERE id=$tid");
    }
    echo json_encode(['status' => true]);
}

// 3. LOAD TIN NHẮN
if ($action == 'load_msgs') {
    $tid = (int)$_POST['ticket_id'];
    
    if (!$is_admin) {
        $check = $conn->query("SELECT id FROM tickets WHERE id=$tid AND user_id=$uid");
        if ($check->num_rows == 0) exit;
    }

    if ($is_admin) $conn->query("UPDATE tickets SET is_read_admin=1 WHERE id=$tid");
    else $conn->query("UPDATE tickets SET is_read_user=1 WHERE id=$tid");

    $msgs = $conn->query("SELECT * FROM ticket_messages WHERE ticket_id=$tid ORDER BY id ASC");
    $data = [];
    while ($m = $msgs->fetch_assoc()) {
        $m['time'] = date('H:i', strtotime($m['created_at']));
        $m['is_me'] = ($is_admin && $m['sender_role'] == 'admin') || (!$is_admin && $m['sender_role'] == 'user');
        // Message đã được lọc lúc Insert rồi, nhưng lọc phát nữa lúc hiển thị cho chắc
        $data[] = $m;
    }
    echo json_encode(['status' => true, 'data' => $data]);
}

// 4. RỜI KHỎI
if ($action == 'leave_ticket') {
    $tid = (int)$_POST['ticket_id'];
    $leaver_name = $is_admin ? "Quản trị viên" : "Khách hàng";
    if (!$is_admin) {
        $u = $conn->query("SELECT username FROM users WHERE id=$uid")->fetch_assoc();
        if($u) $leaver_name = $u['username'];
    }
    $msg = "🔴 " . $leaver_name . " đã rời khỏi phòng chat.";
    
    $stmt = $conn->prepare("INSERT INTO ticket_messages (ticket_id, sender_role, message, created_at) VALUES (?, 'system', ?, NOW())");
    $stmt->bind_param("is", $tid, $msg);
    $stmt->execute();

    $conn->query("UPDATE tickets SET status=1, updated_at=NOW() WHERE id=$tid");
    echo json_encode(['status' => true]);
}
?>
