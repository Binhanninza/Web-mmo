<?php
require_once 'config/database.php';

// Lấy Key từ URL
if (!isset($_GET['key'])) { die("Thiếu mã xác nhận!"); }
$key_code = trim($_GET['key']);

// 1. Kiểm tra Key có tồn tại không
$stmt = $conn->prepare("SELECT id, ip_address FROM mission_keys WHERE key_code = ?");
$stmt->bind_param("s", $key_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Mã này không tồn tại hoặc đã hết hạn!");
}

$row = $result->fetch_assoc();
$current_ip = $_SERVER['REMOTE_ADDR'];

// 2. Logic Lưu IP:
// Chỉ lưu IP lần đầu tiên truy cập. Nếu người khác vào link này sau đó thì không ghi đè.
if (empty($row['ip_address'])) {
    $update = $conn->prepare("UPDATE mission_keys SET ip_address = ? WHERE id = ?");
    $update->bind_param("si", $current_ip, $row['id']);
    $update->execute();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lấy mã | <?php echo isset($CMS['site_name']) ? $CMS['site_name'] : 'System'; ?></title>
    <link rel="stylesheet" href="/assets/vendor/css/core.css" />
    <style>
        body { background: #f5f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column; font-family: 'Public Sans', sans-serif; }
        .card { max-width: 400px; width: 90%; text-align: center; padding: 2rem; background: #fff; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .key-display { font-size: 32px; font-weight: 800; color: #696cff; letter-spacing: 2px; margin: 20px 0; border: 2px dashed #696cff; padding: 15px; background: #f0f0ff; border-radius: 8px; cursor: pointer; }
        .btn-copy { background: #696cff; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <h3>🎉 Thành công!</h3>
        <p>Mã xác nhận của bạn là:</p>
        <div class="key-display" onclick="copyKey()" id="keyBox"><?php echo htmlspecialchars($key_code); ?></div>
        <p class="small text-muted">Bấm vào mã để sao chép</p>
        <a href="app/mission.php" class="btn-copy">Quay lại nhập mã</a>
    </div>
    <script>
        function copyKey() {
            var text = document.getElementById("keyBox").innerText;
            navigator.clipboard.writeText(text);
            alert("Đã sao chép: " + text);
        }
    </script>
</body>
</html>
