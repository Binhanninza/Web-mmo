<?php
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

// XỬ LÝ CỘNG/TRỪ TIỀN & SỬA USER (GIỮ NGUYÊN CODE CŨ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    check_csrf_token();
    $uid = (int)$_POST['user_id'];
    $action = $_POST['money_action']; $amount = (int)$_POST['money_amount']; $reason = trim($_POST['reason']);
    $new_lv = (int)$_POST['lv']; $new_ban = (int)$_POST['ban']; $new_rep = (int)$_POST['reputation'];

    if ($action != 'none' && $amount > 0) {
        $type = ($action == 'plus') ? 'admin_add' : 'admin_sub';
        $real_amt = ($action == 'plus') ? $amount : -$amount;
        $desc = "Admin: " . ($reason ? $reason : 'Chỉnh sửa');
        change_balance($uid, $real_amt, $type, $_SESSION['user_id'], $desc);
    }
    $stmt = $conn->prepare("UPDATE users SET lv=?, ban=?, reputation=? WHERE id=?");
    $stmt->bind_param("iiii", $new_lv, $new_ban, $new_rep, $uid);
    $stmt->execute();
    echo "<script>alert('Đã cập nhật!'); window.location='users.php';</script>";
}

// XÓA
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$uid");
    echo "<script>window.location='users.php';</script>";
}

// LỌC THEO IP HOẶC TỪ KHÓA
$where = "WHERE 1=1";
$search_title = "Danh sách thành viên";
if (isset($_GET['search_ip'])) {
    $ip = $conn->real_escape_string($_GET['search_ip']);
    $where .= " AND u.ip_address = '$ip'";
    $search_title = "Danh sách trùng IP: <span class='text-danger'>$ip</span>";
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0"><?php echo $search_title; ?></h4>
            <a href="users.php" class="btn btn-sm btn-outline-primary">Hiện tất cả</a>
        </div>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User / Info</th>
                            <th>IP & Hoạt động</th>
                            <th>Người giới thiệu</th>
                            <th>Số dư</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // JOIN VỚI CHÍNH BẢNG USERS ĐỂ LẤY TÊN NGƯỜI GIỚI THIỆU
                        $sql = "SELECT u.*, r.username as ref_name 
                                FROM users u 
                                LEFT JOIN users r ON u.referred_by = r.id 
                                $where 
                                ORDER BY u.id DESC LIMIT 100";
                        $res = $conn->query($sql);
                        
                        while ($row = $res->fetch_assoc()) {
                            $stt_badge = ($row['ban'] == 1) ? '<span class="badge bg-danger">BANNED</span>' : '<span class="badge bg-success">Active</span>';
                            
                            // Xử lý thời gian hoạt động
                            $last_active = $row['last_active'] ? date('H:i d/m', strtotime($row['last_active'])) : 'Chưa rõ';
                            $time_diff = time() - strtotime($row['last_active']);
                            $online_status = ($time_diff < 300) ? '<span class="badge bg-label-success">Online</span>' : '<span class="badge bg-label-secondary">Offline</span>';

                            // Xử lý Người giới thiệu
                            $ref_html = '<span class="text-muted">Không có</span>';
                            if ($row['referred_by'] > 0) {
                                $ref_name = $row['ref_name'] ? $row['ref_name'] : 'Unknown';
                                // Bấm vào tên Ref -> Mở Modal sửa người đó
                                $ref_html = "<a href='javascript:void(0)' class='fw-bold text-info' onclick='openEditModalByData({$row['referred_by']})'><i class='bx bx-link'></i> $ref_name</a>";
                            }

                            // Xử lý IP (Bấm vào -> Lọc trùng IP)
                            $ip_link = "<a href='users.php?search_ip={$row['ip_address']}' class='badge bg-label-dark' title='Bấm để xem các nick trùng IP'>{$row['ip_address']}</a>";

                            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                            echo "<tr>
                                <td>
                                    <strong>{$row['username']}</strong> <small class='text-muted'>#{$row['id']}</small><br>
                                    $stt_badge
                                </td>
                                <td>
                                    $ip_link <br>
                                    <small>$online_status $last_active</small>
                                </td>
                                <td>$ref_html</td>
                                <td class='fw-bold text-primary'>".number_format($row['money'])." đ</td>
                                <td>
                                    <button class='btn btn-sm btn-info' onclick='editUser($json_data)'><i class='bx bx-edit'></i></button>
                                    <a href='?delete={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Xóa user này?\")'><i class='bx bx-trash'></i></a>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <div class="modal-header"><h5 class="modal-title">Chỉnh sửa User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="save_user" value="1">
                <input type="hidden" name="user_id" id="edit_id">
                
                <div class="mb-3"><label>Username</label><input type="text" class="form-control" id="edit_username" disabled></div>
                <div class="row mb-3">
                    <div class="col-6"><label>Uy Tín</label><input type="number" name="reputation" id="edit_rep" class="form-control"></div>
                    <div class="col-6"><label>Quyền hạn</label><select name="lv" id="edit_lv" class="form-select"><option value="1">Member</option><option value="9">Admin</option></select></div>
                </div>
                <div class="mb-3"><label>Trạng thái</label><select name="ban" id="edit_ban" class="form-select"><option value="0">Hoạt động</option><option value="1">KHÓA (BAN)</option></select></div>
                <hr>
                <h6 class="text-danger">💸 Điều chỉnh số dư</h6>
                <div class="row mb-2">
                    <div class="col-4"><select name="money_action" class="form-select"><option value="none">Không đổi</option><option value="plus">Cộng (+)</option><option value="minus">Trừ (-)</option></select></div>
                    <div class="col-8"><input type="number" name="money_amount" class="form-control" placeholder="Nhập số tiền..."></div>
                </div>
                <div class="mb-3"><input type="text" name="reason" class="form-control" placeholder="Lý do..."></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>

<script>
function editUser(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_username').value = data.username;
    document.getElementById('edit_rep').value = data.reputation;
    document.getElementById('edit_lv').value = data.lv;
    document.getElementById('edit_ban').value = data.ban;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
// Hàm fake để hỗ trợ click ref (Nếu cần kỹ hơn thì phải fetch ajax, ở đây tạm thời chỉ view list)
function openEditModalByData(id) {
    alert("Để sửa Ref này, vui lòng tìm ID #" + id + " trong danh sách!");
}
</script>
<?php require_once '../includes/footer.php'; ?>
