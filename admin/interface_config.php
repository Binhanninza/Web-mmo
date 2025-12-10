<?php
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

// --- XỬ LÝ UPLOAD & LƯU ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marquee = trim($_POST['marquee']);
    $note = trim($_POST['note']);
    
    // Update Text
    $conn->query("UPDATE settings SET marquee_text = '$marquee', admin_note = '$note' WHERE id = 1");

    // Xử lý 7 Ảnh Slider
    for ($i = 1; $i <= 7; $i++) {
        $file_input = "slide_$i";
        if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] == 0) {
            $target_dir = "../assets/img/banners/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true); // Tạo thư mục nếu chưa có
            
            $ext = pathinfo($_FILES[$file_input]['name'], PATHINFO_EXTENSION);
            $new_name = "banner_$i." . $ext;
            $target_file = $target_dir . $new_name;
            
            if (move_uploaded_file($_FILES[$file_input]['tmp_name'], $target_file)) {
                $db_path = "/assets/img/banners/" . $new_name . "?v=" . time(); // Thêm time để xóa cache
                $conn->query("UPDATE settings SET slide_$i = '$db_path' WHERE id = 1");
            }
        }
        // Xóa ảnh nếu bấm nút xóa
        if (isset($_POST["del_slide_$i"])) {
            $conn->query("UPDATE settings SET slide_$i = NULL WHERE id = 1");
        }
    }
    echo "<script>Swal.fire('Ngon!', 'Đã lưu cấu hình giao diện!', 'success');</script>";
}

// Lấy dữ liệu cũ
$set = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">🎨 Cấu hình Giao diện User</h4>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card border-danger">
                        <h5 class="card-header bg-danger text-white"><i class='bx bx-run'></i> Thông báo chạy (Marquee)</h5>
                        <div class="card-body pt-3">
                            <label class="form-label">Nội dung chạy ngang màn hình:</label>
                            <input type="text" class="form-control" name="marquee" value="<?php echo htmlspecialchars($set['marquee_text']); ?>" placeholder="Nhập nội dung thông báo quan trọng...">
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb-4">
                    <div class="card border-primary">
                        <h5 class="card-header bg-primary text-white"><i class='bx bx-note'></i> Admin Note (Ghim Tĩnh)</h5>
                        <div class="card-body pt-3">
                            <label class="form-label">Nội dung ghi chú cho thành viên:</label>
                            <textarea class="form-control" name="note" rows="3" placeholder="Nhập lời nhắn nhủ yêu thương..."><?php echo htmlspecialchars($set['admin_note']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card border-success">
                        <h5 class="card-header bg-success text-white"><i class='bx bx-images'></i> Banner Quảng cáo (Slider)</h5>
                        <div class="card-body pt-3">
                            <div class="row">
                                <?php for($i=1; $i<=7; $i++): ?>
                                <div class="col-md-4 mb-3 text-center">
                                    <div class="border p-2 rounded">
                                        <label class="form-label fw-bold">Banner Số <?php echo $i; ?></label>
                                        <div class="mb-2" style="height: 100px; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center;">
                                            <?php if(!empty($set["slide_$i"])): ?>
                                                <img src="<?php echo $set["slide_$i"]; ?>" style="max-width:100%; max-height:100%;">
                                            <?php else: ?>
                                                <span class="text-muted">Trống</span>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" class="form-control mb-1" name="slide_<?php echo $i; ?>" accept="image/*">
                                        <?php if(!empty($set["slide_$i"])): ?>
                                            <div class="form-check text-start">
                                                <input class="form-check-input" type="checkbox" name="del_slide_<?php echo $i; ?>" id="del_<?php echo $i; ?>">
                                                <label class="form-check-label text-danger" for="del_<?php echo $i; ?>">Xóa ảnh này</label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 btn-lg mt-3 fw-bold">💾 LƯU CẤU HÌNH</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
