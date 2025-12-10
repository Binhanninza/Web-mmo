<?php
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

// 1. XỬ LÝ THÊM NHIỆM VỤ
if (isset($_POST['add_mission'])) {
    $name = trim($_POST['name']);
    $api_url = trim($_POST['api_url']); // Link API mẫu
    $api_token = trim($_POST['api_token']); // Token
    $reward = (int)$_POST['reward'];
    $limit = (int)$_POST['daily_limit'];
    
    if (empty($name) || empty($api_url) || empty($api_token)) {
        echo "<script>alert('Vui lòng nhập đủ thông tin!');</script>";
    } else {
        // Tạo code giả (Dummy) để lấp đầy cột 'code' trong DB (vì hệ thống mới dùng Key động, nhưng DB cũ yêu cầu cột này)
        $dummy_code = bin2hex(random_bytes(4)); 
        
        // Lưu vào DB (Dùng Prepared Statement chống Hack)
        // Cột link_original lưu API URL
        // Cột link_short lưu API Token
        $stmt = $conn->prepare("INSERT INTO missions (name, link_original, link_short, reward, daily_limit, code, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssiss", $name, $api_url, $api_token, $reward, $limit, $dummy_code);
        
        if ($stmt->execute()) {
            echo "<script>alert('Thêm nhiệm vụ thành công!'); window.location='missions.php';</script>";
        } else {
            echo "<script>alert('Lỗi: ".$conn->error."');</script>";
        }
        $stmt->close();
    }
}

// 2. XỬ LÝ XÓA
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Xóa nhiệm vụ
    $conn->query("DELETE FROM missions WHERE id = $id");
    
    // Xóa luôn các Key rác liên quan đến nhiệm vụ này để sạch DB
    $conn->query("DELETE FROM mission_keys WHERE mission_id = $id");
    
    echo "<script>alert('Đã xóa nhiệm vụ!'); window.location='missions.php';</script>";
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Quản lý /</span> Nhiệm vụ API</h4>

        <div class="row">
            <div class="col-md-5">
                <div class="card mb-4">
                    <h5 class="card-header bg-primary text-white">Cấu hình API</h5>
                    <div class="card-body pt-3">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên nhiệm vụ</label>
                                <input type="text" name="name" class="form-control" placeholder="VD: Vượt link 1s..." required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Link API Rút gọn (Mẫu)</label>
                                <input type="text" name="api_url" class="form-control" value="https://link1s.com/api?api={token}&url={link}" required>
                                <div class="form-text">Giữ nguyên <code>{token}</code> và <code>{link}</code></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-danger">API Token (Key)</label>
                                <input type="text" name="api_token" class="form-control" placeholder="Nhập Token của web rút gọn..." required>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Thưởng (đ)</label>
                                    <input type="number" name="reward" class="form-control" value="500" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Max / Ngày</label>
                                    <input type="number" name="daily_limit" class="form-control" value="3" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="add_mission" class="btn btn-primary w-100 fw-bold">LƯU NHIỆM VỤ</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <h5 class="card-header">Danh sách đang chạy</h5>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Thưởng</th>
                                    <th>Limit</th>
                                    <th>Token (Ẩn)</th>
                                    <th>Xóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM missions ORDER BY id DESC");
                                if ($res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        // Ẩn bớt Token cho bảo mật
                                        $hidden_token = substr($row['link_short'], 0, 5) . '...';
                                        
                                        echo "<tr>
                                            <td>{$row['id']}</td>
                                            <td><strong>{$row['name']}</strong></td>
                                            <td><span class='badge bg-label-success'>+".number_format($row['reward'])."</span></td>
                                            <td><span class='badge bg-label-info'>{$row['daily_limit']}</span></td>
                                            <td><code class='text-muted'>$hidden_token</code></td>
                                            <td>
                                                <a href='?delete={$row['id']}' class='btn btn-sm btn-icon btn-outline-danger' onclick='return confirm(\"Xóa nhiệm vụ này?\")'>
                                                    <i class='bx bx-trash'></i>
                                                </a>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Chưa có nhiệm vụ nào</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
