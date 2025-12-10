<?php
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

// --- XỬ LÝ HÀNH ĐỘNG ---

// 1. Thu hồi tiền (Revoke)
if (isset($_GET['revoke_id'])) {
    $hid = (int)$_GET['revoke_id'];
    // Lấy thông tin lịch sử
    $h = $conn->query("SELECT * FROM history WHERE id = $hid AND is_revoked = 0")->fetch_assoc();
    if ($h) {
        $amount = $h['amount'];
        $uid = $h['user_id'];
        
        // Trừ tiền user
        $conn->query("UPDATE users SET money = GREATEST(0, money - $amount), reputation = GREATEST(0, reputation - 10) WHERE id = $uid");
        // Đánh dấu đã thu hồi
        $conn->query("UPDATE history SET is_revoked = 1 WHERE id = $hid");
        
        echo "<script>alert('Đã thu hồi tiền và trừ 10 điểm uy tín!'); window.location.href='history_jobs.php';</script>";
    }
}

// 2. Ban User
if (isset($_GET['ban_user'])) {
    $uid = (int)$_GET['ban_user'];
    $conn->query("UPDATE users SET lv = 0 WHERE id = $uid");
    echo "<script>alert('Đã khóa tài khoản user #$uid!'); window.location.href='history_jobs.php';</script>";
}

// --- XỬ LÝ PHÂN TRANG ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Tổng số dòng
$total_rows = $conn->query("SELECT COUNT(*) FROM history")->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// Lấy dữ liệu
$sql = "SELECT h.*, u.username, m.name as mission_name 
        FROM history h 
        JOIN users u ON h.user_id = u.id 
        LEFT JOIN missions m ON h.mission_id = m.id 
        ORDER BY h.created_at DESC 
        LIMIT $offset, $limit";
$result = $conn->query($sql);
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Quản lý Lịch sử Job</h4>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách hoàn thành</h5>
                
                <form method="GET" class="d-flex align-items-center">
                    <label class="me-2 text-nowrap">Hiển thị:</label>
                    <select name="limit" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                        <option value="10" <?php echo ($limit==10)?'selected':''; ?>>10</option>
                        <option value="25" <?php echo ($limit==25)?'selected':''; ?>>25</option>
                        <option value="50" <?php echo ($limit==50)?'selected':''; ?>>50</option>
                        <option value="100" <?php echo ($limit==100)?'selected':''; ?>>100</option>
                        <option value="200" <?php echo ($limit==200)?'selected':''; ?>>200</option>
                    </select>
                </form>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr class="bg-light">
                            <th>ID</th>
                            <th>Thành viên</th>
                            <th>Job & Code</th>
                            <th>Link Rút Gọn</th>
                            <th>IP Làm Job</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $ip = $row['ip_address'] ? $row['ip_address'] : 'KXD';
                                $status = ($row['is_revoked'] == 1) 
                                    ? '<span class="badge bg-danger">Đã thu hồi</span>' 
                                    : '<span class="badge bg-success">Hoàn thành</span>';
                                
                                echo "<tr>
                                    <td>#{$row['id']}</td>
                                    <td>
                                        <div class='fw-bold'>{$row['username']}</div>
                                        <small class='text-muted'>ID: {$row['user_id']}</small>
                                    </td>
                                    <td>
                                        <div class='text-primary'>{$row['mission_name']}</div>
                                        <code>{$row['code']}</code>
                                    </td>
                                    <td>
                                        <input type='text' class='form-control form-control-sm' value='{$row['short_link']}' readonly onclick='this.select()'>
                                    </td>
                                    <td>
                                        <button class='btn btn-xs btn-outline-info' onclick='checkIP(\"$ip\")'>
                                            <i class='bx bx-target-lock'></i> $ip
                                        </button>
                                    </td>
                                    <td>$status</td>
                                    <td>
                                        <div class='dropdown'>
                                            <button type='button' class='btn p-0 dropdown-toggle hide-arrow' data-bs-toggle='dropdown'><i class='bx bx-dots-vertical-rounded'></i></button>
                                            <div class='dropdown-menu'>
                                                <a class='dropdown-item text-warning' href='?revoke_id={$row['id']}' onclick='return confirm(\"Thu hồi tiền job này?\")'><i class='bx bx-money me-1'></i> Thu hồi tiền</a>
                                                <a class='dropdown-item text-danger' href='?ban_user={$row['user_id']}' onclick='return confirm(\"Khóa tài khoản này vĩnh viễn?\")'><i class='bx bx-block me-1'></i> Ban User</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4'>Chưa có dữ liệu</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-end">
                <nav>
                    <ul class="pagination">
                        <?php if($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&limit=<?php echo $limit; ?>">Trước</a></li>
                        <?php endif; ?>
                        
                        <li class="page-item active"><a class="page-link" href="#"><?php echo $page; ?> / <?php echo $total_pages; ?></a></li>
                        
                        <?php if($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&limit=<?php echo $limit; ?>">Sau</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ipModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lịch sử hoạt động IP: <span id="modal_ip_title" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">Dữ liệu trong 7 ngày gần nhất</div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Thời gian</th><th>User</th><th>Job</th><th>Trạng thái</th></tr></thead>
                        <tbody id="ip_history_body">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkIP(ip) {
    if(ip == 'KXD') { alert('Không xác định được IP'); return; }
    
    // Set title
    document.getElementById('modal_ip_title').innerText = ip;
    document.getElementById('ip_history_body').innerHTML = '<tr><td colspan="4" class="text-center">Đang tải dữ liệu...</td></tr>';
    
    // Gọi AJAX lấy lịch sử
    const modal = new bootstrap.Modal(document.getElementById('ipModal'));
    modal.show();

    fetch('ajax_check_ip.php?ip=' + ip)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if(data.length > 0) {
                data.forEach(item => {
                    let st = (item.is_revoked == 1) ? '<span class="text-danger">Thu hồi</span>' : '<span class="text-success">OK</span>';
                    html += `<tr>
                        <td>${item.created_at}</td>
                        <td>${item.username}</td>
                        <td>${item.mission_name}</td>
                        <td>${st}</td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="4" class="text-center">IP này chưa làm job nào khác trong 7 ngày qua.</td></tr>';
            }
            document.getElementById('ip_history_body').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('ip_history_body').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
