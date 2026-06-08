<?php
// === START: LOGIC XỬ LÝ (PHP) ===
ob_start(); // Bật bộ đệm đầu ra
require_once '../config/database.php';

// 1. CHECK LOGIN
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// --- KHAI BÁO HÀM (ĐƯA RA NGOÀI IF ĐỂ TRÁNH LỖI REDECLARE) ---

// Hàm phạt user (Dùng Prepared Statement + Chống XSS)
function punishUser($uid, $conn, $reason) {
    // 1. Fix SQL Injection: Dùng prepare thay vì nối chuỗi
    $stmt = $conn->prepare("UPDATE users SET warning_count = warning_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();

    // Lấy số lần cảnh báo
    $stmt = $conn->prepare("SELECT warning_count FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // 8 GẬY MỚI BAN
    if ($u['warning_count'] >= 8) {
        $stmt = $conn->prepare("UPDATE users SET ban = 1 WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
        
        session_destroy();
        
        // 2. Fix XSS: Dùng json_encode để an toàn khi đưa vào Javascript
        $safe_reason = json_encode($reason, JSON_UNESCAPED_UNICODE);
        die("<script>alert('Tài khoản bị khóa vĩnh viễn: ' + $safe_reason); window.location='/login.php';</script>");
    }
}

// Hàm thông báo Flash (Chuyển hướng an toàn)
function setFlashAndRedirect($icon, $title, $text) {
    $_SESSION['swal_flash'] = ['icon' => $icon, 'title' => $title, 'text' => $text];
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit();
}

// 2. CHECK BAN (Dùng Prepared Statement)
$stmt = $conn->prepare("SELECT ban, money, reputation, exp, referred_by FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$check_ban = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$check_ban || $check_ban['ban'] == 1) { die("Tài khoản đã bị khóa!"); }


// 3. XỬ LÝ POST (NHẬN THƯỞNG)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_code'])) {
    
    // Check CSRF
    if(function_exists('check_csrf_token')) check_csrf_token(); 
    
    // Rate Limit (2 giây)
    if (isset($_SESSION['last_sub']) && (time() - $_SESSION['last_sub'] < 2)) {
        setFlashAndRedirect('warning', 'Từ từ thôi!', 'Thao tác quá nhanh!');
    }
    $_SESSION['last_sub'] = time();

    // Check Captcha Toán Học
    $math_ans = isset($_POST['math_ans']) ? (int)$_POST['math_ans'] : 0;
    if (!isset($_SESSION['math_res']) || $math_ans !== $_SESSION['math_res']) {
        punishUser($user_id, $conn, "Sai Captcha");
        setFlashAndRedirect('error', 'Lỗi', 'Tính sai rồi đại ca!');
    } 
    
    unset($_SESSION['math_res']); // Xóa kết quả cũ ngay
    
    $mid = (int)$_POST['mission_id'];
    $code = trim($_POST['user_code']);
    
    $conn->begin_transaction(); 
    try {
        // Lock row user để cộng tiền an toàn
        $u_stmt = $conn->prepare("SELECT created_at, money FROM users WHERE id = ? FOR UPDATE");
        $u_stmt->bind_param("i", $user_id); 
        $u_stmt->execute();
        $u_data = $u_stmt->get_result()->fetch_assoc();
        $u_stmt->close();

        // Check mã Key (Prepared Statement)
        $stmt = $conn->prepare("SELECT * FROM mission_keys WHERE user_id = ? AND mission_id = ? AND key_code = ? FOR UPDATE");
        $stmt->bind_param("iis", $user_id, $mid, $code); 
        $stmt->execute();
        $key_res = $stmt->get_result();
        
        if ($key_res->num_rows == 0) throw new Exception("Mã sai hoặc hết hạn!");
        $key_info = $key_res->fetch_assoc();
        $stmt->close();

        // Check Device (User Agent)
        if ($key_info['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) { 
            punishUser($user_id, $conn, "Fake IP/Device"); 
            throw new Exception("Sai thiết bị! Vui lòng dùng đúng trình duyệt lấy link."); 
        }
        
        // Check Time (50s)
        if ((time() - strtotime($key_info['created_at'])) < 50) { 
            punishUser($user_id, $conn, "Hack Time"); 
            throw new Exception("Làm quá nhanh (dưới 50s)!"); 
        }

        // Lấy thông tin nhiệm vụ
        $m_stmt = $conn->prepare("SELECT * FROM missions WHERE id = ?");
        $m_stmt->bind_param("i", $mid);
        $m_stmt->execute();
        $m_info = $m_stmt->get_result()->fetch_assoc();
        $m_stmt->close();

        // Check giới hạn ngày (Prepared Statement)
        $today = date('Y-m-d');
        $h_stmt = $conn->prepare("SELECT COUNT(*) FROM history WHERE user_id = ? AND mission_id = ? AND DATE(created_at) = ?");
        $h_stmt->bind_param("iis", $user_id, $mid, $today);
        $h_stmt->execute();
        $done = $h_stmt->get_result()->fetch_row()[0];
        $h_stmt->close();

        if ($done >= $m_info['daily_limit']) throw new Exception("Hết lượt hôm nay!");

        // --- CỘNG TIỀN & EXP ---
        $reward = $m_info['reward'];
        $new_bal = $u_data['money'] + $reward;
        
        // Update User (Prepared Statement)
        $upd = $conn->prepare("UPDATE users SET money = ?, reputation = LEAST(100, reputation + 1), exp = exp + 5 WHERE id = ?");
        $upd->bind_param("ii", $new_bal, $user_id);
        $upd->execute();
        $upd->close();

        // Log Giao Dịch
        $desc = "Job #$mid";
        $trans = $conn->prepare("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference_id) VALUES (?, 'mission', ?, ?, ?, ?, ?)");
        $trans->bind_param("iiiiis", $user_id, $reward, $u_data['money'], $new_bal, $desc, $mid);
        $trans->execute();
        $trans->close();

        // Trả thưởng Ref (Nếu có)
        if ($check_ban['referred_by'] > 0) {
            $cms = $conn->query("SELECT ref_percent FROM settings WHERE id=1")->fetch_assoc();
            $percent = isset($cms['ref_percent']) ? $cms['ref_percent'] : 10;
            $comm = floor($reward * ($percent / 100));
            if($comm > 0) {
                $ref_upd = $conn->prepare("UPDATE users SET money = money + ? WHERE id = ?");
                $ref_upd->bind_param("ii", $comm, $check_ban['referred_by']);
                $ref_upd->execute();
                $ref_upd->close();
            }
        }

        // Lưu Lịch Sử (Prepared Statement)
        $ip = $_SERVER['REMOTE_ADDR']; 
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $hst = $conn->prepare("INSERT INTO history (user_id, mission_id, amount, ip_address, code, short_link, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $hst->bind_param("iiissss", $user_id, $mid, $reward, $ip, $code, $key_info['short_link'], $ua);
        $hst->execute();
        $hst->close();
        
        // Xóa Key đã dùng
        $del_key = $conn->prepare("DELETE FROM mission_keys WHERE id = ?");
        $del_key->bind_param("i", $key_info['id']);
        $del_key->execute();
        $del_key->close();

        $conn->commit();
        
        setFlashAndRedirect('success', 'Thành công!', 'Cộng +'.number_format($reward).'đ');

    } catch (Exception $e) {
        $conn->rollback();
        setFlashAndRedirect('error', 'Lỗi', $e->getMessage());
    }
}

// 4. LOAD DỮ LIỆU HIỂN THỊ
$n1 = rand(1, 5); $n2 = rand(1, 5); $_SESSION['math_res'] = $n1 + $n2;

// Lấy Job đang làm dở
$cur_stmt = $conn->prepare("SELECT mission_id FROM mission_keys WHERE user_id = ? LIMIT 1");
$cur_stmt->bind_param("i", $user_id);
$cur_stmt->execute();
$current_res = $cur_stmt->get_result();
$current_doing_id = ($current_res->num_rows > 0) ? $current_res->fetch_assoc()['mission_id'] : 0;
$cur_stmt->close();

$cooldown_left = (isset($_SESSION['cancel_cooldown']) && time() < $_SESSION['cancel_cooldown']) ? ($_SESSION['cancel_cooldown'] - time()) : 0;

// === END LOGIC ===
?>

<?php require_once '../includes/header.php'; ?>

<style>
    .wallet-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 30px rgba(118, 75, 162, 0.3); margin-bottom: 25px; position: relative; overflow: hidden; }
    .wallet-card::before { content: ''; position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; }
    .wallet-balance { font-size: 32px; font-weight: 800; margin: 10px 0; }
    .job-card { background: #fff; border-radius: 20px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f5f5f5; position: relative; overflow: hidden; }
    .job-card.active { border: 2px solid #696cff; box-shadow: 0 5px 20px rgba(105, 108, 255, 0.2); }
    .job-icon { width: 50px; height: 50px; background: #e7e7ff; color: #696cff; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 15px; }
    .job-reward { font-weight: 800; color: #2ecc71; font-size: 16px; }
    .btn-app { border-radius: 50px; font-weight: 700; padding: 10px 20px; width: 100%; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-badge { position: absolute; top: 0; right: 0; background: #696cff; color: #fff; font-size: 10px; padding: 5px 10px; border-radius: 0 20px 0 10px; font-weight: bold; }
    .app-input { background: #f5f5f9; border: none; padding: 15px; border-radius: 15px; font-weight: bold; font-size: 18px; color: #696cff; text-align: center; margin-bottom: 10px; }
    .app-input:focus { background: #fff; box-shadow: 0 0 0 2px #696cff; outline: none; }
</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <div class="wallet-card animate__animated animate__fadeInDown">
            <div class="d-flex justify-content-between"><div><div class="wallet-label">Số dư hiện tại</div><div class="wallet-balance"><?php echo number_format($check_ban['money']); ?> đ</div></div><i class='bx bx-trophy fs-1 opacity-50'></i></div>
            <div class="mt-3 d-flex gap-3">
                <div class="badge bg-white text-primary bg-opacity-25"><i class='bx bx-check-circle'></i> <?php echo $check_ban['reputation']; ?> Uy tín</div>
                <div class="badge bg-white text-warning bg-opacity-25"><i class='bx bx-star'></i> EXP: <?php echo $check_ban['exp']; ?></div>
            </div>
        </div>

        <h5 class="fw-bold py-2 mb-3"><i class='bx bxs-hot text-danger'></i> Nhiệm vụ kiếm tiền</h5>

        <div class="row">
            <?php
            $missions = $conn->query("SELECT * FROM missions WHERE status = 1 ORDER BY reward DESC");
            if ($missions->num_rows > 0) {
                while ($row = $missions->fetch_assoc()) {
                    $mid = (int)$row['id']; 
                    $limit = (int)$row['daily_limit']; 
                    $today = date('Y-m-d');
                    
                    // Check limit (Prepared Statement)
                    $chk_limit = $conn->prepare("SELECT COUNT(*) FROM history WHERE user_id=? AND mission_id=? AND DATE(created_at)=?");
                    $chk_limit->bind_param("iis", $user_id, $mid, $today);
                    $chk_limit->execute();
                    $done = $chk_limit->get_result()->fetch_row()[0];
                    $chk_limit->close();
                    
                    $locked = ($done >= $limit);
                    $is_this = ($current_doing_id == $mid);
                    $is_other = ($current_doing_id > 0 && $current_doing_id != $mid);
                    
                    // Lấy link an toàn (Prepared Statement)
                    $link = '';
                    if ($is_this) {
                        $lnk_stmt = $conn->prepare("SELECT short_link FROM mission_keys WHERE user_id=? AND mission_id=?");
                        $lnk_stmt->bind_param("ii", $user_id, $mid);
                        $lnk_stmt->execute();
                        $link_res = $lnk_stmt->get_result();
                        if ($link_res->num_rows > 0) $link = $link_res->fetch_assoc()['short_link'];
                        $lnk_stmt->close();
                    }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="job-card <?php echo $is_this ? 'active animate__animated animate__pulse animate__infinite' : ''; ?>">
                    <?php if($is_this): ?><div class="status-badge">ĐANG THỰC HIỆN</div><?php endif; ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="job-icon"><i class='bx bx-link'></i></div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1 text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($row['name']); ?></h6>
                            <div class="text-muted small">Còn lại: <?php echo ($limit - $done); ?> lượt</div>
                        </div>
                        <div class="job-reward">+<?php echo number_format($row['reward']); ?></div>
                    </div>
                    <?php if($locked): ?>
                        <button class="btn btn-secondary btn-app" disabled><i class='bx bx-lock'></i> ĐÃ HẾT LƯỢT</button>
                    <?php elseif($is_other): ?>
                        <button class="btn btn-light text-muted btn-app" disabled><i class='bx bx-block'></i> ĐANG BẬN JOB KHÁC</button>
                    <?php elseif($is_this): ?>
                        <div class="animate__animated animate__fadeIn">
                            <button type="button" class="btn btn-outline-primary w-100 mb-3 fw-bold rounded-pill" onclick="showAccessModal('<?php echo htmlspecialchars($link, ENT_QUOTES); ?>', <?php echo $mid; ?>)">
                                <i class='bx bx-link-external'></i> MỞ LẠI LINK
                            </button>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                <input type="hidden" name="mission_id" value="<?php echo $mid; ?>">
                                <input type="text" name="user_code" class="app-input w-100" placeholder="Nhập mã vào đây..." required autocomplete="off">
                                <div class="d-flex align-items-center justify-content-center mb-3 text-muted fw-bold">
                                    <span class="me-2">Xác thực: <?php echo "$n1 + $n2 ="; ?></span>
                                    <input type="number" name="math_ans" class="form-control text-center d-inline-block" style="width: 60px;" required>
                                </div>
                                <button class="btn btn-primary btn-app shadow-lg" name="submit_code">NHẬN THƯỞNG NGAY</button>
                            </form>
                            <div class="text-center mt-3"><small class="text-danger fw-bold cursor-pointer" onclick="openReport(<?php echo $mid; ?>)">Báo lỗi / Hủy Job</small></div>
                        </div>
                    <?php else: ?>
                        <button class="btn btn-primary btn-app shadow-sm btn-gen" data-id="<?php echo $mid; ?>">BẮT ĐẦU KIẾM TIỀN</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php } } else { echo '<div class="col-12 text-center py-5 text-muted">Hết nhiệm vụ rồi!</div>'; } ?>
        </div>
    </div>

    <div class="modal fade" id="accessModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-body text-center p-4">
                    <div class="mb-3"><div class="avatar avatar-xl bg-label-primary rounded-circle mx-auto p-3 mb-2"><i class='bx bxs-rocket display-4'></i></div></div>
                    <h4 class="fw-bold">Sẵn sàng kiếm tiền?</h4>
                    <p class="text-muted mb-4">Chúng tôi sẽ chuyển bạn đến trang đối tác.<br>Lấy mã và quay lại đây nhập nhé!</p>
                    <input type="hidden" id="modal-url-input">
                    <button type="button" class="btn btn-primary btn-app mb-3 p-3 fs-6" id="btn-open-link">🚀 ĐI LẤY MÃ NGAY</button>
                    <button type="button" class="btn btn-label-secondary w-100 rounded-pill" id="btn-cancel-access">Hủy bỏ (-3 Uy tín)</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px;">
                <div class="modal-body p-4 text-center">
                    <h5 class="fw-bold text-danger mb-3">Hủy nhiệm vụ?</h5>
                    <input type="hidden" id="report_mid">
                    <select class="form-select mb-3" id="reason_select">
                        <option value="Link lỗi">Link bị lỗi/Die</option>
                        <option value="Khó quá">Nhiệm vụ khó quá</option>
                        <option value="Khác">Lý do khác...</option>
                    </select>
                    <textarea class="form-control mb-3" id="report_note" rows="2" placeholder="Ghi chú thêm..." style="display:none;"></textarea>
                    <button class="btn btn-danger w-100 rounded-pill fw-bold" onclick="submitReport()">Xác nhận Hủy</button>
                </div>
            </div>
        </div>
    </div>

    <div id="cooldown-badge" class="position-fixed bottom-0 end-0 m-3 bg-dark text-white px-3 py-2 rounded-pill shadow" style="display:none; z-index:9999;">
        <i class='bx bx-time'></i> Chờ: <span id="timer-val">30</span>s
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    var tempUrl = ''; var tempMid = 0; var waitTime = <?php echo $cooldown_left; ?>;
    var csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;

    $(document).ready(function() { 
        <?php if (isset($_SESSION['swal_flash'])): ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['swal_flash']['icon']; ?>',
                title: '<?php echo $_SESSION['swal_flash']['title']; ?>',
                text: '<?php echo $_SESSION['swal_flash']['text']; ?>',
                confirmButtonText: 'OK'
            });
            <?php unset($_SESSION['swal_flash']); ?>
        <?php endif; ?>

        if (waitTime > 0) startCooldown(waitTime);
        else {
            var activeForm = $('[name="user_code"]:visible');
            if (activeForm.length > 0) $('.btn-gen').prop('disabled',true).addClass('btn-disabled-custom').text('Đang bận');
        }
    });

    $('.btn-gen').click(function() {
        if (waitTime > 0) { Swal.fire('Phạt!', 'Đang trong thời gian chờ!', 'warning'); return; }
        var btn = $(this); var mid = btn.data('id'); var txt = btn.text();
        btn.prop('disabled',true).html('<i class="bx bx-loader-alt bx-spin"></i>');
        
        $.ajax({
            url: 'ajax_gen_link.php', type: 'POST', dataType: 'json', data: { mission_id: mid, csrf_token: csrfToken },
            success: function(res) {
                if(res.status) { showAccessModal(res.url, mid); btn.prop('disabled',false).text(txt); }
                else { Swal.fire('Lỗi', res.msg, 'error'); btn.prop('disabled',false).text(txt); }
            },
            error: function() { Swal.fire('Lỗi mạng', '', 'error'); btn.prop('disabled',false).text(txt); }
        });
    });

    function showAccessModal(url, mid) {
        tempUrl = url; tempMid = mid;
        $('#accessModal').modal('show');
    }

    $('#btn-open-link').click(function() {
        if(!tempUrl) return;
        window.open(tempUrl, '_blank'); 
        $('#accessModal').modal('hide');
        Swal.fire({ title: 'Đang thực hiện...', text: 'Đừng đóng tab này nhé!', icon: 'info', timer: 2000, showConfirmButton:false }).then(()=>{ location.reload(); });
    });

    $('#btn-cancel-access').click(function() {
        if(confirm('Hủy sẽ bị trừ 3 Uy tín?')) { $('#report_mid').val(tempMid); submitReport(); $('#accessModal').modal('hide'); }
    });

    function startCooldown(s) {
        waitTime = s; $('#cooldown-badge').fadeIn();
        var i = setInterval(function() {
            waitTime--; $('#timer-val').text(waitTime);
            if (waitTime <= 0) { clearInterval(i); $('#cooldown-badge').fadeOut(); location.reload(); }
        }, 1000);
    }

    $('#reason_select').change(function() { if ($(this).val() == 'Khác') $('#report_note').slideDown(); else $('#report_note').slideUp(); });
    function openReport(id) { $('#report_mid').val(id); $('#reportModal').modal('show'); }
    
    function submitReport() {
        var mid = $('#report_mid').val(); var reason = $('#reason_select').val(); var note = $('#report_note').val();
        $.ajax({
            url: 'ajax_report.php', type: 'POST', dataType: 'json', data: { mission_id: mid, reason: reason, note: note, csrf_token: csrfToken },
            success: function(res) { if (res.status) location.reload(); else Swal.fire('Lỗi', res.msg, 'error'); }
        });
    }
    </script>

<?php require_once '../includes/footer.php'; ?>
