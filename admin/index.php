<?php
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

// 1. THỐNG KÊ SỐ LIỆU
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_money_paid = $conn->query("SELECT SUM(amount) FROM withdraws WHERE status = 1")->fetch_row()[0];
$pending_withdraws = $conn->query("SELECT COUNT(*) FROM withdraws WHERE status = 0")->fetch_row()[0];
$total_reports = $conn->query("SELECT COUNT(*) FROM reports")->fetch_row()[0];

// 2. BIỂU ĐỒ DOANH THU (Giả lập data 7 ngày gần nhất để vẽ chart cho đẹp)
// Phần này làm màu là chính, sau này web đông khách em code thống kê thật sau.
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Tổng quan hệ thống</h4>

        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="card-info">
                                <p class="card-text">Thành viên</p>
                                <div class="d-flex align-items-end mb-2">
                                    <h4 class="card-title mb-0 me-2"><?php echo number_format($total_users); ?></h4>
                                </div>
                            </div>
                            <div class="card-icon">
                                <span class="badge bg-label-primary rounded p-2"><i class='bx bx-user fs-3'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="card-info">
                                <p class="card-text">Đã chi trả</p>
                                <div class="d-flex align-items-end mb-2">
                                    <h4 class="card-title mb-0 me-2 text-success"><?php echo number_format((int)$total_money_paid); ?> đ</h4>
                                </div>
                            </div>
                            <div class="card-icon">
                                <span class="badge bg-label-success rounded p-2"><i class='bx bx-money fs-3'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="card-info">
                                <p class="card-text">Đơn rút chờ</p>
                                <div class="d-flex align-items-end mb-2">
                                    <h4 class="card-title mb-0 me-2 text-warning"><?php echo number_format($pending_withdraws); ?></h4>
                                    <small class="text-muted">đơn</small>
                                </div>
                            </div>
                            <div class="card-icon">
                                <span class="badge bg-label-warning rounded p-2"><i class='bx bx-time-five fs-3'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="card-info">
                                <p class="card-text">Báo cáo lỗi</p>
                                <div class="d-flex align-items-end mb-2">
                                    <h4 class="card-title mb-0 me-2 text-danger"><?php echo number_format($total_reports); ?></h4>
                                </div>
                            </div>
                            <div class="card-icon">
                                <span class="badge bg-label-danger rounded p-2"><i class='bx bx-error-circle fs-3'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h5 class="card-header">Thành viên mới đăng ký</h5>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead><tr><th>ID</th><th>Username</th><th>Số dư</th><th>Ngày tham gia</th></tr></thead>
                    <tbody>
                        <?php
                        $new_users = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 5");
                        while ($u = $new_users->fetch_assoc()) {
                            echo "<tr>
                                <td>#{$u['id']}</td>
                                <td><strong>{$u['username']}</strong></td>
                                <td class='text-primary'>".number_format($u['money'])." đ</td>
                                <td>{$u['created_at']}</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
