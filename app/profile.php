<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
$uid = (int)$_SESSION['user_id'];

$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $uid);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// XỬ LÝ POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    check_csrf_token();

    // 1. XỬ LÝ ĐỔI AVATAR
    if (isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
        $file = $_FILES['avatar'];
        $max_size = 2 * 1024 * 1024;
        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if ($file['error'] != UPLOAD_ERR_OK) {
            $_SESSION['flash_msg'] = "Lỗi tải file!"; $_SESSION['flash_type'] = "error";
        } elseif ($file['size'] > $max_size) {
            $_SESSION['flash_msg'] = "Ảnh đại diện tối đa 2MB!"; $_SESSION['flash_type'] = "warning";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $image_info = getimagesize($file['tmp_name']);

            if (!isset($allowed_mimes[$mime]) || $image_info === false) {
                $_SESSION['flash_msg'] = "Chỉ chấp nhận ảnh JPG/PNG hợp lệ!"; $_SESSION['flash_type'] = "warning";
            } else {
                $ext = $allowed_mimes[$mime];
                $new_name = "avt_{$uid}_" . bin2hex(random_bytes(8)) . ".{$ext}";
                $path = "../assets/uploads/avatars/";
                if(!is_dir($path)) mkdir($path, 0755, true);

                if (move_uploaded_file($file['tmp_name'], $path.$new_name)) {
                    $old_avatar = $user['avatar'] ?? '';
                    if (strpos($old_avatar, '/assets/uploads/avatars/') === 0 && file_exists("..".$old_avatar)) {
                        @unlink("..".$old_avatar);
                    }

                    $db_path = "/assets/uploads/avatars/$new_name";
                    $avatar_stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $avatar_stmt->bind_param("si", $db_path, $uid);
                    $avatar_stmt->execute();
                    $avatar_stmt->close();

                    $_SESSION['flash_msg'] = "Đổi ảnh đại diện thành công!";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_msg'] = "Lỗi lưu file lên server!"; $_SESSION['flash_type'] = "error";
                }
            }
        }
    }

    // 2. XỬ LÝ ĐỔI MẬT KHẨU
    elseif (isset($_POST['change_pass'])) {
        $old = $_POST['old_pass']; $new = $_POST['new_pass']; $re = $_POST['re_pass'];
        $check = password_verify($old, $user['password']) || md5($old) === $user['password'];

        if (!$check) {
            $_SESSION['flash_msg'] = "Mật khẩu cũ không đúng!"; $_SESSION['flash_type'] = "error";
        } elseif ($new !== $re) {
            $_SESSION['flash_msg'] = "Mật khẩu nhập lại không khớp!"; $_SESSION['flash_type'] = "error";
        } elseif (strlen($new) < 6) {
            $_SESSION['flash_msg'] = "Mật khẩu quá ngắn!"; $_SESSION['flash_type'] = "warning";
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pass_stmt->bind_param("si", $hash, $uid);
            $pass_stmt->execute();
            $pass_stmt->close();
            session_regenerate_id(true);
            $_SESSION['flash_msg'] = "Đổi mật khẩu thành công!"; $_SESSION['flash_type'] = "success";
        }
    }

    header("Location: profile.php");
    exit();
}

require_once '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><i class='bx bx-user-circle'></i> Hồ sơ cá nhân</h4>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block">
                            <img src="<?php echo e($user['avatar'] ?? '/assets/img/avatars/1.png'); ?>?t=<?php echo time(); ?>" alt="Avatar" class="rounded-circle mb-3" style="width:140px; height:140px; object-fit:cover; border: 4px solid #f5f5f9;">
                            <span class="position-absolute bottom-0 end-0 bg-success p-2 border border-light rounded-circle"></span>
                        </div>
                        <h5 class="mb-1 fw-bold"><?php echo e($user['username']); ?></h5>
                        <p class="text-muted mb-3">Thành viên</p>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                            <label class="btn btn-primary w-100 cursor-pointer shadow-sm">
                                <i class='bx bx-camera'></i> Tải ảnh lên
                                <input type="file" name="avatar" accept="image/jpeg,image/png" hidden onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>
                    <hr class="my-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class='bx bx-calendar'></i> Tham gia:</span>
                            <span class="fw-bold"><?php echo e(date('d/m/Y', strtotime($user['created_at']))); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class='bx bx-map-pin'></i> IP Đăng ký:</span>
                            <span class="fw-bold"><?php echo e($user['ip_address'] ?? ''); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <h5 class="card-header bg-label-secondary">Thông tin tài khoản</h5>
                    <div class="card-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Số dư khả dụng</label>
                                <input type="text" class="form-control fw-bold text-primary" value="<?php echo e(number_format($user['money'])); ?> VNĐ" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Điểm Uy Tín</label>
                                <input type="text" class="form-control fw-bold text-success" value="<?php echo e($user['reputation']); ?> điểm" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h5 class="card-header bg-label-warning text-dark">Đổi mật khẩu</h5>
                    <div class="card-body pt-3">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="change_pass" value="1">

                            <div class="mb-3">
                                <label class="form-label">Mật khẩu hiện tại</label>
                                <input type="password" class="form-control" name="old_pass" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Mật khẩu mới</label>
                                    <input type="password" class="form-control" name="new_pass" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Nhập lại mới</label>
                                    <input type="password" class="form-control" name="re_pass" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning fw-bold">Lưu thay đổi</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_msg'])): ?>
<script>
    Swal.fire({
        title: "Thông báo",
        text: <?php echo json_encode($_SESSION['flash_msg']); ?>,
        icon: <?php echo json_encode($_SESSION['flash_type']); ?>,
        confirmButtonText: "OK"
    });
</script>
<?php
unset($_SESSION['flash_msg']);
unset($_SESSION['flash_type']);
endif;
?>

<?php require_once '../includes/footer.php'; ?>
