<?php
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

// XỬ LÝ THÊM MÃ
if (isset($_POST['add_code'])) {
    check_csrf_token();
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['type'];
    $val = (int)$_POST['value'];
    $msg = trim($_POST['message']);
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    $conn->query("INSERT INTO giftcodes (code, type, value, message, start_time, end_time) VALUES ('$code', '$type', $val, '$msg', '$start', '$end')");
    echo "<script>alert('Đã tạo Giftcode: $code'); window.location='giftcodes.php';</script>";
}

// XÓA MÃ
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $conn->query("DELETE FROM giftcodes WHERE id=$id");
    $conn->query("DELETE FROM giftcode_history WHERE giftcode_id=$id"); // Xóa cả lịch sử
    echo "<script>window.location='giftcodes.php';</script>";
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Quản lý Giftcode</h4>

        <div class="card mb-4">
            <h5 class="card-header bg-primary text-white">Phát hành Mã Quà Tặng</h5>
            <div class="card-body pt-3">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Mã Code (Viết liền)</label>
                            <input type="text" name="code" class="form-control text-uppercase" placeholder="VD: TET2025" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Loại phần thưởng</label>
                            <select name="type" class="form-select">
                                <option value="money">Tiền (VNĐ)</option>
                                <option value="reputation">Uy Tín</option>
                                <option value="exp">Kinh nghiệm (EXP)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Giá trị</label>
                            <input type="number" name="value" class="form-control" placeholder="VD: 10000" required>
                            <div class="col-md-4 mb-3">
    <label class="form-label fw-bold">Số lượng (Giới hạn)</label>
    <input type="number" name="usage_limit" class="form-control" placeholder="VD: 100" value="100" required>
</div>

                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày kết thúc</label>
                            <input type="datetime-local" name="end_time" class="form-control" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Nội dung thư (Lời nhắn)</label>
                            <textarea name="message" class="form-control" rows="2" placeholder="Chúc mừng bạn đã nhận được..."></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_code" class="btn btn-primary w-100 fw-bold">PHÁT HÀNH NGAY</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h5 class="card-header">Danh sách đang chạy</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead><tr><th>Mã</th><th>Thưởng</th><th>Thời gian</th><th>Đã dùng</th><th>Hành động</th></tr></thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT * FROM giftcodes ORDER BY id DESC");
                        while($r = $res->fetch_assoc()) {
                            $used = $conn->query("SELECT COUNT(*) FROM giftcode_history WHERE giftcode_id={$r['id']}")->fetch_row()[0];
                            echo "<tr>
                                <td><span class='badge bg-label-primary'>{$r['code']}</span></td>
                                <td>+".number_format($r['value'])." <span class='text-uppercase'>{$r['type']}</span></td>
                                <td><small>Start: {$r['start_time']}<br>End: {$r['end_time']}</small></td>
                                <td><b>$used</b> người</td>
                                <td><a href='?del={$r['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Xóa?\")'>Xóa</a></td>
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
