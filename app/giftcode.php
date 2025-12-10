<?php
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
require_once '../includes/header.php';
?>

<style>
    /* NỀN & CARD */
    .gift-wrapper {
        background: linear-gradient(135deg, #8E2DE2, #4A00E0);
        min-height: 80vh;
        border-radius: 20px;
        padding: 40px 20px;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .gift-icon { font-size: 80px; margin-bottom: 20px; animation: float 3s ease-in-out infinite; }
    @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-20px); } 100% { transform: translateY(0px); } }
    
    .gift-input {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 50px;
        padding: 15px 25px;
        color: white;
        font-size: 20px;
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
        width: 100%;
        max-width: 400px;
        margin-bottom: 20px;
        backdrop-filter: blur(5px);
    }
    .gift-input::placeholder { color: rgba(255, 255, 255, 0.7); text-transform: none; }
    .gift-input:focus { outline: none; background: rgba(255, 255, 255, 0.3); border-color: #fff; box-shadow: 0 0 15px rgba(255,255,255,0.5); }

    .btn-gift {
        background: #ffeb3b;
        color: #d32f2f;
        font-weight: 900;
        padding: 15px 40px;
        border-radius: 50px;
        border: none;
        font-size: 18px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        transition: transform 0.2s;
        text-transform: uppercase;
    }
    .btn-gift:active { transform: scale(0.95); }

    /* HIỆU ỨNG PHONG THƯ (LETTER) */
    .letter-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .envelope {
        width: 300px; height: 200px;
        background: #c0392b;
        position: relative;
        border-radius: 10px;
        cursor: pointer;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        transition: transform 0.5s;
    }
    .envelope:hover { transform: scale(1.05); }
    .envelope::before { /* Nắp bao thư */
        content: ''; position: absolute; top: 0; left: 0;
        border-left: 150px solid transparent;
        border-right: 150px solid transparent;
        border-top: 100px solid #e74c3c;
        transform-origin: top;
        transition: transform 0.5s 0.5s; /* Delay mở nắp */
    }
    .envelope.open::before { transform: rotateX(180deg); z-index: 0; }
    
    .paper {
        position: absolute; bottom: 10px; left: 10px; right: 10px;
        background: #fff;
        height: 180px;
        padding: 20px;
        transition: bottom 0.5s 1s, height 0.5s 1s; /* Trượt lên sau khi mở nắp */
        z-index: 1;
        overflow: hidden;
        border-radius: 5px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center;
    }
    .envelope.open .paper {
        bottom: 50px; height: 350px; /* Trượt hẳn lên cao */
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }
    
    .paper-content { opacity: 0; transition: opacity 0.5s 1.5s; color: #333; }
    .envelope.open .paper-content { opacity: 1; }
    
    .reward-badge { font-size: 24px; font-weight: 800; color: #d35400; margin-bottom: 10px; display: block; }
    .letter-text { font-size: 15px; font-style: italic; line-height: 1.6; color: #555; }
    .close-letter { margin-top: 20px; background: #333; color: #fff; border: none; padding: 8px 20px; border-radius: 20px; font-size: 12px; cursor: pointer; }
</style>

<input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <div class="gift-wrapper animate__animated animate__zoomIn">
            <div class="gift-icon">🎁</div>
            <h2 class="fw-bold text-white mb-2">GIFTCODE</h2>
            <p class="text-white opacity-75 mb-4">Nhập mã quà tặng bí mật từ Admin để nhận thưởng lớn!</p>
            
            <div class="d-flex justify-content-center">
                <div style="width: 100%; max-width: 400px;">
                    <input type="text" id="giftcode_input" class="gift-input" placeholder="Nhập mã tại đây..." autocomplete="off">
                    <button class="btn-gift" onclick="submitCode()">NHẬN QUÀ NGAY</button>
                </div>
            </div>
            
            <div class="mt-4 text-white-50 small">
                * Mỗi mã chỉ sử dụng được 1 lần duy nhất.<br>
                * Hãy theo dõi kênh Telegram để săn mã.
            </div>
        </div>

    </div>
</div>

<div class="letter-overlay" id="letter_scene">
    <div class="text-white mb-4 fw-bold animate__animated animate__fadeInDown">👇 BẤM ĐỂ MỞ QUÀ 👇</div>
    <div class="envelope" onclick="openLetter()">
        <div class="paper">
            <div class="paper-content">
                <div class="mb-2">🎉 CHÚC MỪNG 🎉</div>
                <span class="reward-badge" id="reward_val"></span>
                <div class="letter-text" id="letter_msg"></div>
                <button class="close-letter" onclick="closeLetter(event)">Xác nhận & Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function submitCode() {
    var code = $('#giftcode_input').val().trim();
    if(code == '') { Swal.fire('Lỗi', 'Chưa nhập mã kìa!', 'warning'); return; }
    
    var btn = $('.btn-gift');
    btn.prop('disabled', true).text('ĐANG KIỂM TRA...');

    $.ajax({
        url: 'ajax_giftcode.php', type: 'POST', dataType: 'json',
        data: { code: code, csrf_token: $('#csrf_token').val() },
        success: function(res) {
            btn.prop('disabled', false).text('NHẬN QUÀ NGAY');
            
            if (res.status) {
                // Thành công -> Hiện phong thư
                $('#reward_val').text(res.reward_text);
                $('#letter_msg').html(res.letter);
                $('#letter_scene').css('display', 'flex'); // Hiện Overlay
                // Reset input
                $('#giftcode_input').val('');
            } else {
                Swal.fire('Thất bại', res.msg, 'error');
            }
        },
        error: function() {
            btn.prop('disabled', false).text('NHẬN QUÀ NGAY');
            Swal.fire('Lỗi', 'Mất kết nối server!', 'error');
        }
    });
}

function openLetter() {
    $('.envelope').addClass('open'); // Kích hoạt CSS mở nắp và trượt giấy
    $('.text-white.mb-4').fadeOut(); // Ẩn dòng chữ hướng dẫn
}

function closeLetter(e) {
    e.stopPropagation(); // Chặn sự kiện click xuyên qua
    $('#letter_scene').fadeOut();
    setTimeout(function() {
        $('.envelope').removeClass('open'); // Reset phong thư
        location.reload(); // Reload để cập nhật tiền
    }, 500);
}
</script>

<?php require_once '../includes/footer.php'; ?>
