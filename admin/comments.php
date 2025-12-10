<?php
require_once 'protect.php';
require_once '../config/database.php';

// XỬ LÝ TRƯỚC KHI LOAD HEADER
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $conn->query("UPDATE comments SET status = 1 WHERE id = $id");
    header("Location: comments.php"); exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM comments WHERE id = $id");
    header("Location: comments.php"); exit;
}

if (isset($_GET['clear_all'])) {
    $conn->query("DELETE FROM comments WHERE status = 1");
    header("Location: comments.php"); exit;
}

require_once 'header.php';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Quản lý Bình luận</h4>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách bình luận</h5>
                <a href="?clear_all=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xóa sạch comment cũ?')">
                    <i class='bx bx-trash'></i> Dọn dẹp
                </a>
            </div>
            
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead><tr><th>User</th><th>Nội dung</th><th>Bài viết</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
                    <tbody>
                        <?php
                        $sql = "SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id ORDER BY c.status ASC, c.created_at DESC";
                        $res = $conn->query($sql);

                        if ($res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $status = ($row['status'] == 0) ? '<span class="badge bg-warning">Chờ duyệt</span>' : '<span class="badge bg-success">Đã hiện</span>';
                                
                                // Fix lỗi cú pháp ở đây (Dùng nháy đơn cho class)
                                $btn_approve = ($row['status'] == 0) ? '<a href="?approve='.$row['id'].'" class="btn btn-sm btn-icon btn-success"><i class="bx bx-check"></i></a>' : '';

                                echo "<tr>
                                    <td><strong>{$row['username']}</strong></td>
                                    <td style='white-space:normal; max-width: 300px;'>".htmlspecialchars($row['content'])."</td>
                                    <td>#{$row['post_id']}</td>
                                    <td>$status</td>
                                    <td>
                                        $btn_approve
                                        <a href='?delete={$row['id']}' class='btn btn-sm btn-icon btn-danger' onclick=\"return confirm('Xóa nhé?')\"><i class='bx bx-trash'></i></a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-4'>Không có dữ liệu</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
