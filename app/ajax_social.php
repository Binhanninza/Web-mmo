<?php
// 1. TẮT LỖI RÁC & DỌN BUFFER (Để JSON không bị hỏng)
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');
ob_clean(); 

// 2. CHECK LOGIN
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['status'=>false, 'msg'=>'Vui lòng đăng nhập!']); exit; 
}
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 3. CHECK CSRF (Chống tool bên ngoài)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => false, 'msg' => 'Lỗi bảo mật CSRF! F5 lại trang.']); exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $pid = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

    // --- CHỨC NĂNG LIKE ---
    if ($action == 'like') {
        // Có thể thêm check xem user đã like chưa ở đây nếu muốn kỹ hơn
        $stmt = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        echo json_encode(['status' => true]);
    }

    // --- CHỨC NĂNG BÌNH LUẬN ---
    if ($action == 'comment') {
        // A. CHỐNG SPAM (3 GIÂY MỚI ĐƯỢC CMT 1 LẦN)
        if (isset($_SESSION['last_cmt_time']) && (time() - $_SESSION['last_cmt_time'] < 3)) {
            echo json_encode(['status' => false, 'msg' => 'Bình luận chậm thôi đại ca!']); 
            exit;
        }
        $_SESSION['last_cmt_time'] = time();

        $content = trim($_POST['content']);
        $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

        if (!empty($content)) {
            // B. INSERT VÀO DB (Status = 0: Chờ duyệt)
            // Dùng Prepared Statement chống SQL Injection tuyệt đối
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, parent_id, status, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            $stmt->bind_param("iisi", $pid, $uid, $content, $parent_id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => true, 'msg' => 'Thành công!']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Lỗi hệ thống!']);
            }
        } else {
            echo json_encode(['status' => false, 'msg' => 'Nội dung không được để trống!']);
        }
    }

    // --- CHỨC NĂNG LOAD DANH SÁCH (PHÂN TRANG) ---
    if ($action == 'load_comments') {
        $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;

        // Chỉ lấy comment ĐÃ DUYỆT (status=1)
        // Sắp xếp: Mới nhất lên đầu (DESC)
        $stmt = $conn->prepare("SELECT c.*, u.username, u.avatar 
                                FROM comments c 
                                JOIN users u ON c.user_id = u.id 
                                WHERE c.post_id = ? AND c.status = 1 
                                ORDER BY c.id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $pid, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $html = "";
        $count = 0;
        
        while ($row = $res->fetch_assoc()) {
            // C. CHỐNG XSS (Lọc mã độc HTML/JS)
            $row['username'] = htmlspecialchars($row['username']);
            $row['content'] = nl2br(htmlspecialchars($row['content']));
            $row['avatar'] = !empty($row['avatar']) ? $row['avatar'] : '/assets/img/avatars/1.png';
            
            // Xử lý thời gian hiển thị (Vừa xong, x phút trước...)
            $diff = time() - strtotime($row['created_at']);
            if($diff < 60) $time = 'Vừa xong';
            elseif($diff < 3600) $time = floor($diff/60).' phút trước';
            elseif($diff < 86400) $time = floor($diff/3600).' giờ trước';
            else $time = date('d/m', strtotime($row['created_at']));

            // Thụt lề nếu là Reply
            $margin = ($row['parent_id'] > 0) ? 'margin-left:40px; border-left:2px solid #eee; padding-left:10px;' : '';
            
            $html .= '
            <div class="cmt-item animate__animated animate__fadeIn" style="'.$margin.'">
                <img src="'.$row['avatar'].'" class="cmt-avatar">
                <div class="cmt-body">
                    <div class="cmt-user">'.$row['username'].'</div>
                    <div class="cmt-text">'.$row['content'].'</div>
                    <div class="cmt-meta">
                        <span>'.$time.'</span>
                        <span class="cmt-reply-btn" onclick="replyCmt('.$row['id'].', \''.$row['username'].'\')">Trả lời</span>
                    </div>
                </div>
            </div>';
            $count++;
        }

        // Đếm số lượng còn lại để hiển thị nút "Xem thêm"
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id=? AND status=1");
        $count_stmt->bind_param("i", $pid);
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_row()[0];
        
        $remaining = $total - ($offset + $count);

        echo json_encode(['status' => true, 'html' => $html, 'remaining' => $remaining]);
    }
}
?>
