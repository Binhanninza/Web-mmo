<?php
// 1. KÍCH HOẠT BẢO VỆ 404 (QUAN TRỌNG NHẤT)
require_once 'protect.php'; 

require_once '../config/database.php';
// Check login lần nữa cho chắc (dù protect.php đã check rồi)
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

$cur = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi" class="light-style layout-menu-fixed" dir="ltr" data-assets-path="/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>QUẢN TRỊ - <?php echo isset($CMS['site_name']) ? $CMS['site_name'] : 'System'; ?></title>
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
    <link rel="stylesheet" href="/assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />
    <link rel="stylesheet" href="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <script src="/assets/vendor/js/helpers.js"></script>
    <script src="/assets/js/config.js"></script>
    <script>if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark-mode'); }</script>
    <style>
        html.dark-mode body { background-color: #232333; color: #a3a4cc; }
        html.dark-mode .bg-menu-theme, html.dark-mode .bg-navbar-theme, html.dark-mode .footer { background-color: #2b2c40 !important; color: #a3a4cc; }
        html.dark-mode .card { background-color: #2b2c40; color: #a3a4cc; border: none; }
        html.dark-mode .form-control, html.dark-mode .form-select { background-color: #323349; border-color: #444564; color: #fff; }
        html.dark-mode .menu-link, html.dark-mode .nav-link { color: #a3a4cc; }
        html.dark-mode .dropdown-menu { background-color: #2b2c40; border-color: #444564; }
        html.dark-mode .table { color: #a3a4cc; }
        html.dark-mode .table-hover tbody tr:hover { color: #fff; background-color: #323349; }
    </style>
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">
            <a href="index.php" class="app-brand-link"><span class="app-brand-text demo menu-text fw-bolder ms-2 text-uppercase text-danger">ADMIN</span></a>
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>
          <div class="menu-inner-shadow"></div>
          
          <ul class="menu-inner py-1">
            <li class="menu-item <?php echo ($cur == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php" class="menu-link"><i class="menu-icon tf-icons bx bx-grid-alt"></i><div>Tổng quan</div></a>
            </li>
            <li class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'giftcodes.php') ? 'active' : ''; ?>">
    <a href="/admin/giftcodes.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-gift"></i>
        <div>Quản lý Giftcode</div>
    </a>
</li>

            
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Quản lý</span></li>
            
            <li class="menu-item <?php echo ($cur == 'users.php') ? 'active' : ''; ?>">
                <a href="users.php" class="menu-link"><i class="menu-icon tf-icons bx bx-user"></i><div>Thành viên</div></a>
            </li>
            
            <li class="menu-item <?php echo ($cur == 'missions.php') ? 'active' : ''; ?>">
                <a href="missions.php" class="menu-link"><i class="menu-icon tf-icons bx bx-list-check"></i><div>Nhiệm vụ API</div></a>
            </li>
            
            <li class="menu-item <?php echo ($cur == 'history_jobs.php') ? 'active' : ''; ?>">
                <a href="history_jobs.php" class="menu-link"><i class="menu-icon tf-icons bx bx-history"></i><div>Lịch sử Job</div></a>
            </li>
            <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'active' : ''; ?>">
    <a href="tickets.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-message-square-dots text-warning"></i>
        <div data-i18n="Tickets">Danh sách Hỗ trợ</div>
        
        <?php 
        // Đếm tin chưa đọc (Logic nhanh)
        global $conn;
        $unread = $conn->query("SELECT COUNT(*) FROM tickets WHERE status=0 AND is_read_admin=0")->fetch_row()[0];
        if($unread > 0) echo '<span class="badge badge-center rounded-pill bg-danger ms-auto">'.$unread.'</span>';
        ?>
    </a>
</li>

            
            <li class="menu-item <?php echo ($cur == 'withdraws.php') ? 'active' : ''; ?>">
                <a href="withdraws.php" class="menu-link"><i class="menu-icon tf-icons bx bx-money"></i><div>Duyệt Rút Tiền</div></a>
            </li>

            <li class="menu-item <?php echo ($cur == 'posts.php') ? 'active' : ''; ?>">
                <a href="posts.php" class="menu-link"><i class="menu-icon tf-icons bx bx-news"></i><div>Đăng bài viết</div></a>
            </li>

            <li class="menu-item <?php echo ($cur == 'comments.php') ? 'active' : ''; ?>">
                <a href="comments.php" class="menu-link"><i class="menu-icon tf-icons bx bx-chat"></i><div>Duyệt Comment</div></a>
            </li>

            <li class="menu-item <?php echo ($cur == 'reports.php') ? 'active' : ''; ?>">
                <a href="reports.php" class="menu-link"><i class="menu-icon tf-icons bx bx-error"></i><div>Báo cáo lỗi</div></a>
            </li>

            <li class="menu-header small text-uppercase"><span class="menu-header-text">Hệ thống</span></li>
            
            <li class="menu-item <?php echo ($cur == 'settings.php') ? 'active' : ''; ?>">
                <a href="settings.php" class="menu-link"><i class="menu-icon tf-icons bx bx-cog"></i><div>Cài đặt chung</div></a>
            </li>
            <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
    <a href="events.php" class="menu-link">
        <i class="menu-icon tf-icons bx bx-trophy text-warning"></i>
        <div>Sự kiện Đua Top</div>
    </a>
</li>

            
            <li class="menu-item">
                <a href="../app/index.php" class="menu-link"><i class="menu-icon tf-icons bx bx-log-out-circle"></i><div>Về trang User</div></a>
            </li>
          </ul>
        </aside>
        
        <div class="layout-page">
            <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                  <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                    <i class="bx bx-menu bx-sm"></i>
                  </a>
                </div>
                <div class="navbar-nav-right d-flex align-items-center justify-content-end w-100">
                    <span class="fw-bold me-3 text-danger">Quản trị viên</span>
                    <div class="avatar avatar-online"><img src="/assets/img/avatars/1.png" alt class="rounded-circle" /></div>
                </div>
            </nav>
            