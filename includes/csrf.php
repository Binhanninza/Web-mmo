<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function is_valid_csrf_token(?string $token = null): bool {
    $posted_token = $token ?? ($_POST['csrf_token'] ?? '');
    $session_token = $_SESSION['csrf_token'] ?? '';

    return $posted_token !== '' && $session_token !== '' && hash_equals($session_token, $posted_token);
}

function check_csrf_token() {
    if (!is_valid_csrf_token()) {
        http_response_code(403);
        die('Lỗi bảo mật CSRF: Token không hợp lệ!');
    }
}
generate_csrf_token();
?>
