<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// 1. CHECK ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'msg' => 'Vui lòng đăng nhập!']); exit;
}

// 2. CHECK COOLDOWN (CHỐNG SPAM HỦY)
if (isset($_SESSION['cancel_cooldown']) && time() < $_SESSION['cancel_cooldown']) {
    $wait = $_SESSION['cancel_cooldown'] - time();
    echo json_encode(['status' => false, 'msg' => "Bạn vừa hủy nhiệm vụ. Chờ $wait giây nữa!"]); exit;
}

// 3. RATE LIMIT TẠO LINK (QUAN TRỌNG: 5s mới được click 1 lần)
if (isset($_SESSION['last_gen_link']) && (time() - $_SESSION['last_gen_link'] < 5)) {
    echo json_encode(['status' => false, 'msg' => 'Thao tác quá nhanh! Chờ 5s nhé đại ca.']); exit;
}
$_SESSION['last_gen_link'] = time();

$user_id = $_SESSION['user_id'];
$mission_id = isset($_POST['mission_id']) ? (int)$_POST['mission_id'] : 0;

if ($mission_id == 0) {
    echo json_encode(['status' => false, 'msg' => 'Dữ liệu không hợp lệ!']); exit;
}

// 4. KIỂM TRA LINK CŨ (Prepared Statement)
$stmt = $conn->prepare("SELECT short_link FROM mission_keys WHERE user_id = ? AND mission_id = ?");
$stmt->bind_param("ii", $user_id, $mission_id);
$stmt->execute();
$res_exist = $stmt->get_result();

if ($res_exist->num_rows > 0) {
    $row = $res_exist->fetch_assoc();
    if (!empty($row['short_link'])) {
        echo json_encode(['status' => true, 'url' => $row['short_link'], 'msg' => 'Đã lấy lại link cũ']);
        exit;
    }
    // Nếu có row rỗng thì xóa đi
    $conn->query("DELETE FROM mission_keys WHERE user_id = $user_id AND mission_id = $mission_id");
}

// 5. LẤY THÔNG TIN NHIỆM VỤ
$sql = "SELECT * FROM missions WHERE id = $mission_id AND status = 1";
$res = $conn->query($sql);

if ($res->num_rows == 0) {
    echo json_encode(['status' => false, 'msg' => 'Nhiệm vụ không tồn tại hoặc đã bị khóa!']); exit;
}

$mission = $res->fetch_assoc();
$api_url_template = $mission['link_original']; 
$api_token = $mission['link_short'];           
$limit = $mission['daily_limit'];

// 6. CHECK GIỚI HẠN NGÀY (Prepared Statement - Fix SQLi)
$today = date('Y-m-d');
$chk = $conn->prepare("SELECT COUNT(*) as c FROM history WHERE user_id = ? AND mission_id = ? AND DATE(created_at) = ?");
$chk->bind_param("iis", $user_id, $mission_id, $today);
$chk->execute();
$check_limit = $chk->get_result()->fetch_assoc()['c'];

if ($check_limit >= $limit) {
    echo json_encode(['status' => false, 'msg' => 'Hết lượt làm nhiệm vụ này hôm nay!']); exit;
}

// 7. TẠO MÃ & LINK
$random_key = substr(md5(uniqid(rand(), true)), 0, 8); 
$target_url = "https://vietrust.site/getkey.php?key=" . $random_key;

// 8. GỌI API
$requestUrl = str_replace('{token}', $api_token, $api_url_template);
$requestUrl = str_replace('{link}', urlencode($target_url), $requestUrl);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $requestUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => false, 'msg' => 'Lỗi kết nối API rút gọn: ' . $curl_error]); exit;
}

$json = json_decode($response, true);
$shortenedUrl = false;

if (isset($json['shortenedUrl'])) $shortenedUrl = $json['shortenedUrl'];
elseif (isset($json['url'])) $shortenedUrl = $json['url'];
elseif (isset($json['short_url'])) $shortenedUrl = $json['short_url'];
elseif (filter_var($response, FILTER_VALIDATE_URL)) $shortenedUrl = trim($response); 

if ($shortenedUrl) {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    
    // Lưu vào DB (Prepared Statement)
    $stmt = $conn->prepare("INSERT INTO mission_keys (user_id, mission_id, key_code, short_link, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $user_id, $mission_id, $random_key, $shortenedUrl, $ua);

    if ($stmt->execute()) {
        echo json_encode(['status' => true, 'url' => $shortenedUrl]);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Lỗi DB: Không lưu được mã!']);
    }
} else {
    $api_msg = isset($json['message']) ? $json['message'] : 'API không trả về link';
    echo json_encode(['status' => false, 'msg' => 'Lỗi API: ' . $api_msg]);
}
?>
