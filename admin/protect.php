<?php
// 1. CẤU HÌNH SESSION
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =================================================================
// 🔰 CẤU HÌNH BẢO MẬT
// =================================================================
$SECRET_KEY = "Binhandayma12345@"; // Mã cứu hộ của đại ca
$COOKIE_NAME = "VKL_TRUSTED_DEVICE"; // Tên con chip (Cookie)
// =================================================================

require_once '../config/database.php';

function get_client_ip() {
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) return $_SERVER["HTTP_CF_CONNECTING_IP"];
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) return trim(explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"])[0]);
    return $_SERVER["REMOTE_ADDR"];
}

$current_ip = get_client_ip();

// --- TÍNH NĂNG 1: CỨU HỘ & GẮN CHIP (Khi chạy link ?capnhatip=...) ---
if (isset($_GET['capnhatip']) && $_GET['capnhatip'] === $SECRET_KEY) {
    // 1. Thêm IP vào DB
    $check = $conn->query("SELECT id FROM admin_whitelist WHERE ip_address = '$current_ip'");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO admin_whitelist (ip_address, note) VALUES (?, 'Manual Add')");
        $stmt->bind_param("s", $current_ip);
        $stmt->execute();
    }
    
    // 2. Gắn Cookie "Thiết bị tin cậy" (Sống 30 ngày)
    // Mã hóa token để hacker không fake được
    $token = hash('sha256', $SECRET_KEY . 'thiet_bi_chinh_chu');
    setcookie($COOKIE_NAME, $token, time() + (86400 * 30), "/"); // Lưu 30 ngày

    echo "<h3 style='color:green'>Đã thêm IP $current_ip và xác thực thiết bị này! <a href='index.php'>Vào Admin ngay</a></h3>";
    exit;
}

// 2. CHECK QUYỀN (LOGIN & LEVEL)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['lv']) || $_SESSION['lv'] != 9) {
    fake_404_and_die();
}

// 3. CHECK IP WHITELIST (THÔNG MINH HƠN)
$stmt = $conn->prepare("SELECT id FROM admin_whitelist WHERE ip_address = ?");
$stmt->bind_param("s", $current_ip);
$stmt->execute();
$is_allowed = $stmt->get_result()->num_rows > 0;

if (!$is_allowed) {
    // IP lạ! Kiểm tra xem có phải "Thiết bị tin cậy" (Cookie) không?
    $token_check = hash('sha256', $SECRET_KEY . 'thiet_bi_chinh_chu');
    
    if (isset($_COOKIE[$COOKIE_NAME]) && $_COOKIE[$COOKIE_NAME] === $token_check) {
        // A! Người nhà đây rồi. IP bị đổi chứ gì?
        // Tự động thêm IP mới này vào DB luôn
        $stmt = $conn->prepare("INSERT INTO admin_whitelist (ip_address, note) VALUES (?, 'Auto Update via Cookie')");
        $stmt->bind_param("s", $current_ip);
        $stmt->execute();
        
        // Cho qua (Reload lại trang để nhận IP mới)
        header("Refresh:0");
        exit;
    } else {
        // IP lạ và không có Cookie => CÚT
        fake_404_and_die(); 
    }
}

// --- HÀM GIẢ MẠO 404 ---
function fake_404_and_die() {
    header("HTTP/1.1 404 Not Found");
    ?>
    <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
    <html><head>
    <title>404 Not Found</title>
    </head><body>
    <h1>Not Found</h1>
    <p>The requested URL was not found on this server.</p>
    <hr>
    <address>Apache/2.4.41 (Ubuntu) Server at <?php echo $_SERVER['HTTP_HOST']; ?> Port 80</address>
    </body></html>
    <?php
    exit();
}
?>
