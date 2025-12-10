<?php
require_once 'protect.php';
require_once '../config/database.php';

// 1. XỬ LÝ XÓA TRƯỚC (Đưa lên đầu để fix lỗi Header)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM reports WHERE id = $id");
    header("Location: reports.php"); 
    exit;
}

// 2. SAU ĐÓ MỚI GỌI GIAO DIỆN
require_once 'header.php';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Quản lý /</span> Báo cáo lỗi</h4>

        <div class="card">
            <h5 class="card-header">Danh sách báo cáo từ thành viên</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thành viên</th>
                            <th>Nhiệm vụ</th>
                            <th>Lý do</th>
                            <th>Ghi chú</th>
                            <th>Ngày báo</th>
                            <th>Xử lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT r.*, u.username, m.name as mission_name 
                                FROM reports r 
                                JOIN users u ON r.user_id = u.id 
                                LEFT JOIN missions m ON r.mission_id = m.id 
                                ORDER BY r.id DESC";
                        $res = $conn->query($sql);
                        
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $job_name = $row['mission_name'] ? $row['mission_name'] : '<span class="text-muted">Job đã xóa</span>';
                                echo "<tr>
                                    <td>#{$row['id']}</td>
                                    <td><strong>{$row['username']}</strong></td>
                                    <td>$job_name</td>
                                    <td><span class='badge bg-label-danger'>{$row['reason']}</span></td>
                                    <td><small>{$row['note']}</small></td>
                                    <td>".date('H:i d/m', strtotime($row['created_at']))."</td>
                                    <td>
                                        <a href='?delete={$row['id']}' class='btn btn-sm btn-outline-secondary' onclick='return confirm(\"Đánh dấu đã xử lý?\")'>
                                            <i class='bx bx-check'></i> Xong
                                        </a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-5 text-muted'>Sạch bóng! Không có báo cáo nào.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
