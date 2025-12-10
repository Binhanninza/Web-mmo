<?php
require_once '../config/database.php';
// Check login
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
require_once '../includes/header.php';

$uid = $_SESSION['user_id'];

// --- LOGIC TÌM SỰ KIỆN (AUTO) ---
$stmt = $conn->prepare("SELECT * FROM events WHERE start_time <= NOW() AND end_time >= NOW() AND status = 1 LIMIT 1");
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$state = 'running';

if (!$event) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE start_time > NOW() AND start_time <= DATE_ADD(NOW(), INTERVAL 3 DAY) AND status = 1 ORDER BY start_time ASC LIMIT 1");
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $state = $event ? 'upcoming' : 'empty';
}
?>

<style>
    /* --- CSS STYLE TIKTOK / GAME --- */
    :root {
        --primary-gradient: linear-gradient(135deg, #FF0050 0%, #00F2EA 100%); /* Màu TikTok */
        --glass-bg: rgba(255, 255, 255, 0.9);
        --card-radius: 24px;
    }

    /* Ẩn footer mặc định để hiện cái Sticky Bar của mình */
    footer.content-footer { margin-bottom: 80px; } 

    .app-container {
        max-width: 600px; /* Gom gọn lại cho giống giao diện Mobile App */
        margin: 0 auto;
        padding-bottom: 100px;
    }

    /* HEADER BANNER */
    .top-banner {
        background: #111;
        color: #fff;
        border-radius: var(--card-radius);
        padding: 30px 20px;
        text-align: center;
        position: relative;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .top-banner::before {
        content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
        background: radial-gradient(circle, rgba(0, 242, 234, 0.4) 0%, transparent 60%);
        animation: pulse 5s infinite;
    }
    .event-name { font-weight: 900; font-size: 1.8rem; text-transform: uppercase; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; position: relative; z-index: 2; margin-bottom: 5px; }
    .countdown-badge { background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 8px 20px; border-radius: 50px; font-family: monospace; font-size: 1.2rem; display: inline-block; border: 1px solid rgba(255,255,255,0.2); position: relative; z-index: 2; }

    /* PODIUM (BỤC VINH QUANG) */
    .podium-container {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        margin-bottom: 40px;
        height: 220px;
    }
    .podium-item { text-align: center; position: relative; width: 33%; }
    
    /* Avatar trên bục */
    .p-avatar-box { position: relative; margin: 0 auto 10px; }
    .p-avatar { border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.2); transition: 0.3s; }
    .p-crown { position: absolute; top: -25px; left: 50%; transform: translateX(-50%); width: 40px; animation: bounce 2s infinite; }
    
    /* TOP 1 */
    .rank-1 .p-avatar { width: 90px; height: 90px; border-color: #FFD700; box-shadow: 0 0 20px rgba(255, 215, 0, 0.6); }
    .rank-1 { z-index: 3; }
    .rank-1 .p-name { font-size: 1.1rem; color: #FFD700; font-weight: bold; }
    .rank-1 .p-score { font-weight: 900; font-size: 1rem; }
    
    /* TOP 2 & 3 */
    .rank-2 .p-avatar, .rank-3 .p-avatar { width: 70px; height: 70px; }
    .rank-2 .p-avatar { border-color: #C0C0C0; }
    .rank-3 .p-avatar { border-color: #CD7F32; }
    .rank-2 { order: 1; margin-right: -10px; padding-bottom: 10px; } /* Đẩy Top 2 sang trái */
    .rank-3 { order: 3; margin-left: -10px; padding-bottom: 20px; }  /* Đẩy Top 3 sang phải */
    .rank-1 { order: 2; padding-bottom: 40px; margin-bottom: 10px; } /* Top 1 ở giữa cao nhất */

    .p-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; font-size: 0.9rem; margin-bottom: 2px; font-weight: 600; }
    .p-score { font-size: 0.8rem; color: #666; }

    /* LIST RANK (TOP 4 TRỞ ĐI) */
    .rank-list { padding: 0; }
    .rank-row {
        display: flex; align-items: center; justify-content: space-between;
        background: #fff;
        margin-bottom: 10px;
        padding: 15px;
        border-radius: 18px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        transition: 0.2s;
    }
    .rank-row:active { transform: scale(0.98); }
    
    .r-idx { font-size: 1.2rem; font-weight: 900; color: #999; width: 40px; text-align: center; }
    .r-info { display: flex; align-items: center; flex-grow: 1; }
    .r-avt { width: 45px; height: 45px; border-radius: 50%; margin-right: 12px; object-fit: cover; background: #eee; }
    .r-name { font-weight: 700; color: #333; font-size: 0.95rem; }
    .r-val { font-weight: 800; color: #FF0050; text-align: right; }
    
    /* STICKY USER BAR (Thanh của tôi) */
    .my-rank-bar {
        position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
        width: 90%; max-width: 580px;
        background: rgba(30, 30, 30, 0.95);
        backdrop-filter: blur(15px);
        color: #fff;
        padding: 15px 20px;
        border-radius: 50px;
        display: flex; align-items: center; justify-content: space-between;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        z-index: 1000;
        border: 1px solid rgba(255,255,255,0.2);
    }
    .my-rank-bar .r-name { color: #fff; }
    .my-rank-bar .r-idx { color: #fff; }
    
    /* ANIMATIONS */
    @keyframes pulse { 0% { transform: scale(1); opacity: 0.4; } 50% { transform: scale(1.2); opacity: 0; } 100% { transform: scale(1); opacity: 0; } }
    @keyframes bounce { 0%, 100% { transform: translate(-50%, 0); } 50% { transform: translate(-50%, -10px); } }
</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <div class="app-container">

            <?php if ($state == 'empty'): ?>
                <div class="text-center py-5">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="120" style="opacity:0.5; margin-bottom:20px;">
                    <h3 class="fw-bold text-muted">Server Bình Yên</h3>
                    <p>Chưa có giải đấu nào. Quay lại sau nhé!</p>
                </div>
            
            <?php else: ?>
                <div class="top-banner">
                    <div class="badge bg-white text-dark mb-2 fw-bold" style="border-radius:10px;"><?php echo ($state=='running') ? '🔥 ĐANG DIỄN RA' : '⏳ SẮP KHỞI TRANH'; ?></div>
                    <div class="event-name"><?php echo htmlspecialchars($event['name']); ?></div>
                    <div class="countdown-badge" id="countdown">00:00:00</div>
                    
                    <script>
                        var countDownDate = new Date("<?php echo $event['start_time']; ?>").getTime();
                        // Nếu đang chạy thì đếm ngược đến lúc kết thúc
                        <?php if($state=='running'): ?>
                        countDownDate = new Date("<?php echo $event['end_time']; ?>").getTime();
                        <?php endif; ?>
                        
                        var x = setInterval(function() {
                            var now = new Date().getTime(); var distance = countDownDate - now;
                            var d = Math.floor(distance / (1000 * 60 * 60 * 24));
                            var h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            var m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            var s = Math.floor((distance % (1000 * 60)) / 1000);
                            
                            var str = d>0 ? (d + "d " + h + "h") : (h + ":" + m + ":" + s);
                            document.getElementById("countdown").innerHTML = str;
                            if (distance < 0) { clearInterval(x); location.reload(); }
                        }, 1000);
                    </script>
                </div>

                <?php
                // --- LOGIC LẤY DATA VÀ TÁCH TOP 3 ---
                $start = $event['start_time'];
                $end = $event['end_time'];
                
                if ($event['type'] == 'job') {
                    $sql = "SELECT u.id, u.username, u.avatar, COUNT(h.id) as score FROM history h JOIN users u ON h.user_id = u.id WHERE h.created_at >= ? AND h.created_at <= ? GROUP BY h.user_id ORDER BY score DESC LIMIT 50";
                    $unit = 'Job';
                } else {
                    $sql = "SELECT u.id, u.username, u.avatar, SUM(h.amount) as score FROM history h JOIN users u ON h.user_id = u.id WHERE h.created_at >= ? AND h.created_at <= ? GROUP BY h.user_id ORDER BY score DESC LIMIT 50";
                    $unit = 'đ';
                }

                $stmt = $conn->prepare($sql); $stmt->bind_param("ss", $start, $end); $stmt->execute();
                $result = $stmt->get_result();
                
                $data = [];
                while ($row = $result->fetch_assoc()) $data[] = $row;
                
                // Tìm vị trí của mình
                $my_rank = 0; $my_score = 0; $me_in_list = false;
                foreach($data as $idx => $user) {
                    if($user['id'] == $uid) { $my_rank = $idx + 1; $my_score = $user['score']; $me_in_list = true; break; }
                }
                
                // Nếu mình chưa có trong top 50, query riêng để lấy điểm
                if (!$me_in_list) {
                    // (Code query riêng này hơi dài, tạm thời nếu chưa làm gì thì coi như 0 điểm)
                    $my_rank = '--'; $my_score = 0;
                }
                ?>

                <?php if(count($data) > 0): ?>
                <div class="podium-container">
                    <div class="podium-item rank-2 animate__animated animate__fadeInLeft" style="opacity: <?php echo isset($data[1])?1:0; ?>">
                        <?php if(isset($data[1])): ?>
                        <div class="p-avatar-box">
                            <img src="<?php echo !empty($data[1]['avatar'])?$data[1]['avatar']:'/assets/img/avatars/1.png'; ?>" class="p-avatar">
                        </div>
                        <div class="p-name"><?php echo substr($data[1]['username'],0,4).'***'; ?></div>
                        <div class="p-score"><?php echo number_format($data[1]['score']); ?></div>
                        <div class="badge bg-secondary rounded-pill mt-1">#2</div>
                        <?php endif; ?>
                    </div>

                    <div class="podium-item rank-1 animate__animated animate__zoomIn">
                        <?php if(isset($data[0])): ?>
                        <div class="p-avatar-box">
                            <img src="https://cdn-icons-png.flaticon.com/512/6941/6941697.png" class="p-crown">
                            <img src="<?php echo !empty($data[0]['avatar'])?$data[0]['avatar']:'/assets/img/avatars/1.png'; ?>" class="p-avatar">
                        </div>
                        <div class="p-name"><?php echo substr($data[0]['username'],0,4).'***'; ?></div>
                        <div class="p-score text-warning"><?php echo number_format($data[0]['score']); ?></div>
                        <div class="badge bg-warning rounded-pill mt-1 text-dark">#1</div>
                        <?php endif; ?>
                    </div>

                    <div class="podium-item rank-3 animate__animated animate__fadeInRight" style="opacity: <?php echo isset($data[2])?1:0; ?>">
                        <?php if(isset($data[2])): ?>
                        <div class="p-avatar-box">
                            <img src="<?php echo !empty($data[2]['avatar'])?$data[2]['avatar']:'/assets/img/avatars/1.png'; ?>" class="p-avatar">
                        </div>
                        <div class="p-name"><?php echo substr($data[2]['username'],0,4).'***'; ?></div>
                        <div class="p-score"><?php echo number_format($data[2]['score']); ?></div>
                        <div class="badge bg-danger rounded-pill mt-1" style="background:#CD7F32;">#3</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="rank-list">
                    <?php 
                    if(count($data) > 3) {
                        for($i = 3; $i < count($data); $i++) {
                            $u = $data[$i];
                            $avt = !empty($u['avatar']) ? $u['avatar'] : '/assets/img/avatars/1.png';
                            echo '
                            <div class="rank-row animate__animated animate__fadeInUp">
                                <div class="r-idx">'.($i+1).'</div>
                                <div class="r-info">
                                    <img src="'.$avt.'" class="r-avt">
                                    <div class="r-name">'.substr($u['username'],0,4).'***</div>
                                </div>
                                <div class="r-val">'.number_format($u['score']).' <small style="font-size:10px; color:#aaa;">'.$unit.'</small></div>
                            </div>';
                        }
                    } else if (count($data) == 0) {
                        echo '<div class="text-center text-muted">Chưa có ai đua top. Hãy mở bát đi đại ca!</div>';
                    }
                    ?>
                </div>

                <div class="my-rank-bar animate__animated animate__fadeInUpBig">
                    <div class="d-flex align-items-center">
                        <div class="r-idx me-3" style="font-size:1rem; width:auto;"><?php echo ($my_rank > 0) ? '#'.$my_rank : '--'; ?></div>
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $my_avt; ?>" class="rounded-circle" style="width:35px; height:35px; border:2px solid #fff; margin-right:10px;">
                            <div style="line-height:1.2">
                                <div style="font-weight:bold; font-size:0.9rem;">Tôi</div>
                                <div style="font-size:0.7rem; opacity:0.7;">Hạng hiện tại</div>
                            </div>
                        </div>
                    </div>
                    <div style="font-weight:900; font-size:1.1rem; color:#00F2EA;">
                        <?php echo number_format($my_score); ?> <?php echo $unit; ?>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
