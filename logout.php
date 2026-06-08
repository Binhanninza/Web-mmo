<?php
// FILE: logout.php

require_once __DIR__ . '/includes/security_helpers.php';

// Khởi động session để còn biết đường mà xóa
session_start();

// 1. XÓA SẠCH SESSION
session_unset();
session_destroy();

// 2. XÓA COOKIE "GHI NHỚ" (QUAN TRỌNG VKL)
// Phải set thời gian về quá khứ để trình duyệt tự xóa nó đi
if (isset($_COOKIE['site_remember'])) {
    // Lưu ý: Path '/' phải trùng với lúc tạo cookie
    clear_remember_cookie();
}

// 3. ĐÁ VỀ TRANG LOGIN
header("Location: login.php");
exit();
?>
