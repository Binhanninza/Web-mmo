<?php
// 1. BẢO VỆ & KẾT NỐI
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

// --- XỬ LÝ PHP ---

// 1. XỬ LÝ XOÁ (Bulk Action)
if (isset($_POST['bulk_action']) && isset($_POST['ids'])) {
    $ids = implode(',', array_map('intval', $_POST['ids']));
    $type = $_POST['bulk_action'];
    if ($type == 'delete') {
        $conn->query("DELETE FROM tickets WHERE id IN ($ids)");
        $conn->query("DELETE FROM ticket_messages WHERE ticket_id IN ($ids)");
        echo "<script>Swal.fire('Xong', 'Đã xóa các vé đã chọn', 'success');</script>";
    }
}

// 2. XỬ LÝ TẠO CHAT VỚI USER (Admin chủ động nhắn)
if (isset($_POST['create_chat_admin'])) {
    $target_uid = (int)$_POST['target_uid'];
    // Check user tồn tại
    $check = $conn->query("SELECT id FROM users WHERE id = $target_uid");
    if ($check->num_rows > 0) {
        $conn->query("INSERT INTO tickets (user_id, admin_id, priority, updated_at) VALUES ($target_uid, 1, 3, NOW())");
        echo "<script>Swal.fire('Success', 'Đã mở phòng chat với User #$target_uid', 'success');</script>";
    } else {
        echo "<script>Swal.fire('Lỗi', 'User ID không tồn tại', 'error');</script>";
    }
}

// 3. BỘ LỌC
$filter = $_GET['filter'] ?? 'unread';
$where = "";
if ($filter == 'unread') $where = "AND status=0 AND is_read_admin=0";
elseif ($filter == 'done') $where = "AND status=1";
elseif ($filter == 'process') $where = "AND status=0";

