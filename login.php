<?php
require_once 'config/database.php';

// Check nếu đã đăng nhập thì đá đi chỗ khác
if (isset($_SESSION['user_id'])) { 
    header("Location: ".($_SESSION['lv']==9 ? "admin/index.php" : "app/index.php")); 
    exit(); 
}

$msg = ""; $msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Check CSRF
    if(function_exists('check_csrf_token')) check_csrf_token();

    $u = trim($_POST['username']);
    $p = $_POST['password'];
    $remember = isset($_POST['remember']); // Check xem có tick ghi nhớ không
    $ip = $_SERVER['REMOTE_ADDR'];

    // 2. CHỐNG BRUTE-FORCE (Check 5 lần sai)
    $check_spam = $conn->query("SELECT COUNT(*) FROM failed_logins WHERE ip_address = '$ip' AND attempt_time > DATE_SUB(NOW(), INTERVAL 10 MINUTE)")->fetch_row()[0];

    if ($check_spam >= 5) {
        $msg = "Bạn đã nhập sai quá 5 lần! Vui lòng thử lại sau 10 phút."; $msg_type = "danger";
    } else {
        // 3. LẤY INFO USER
        $stmt = $conn->prepare("SELECT id, password, lv, ban FROM users WHERE username = ?");
        $stmt->bind_param("s", $u);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $is_valid = false;
            $rehash_needed = false;
            
            // Check Pass (Bcrypt hoặc MD5 cũ)
            if (password_verify($p, $row['password'])) { 
                $is_valid = true; 
            } elseif (md5($p) === $row['password']) { 
                $is_valid = true; 
                $rehash_needed = true;
            }
            
            if ($is_valid) {
                if ($row['ban'] == 1) {
                    $msg = "Tài khoản của bạn đã bị KHÓA vĩnh viễn!"; $msg_type = "danger";
                } else {
                    // Nâng cấp pass MD5 lên Bcrypt nếu cần
                    if ($rehash_needed) {
                        $new_hash = password_hash($p, PASSWORD_BCRYPT);
                        $conn->query("UPDATE users SET password = '$new_hash' WHERE id = {$row['id']}");
                    }

                    // Xóa log đăng nhập sai
                    $conn->query("DELETE FROM failed_logins WHERE ip_address = '$ip'"); 
                    session_regenerate_id(true); 
                    
                    // LƯU SESSION
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['lv'] = $row['lv'];

                    // --- [TÍNH NĂNG MỚI] GHI NHỚ ĐĂNG NHẬP 7 NGÀY ---
                    if ($remember) {
                        // 1. Tạo Token ngẫu nhiên an toàn
                        $token = bin2hex(random_bytes(32)); 
                        
                        // 2. Lưu Token vào Database (Để đối chiếu sau này)
                        $tk_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $tk_stmt->bind_param("si", $token, $row['id']);
                        $tk_stmt->execute();
                        
                        // 3. Lưu Token vào Cookie (7 ngày, HttpOnly để chống XSS lấy trộm)
                        setcookie('site_remember', $token, time() + (86400 * 7), "/", "", false, true);
                    }
                    // --------------------------------------------------
                    
                    $redirect = ($row['lv'] == 9) ? "admin/index.php" : "app/index.php";
                    header("Location: $redirect"); exit();
                }
            } else {
                $msg = "Mật khẩu không chính xác!"; $msg_type = "danger";
                $conn->query("INSERT INTO failed_logins (ip_address, attempt_time) VALUES ('$ip', NOW())");
            }
        } else {
            $msg = "Tài khoản không tồn tại!"; $msg_type = "danger";
            $conn->query("INSERT INTO failed_logins (ip_address, attempt_time) VALUES ('$ip', NOW())");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi" class="light-style customizer-hide">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng nhập</title>
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="/assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />
    <link rel="stylesheet" href="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="/assets/vendor/css/pages/page-auth.css" />
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
              <h4 class="mb-2">Đăng nhập 👋</h4>
              <p class="mb-4">Vui lòng đăng nhập để tiếp tục</p>

              <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible" role="alert">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <form id="formAuthentication" class="mb-3" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div class="mb-3">
                  <label for="username" class="form-label">Tên đăng nhập</label>
                  <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" autofocus required />
                </div>
                <div class="mb-3 form-password-toggle">
                  <div class="d-flex justify-content-between">
                    <label class="form-label" for="password">Mật khẩu</label>
                    </div>
                  <div class="input-group input-group-merge">
                    <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                  </div>
                </div>
                <div class="mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember-me" name="remember" />
                    <label class="form-check-label" for="remember-me"> Ghi nhớ thiết bị (7 ngày) </label>
                  </div>
                </div>
                <div class="mb-3">
                  <button class="btn btn-primary d-grid w-100" type="submit">Đăng nhập</button>
                </div>
              </form>

              <p class="text-center">
                <span>Người dùng mới?</span>
                <a href="register.php">
                  <span>Đăng ký ngay</span>
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="/assets/vendor/js/helpers.js"></script>
    <script src="/assets/js/config.js"></script>
    <script src="/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/assets/vendor/libs/popper/popper.js"></script>
    <script src="/assets/vendor/js/bootstrap.js"></script>
    <script src="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/assets/vendor/js/menu.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
