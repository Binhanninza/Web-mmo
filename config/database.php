<?php
// FILE: config/database.php

// 1. KÍCH HOẠT TƯỜNG LỬA
require_once __DIR__ . '/../includes/security_helpers.php';
load_env_file(__DIR__ . '/../.env');

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
$host = env_value('DB_HOST', 'localhost');
$user = env_value('DB_USER');
$pass = env_value('DB_PASS');
$db   = env_value('DB_NAME');

if (empty($user) || empty($db)) {
    error_log('Database credentials are not configured. Set DB_HOST, DB_USER, DB_PASS, DB_NAME in environment or .env.');
    die('Lỗi cấu hình hệ thống. Vui lòng liên hệ quản trị viên.');
}

$conn = new mysqli($host, $user, $pass ?? '', $db);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Lỗi kết nối database. Vui lòng thử lại sau.');
}
$conn->set_charset("utf8mb4");

// 4. CẤU HÌNH MÚI GIỜ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// =================================================================
// 5. TÍNH NĂNG: TỰ ĐỘNG ĐĂNG NHẬP (FIX LỖI UPDATE_AT)
// =================================================================
if (!isset($_SESSION['user_id']) && isset($_COOKIE['site_remember'])) {
    $token = $_COOKIE['site_remember'];
    $token_hash = hash('sha256', $token);
    
    // Check Token (ưu tiên token đã hash)
    $stmt = $conn->prepare("SELECT id, lv, ban, remember_token FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Hỗ trợ token legacy (plain) và migrate sang hash
    if ($res->num_rows === 0) {
        $stmt->close();
        $stmt = $conn->prepare("SELECT id, lv, ban, remember_token FROM users WHERE remember_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
    }
    
    if ($res->num_rows > 0) {
        $u = $res->fetch_assoc();
        
        if ($u['ban'] == 0) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['lv'] = $u['lv'];
            
            // Gia hạn Cookie + chuẩn hóa token lưu DB
            if ($u['remember_token'] !== $token_hash) {
                $update = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $update->bind_param("si", $token_hash, $u['id']);
                $update->execute();
                $update->close();
            }
            
            set_remember_cookie($token, time() + (86400 * 7));
            
            // [ĐÃ SỬA] Chỉ update IP, bỏ qua updated_at để không bị lỗi
            $ip = $_SERVER['REMOTE_ADDR'];
            $ip_stmt = $conn->prepare("UPDATE users SET ip_address = ? WHERE id = ?");
            $ip_stmt->bind_param("si", $ip, $u['id']);
            $ip_stmt->execute();
            $ip_stmt->close();
        } else {
            clear_remember_cookie();
        }
    } else {
        clear_remember_cookie();
    }
    $stmt->close();
}

// 6. NHÚNG CSRF
if(file_exists(__DIR__ . '/../includes/csrf.php')) require_once __DIR__ . '/../includes/csrf.php'; 
?>
