<?php
// === START: PHẦN PHP CẦN CHẠY TRƯỚC HTML (FIX TRANG TRẮNG & PRG) ===

// 1. BẢO VỆ & KẾT NỐI
require_once 'protect.php'; 
require_once '../config/database.php';

$msg = ""; 
$msg_type = "";

// 2. XỬ LÝ LƯU CẤU HÌNH (POST) VÀ REDIRECT (PRG)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    
    // Lấy dữ liệu từ Form
    $site_name = trim($_POST['site_name']);
    $site_title = trim($_POST['site_title']);
    $site_desc = trim($_POST['site_description']);
    $site_key = trim($_POST['site_keywords']);
    
    $min_withdraw = (int)$_POST['min_withdraw'];
    $ref_percent = (int)$_POST['ref_percent']; 
    
    $tele = trim($_POST['tele_support']);
    $maint = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    // SỬA LẠI: Lấy giá trị chuỗi từ Dropdown (tet, noel, none...)
    $holiday = trim($_POST['holiday_mode']); 

    // Lấy CMS hiện tại để xử lý Logo
    $cms_temp = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc();
    $logo_path = $cms_temp['site_logo']; 
    
    // --- XỬ LÝ UPLOAD LOGO ---
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['size'] > 0) {
        $file = $_FILES['site_logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $new_name = "logo_" . time() . "." . $ext;
            $upload_dir = "../assets/uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $logo_path = "/assets/uploads/" . $new_name;
            } else {
                $msg = "Lỗi: Không thể ghi file logo!"; 
                $msg_type = "danger";
            }
        } else {
            $msg = "Lỗi: Định dạng ảnh không hỗ trợ!"; 
            $msg_type = "danger";
        }
    }
    // --- KẾT THÚC XỬ LÝ UPLOAD LOGO ---

    // Nếu không có lỗi thì lưu vào DB và REDIRECT
    if (empty($msg)) {
        // SQL Update
        $sql = "UPDATE settings SET 
                site_name=?, site_title=?, site_description=?, site_keywords=?, 
                min_withdraw=?, tele_support=?, maintenance_mode=?, holiday_mode=?, 
                site_logo=?, ref_percent=? 
                WHERE id=1";
                
        $stmt = $conn->prepare($sql);
        // Bind param: ssssisisis
        $stmt->bind_param("ssssisisis",
            $site_name, $site_title, $site_desc, $site_key, 
            $min_withdraw, $tele, $maint, $holiday, 
            $logo_path, $ref_percent
        );
        
        if ($stmt->execute()) {
            // LƯU THÔNG BÁO VÀO SESSION VÀ CHUYỂN HƯỚNG
            $_SESSION['msg'] = "Đã lưu cấu hình hệ thống thành công!"; 
            $_SESSION['msg_type'] = "success";
            
            header('Location: ' . $_SERVER['PHP_SELF']); 
            exit(); 
        } else {
            $msg = "Lỗi Database: " . $conn->error; 
            $msg_type = "danger";
        }
    }
}

// 3. XỬ LÝ THÔNG BÁO VÀ LOAD CẤU HÌNH HIỆN TẠI
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msg_type = $_SESSION['msg_type'];
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}

// LOAD CẤU HÌNH CUỐI CÙNG SAU KHI LƯU HOẶC SAU KHI REDIRECT
$cms = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc();

// === END: PHẦN PHP CẦN CHẠY TRƯỚC HTML ===
?>

