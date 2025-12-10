<?php
// FILE: logout.php

// Khởi động session để còn biết đường mà xóa
session_start();

// 1. XÓA SẠCH SESSION
session_unset();
session_destroy();

// 2. XÓA COOKIE "GHI NHỚ" (QUAN TRỌNG VKL)
// Phải set thời gian về quá khứ để trình duyệt tự xóa nó đi
if (isset($_COOKIE['site_remember'])) {
    // Lưu ý: Path '/' phải trùng với lúc tạo cookie
    setcookie('site_remember', '', time() - 3600, '/'); 
    unset($_COOKIE['site_remember']);
}

// 3. ĐÁ VỀ TRANG LOGIN
header("Location: login.php");
exit();
?>
