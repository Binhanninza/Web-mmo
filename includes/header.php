<?php
// 1. LOGIC PHP & BẢO MẬT
require_once __DIR__ . '/../config/database.php';

// Check login (Redirect nếu chưa đăng nhập)
if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
}

$cur = basename($_SERVER['PHP_SELF']);
$menu_money = 0; $user_lv = 0; $my_avt = '/assets/img/avatars/1.png';

// Lấy Info User
if (isset($_SESSION['user_id'])) {
    $uid_menu = $_SESSION['user_id'];
    if(isset($conn)) {
        $conn->query("UPDATE users SET last_active = NOW() WHERE id = $uid_menu");
        $res_menu = $conn->query("SELECT money, lv, avatar FROM users WHERE id = $uid_menu");
        if ($res_menu && $res_menu->num_rows > 0) {
            $d = $res_menu->fetch_assoc();
            $menu_money = $d['money']; 
            $user_lv = $d['lv'];
            if (!empty($d['avatar'])) $my_avt = $d['avatar'];
        }
    }
}

// Lấy Setting Web
$site_name = 'MMO TOOL';
$site_logo = '';
if(isset($conn)) {
    $query_cms = $conn->query("SELECT * FROM settings WHERE id=1");
    if ($query_cms && $query_cms->num_rows > 0) {
        $CMS = $query_cms->fetch_assoc();
        if(isset($CMS['site_name'])) $site_name = $CMS['site_name'];
        if(isset($CMS['site_logo'])) $site_logo = $CMS['site_logo'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi" class="light-style layout-menu-fixed" dir="ltr" data-assets-path="/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?php echo $site_name; ?></title>
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico" />
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
    
    <link rel="stylesheet" href="/assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />
    <link rel="stylesheet" href="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    
    <script src="/assets/vendor/js/helpers.js"></script>
    <script src="/assets/js/config.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark-mode'); }</script>
    
    <style>
        /* --- ÉP DÙNG FONT QUICKSAND (Trừ Icon ra) --- */
        body, h1, h2, h3, h4, h5, h6, p, span, div, a, button, input, select, textarea, .btn, .card, .menu-link, .fw-bold, .fw-semibold {
            font-family: 'Quicksand', sans-serif !important;
        }
        
        /* --- FIX ICON BOXICONS (Đảm bảo font của icon không bị đè) --- */
        .bx, .bx-menu, .menu-icon, .tf-icons {
            font-family: 'boxicons' !important; /* Quan trọng VCL */
        }

        /* --- DARK MODE --- */
        html.dark-mode body { background-color: #232333; color: #a3a4cc; }
        html.dark-mode .bg-menu-theme, html.dark-mode .bg-navbar-theme, html.dark-mode .footer { background-color: #2b2c40 !important; color: #a3a4cc; }
        html.dark-mode .card { background-color: #2b2c40; color: #a3a4cc; border:none; }
        html.dark-mode .form-control, html.dark-mode .form-select { background-color: #323349; border-color: #444564; color: #fff; }
        html.dark-mode .menu-link { color: #a3a4cc; }
        html.dark-mode .dropdown-menu { background-color: #2b2c40; border-color: #444564; }

        /* --- FIX AVATAR & NAVBAR --- */
        .navbar-nav-right {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: nowrap;
            width: 100%;
        }

        .avatar {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important; 
            min-height: 40px !important; 
            flex-shrink: 0 !important; 
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50% !important; 
            overflow: hidden !important; 
            border: 2px solid rgba(255,255,255,0.2); 
        }
        
        .avatar img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important; 
            object-position: center !important;
            display: block;
            border-radius: 0 !important;
        }

        .layout-menu-toggle {
            margin-right: 15px;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="/index.php" class="app-brand-link">
              <?php if(!empty($site_logo)): ?>
                  <span class="app-brand-logo demo"><img src="<?php echo $site_logo; ?>" style="max-height:40px;"></span>
              <?php else: ?>
                  <span class="app-brand-text demo menu-text fw-bolder ms-2 text-uppercase"><?php echo $site_name; ?></span>
              <?php endif; ?>
            </a>
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>
          <div class="menu-inner-shadow"></div>
          
          <ul class="menu-inner py-1">
            <?php if ($user_lv == 9): ?>
            <li class="menu-item" style="background: #ffe0e3;">
                <a href="/admin/index.php" class="menu-link"><i class="menu-icon tf-icons bx bx-crown text-danger"></i><div class="text-danger fw-bold">Admin Panel</div></a>
            </li>
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Khu vực User</span></li>
            <?php endif; ?>
            
            <li class="menu-item <?php echo ($cur == 'index.php') ? 'active' : ''; ?>">
                <a href="/app/index.php" class="menu-link"><i class="menu-icon tf-icons bx bx-home-circle"></i><div>Trang chủ</div></a>
            </li>
            <li class="menu-item <?php echo ($cur == 'mission.php') ? 'active' : ''; ?>">
                <a href="/app/mission.php" class="menu-link"><i class="menu-icon tf-icons bx bx-dollar-circle"></i><div>Kiếm tiền ngay</div></a>
            </li>
            <li class="menu-item <?php echo ($cur == 'referral.php') ? 'active' : ''; ?>">
                <a href="/app/referral.php" class="menu-link"><i class="menu-icon tf-icons bx bx-share-alt"></i><div>Giới thiệu</div></a>
            </li>
            <li class="menu-item <?php echo ($cur == 'top.php') ? 'active' : ''; ?>">
                <a href="/app/top.php" class="menu-link"><i class="menu-icon tf-icons bx bx-trophy"></i><div>Đua Top</div></a>
            </li>
            <li class="menu-item <?php echo ($cur == 'wallet.php') ? 'active' : ''; ?>">
                <a href="/app/wallet.php" class="menu-link"><i class="menu-icon tf-icons bx bx-wallet"></i><div>Rút tiền</div></a>
            </li>
            <li class="menu-item <?php echo ($cur == 'giftcode.php') ? 'active' : ''; ?>">
                <a href="/app/giftcode.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-gift text-warning"></i>
                    <div class="fw-bold text-warning">Nhập Code (Hot)</div>
                </a>
            </li>

            <li class="menu-item <?php echo ($cur == 'profile.php') ? 'active' : ''; ?>">
                <a href="/app/profile.php" class="menu-link"><i class="menu-icon tf-icons bx bx-user-circle"></i><div>Tài khoản</div></a>
            </li>
            <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'ticket.php' ? 'active' : ''; ?>">
                <a href="/app/ticket.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-support text-primary"></i>
                    <div data-i18n="Support">Hỗ trợ / Ticket</div>
                </a>
            </li>

            
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Tài chính</span></li>
            <li class="menu-item disabled">
                <a href="javascript:void(0);" class="menu-link" style="cursor:default!important;">
                    <i class="menu-icon tf-icons bx bx-coin-stack"></i>
                    <div class="fw-bold text-danger">Số dư: <?php echo number_format($menu_money); ?> đ</div>
                </a>
            </li>
          </ul>
        </aside>

        <div class="layout-page">
          <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
            
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                
                <div class="navbar-nav align-items-center flex-grow-1">
                     <span class="fw-bold text-primary fs-4 text-uppercase text-nowrap">
                        <?php echo $site_name; ?>
                     </span>
                </div>

                <ul class="navbar-nav flex-row align-items-center ms-auto">
                    <li class="nav-item me-3">
                        <a class="nav-link style-switcher-toggle hide-arrow p-0" href="javascript:void(0);" id="btn-theme-toggle">
                            <i class="bx bx-moon bx-sm" id="icon-theme"></i>
                        </a>
                    </li>

                    <li class="nav-item navbar-dropdown dropdown-user dropdown">
                      <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                        <div class="avatar avatar-online"> 
                            <img src="<?php echo !empty($my_avt) ? $my_avt : '/assets/img/avatars/1.png'; ?>" 
                                 alt="Avatar" 
                                 onerror="this.src='/assets/img/avatars/1.png'" />
                        </div>
                      </a>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar avatar-online">
                                            <img src="<?php echo !empty($my_avt) ? $my_avt : '/assets/img/avatars/1.png'; ?>" onerror="this.src='/assets/img/avatars/1.png'" />
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-semibold d-block">ID: <?php echo $_SESSION['user_id']; ?></span>
                                        <small class="text-muted">Thành viên</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li><div class="dropdown-divider"></div></li>
                        <li><a class="dropdown-item" href="/app/profile.php"><i class="bx bx-user me-2"></i>Tài khoản</a></li>
                        <li><div class="dropdown-divider"></div></li>
                        <li><a class="dropdown-item" href="/logout.php"><i class="bx bx-power-off me-2"></i>Đăng xuất</a></li>
                      </ul>
                    </li>
                </ul>
            </div>
          </nav>
