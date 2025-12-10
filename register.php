<?php
require_once 'config/database.php';

// Check đã đăng nhập chưa
if (isset($_SESSION['user_id'])) { header("Location: app/index.php"); exit(); }

$ref_id = isset($_GET['ref']) ? (int)$_GET['ref'] : 0;
$msg = ""; $msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. CHECK CSRF (Bắt buộc)
    if(function_exists('check_csrf_token')) check_csrf_token();

    $u = trim($_POST['username']);
    $p = $_POST['password'];
    $rp = $_POST['repassword'];
    $ref = (int)$_POST['ref_id'];
    $terms = isset($_POST['terms']);
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Validate tên đăng nhập (Chỉ chữ thường, hoa, số, gạch dưới. 5-20 ký tự)
    // Cái này chống hacker đặt tên kiểu <script>alert(1)</script>
    $is_valid_user = preg_match('/^[a-zA-Z0-9_]{5,20}$/', $u);

    // 2. CHECK SPAM & VALIDATE
    $check_ip = $conn->query("SELECT COUNT(*) FROM users WHERE ip_address = '$ip'")->fetch_row()[0];

    if ($check_ip >= 3) {
        $msg = "IP này đã tạo quá giới hạn (3 acc)!"; $msg_type = "danger";
    } elseif (!$is_valid_user) {
        $msg = "Tên đăng nhập 5-20 ký tự, viết liền không dấu, không ký tự đặc biệt!"; $msg_type = "danger";
    } elseif (empty($p)) {
        $msg = "Vui lòng nhập mật khẩu!"; $msg_type = "danger";
    } elseif ($p !== $rp) {
        $msg = "Mật khẩu nhập lại không khớp!"; $msg_type = "danger";
    } elseif (!$terms) {
        $msg = "Bạn chưa đồng ý với điều khoản!"; $msg_type = "danger";
    } else {
        // 3. CHECK TRÙNG USERNAME (Prepared Statement)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $u);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $msg = "Tên đăng nhập này đã tồn tại!"; $msg_type = "danger";
        } else {
            // 4. TẠO TÀI KHOẢN (BCRYPT)
            $hash = password_hash($p, PASSWORD_BCRYPT);
            
            // Insert
            $stmt = $conn->prepare("INSERT INTO users (username, password, ip_address, referred_by, created_at, money, lv, reputation) VALUES (?, ?, ?, ?, NOW(), 0, 1, 0)");
            $stmt->bind_param("sssi", $u, $hash, $ip, $ref);
            
            if ($stmt->execute()) {
                $msg = "Đăng ký thành công! Đang chuyển hướng..."; $msg_type = "success";
                echo "<script>setTimeout(function(){ window.location.href='login.php'; }, 2000);</script>";
            } else {
                $msg = "Lỗi hệ thống! Vui lòng thử lại."; $msg_type = "danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Đăng ký tài khoản</title>
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="/assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />
    <link rel="stylesheet" href="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="/assets/vendor/css/pages/page-auth.css" />
    <script src="/assets/vendor/js/helpers.js"></script>
    <script src="/assets/js/config.js"></script>
</head>

<body>
    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
          <div class="card">
            <div class="card-body">
              <div class="app-brand justify-content-center">
                <a href="index.php" class="app-brand-link gap-2">
                  <span class="app-brand-text demo text-body fw-bolder text-uppercase">VIETRUST MMO</span>
                </a>
              </div>
              <h4 class="mb-2">Đăng ký ngay 🚀</h4>
              <p class="mb-4">Tạo tài khoản để bắt đầu kiếm tiền online dễ dàng!</p>

              <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible" role="alert">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <form id="formAuthentication" class="mb-3" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="ref_id" value="<?php echo $ref_id; ?>">
                
                <div class="mb-3">
                  <label for="username" class="form-label">Tên đăng nhập</label>
                  <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" autofocus required />
                </div>
                
                <div class="mb-3 form-password-toggle">
                  <label class="form-label" for="password">Mật khẩu</label>
                  <div class="input-group input-group-merge">
                    <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  </div>
                </div>

                <div class="mb-3 form-password-toggle">
                  <label class="form-label" for="repassword">Nhập lại mật khẩu</label>
                  <div class="input-group input-group-merge">
                    <input type="password" id="repassword" class="form-control" name="repassword" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms" required />
                    <label class="form-check-label" for="terms-conditions">
                      Tôi đồng ý với <a href="javascript:void(0);">chính sách & điều khoản</a>
                    </label>
                  </div>
                </div>
                <button class="btn btn-primary d-grid w-100">Đăng ký</button>
              </form>

              <p class="text-center">
                <span>Bạn đã có tài khoản?</span>
                <a href="login.php">
                  <span>Đăng nhập thay thế</span>
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/assets/vendor/libs/popper/popper.js"></script>
    <script src="/assets/vendor/js/bootstrap.js"></script>
    <script src="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/assets/vendor/js/menu.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
