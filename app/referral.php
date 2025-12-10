<?php
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
require_once '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Đếm số người đã mời
$count_ref = $conn->query("SELECT COUNT(*) as c FROM users WHERE referred_by = $user_id")->fetch_assoc()['c'];

// Tạo link giới thiệu
// Giả sử link web là vietrust.site
$ref_link = "https://vietrust.site/register.php?ref=" . $user_id;
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Kiếm thêm /</span> Giới thiệu bạn bè</h4>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <img src="https://img.icons8.com/clouds/200/groups.png" height="120">
                        </div>
                        <h3 class="card-title text-primary mb-2"><?php echo $count_ref; ?></h3>
                        <p class="text-muted">Lượt giới thiệu thành công</p>
                        
                        <div class="alert alert-primary text-start" role="alert">
                            <i class='bx bx-gift me-1'></i> <b>Chính sách:</b> Chia sẻ liên kết và nhận <b>5% hoa hồng</b> trọn đời từ mỗi nhiệm vụ mà người được giới thiệu hoàn thành.
                            <br><small class="text-danger">* Gian lận tạo nick ảo sẽ bị khóa vĩnh viễn.</small>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label fw-bold">Link giới thiệu của bạn:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo $ref_link; ?>" id="refLink" readonly>
                                <button class="btn btn-primary" type="button" onclick="copyRef()">
                                    <i class='bx bx-copy'></i> Sao chép
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="#" class="btn btn-icon btn-label-info btn-lg"><i class='bx bxl-telegram'></i></a>
                            <a href="#" class="btn btn-icon btn-label-primary btn-lg"><i class='bx bxl-facebook'></i></a>
                            <a href="#" class="btn btn-icon btn-label-success btn-lg"><i class='bx bxs-phone'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function copyRef() {
    var copyText = document.getElementById("refLink");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Đã sao chép link: " + copyText.value);
}
</script>
<?php require_once '../includes/footer.php'; ?>
