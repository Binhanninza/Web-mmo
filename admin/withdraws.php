<?php
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

// XỬ LÝ DUYỆT / TỪ CHỐI
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id']; 
    $act = $_GET['action']; 
    $admin_id = $_SESSION['user_id'];
    
    // Chỉ xử lý đơn đang "Pending" (Chờ duyệt)
    $wd = $conn->query("SELECT * FROM withdraws WHERE id=$id AND status='pending'")->fetch_assoc();
    
    if ($wd) {
        if ($act == 'approve') {
            // DUYỆT: Chỉ update trạng thái (Tiền đã trừ lúc user đặt lệnh rồi)
            $conn->query("UPDATE withdraws SET status='approved', admin_id=$admin_id, processed_at=NOW() WHERE id=$id");
            echo "<script>alert('Đã duyệt đơn thành công!'); window.location='withdraws.php';</script>";
        } 
        elseif ($act == 'reject') {
            // TỪ CHỐI: Hoàn tiền lại cho User
            // Lý do: Admin hủy đơn
            if (change_balance($wd['user_id'], $wd['amount'], 'withdraw_refund', $id, 'Hoàn tiền rút')) {
                $conn->query("UPDATE withdraws SET status='rejected', admin_id=$admin_id, processed_at=NOW() WHERE id=$id");
                echo "<script>alert('Đã từ chối và hoàn tiền cho User!'); window.location='withdraws.php';</script>";
            } else {
                echo "<script>alert('Lỗi hoàn tiền! Kiểm tra lại.');</script>";
            }
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Quản lý /</span> Duyệt Rút Tiền</h4>

        <div class="card">
            <h5 class="card-header">Danh sách yêu cầu</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Số tiền</th>
                            <th>Thông tin nhận</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Lấy danh sách, ưu tiên đơn Chờ duyệt lên đầu
                        $sql = "SELECT w.*, u.username 
                                FROM withdraws w 
                                JOIN users u ON w.user_id = u.id 
                                ORDER BY field(w.status, 'pending', 'approved', 'rejected'), w.created_at DESC";
                        $res = $conn->query($sql);
                        
                        if ($res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $st = $row['status'];
                                $badge = ($st=='pending')?'warning':(($st=='approved')?'success':'danger');
                                $st_text = ($st=='pending')?'Chờ duyệt':(($st=='approved')?'Thành công':'Đã hủy');
                                
                                // GIẢI MÃ SỐ TÀI KHOẢN
                                $acc_num = data_decrypt($row['account_number']);

                                echo "<tr>
                                    <td><strong>{$row['username']}</strong><br><small class='text-muted'>#{$row['user_id']}</small></td>
                                    <td class='text-danger fw-bold'>".number_format($row['amount'])." đ</td>
                                    <td>
                                        <span class='badge bg-label-primary'>{$row['method']}</span><br>
                                        <b>$acc_num</b><br>
                                        <small>{$row['account_name']}</small>
                                    </td>
                                    <td>".date('d/m H:i', strtotime($row['created_at']))."</td>
                                    <td><span class='badge bg-$badge'>$st_text</span></td>
                                    <td>";
                                
                                if ($st == 'pending') {
                                    echo "
                                    <a href='?action=approve&id={$row['id']}' class='btn btn-sm btn-success' onclick='return confirm(\"Xác nhận đã chuyển tiền?\")'><i class='bx bx-check'></i> Duyệt</a>
                                    <a href='?action=reject&id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Hủy đơn và hoàn tiền?\")'><i class='bx bx-x'></i> Hủy</a>
                                    ";
                                } else {
                                    echo "<span class='text-muted fst-italic'>Đã xử lý</span>";
                                }
                                
                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5'>Không có đơn rút nào!</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
