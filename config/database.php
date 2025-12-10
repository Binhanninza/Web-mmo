<?php
// FILE: config/database.php

// 1. KÍCH HOẠT TƯỜNG LỬA
if (file_exists(__DIR__ . '/../includes/security_firewall.php')) {
    require_once __DIR__ . '/../includes/security_firewall.php';
}

// 2. CẤU HÌNH SESSION
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 86400); 
    session_start();
}

// 3. KẾT NỐI DATABASE
$host = "localhost";
$user = "vietrust_Hosting"; 
$pass = "vietrust_Hosting";  
$db   = "vietrust_Hosting"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 4. CẤU HÌNH MÚI GIỜ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// =================================================================
// 5. TÍNH NĂNG: TỰ ĐỘNG ĐĂNG NHẬP (FIX LỖI UPDATE_AT)
// =================================================================
if (!isset($_SESSION['user_id']) && isset($_COOKIE['site_remember'])) {
    $token = $_COOKIE['site_remember'];
    
    // Check Token
    $stmt = $conn->prepare("SELECT id, lv, ban FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $u = $res->fetch_assoc();
        
        if ($u['ban'] == 0) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['lv'] = $u['lv'];
            
            // Gia hạn Cookie
            setcookie('site_remember', $token, time() + (86400 * 7), "/", "", false, true);
            
            // [ĐÃ SỬA] Chỉ update IP, bỏ qua updated_at để không bị lỗi
            $ip = $_SERVER['REMOTE_ADDR'];
            $conn->query("UPDATE users SET ip_address = '$ip' WHERE id = {$u['id']}");
        } else {
            setcookie('site_remember', '', time() - 3600, "/");
        }
    } else {
        setcookie('site_remember', '', time() - 3600, "/");
    }
    $stmt->close();
}

// 6. NHÚNG CSRF
if(file_exists(__DIR__ . '/../includes/csrf.php')) require_once __DIR__ . '/../includes/csrf.php'; 
?>
