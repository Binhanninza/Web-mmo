<?php
// File này sẽ được include vào config/database.php
// Mục đích: Load toàn bộ cấu hình web ra biến $CMS

$sql_settings = "SELECT * FROM settings WHERE id = 1";
$result_settings = $conn->query($sql_settings);

if ($result_settings && $result_settings->num_rows > 0) {
    $CMS = $result_settings->fetch_assoc();
} else {
    // Giá trị dự phòng nếu lỡ tay xóa DB
    $CMS = [
        'site_name' => 'SYSTEM ERROR',
        'site_title' => 'Lỗi cấu hình',
        'min_withdraw' => 10000,
        'maintenance_mode' => 0
    ];
}

// --- LOGIC BẢO TRÌ (Maintenance Mode) ---
// Nếu đang bật bảo trì (maintenance_mode = 1)
// Và người truy cập KHÔNG PHẢI LÀ ADMIN (Check session lv)
// Thì chặn luôn, không cho vào web.

$is_admin = (isset($_SESSION['user_id']) && isset($_SESSION['lv']) && $_SESSION['lv'] == 9);

if ($CMS['maintenance_mode'] == 1 && !$is_admin && strpos($_SERVER['REQUEST_URI'], '/admin/') === false && strpos($_SERVER['REQUEST_URI'], 'login.php') === false) {
    die('<div style="text-align:center; padding-top:50px; font-family: sans-serif;">
            <img src="https://img.icons8.com/clouds/200/maintenance.png">
            <h1>Hệ thống đang bảo trì!</h1>
            <p>Vui lòng quay lại sau ít phút. Admin đang nâng cấp server.</p>
         </div>');
}
?>
