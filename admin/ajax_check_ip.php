<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Check Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['lv']) || $_SESSION['lv'] != 9) {
    echo json_encode([]); exit;
}

$ip = isset($_GET['ip']) ? $conn->real_escape_string($_GET['ip']) : '';

if ($ip != '') {
    // Query lấy lịch sử 7 ngày của IP này
    $sql = "SELECT h.created_at, h.is_revoked, u.username, m.name as mission_name 
            FROM history h 
            JOIN users u ON h.user_id = u.id 
            LEFT JOIN missions m ON h.mission_id = m.id 
            WHERE h.ip_address = '$ip' 
            AND h.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
            ORDER BY h.created_at DESC";
            
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    echo json_encode([]);
}
?>
