<?php
// =============================================================
// 🔥 VIETRUST FIREWALL - LỚP BẢO VỆ TOÀN DIỆN :))🔥
// =============================================================

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// 1. CHẶN BAD BOTS & TOOLS (Curl, Python, Wget...)
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/curl|wget|python|java|libwww|httpclient|postman/i', $user_agent)) {
    header("HTTP/1.1 403 Forbidden");
    die("<h1>403 - Access Denied</h1><p>Vui lòng truy cập bằng trình duyệt thật!</p>");
}

// 2. RATE LIMIT TOÀN CỤC (CHỐNG DDOS NHẸ)
// Giới hạn: 10 request / 1 giây
if (!isset($_SESSION['fw_last_req'])) $_SESSION['fw_last_req'] = microtime(true);
if (!isset($_SESSION['fw_req_count'])) $_SESSION['fw_req_count'] = 0;

$time_window = 1; // 1 giây
$limit = 15; // Cho phép 15 request (Nới lỏng chút để load ảnh/css không bị chặn)

$current = microtime(true);
if ($current - $_SESSION['fw_last_req'] < $time_window) {
    $_SESSION['fw_req_count']++;
    if ($_SESSION['fw_req_count'] > $limit) {
        header("HTTP/1.1 429 Too Many Requests");
        die("<h1>429 - Thao tác quá nhanh!</h1><p>Hệ thống phát hiện spam. Vui lòng chờ 5 giây...</p>");
    }
} else {
    $_SESSION['fw_last_req'] = $current;
    $_SESSION['fw_req_count'] = 0;
}

// 3. WAF: QUÉT TỪ KHÓA NGUY HIỂM (SQLi & XSS)
function firewall_scan($data) {
    if (is_array($data)) {
        return array_map('firewall_scan', $data); // Quét đệ quy nếu là mảng
    }

    // Danh sách từ khóa CẤM TUYỆT ĐỐI (Dạng Regex)
    $patterns = [
        '/union\s+select/i',        // SQL Injection
        '/information_schema/i',    // SQL Injection
        '/select\s+.*\s+from/i',    // SQL Injection
        '/<script>/i',              // XSS
        '/javascript:/i',           // XSS
        '/onload=/i',               // XSS
        '/onerror=/i',              // XSS
        '/base64_decode/i',         // Backdoor PHP
        '/system\(/i',              // Command Injection
        '/exec\(/i',                // Command Injection
        '/shell_exec/i',            // Command Injection
        '/pass_through/i',          // Command Injection
        '/proc_open/i',             // Command Injection
        '/\.\.\//'                  // Path Traversal (../)
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $data)) {
            // Ghi log hacker (Nếu cần)
            // file_put_contents('hack_log.txt', date('Y-m-d H:i:s')." - IP: ".$_SERVER['REMOTE_ADDR']." - Data: $data\n", FILE_APPEND);
            
            header("HTTP/1.1 403 Forbidden");
            die("<h1>403 - Firewall Blocked</h1><p>Hệ thống phát hiện dữ liệu không an toàn!</p>");
        }
    }
    
    // Xóa khoảng trắng thừa
    return trim($data);
}

// Áp dụng Firewall cho tất cả dữ liệu đầu vào
if (!empty($_GET)) $_GET = firewall_scan($_GET);
if (!empty($_POST)) $_POST = firewall_scan($_POST);
if (!empty($_COOKIE)) $_COOKIE = firewall_scan($_COOKIE);
if (!empty($_REQUEST)) $_REQUEST = firewall_scan($_REQUEST);

// 4. SECURE HEADERS (Kích hoạt bảo mật trình duyệt)
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
// CSP nới lỏng để chạy được Boxicons, Google Fonts, jQuery
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https:;");

?>