<?php 
// 4. INCLUDE HEADER (Bắt đầu xuất HTML)
require_once 'header.php'; 
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Cấu hình /</span> Chung</h4>

        <?php if (!empty($msg)) echo "<div class=\"alert alert-$msg_type animate__animated animate__fadeInDown\"><i class='bx bx-bell me-2'></i> $msg</div>"; ?>

        <div class="card">
            <div class="card-header bg-label-primary text-primary fw-bold">
                <i class='bx bx-cog me-1'></i> CẤU HÌNH CHUNG WEBSITE
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="save_settings" value="1">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3 text-primary"><i class='bx bx-globe'></i> Cài đặt SEO</h6>

                            <div class="mb-3">
                                <label for="site_name" class="form-label">Tên website</label>
                                <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($cms['site_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="site_title" class="form-label">Tiêu đề (Title)</label>
                                <input type="text" class="form-control" name="site_title" value="<?php echo htmlspecialchars($cms['site_title']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Mô tả ngắn (Description)</label>
                                <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars($cms['site_description']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="site_keywords" class="form-label">Từ khóa (Keywords)</label>
                                <input type="text" class="form-control" name="site_keywords" value="<?php echo htmlspecialchars($cms['site_keywords']); ?>">
                            </div>
                            
                            <h6 class="border-bottom pb-2 mb-3 text-primary mt-4"><i class='bx bx-image-alt'></i> Logo & Avatar</h6>
                            <div class="mb-3">
                                <label for="site_logo" class="form-label">Logo website</label>
                                <input class="form-control" type="file" id="site_logo" name="site_logo" accept=".jpg,.jpeg,.png,.gif,.svg,.webp">
                                <?php if (!empty($cms['site_logo'])): ?>
                                <small class="text-muted d-block mt-1">Logo hiện tại: <img src="<?php echo $cms['site_logo']; ?>" style="max-height: 40px; border: 1px solid #eee;"></small>
                                <?php endif; ?>
                            </div>

                        </div>
                        
                        <div class="col-md-6 border-start border-light-2">
                            <h6 class="border-bottom pb-2 mb-3 text-primary"><i class='bx bx-wrench'></i> Cài đặt Chức năng</h6>
                            
                            <div class="mb-3">
                                <label for="min_withdraw" class="form-label">Số dư rút tối thiểu (đ)</label>
                                <input type="number" class="form-control" name="min_withdraw" value="<?php echo (int)$cms['min_withdraw']; ?>" required>
                                <small class="text-muted">Số tiền tối thiểu thành viên cần có để tạo lệnh rút tiền.</small>
                            </div>

                            <div class="mb-3">
                                <label for="ref_percent" class="form-label">% Hoa hồng giới thiệu</label>
                                <input type="number" class="form-control" name="ref_percent" value="<?php echo (int)$cms['ref_percent']; ?>" min="0" max="100">
                                <small class="text-muted">Phần trăm hoa hồng mà người giới thiệu nhận được trên mỗi nhiệm vụ thành viên mới làm.</small>
                            </div>

                            <div class="mb-3">
                                <label for="tele_support" class="form-label">Link Telegram hỗ trợ</label>
                                <input type="text" class="form-control" name="tele_support" value="<?php echo htmlspecialchars($cms['tele_support']); ?>" placeholder="https://t.me/...">
                            </div>

                            <div class="card bg-label-secondary p-3 mt-4">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" <?php echo ($cms['maintenance_mode'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold text-danger" for="maintenance_mode">BẬT CHẾ ĐỘ BẢO TRÌ</label>
                                </div>
                                <small class="text-muted">Khi bật, chỉ có Admin mới truy cập được website. Thành viên sẽ thấy trang thông báo bảo trì.</small>
                            </div>
                            
                            <div class="card bg-label-info p-3 mt-4">
                                <label class="form-label fw-bold text-primary" for="holiday_mode">
                                    <i class='bx bx-party'></i> CHẾ ĐỘ HIỆU ỨNG LỄ HỘI
                                </label>
                                <select class="form-select" name="holiday_mode" id="holiday_mode">
                                    <option value="none" <?php echo ($cms['holiday_mode'] == 'none') ? 'selected' : ''; ?>>🔴 Tắt hiệu ứng (Mặc định)</option>
                                    <option value="tet" <?php echo ($cms['holiday_mode'] == 'tet') ? 'selected' : ''; ?>>🌸 Tết Nguyên Đán (Hoa đào + Pháo)</option>
                                    <option value="noel" <?php echo ($cms['holiday_mode'] == 'noel') ? 'selected' : ''; ?>>🎄 Giáng Sinh (Tuyết rơi + Santa)</option>
                                    <option value="val" <?php echo ($cms['holiday_mode'] == 'val') ? 'selected' : ''; ?>>❤️ Valentine (Tim bay + Cupid)</option>
                                    <option value="halloween" <?php echo ($cms['holiday_mode'] == 'halloween') ? 'selected' : ''; ?>>🎃 Halloween (Ma bay)</option>
                                    <option value="hbd" <?php echo ($cms['holiday_mode'] == 'hbd') ? 'selected' : ''; ?>>🎂 Sinh nhật (Pháo giấy)</option>
                                </select>
                                <small class="text-muted mt-2 d-block">Chọn hiệu ứng sẽ hiển thị trên toàn website (Footer).</small>
                            </div>
                            </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="reset" class="btn btn-label-secondary me-2">Nhập lại</button>
                        <button type="submit" name="save_settings" class="btn btn-primary px-5 fw-bold">
                            <i class='bx bx-save'></i> LƯU CẤU HÌNH
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
