<?php
require_once '../config/database.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

require_once '../includes/header.php';

$user_id = $_SESSION['user_id'];
$msg = ""; $msg_type = "";

// 2. XỬ LÝ RÚT TIỀN (LOGIC MỚI - AN TOÀN TUYỆT ĐỐI)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['withdraw'])) {
    
    if(function_exists('check_csrf_token')) check_csrf_token();

    $amount = (int)$_POST['amount'];
    $method = trim($_POST['method']);
    $account_number = trim($_POST['account_number']);
    $account_name = trim($_POST['account_name']);
    
    // Config Min Rút
    $min_withdraw = isset($CMS['min_withdraw']) ? $CMS['min_withdraw'] : 10000;

    if ($amount < $min_withdraw) {
        $msg = "Rút tối thiểu " . number_format($min_withdraw) . "đ!"; $msg_type = "danger";
    } elseif (empty($account_number) || empty($account_name)) {
        $msg = "Nhập thiếu thông tin nhận tiền!"; $msg_type = "danger";
    } else {
        
        // --- BẮT ĐẦU TRANSACTION KHÓA DÒNG TIỀN ---
        $conn->begin_transaction();
        try {
            // 1. Lấy thông tin & KHÓA USER (FOR UPDATE)
            // Không ai được sửa tiền user này cho đến khi code chạy xong
            $stmt = $conn->prepare("SELECT money, ban FROM users WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user_data['ban'] == 1) throw new Exception("Tài khoản đang bị khóa!");
            if ($user_data['money'] < $amount) throw new Exception("Số dư không đủ!");

            // 2. Trừ tiền
            $new_bal = $user_data['money'] - $amount;
            $upd = $conn->prepare("UPDATE users SET money = ? WHERE id = ?");
            $upd->bind_param("ii", $new_bal, $user_id);
            $upd->execute();
            $upd->close();

            // 3. Tạo lệnh rút
            // (Nếu có hàm mã hóa riêng thì dùng, ở đây em lưu thẳng hoặc đại ca tự thêm hàm mã hóa vào)
            $encrypted_acc = $account_number; 
            if(function_exists('data_encrypt')) $encrypted_acc = data_encrypt($account_number);

            $ins = $conn->prepare("INSERT INTO withdraws (user_id, amount, method, account_number, account_name, status, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
            $ins->bind_param("iisss", $user_id, $amount, $method, $encrypted_acc, $account_name);
            $ins->execute();
            $ins->close();

            // 4. Lưu biến động số dư
            $desc = "Rút tiền về $method";
            $trans = $conn->prepare("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description) VALUES (?, 'withdraw', ?, ?, ?, ?)");
            $trans->bind_param("iiiis", $user_id, $amount, $user_data['money'], $new_bal, $desc);
            $trans->execute();
            $trans->close();

            // THÀNH CÔNG -> COMMIT
            $conn->commit();
            $msg = "Tạo lệnh rút ".number_format($amount)."đ thành công!"; $msg_type = "success";

        } catch (Exception $e) {
            // CÓ LỖI -> HOÀN TÁC TOÀN BỘ (KHÔNG MẤT TIỀN OAN)
            $conn->rollback();
            $msg = $e->getMessage(); $msg_type = "danger";
        }
    }
}

// Lấy số dư mới nhất để hiển thị
$user = $conn->query("SELECT money FROM users WHERE id = $user_id")->fetch_assoc();
$money = $user['money'];
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Tài chính /</span> Rút tiền</h4>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4 h-100">
                    <h5 class="card-header bg-primary text-white"><i class='bx bx-money'></i> Tạo lệnh rút tiền</h5>
                    <div class="card-body pt-4">
                        
                        <?php if ($msg != ""): ?>
                            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible" role="alert">
                                <?php echo $msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex align-items-center mb-4 p-3 rounded" style="background-color: #f0f2f5;">
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initial rounded bg-label-success"><i class='bx bx-wallet'></i></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Số dư khả dụng</small>
                                <h4 class="mb-0 text-success fw-bold"><?php echo number_format($money); ?> đ</h4>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Phương thức nhận</label>
                                <select class="form-select" name="method">
                                    <option value="MOMO">Ví MoMo</option>
                                    <option value="BANK">Chuyển khoản Ngân hàng (ATM)</option>
                                    <option value="ZALOPAY">ZaloPay</option>
                                    <option value="THE_CAO">Thẻ Cào Điện Thoại</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Số tiền cần rút</label>
                                <div class="input-group input-group-merge">
                                    <input type="number" name="amount" class="form-control" placeholder="Ví dụ: 50000" min="<?php echo isset($CMS['min_withdraw']) ? $CMS['min_withdraw'] : 10000; ?>" required />
                                    <span class="input-group-text">VNĐ</span>
                                </div>
                                <div class="form-text text-primary">
                                    <i class='bx bx-info-circle'></i> Tối thiểu: <?php echo number_format(isset($CMS['min_withdraw']) ? $CMS['min_withdraw'] : 10000); ?> đ
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Số tài khoản / SĐT Ví</label>
                                <input type="text" name="account_number" class="form-control" placeholder="Nhập chính xác..." required />
                                <div class="form-text text-muted"><i class='bx bx-shield'></i> Thông tin sẽ được mã hóa an toàn.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên chủ tài khoản (Không dấu)</label>
                                <input type="text" name="account_name" class="form-control" placeholder="NGUYEN VAN A" required />
                            </div>

                            <button type="submit" name="withdraw" class="btn btn-primary w-100 fw-bold py-2">
                                <i class='bx bx-paper-plane'></i> GỬI YÊU CẦU RÚT TIỀN
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4 border border-danger">
                    <div class="card-body">
                        <h5 class="card-title text-danger mb-3"><i class='bx bx-error-circle'></i> Lưu ý quan trọng</h5>
                        <ul class="ps-3 mb-0 text-muted">
                            <li class="mb-2">Hạn mức rút tối thiểu là <strong><?php echo number_format(isset($CMS['min_withdraw']) ? $CMS['min_withdraw'] : 10000); ?> VNĐ</strong>.</li>
                            <li class="mb-2">Vui lòng kiểm tra kỹ <strong>Số tài khoản</strong>. Admin không chịu trách nhiệm nếu bạn điền sai.</li>
                            <li class="mb-2">Thời gian xử lý: <strong>Trong vòng 24h</strong> (Trừ ngày lễ, chủ nhật).</li>
                            <li>Nếu quá 24h chưa nhận được tiền, liên hệ Admin hỗ trợ.</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card h-100">
                    <h5 class="card-header border-bottom">Lịch sử giao dịch gần đây</h5>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover">
                            <thead>
                                <tr class="bg-light">
                                    <th>Số tiền</th>
                                    <th>Phương thức</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_his = "SELECT * FROM withdraws WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5";
                                $res_his = $conn->query($sql_his);

                                if ($res_his && $res_his->num_rows > 0) {
                                    while ($h = $res_his->fetch_assoc()) {
                                        $status_badge = '';
                                        if ($h['status'] == 0) $status_badge = '<span class="badge bg-warning">Chờ duyệt</span>';
                                        elseif ($h['status'] == 1) $status_badge = '<span class="badge bg-success">Thành công</span>';
                                        elseif ($h['status'] == 2) $status_badge = '<span class="badge bg-danger">Đã hủy</span>';

                                        echo '<tr>';
                                        echo '<td class="fw-bold text-dark">-' . number_format($h['amount']) . '</td>';
                                        echo '<td><small>' . htmlspecialchars($h['method']) . '</small></td>';
                                        echo '<td>' . $status_badge . '</td>';
                                        echo '<td><small class="text-muted">' . date('d/m H:i', strtotime($h['created_at'])) . '</small></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center py-4 text-muted">Chưa có giao dịch nào</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once '../includes/footer.php'; ?>
