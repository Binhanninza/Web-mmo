<?php
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
require_once '../includes/header.php';
$user_id = $_SESSION['user_id'];
?>

<style>
    /* === GIAO DIỆN TELEGRAM STYLE === */
    
    /* 1. Danh sách Ticket */
    .ticket-list-item { 
        transition: 0.2s; 
        border-radius: 12px; 
        margin-bottom: 10px; 
        border: 1px solid #eee; 
        cursor: pointer; 
        background: #fff;
    }
    .ticket-list-item:hover { 
        background: #f8f9fa; 
        transform: translateX(5px); 
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    /* 2. Khung Chat (Room) */
    .chat-container { 
        background: #eef2f5; 
        height: 75vh; 
        display: flex; 
        flex-direction: column; 
        border-radius: 15px; 
        overflow: hidden; 
        position: relative; 
        border: 1px solid #e0e0e0;
    }
    .chat-header { 
        background: #fff; 
        padding: 10px 15px; 
        border-bottom: 1px solid #ddd; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
        z-index: 10; 
    }
    .chat-body { 
        flex: 1; 
        overflow-y: auto; 
        padding: 20px; 
        /* Hình nền Telegram (Hoặc màu trơn nếu thích) */
        background-color: #94aab9; 
        background-image: url('https://w.wallhaven.cc/full/l8/wallhaven-l83o92.jpg'); 
        background-size: cover; 
        background-position: center; 
    }
    .chat-footer { 
        background: #fff; 
        padding: 10px; 
        border-top: 1px solid #ddd; 
    }
    
    /* 3. Bong bóng Chat (Bubbles) */
    .msg-row { display: flex; margin-bottom: 10px; width: 100%; }
    
    /* Tin nhắn của mình (Phải - Xanh) */
    .msg-row.me { justify-content: flex-end; }
    .msg-row.me .msg-bubble { 
        background: #696cff; 
        color: #fff; 
        border-bottom-right-radius: 2px;
    }
    .msg-row.me .msg-time { color: rgba(255,255,255,0.7); }

    /* Tin nhắn của người khác (Trái - Trắng) */
    .msg-row.other { justify-content: flex-start; }
    .msg-row.other .msg-bubble { 
        background: #fff; 
        color: #333; 
        border-bottom-left-radius: 2px;
    }
    
    /* Tin nhắn HỆ THỐNG (Giữa - Xám) */
    .msg-row.system { justify-content: center; margin: 15px 0; }
    .msg-row.system .msg-bubble { 
        background: rgba(0,0,0,0.3); /* Nền mờ */
        color: #fff; 
        font-size: 12px; 
        font-style: italic; 
        box-shadow: none; 
        padding: 5px 15px; 
        border-radius: 20px; 
        backdrop-filter: blur(2px);
    }

    /* Style chung cho Bubble */
    .msg-bubble { 
        max-width: 75%; 
        padding: 8px 12px; 
        border-radius: 12px; 
        font-size: 14px; 
        position: relative; 
        word-wrap: break-word; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.15); 
    }
    .msg-time { 
        font-size: 10px; 
        margin-top: 4px; 
        display: block; 
        text-align: right; 
        opacity: 0.7; 
    }
    
    /* 4. Tiện ích (Utils) */
    .prio-3 { color: #ff3e1d; font-weight: bold; } /* Khẩn cấp */
    .prio-2 { color: #ffab00; font-weight: bold; } /* Vừa */
    .prio-1 { color: #71dd37; font-weight: bold; } /* Bình thường */
    
    /* Nút Tạo Ticket Nổi (FAB) */
    .fab-btn { 
        position: fixed; 
        bottom: 80px; 
        right: 20px; 
        width: 60px; 
        height: 60px; 
        border-radius: 50%; 
        font-size: 30px; 
        box-shadow: 0 4px 15px rgba(105, 108, 255, 0.4); 
        z-index: 999; 
        transition: 0.3s;
    }
    .fab-btn:active { transform: scale(0.9); }
</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <div id="view-list">
            <h4 class="fw-bold py-3 mb-2"><i class='bx bx-support text-primary'></i> Hỗ trợ trực tuyến</h4>
            
            <div id="ticket-list-area">
                <?php
                // Load danh sách Ticket của User
                $list = $conn->query("SELECT * FROM tickets WHERE user_id = $user_id ORDER BY updated_at DESC");
                if ($list->num_rows > 0) {
                    while ($row = $list->fetch_assoc()) {
                        // Xử lý hiển thị mức độ
                        $p_text = ($row['priority'] == 3) ? 'Khẩn cấp 🆘' : (($row['priority'] == 2) ? 'Mức độ Vừa ⚠️' : 'Bình thường ☕');
                        $p_class = 'prio-' . $row['priority'];
                        
                        // Xử lý trạng thái (Tin mới / Đã đóng / Đang mở)
                        if ($row['status'] == 1) {
                            $stt = '<span class="badge bg-secondary">Đã rời</span>';
                        } elseif ($row['is_read_user'] == 0) {
                            $stt = '<span class="badge bg-danger animate__animated animate__pulse animate__infinite">Tin nhắn mới</span>';
                        } else {
                            $stt = '<span class="badge bg-label-success">Đang mở</span>';
                        }
                        
                        // Item HTML
                        echo '<div class="ticket-list-item p-3 shadow-sm animate__animated animate__fadeInUp" onclick="openRoom('.$row['id'].', '.$row['priority'].', '.$row['status'].')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-label-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <i class="bx bx-hash"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">Phòng Chat số '.$row['id'].'</h6>
                                            <small class="'.$p_class.'" style="font-size: 11px;">'.$p_text.'</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        '.$stt.' 
                                        <div class="text-muted small mt-1"><i class="bx bx-time"></i> '.date('H:i d/m', strtotime($row['updated_at'])).'</div>
                                    </div>
                                </div>
                              </div>';
                    }
                } else {
                    echo '<div class="text-center text-muted py-5 mt-5">
                            <i class="bx bx-message-square-dots display-1 opacity-25"></i>
                            <p class="mt-3">Chưa có yêu cầu hỗ trợ nào.<br>Bấm nút <b>+</b> bên dưới để tạo.</p>
                          </div>';
                }
                ?>
            </div>
            
            <button class="btn btn-primary fab-btn d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class='bx bx-plus'></i>
            </button>
        </div>

        <div id="view-room" style="display:none;" class="animate__animated animate__fadeIn">
            <div class="chat-container">
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-icon btn-label-secondary me-2 rounded-circle" onclick="closeRoom()">
                            <i class='bx bx-arrow-back'></i>
                        </button>
                        <div>
                            <span class="fw-bold fs-6 d-block" id="room-title">Phòng Chat #...</span>
                            <div class="small" id="room-subtitle">Mức độ: ...</div>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-danger rounded-pill fw-bold" onclick="leaveTicket()">
                        <i class='bx bx-log-out-circle'></i> Rời khỏi
                    </button>
                </div>
                
                <div class="chat-body" id="chat-box">
                    </div>
                
                <div class="chat-footer">
                    <form id="chat-form" onsubmit="return sendMessage()">
                        <div class="input-group">
                            <input type="hidden" id="current-ticket-id">
                            <input type="text" id="msg-input" class="form-control border-0 shadow-none bg-light rounded-pill px-3 me-2" placeholder="Nhập tin nhắn..." autocomplete="off">
                            <button class="btn btn-primary rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class='bx bxs-send fs-5'></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-center w-100">Bạn cần hỗ trợ gì?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="text-center text-muted small mb-3">Chọn mức độ ưu tiên để Admin xử lý nhanh nhất.</p>
                <div class="d-grid gap-2">
                    <button onclick="createTicket(3)" class="btn btn-outline-danger fw-bold text-start p-3 rounded-3 position-relative">
                        <i class='bx bxs-hot fs-4 me-2 align-middle'></i> Khẩn cấp (Cháy nhà)
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 badge bg-danger rounded-pill">SOS</span>
                    </button>
                    <button onclick="createTicket(2)" class="btn btn-outline-warning fw-bold text-start p-3 rounded-3">
                        <i class='bx bxs-bell-ring fs-4 me-2 align-middle'></i> Vừa (Cần gấp)
                    </button>
                    <button onclick="createTicket(1)" class="btn btn-outline-success fw-bold text-start p-3 rounded-3">
                        <i class='bx bxs-coffee fs-4 me-2 align-middle'></i> Bình thường (Chill)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var chatInterval;
var currentTid = 0;

// 1. TẠO TICKET
function createTicket(prio) {
    $.post('ajax_ticket.php', { action: 'create_ticket', priority: prio }, function(res) {
        if(res.status) {
            $('#createModal').modal('hide');
            // Thay vì reload, ta có thể tự thêm vào list, nhưng reload cho chắc ăn
            location.reload(); 
        } else {
            alert('Lỗi: ' + res.msg);
        }
    }, 'json');
}

// 2. MỞ PHÒNG CHAT
function openRoom(id, prio, status) {
    currentTid = id;
    $('#current-ticket-id').val(id);
    
    // Chuyển màn hình
    $('#view-list').hide();
    $('#view-room').fadeIn();
    
    // Update Header
    $('#room-title').text('Phòng Chat số ' + id);
    var pText = (prio==3)?'Khẩn cấp 🆘':(prio==2?'Vừa ⚠️':'Bình thường ☕');
    var pColor = (prio==3)?'text-danger':(prio==2?'text-warning':'text-success');
    $('#room-subtitle').html('<span class="'+pColor+' fw-bold">'+pText+'</span>');
    
    // Check trạng thái đóng/mở
    if(status == 1) {
        $('#chat-form input, #chat-form button').prop('disabled', true);
        $('#msg-input').val('Phòng chat đã đóng.').addClass('text-center fst-italic');
    } else {
        $('#chat-form input, #chat-form button').prop('disabled', false);
        $('#msg-input').val('').removeClass('text-center fst-italic').focus();
    }

    // Load tin nhắn ngay và bắt đầu Polling
    loadMessages();
    chatInterval = setInterval(loadMessages, 3000); // 3s check tin mới 1 lần
}

// 3. ĐÓNG PHÒNG (BACK)
function closeRoom() {
    clearInterval(chatInterval); // Dừng polling cho đỡ lag server
    $('#view-room').hide();
    $('#view-list').fadeIn();
    location.reload(); // Reload để mất dấu "Tin nhắn mới"
}

// 4. GỬI TIN NHẮN
function sendMessage() {
    var msg = $('#msg-input').val().trim();
    if(!msg) return false;
    
    $('#msg-input').val(''); // Clear input ngay cho mượt
    
    // Gửi Ajax
    $.post('ajax_ticket.php', { action: 'send_msg', ticket_id: currentTid, message: msg }, function(){
        loadMessages(); // Load lại để hiện tin vừa gửi
    }, 'json');
    return false; // Chặn submit form
}

// 5. LOAD TIN NHẮN (CÓ SYSTEM MESSAGE)
function loadMessages() {
    $.post('ajax_ticket.php', { action: 'load_msgs', ticket_id: currentTid }, function(res) {
        if(res.status) {
            var html = '';
            res.data.forEach(function(m) {
                if (m.sender_role == 'system') {
                    // TIN NHẮN HỆ THỐNG (Giữa - Xám)
                    html += `<div class="msg-row system">
                                <div class="msg-bubble">
                                    ${m.message}
                                    <span class="msg-time" style="text-align: center;">${m.time}</span>
                                </div>
                             </div>`;
                } else {
                    // TIN NHẮN CHAT (Me/Other)
                    var cls = m.is_me ? 'me' : 'other';
                    html += `<div class="msg-row ${cls}">
                                <div class="msg-bubble">
                                    ${m.message}
                                    <span class="msg-time">${m.time}</span>
                                </div>
                             </div>`;
                }
            });
            $('#chat-box').html(html);
            
            // Auto scroll (Nếu muốn luôn cuộn xuống đáy)
            // var d = $('#chat-box');
            // d.scrollTop(d[0].scrollHeight); 
        }
    }, 'json');
}

// 6. RỜI PHÒNG (ĐÓNG TICKET)
function leaveTicket() {
    if(confirm('Bạn có chắc muốn rời khỏi phòng này? Admin sẽ không thể trả lời thêm.')) {
        $.post('ajax_ticket.php', { action: 'leave_ticket', ticket_id: currentTid }, function(){
            closeRoom(); // Quay về list
        }, 'json');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
