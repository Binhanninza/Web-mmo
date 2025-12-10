<?php
require_once 'protect.php';
require_once '../config/database.php';
require_once 'header.php';

$js_alert = "";

// --- XỬ LÝ FORM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'job';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $status = (int)($_POST['status'] ?? 0);
    
    $prizes = [];
    if(!empty($_POST['top1'])) $prizes[] = $_POST['top1'];
    if(!empty($_POST['top2'])) $prizes[] = $_POST['top2'];
    if(!empty($_POST['top3'])) $prizes[] = $_POST['top3'];
    if(!empty($_POST['top_other'])) {
        $others = explode(',', $_POST['top_other']);
        foreach($others as $o) $prizes[] = trim($o);
    }
    $json_prizes = json_encode($prizes, JSON_UNESCAPED_UNICODE);

    if ($action == 'add') {
        $stmt = $conn->prepare("INSERT INTO events (name, type, start_time, end_time, prizes, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $name, $type, $start, $end, $json_prizes, $status);
        if($stmt->execute()) $js_alert = "Swal.fire('Thành công', 'Đã thêm sự kiện!', 'success');";
        else { $err=json_encode($stmt->error); $js_alert = "Swal.fire('Lỗi', $err, 'error');"; }
    } 
    elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE events SET name=?, type=?, start_time=?, end_time=?, prizes=?, status=? WHERE id=?");
        $stmt->bind_param("sssssii", $name, $type, $start, $end, $json_prizes, $status, $id);
        if($stmt->execute()) $js_alert = "Swal.fire('Thành công', 'Đã cập nhật!', 'success');";
    }
    elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM events WHERE id=$id");
        $js_alert = "Swal.fire('Đã xóa', 'Bay màu!', 'success');";
    }
    
    // API LẤY DANH SÁCH WINNER
    elseif ($action == 'get_winners') {
        $eid = (int)$_POST['id'];
        $ev = $conn->query("SELECT * FROM events WHERE id=$eid")->fetch_assoc();
        
        if($ev) {
            $s = $ev['start_time']; $e = $ev['end_time'];
            if ($ev['type'] == 'job') {
                $sql = "SELECT u.id, u.username, COUNT(h.id) as score FROM history h JOIN users u ON h.user_id = u.id WHERE h.created_at >= '$s' AND h.created_at <= '$e' GROUP BY h.user_id ORDER BY score DESC LIMIT 10";
                $unit = "Job";
            } else {
                $sql = "SELECT u.id, u.username, SUM(h.amount) as score FROM history h JOIN users u ON h.user_id = u.id WHERE h.created_at >= '$s' AND h.created_at <= '$e' GROUP BY h.user_id ORDER BY score DESC LIMIT 10";
                $unit = "đ";
            }
            $res = $conn->query($sql);
            $html = '<table class="table table-bordered table-sm"><thead><tr><th>Top</th><th>ID</th><th>User</th><th>Thành tích</th></tr></thead><tbody>';
            $i=1;
            while($r = $res->fetch_assoc()) {
                $icon = ($i==1)?'👑':($i==2?'🥈':($i==3?'🥉':$i));
                $html .= "<tr><td>$icon</td><td><b class='text-danger'>{$r['id']}</b></td><td>{$r['username']}</td><td>".number_format($r['score'])." $unit</td></tr>";
                $i++;
            }
            $html .= '</tbody></table>';
            echo $html; exit;
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Quản lý /</span> Sự kiện Đua Top</h4>
            <button type="button" class="btn btn-primary" id="btn-add-new"><i class='bx bx-plus'></i> Tạo Sự Kiện</button>
        </div>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Sự Kiện</th>
                            <th>Thời Gian</th>
                            <th>Trạng Thái</th>
                            <th>Chức Năng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $events = $conn->query("SELECT * FROM events ORDER BY id DESC");
                        if ($events->num_rows > 0) {
                            while ($row = $events->fetch_assoc()) {
                                $now = time(); $start = strtotime($row['start_time']); $end = strtotime($row['end_time']);
                                
                                $time_stt = ($now < $start) ? '<span class="badge bg-label-warning">Sắp chạy</span>' : 
                                            (($now >= $start && $now <= $end) ? '<span class="badge bg-label-success">Đang chạy</span>' : 
                                            '<span class="badge bg-label-secondary">Đã xong</span>');

                                $manual_stt = ($row['status'] == 1) ? '' : '<span class="badge bg-danger">OFF</span>';
                                $safe_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong><br><small class="text-muted"><?php echo ($row['type']=='money')?'Đua Tiền':'Đua Job'; ?></small></td>
                                <td>
                                    <small class="d-block text-primary">BĐ: <?php echo date('d/m H:i', $start); ?></small>
                                    <small class="d-block text-danger">KT: <?php echo date('d/m H:i', $end); ?></small>
                                </td>
                                <td><?php echo $manual_stt . ' ' . $time_stt; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning btn-view-win" data-id="<?php echo $row['id']; ?>" title="Xem người thắng"><i class='bx bx-trophy'></i> KQ</button>
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-event='<?php echo $safe_json; ?>'><i class='bx bx-edit'></i></button>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $row['id']; ?>"><i class='bx bx-trash'></i></button>
                                </td>
                            </tr>
                            <?php } 
                        } else { echo '<tr><td colspan="5" class="text-center py-4">Chưa có sự kiện nào.</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST">
            <input type="hidden" name="action" id="modal_action" value="add">
            <input type="hidden" name="id" id="modal_id">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_title">Sự Kiện</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Tên sự kiện</label>
                        <input type="text" name="name" id="inp_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Loại đua</label>
                        <select name="type" id="inp_type" class="form-select"><option value="job">Đua Số Job</option><option value="money">Đua Tiền</option></select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Bắt đầu</label><input type="datetime-local" name="start_time" id="inp_start" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Kết thúc</label><input type="datetime-local" name="end_time" id="inp_end" class="form-control" required></div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold text-primary">Giải thưởng</label>
                        <input type="text" name="top1" id="inp_top1" class="form-control mb-2" placeholder="Top 1">
                        <input type="text" name="top2" id="inp_top2" class="form-control mb-2" placeholder="Top 2">
                        <input type="text" name="top3" id="inp_top3" class="form-control mb-2" placeholder="Top 3">
                        <input type="text" name="top_other" id="inp_other" class="form-control" placeholder="Khác">
                    </div>
                    <div class="col-12"><label class="form-label">Trạng thái</label><select name="status" id="inp_status" class="form-select"><option value="1">Bật</option><option value="0">Tắt</option></select></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="winnerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">🏆 Top Chiến Thần</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="winner_body"><div class="text-center"><div class="spinner-border text-primary"></div></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button></div>
        </div>
    </div>
</div>

<form method="POST" id="del_form" style="display:none;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="del_id"></form>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#btn-add-new').click(function() {
        $('#modal_title').text('Thêm Mới'); $('#modal_action').val('add'); $('#modal_id').val('');
        $('#eventModal form')[0].reset(); $('#inp_status').val(1); $('#eventModal').modal('show');
    });

    $('.btn-edit').click(function() {
        var data = $(this).data('event');
        $('#modal_title').text('Sửa #' + data.id); $('#modal_action').val('edit'); $('#modal_id').val(data.id);
        $('#inp_name').val(data.name); $('#inp_type').val(data.type);
        $('#inp_start').val(data.start_time.replace(' ', 'T')); $('#inp_end').val(data.end_time.replace(' ', 'T'));
        $('#inp_status').val(data.status);
        try {
            var prizes = JSON.parse(data.prizes);
            $('#inp_top1').val(prizes[0]||''); $('#inp_top2').val(prizes[1]||''); $('#inp_top3').val(prizes[2]||'');
            if(prizes.length>3) $('#inp_other').val(prizes.slice(3).join(', ')); else $('#inp_other').val('');
        } catch(e){}
        $('#eventModal').modal('show');
    });

    $('.btn-delete').click(function() {
        var id = $(this).data('id');
        Swal.fire({ title: 'Xóa nhé?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Xóa' }).then((r) => {
            if (r.isConfirmed) { $('#del_id').val(id); $('#del_form').submit(); }
        });
    });

    $('.btn-view-win').click(function() {
        var eid = $(this).data('id');
        $('#winnerModal').modal('show');
        $('#winner_body').html('<div class="text-center"><div class="spinner-border text-primary"></div></div>');
        $.post('', { action: 'get_winners', id: eid }, function(data) { $('#winner_body').html(data); });
    });

    <?php if($js_alert) echo $js_alert; ?>
});
</script>
