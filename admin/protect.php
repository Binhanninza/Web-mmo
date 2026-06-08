<?php
// 1. CẤU HÌNH SESSION
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

function fake_404_and_die() {
    header("HTTP/1.1 404 Not Found");
    $host = e($_SERVER['HTTP_HOST'] ?? 'localhost');
    ?>
    <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
    <html><head>
    <title>404 Not Found</title>
    </head><body>
    <h1>Not Found</h1>
    <p>The requested URL was not found on this server.</p>
    <hr>
    <address>Apache/2.4.41 (Ubuntu) Server at <?php echo $host; ?> Port 80</address>
    </body></html>
    <?php
    exit();
}

function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

$current_ip = get_client_ip();
if ($current_ip === '') {
    fake_404_and_die();
}

// 2. CHECK QUYỀN (LOGIN & LEVEL)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['lv']) || (int)$_SESSION['lv'] !== 9) {
    fake_404_and_die();
}

// 3. CHECK IP WHITELIST
$stmt = $conn->prepare("SELECT id FROM admin_whitelist WHERE ip_address = ?");
$stmt->bind_param("s", $current_ip);
$stmt->execute();
$is_allowed = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$is_allowed) {
    fake_404_and_die();
}
?>
