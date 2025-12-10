<?php
// File này chạy tự động để phạt user ngâm link quá 6h
require_once 'config/database.php';

// 1. Tìm các Key đã tạo quá 6 tiếng (NOW - created_at > 6h)
$sql = "SELECT * FROM mission_keys WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 HOUR)";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $uid = $row['user_id'];
        $kid = $row['id'];
        
        // 2. Trừ 5 điểm Uy Tín của user này
        $conn->query("UPDATE users SET reputation = GREATEST(0, reputation - 5) WHERE id = $uid");
        
        // 3. Xóa Key đó đi (Coi như hủy)
        $conn->query("DELETE FROM mission_keys WHERE id = $kid");
        
        $count++;
    }
    echo "Đã quét và phạt $count thanh niên ngâm link!";
} else {
    echo "Không có ai vi phạm.";
}
?>
