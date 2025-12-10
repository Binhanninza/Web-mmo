<?php
// File này nên được chạy tự động bởi Cron Job (ví dụ: 0 0 * * * - Chạy 1 lần mỗi ngày)
// Hoặc đại ca truy cập thủ công: vietrust.site/cron_cleanup.php?key=BIG_BOSS

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'BIG_BOSS')) {
    die("Không có quyền truy cập!");
}

require_once 'config/database.php';

echo "<h3>--- BẮT ĐẦU DỌN DẸP HỆ THỐNG ---</h3>";

// 1. Xóa Key rác (Quá 24h chưa nhập)
$conn->query("DELETE FROM mission_keys WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
echo "✅ Đã xóa " . $conn->affected_rows . " mã key hết hạn.<br>";

// 2. Xóa Log đăng nhập sai (Quá 24h)
$conn->query("DELETE FROM failed_logins WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 DAY)");
echo "✅ Đã xóa " . $conn->affected_rows . " log đăng nhập sai.<br>";

// 3. (Tùy chọn) Reset cảnh cáo cho user ngoan sau 7 ngày
// $conn->query("UPDATE users SET warning_count = 0 WHERE warning_count > 0 AND last_warning < DATE_SUB(NOW(), INTERVAL 7 DAY)");
// echo "✅ Đã reset cảnh cáo cho các user biết hối cải.<br>";

echo "<h3>--- HOÀN TẤT (Database đã sạch đẹp) ---</h3>";
?>