// 4. QUERY LẤY LIST
$sql = "SELECT t.*, u.username FROM tickets t JOIN users u ON t.user_id = u.id WHERE 1=1 $where ORDER BY t.priority DESC, t.updated_at DESC";
$tickets = $conn->query($sql);
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Hỗ trợ /</span> Danh sách Yêu cầu</h4>

        <div class="card mb-4 p-3 bg-label-primary">
            <form method="POST" class="d-flex align-items-center gap-3">
                <i class='bx bx-user-plus fs-3'></i>
                <input type="number" name="target_uid" class="form-control w-25" placeholder="Nhập ID User muốn chat..." required>
                <button name="create_chat_admin" class="btn btn-primary">Mở phòng chat ngay</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="btn-group">
                    <a href="tickets.php?filter=unread" class="btn btn-outline-primary <?php echo $filter=='unread'?'active':''; ?>">Chưa đọc</a>
                    <a href="tickets.php?filter=process" class="btn btn-outline-warning <?php echo $filter=='process'?'active':''; ?>">Chưa xong</a>
                    <a href="tickets.php?filter=done" class="btn btn-outline-secondary <?php echo $filter=='done'?'active':''; ?>">Đã xong</a>
                    <a href="tickets.php?filter=all" class="btn btn-outline-info <?php echo $filter=='all'?'active':''; ?>">Tất cả</a>
                </div>
                <button class="btn btn-danger btn-sm" onclick="$('#bulk-form').submit()">Xóa đã chọn</button>
            </div>

            <div class="table-responsive text-nowrap">
                <form method="POST" id="bulk-form">
                    <input type="hidden" name="bulk_action" value="delete">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:10px;"><input type="checkbox" class="form-check-input" onclick="$('.chk-item').prop('checked', this.checked)"></th>
                                <th>ID</th>
                                <th>User</th>
                                <th>Mức độ</th>
                                <th>Trạng thái</th>
                                <th>Cập nhật</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($tickets->num_rows > 0): ?>
                                <?php while ($row = $tickets->fetch_assoc()): 
                                    $prio_badge = ($row['priority']==3)?'<span class="badge bg-danger">KHẨN CẤP</span>':
                                                  (($row['priority']==2)?'<span class="badge bg-warning">Vừa</span>':'<span class="badge bg-success">Bình thường</span>');
                                    $status_badge = ($row['status']==1)?'<span class="badge bg-label-secondary">Đã rời</span>':'<span class="badge bg-label-primary">Đang mở</span>';
                                    $read_class = ($row['is_read_admin']==0)?'fw-bold bg-lighter':'';
                                ?>
                                <tr class="<?php echo $read_class; ?>">
                                    <td><input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" class="form-check-input chk-item"></td>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?> <small class="text-muted">(ID: <?php echo $row['user_id']; ?>)</small></td>
                                    <td><?php echo $prio_badge; ?></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td><?php echo date('H:i d/m', strtotime($row['updated_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="adminOpenChat(<?php echo $row['id']; ?>)">
                                            <i class='bx bx-chat'></i> Chat
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4">Không có dữ liệu</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="adminChatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="height: 80vh; display: flex; flex-direction: column;">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title fw-bold"><i class='bx bx-support text-primary'></i> Hỗ trợ User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-0" style="flex: 1; background: #eef2f5; overflow: hidden; display: flex; flex-direction: column;">
                <div id="admin-chat-box" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column;">
                    </div>
            </div>
            
            <div class="modal-footer p-2 bg-white border-top">
                <div class="input-group">
                    <input type="text" id="admin-msg-input" class="form-control" placeholder="Nhập tin nhắn trả lời..." autocomplete="off">
                    <button class="btn btn-primary" onclick="adminSendMsg()"><i class='bx bxs-send'></i> Gửi</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS Bong bóng Chat (Giống User) */
.msg-row { display: flex; margin-bottom: 10px; width: 100%; }
.msg-row.me { justify-content: flex-end; }
.msg-row.other { justify-content: flex-start; }

.msg-bubble { 
    max-width: 75%; 
    padding: 8px 14px; 
    border-radius: 15px; 
    font-size: 14px; 
    line-height: 1.4;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Admin (Me) - Màu Xanh */
.msg-row.me .msg-bubble { 
    background: #696cff; 
    color: #fff; 
    border-bottom-right-radius: 2px;
}

/* User (Other) - Màu Trắng */
.msg-row.other .msg-bubble { 
    background: #fff; 
    color: #333; 
    border-bottom-left-radius: 2px;
}

.msg-time { 
    font-size: 10px; 
    margin-top: 3px; 
    display: block; 
    text-align: right; 
    opacity: 0.7; 
}
</style>

<?php require_once '../includes/footer.php'; ?>

<script>
var adminChatInterval;
var currentAdminTid = 0;

function adminOpenChat(tid) {
    currentAdminTid = tid;
    $('#adminChatModal').modal('show');
    loadAdminMsgs();
    
    // Auto scroll xuống dưới cùng khi mở
    setTimeout(function(){ 
        var d = $('#admin-chat-box');
        d.scrollTop(d[0].scrollHeight);
    }, 500);

    adminChatInterval = setInterval(loadAdminMsgs, 3000);
}

// Khi đóng modal thì tắt interval và reload
$('#adminChatModal').on('hidden.bs.modal', function () {
    clearInterval(adminChatInterval);
    location.reload(); 
});

function loadAdminMsgs() {
    // Gọi Ajax từ thư mục app
    $.post('../app/ajax_ticket.php', { action: 'load_msgs', ticket_id: currentAdminTid }, function(res) {
        if(res.status) {
            var html = '';
            res.data.forEach(function(m) {
                // Ở trang Admin: 'me' là role admin
                var cls = (m.is_me) ? 'me' : 'other'; // ajax_ticket đã xử lý logic is_me cho admin
                
                html += `<div class="msg-row ${cls}">
                            <div class="msg-bubble">
                                ${m.message}
                                <span class="msg-time">${m.time}</span>
                            </div>
                         </div>`;
            });
            $('#admin-chat-box').html(html);
        }
    }, 'json');
}

function adminSendMsg() {
    var msg = $('#admin-msg-input').val().trim();
    if(!msg) return;
    
    $('#admin-msg-input').val(''); // Clear trước
    
    $.post('../app/ajax_ticket.php', { action: 'send_msg', ticket_id: currentAdminTid, message: msg }, function(res){
        if(res.status) {
            loadAdminMsgs(); // Load lại ngay
            // Scroll xuống dưới
            var d = $('#admin-chat-box');
            d.animate({ scrollTop: d[0].scrollHeight }, 200);
        }
    }, 'json');
}

// Enter để gửi
$('#admin-msg-input').keypress(function(e) {
    if(e.which == 13) adminSendMsg();
});
</script>
