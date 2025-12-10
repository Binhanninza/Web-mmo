<?php
require_once 'protect.php';
require_once '../config/database.php';

// Check đăng nhập trước khi xử lý
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

// 1. ĐĂNG BÀI MỚI
if (isset($_POST['add_post'])) {
    $content = $conn->real_escape_string($_POST['content']);
    $fake_likes = (int)$_POST['fake_likes'];
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $image = "assets/uploads/" . time() . "_" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], "../" . $image);
    }

    $conn->query("INSERT INTO posts (content, image, likes) VALUES ('$content', '$image', $fake_likes)");
    // Chuyển hướng ngay lập tức
    header("Location: posts.php"); exit;
}

// 2. XÓA BÀI
if (isset($_GET['delete_post'])) {
    $id = (int)$_GET['delete_post'];
    $conn->query("DELETE FROM posts WHERE id=$id");
    $conn->query("DELETE FROM comments WHERE post_id=$id");
    header("Location: posts.php"); exit;
}

// 3. DUYỆT / XÓA COMMENT
if (isset($_GET['approve_cmt'])) {
    $conn->query("UPDATE comments SET status=1 WHERE id=".(int)$_GET['approve_cmt']);
    header("Location: posts.php"); exit;
}
if (isset($_GET['del_cmt'])) {
    $conn->query("DELETE FROM comments WHERE id=".(int)$_GET['del_cmt']);
    header("Location: posts.php"); exit;
}

// --- XỬ LÝ XONG MỚI GỌI HEADER (ĐỂ KHÔNG BỊ LỖI HEADER SENT) ---
require_once 'header.php';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-5">
                <div class="card mb-4">
                    <h5 class="card-header bg-primary text-white">Đăng bài mới</h5>
                    <div class="card-body pt-3">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Nội dung</label>
                                <textarea name="content" class="form-control" rows="4" placeholder="Viết gì đó..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ảnh (Tùy chọn)</label>
                                <input type="file" name="image" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-danger">Like ảo ban đầu</label>
                                <input type="number" name="fake_likes" class="form-control" value="100">
                            </div>
                            <button type="submit" name="add_post" class="btn btn-primary w-100">ĐĂNG BÀI</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <h5 class="card-header">Bài viết đã đăng</h5>
                    <div class="card-body">
                        <?php
                        $posts = $conn->query("SELECT * FROM posts ORDER BY id DESC");
                        while($p = $posts->fetch_assoc()) {
                            echo '<div class="border rounded p-3 mb-3 position-relative">';
                            echo '<a href="?delete_post='.$p['id'].'" class="btn btn-xs btn-danger position-absolute top-0 end-0 m-2" onclick="return confirm(\'Xóa bài này?\')"><i class="bx bx-trash"></i></a>';
                            echo '<p class="mb-2">'.nl2br(htmlspecialchars($p['content'])).'</p>';
                            if($p['image']) echo '<img src="../'.$p['image'].'" class="img-fluid rounded mb-2" style="max-height:150px;">';
                            echo '<div class="text-muted small"><i class="bx bx-like text-primary"></i> <b>'.$p['likes'].'</b> likes</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
