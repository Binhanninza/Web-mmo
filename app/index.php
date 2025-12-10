<?php
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
// Lưu ý: Dùng require_once '../includes/header.php'; ở đây
// để load phần đầu trang (header)
require_once '../includes/header.php';

$user_id = $_SESSION['user_id'];
$u = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$done_task = $conn->query("SELECT COUNT(*) FROM history WHERE user_id = $user_id")->fetch_row()[0];
?>

<style>
    /* GIAO DIỆN APP */
    .wallet-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 25px; color: white; box-shadow: 0 10px 30px rgba(118, 75, 162, 0.3); margin-bottom: 25px; position: relative; overflow: hidden; }
    .wallet-card::before { content: ''; position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; }
    .wallet-balance { font-size: 32px; font-weight: 800; margin: 10px 0; }
    .stats-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 30px; }
    .stat-item { background: #fff; border-radius: 15px; padding: 15px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .stat-icon-circle { width: 45px; height: 45px; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; }

    /* NEWSFEED */
    .fb-post { background: #fff; border-radius: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.04); margin-bottom: 25px; overflow: hidden; }
    .fb-header { padding: 15px; display: flex; align-items: center; }
    .fb-avatar { width: 42px; height: 42px; border-radius: 50%; margin-right: 12px; object-fit: cover; border: 1px solid #eee; }
    .fb-name { font-weight: 700; color: #2d3436; font-size: 15px; }
    .fb-time { font-size: 11px; color: #b2bec3; }
    .fb-content { padding: 0 15px 15px; font-size: 15px; color: #444; line-height: 1.6; }
    .fb-img { width: 100%; height: auto; max-height: 350px; object-fit: cover; display: block; }
    .fb-actions { padding: 10px 15px; border-top: 1px solid #f9f9f9; display: flex; gap: 10px; }
    .fb-btn { flex: 1; text-align: center; padding: 8px; border-radius: 50px; color: #636e72; font-weight: 600; background: #f8f9fa; border: none; font-size: 13px; }
    .heart-anim { color: #ff3e1d !important; animation: heartBeat 0.4s ease-in-out; }
    @keyframes heartBeat { 0% { transform: scale(1); } 50% { transform: scale(1.4); } 100% { transform: scale(1); } }

    /* MODAL TIKTOK STYLE */
    .modal.fade .modal-dialog { transition: transform 0.3s ease-out; transform: translateY(100%); }
    .modal.show .modal-dialog { transform: translateY(0); }
    .modal-dialog-bottom { position: fixed; bottom: 0; left: 0; right: 0; margin: 0; width: 100%; max-width: 100%; height: 80vh; }
    .modal-content-tiktok { height: 100%; border-radius: 20px 20px 0 0; border: none; background: #fff; display: flex; flex-direction: column; }
    .tiktok-header { text-align: center; padding: 15px; border-bottom: 1px solid #eee; font-weight: 700; position: relative; }
    .tiktok-close { position: absolute; right: 15px; top: 12px; font-size: 24px; cursor: pointer; }
    
    .cmt-list-container { flex: 1; overflow-y: auto; padding: 15px; padding-bottom: 80px; }
    .cmt-item { margin-bottom: 15px; display: flex; }
    .cmt-avatar { width: 32px; height: 32px; border-radius: 50%; margin-right: 10px; object-fit: cover; border: 1px solid #eee; }
    .cmt-body { flex: 1; }
    .cmt-user { font-weight: 600; font-size: 13px; color: #161823; }
    .cmt-text { font-size: 14px; color: #161823; line-height: 1.4; margin-top: 2px; }
    .cmt-meta { font-size: 12px; color: #8a8b91; margin-top: 4px; display: flex; gap: 15px; }
    .cmt-reply-btn { font-weight: 600; cursor: pointer; }

    /* SKELETON LOADING */
    .skeleton { background: #eee; background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%); border-radius: 5px; background-size: 200% 100%; animation: 1.5s shine linear infinite; }
    @keyframes shine { to { background-position-x: -200%; } }
    .sk-item { display: flex; margin-bottom: 15px; }
    .sk-avatar { width: 32px; height: 32px; border-radius: 50%; margin-right: 10px; }
    .sk-lines { flex: 1; }
    .sk-line { height: 10px; margin-bottom: 6px; width: 80%; }
    .sk-line.short { width: 40%; }

    /* FOOTER INPUT */
    .tiktok-footer { padding: 10px 15px; border-top: 1px solid #eee; background: #fff; position: absolute; bottom: 0; width: 100%; }
    .reply-indicator { background: #f1f1f2; padding: 8px 12px; font-size: 12px; color: #666; display: none; align-items: center; justify-content: space-between; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid #696cff; }
    .tiktok-input-group { display: flex; align-items: center; gap: 10px; }
    .tiktok-input { flex: 1; background: #f1f1f2; border: none; border-radius: 20px; padding: 10px 15px; font-size: 14px; }
    .tiktok-send { color: #fe2c55; background: none; border: none; font-size: 24px; cursor: pointer; }
    .btn-load-more { text-align: center; color: #888; font-size: 13px; font-weight: 600; cursor: pointer; padding: 10px; display: block; }
    
    /* Style riêng cho Pop-up */
    .notice-content img { max-width: 100%; height: auto; }
</style>

<input type="hidden" id="global_csrf" value="<?php echo $_SESSION['csrf_token']; ?>">

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
            <div><h4 class="fw-bold mb-0">Hi, <?php echo htmlspecialchars($u['username']); ?> 👋</h4></div>
            <a href="profile.php" class="avatar avatar-sm"><img src="<?php echo !empty($u['avatar'])?$u['avatar']:'/assets/img/avatars/1.png'; ?>" class="rounded-circle border"></a>
        </div>

        <div class="wallet-card animate__animated animate__fadeInDown">
            <div class="d-flex justify-content-between"><div><div class="wallet-label">Số dư khả dụng</div><div class="wallet-balance"><?php echo number_format($u['money']); ?> đ</div></div><i class='bx bx-chip fs-1 opacity-50'></i></div>
            <div class="mt-3 d-flex gap-3"><div class="badge bg-white text-primary bg-opacity-25"><i class='bx bx-star'></i> <?php echo $u['reputation']; ?> UT</div><div class="badge bg-white text-warning bg-opacity-25"><i class='bx bx-crown'></i> LV <?php echo $u['exp']; ?></div></div>
        </div>

        <div class="stats-grid">
            <div class="stat-item"><div class="stat-icon-circle bg-label-primary text-primary"><i class='bx bx-task'></i></div><h5 class="mb-0 fw-bold"><?php echo $done_task; ?></h5><small>Đã làm</small></div>
            <div class="stat-item"><div class="stat-icon-circle bg-label-success text-success"><i class='bx bx-check-shield'></i></div><h5 class="mb-0 fw-bold">VIP 1</h5><small>Cấp độ</small></div>
            <div class="stat-item"><div class="stat-icon-circle bg-label-info text-info"><i class='bx bx-gift'></i></div><h5 class="mb-0 fw-bold">0</h5><small>Quà tặng</small></div>
        </div>

        <div class="feed-header mb-3"><h5 class="fw-bold m-0"><i class='bx bxs-hot text-danger'></i> Tin tức</h5></div>
        
        <div class="row justify-content-center">
            <div class="col-md-8 col-12">
                <?php
                $posts = $conn->query("SELECT * FROM posts ORDER BY created_at DESC");
                if($posts->num_rows > 0){
                    while($p = $posts->fetch_assoc()){
                        $total_cmt = $conn->query("SELECT COUNT(*) FROM comments WHERE post_id={$p['id']} AND status=1")->fetch_row()[0];
                ?>
                <div class="fb-post animate__animated animate__fadeInUp">
                    <div class="fb-header">
                        <img src="/assets/img/avatars/1.png" class="fb-avatar">
                        <div><div class="fb-name">Admin System <i class='bx bxs-badge-check text-primary'></i></div><div class="fb-time"><?php echo date('d/m H:i', strtotime($p['created_at'])); ?></div></div>
                    </div>
                    <div class="fb-content"><?php echo nl2br(htmlspecialchars($p['content'])); ?></div>
                    <?php if($p['image']) echo '<img src="/'.$p['image'].'" class="fb-img">'; ?>
                    
                    <div class="px-3 py-2 text-muted small d-flex justify-content-between">
                        <span id="like-text-<?php echo $p['id']; ?>"><i class='bx bxs-heart text-danger'></i> <?php echo number_format($p['likes']); ?></span>
                        <span onclick="openTikTokModal(<?php echo $p['id']; ?>, <?php echo $total_cmt; ?>)"><?php echo $total_cmt; ?> bình luận</span>
                    </div>

                    <div class="fb-actions">
                        <button class="fb-btn" id="btn-like-<?php echo $p['id']; ?>" onclick="spamLike(<?php echo $p['id']; ?>)"><i class='bx bx-heart'></i> Thả tim</button>
                        <button class="fb-btn" onclick="openTikTokModal(<?php echo $p['id']; ?>, <?php echo $total_cmt; ?>)"><i class='bx bx-message-rounded'></i> Bình luận</button>
                    </div>
                </div>
                <?php } } else { echo '<div class="text-center text-muted py-5">Chưa có tin tức!</div>'; } ?>
            </div>
        </div>
    </div>
</div>

<audio id="backgroundMusic" loop></audio>

<div id="music-toggle-btn" 
     style="position: fixed; bottom: 80px; left: 15px; z-index: 1050; width: 50px; height: 50px; background: #696cff; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(105, 108, 255, 0.6); cursor: pointer;"
     data-bs-toggle="modal" 
     data-bs-target="#musicModal">
    <i class='bx bx-volume-low text-white fs-3' id="musicIcon"></i>
</div>

<div class="modal fade" id="musicModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-primary"><i class='bx bx-headphone'></i> Chill Nhạc Nền</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <ul class="list-group list-group-flush" id="music-list">
                    </ul>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-sm btn-danger" id="musicStopBtn" onclick="stopMusic()">
                     <i class='bx bx-stop-circle'></i> Tắt Hẳn
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="tiktokModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-bottom">
        <div class="modal-content modal-content-tiktok">
            <div class="tiktok-header">
                <span id="tiktok-count">0</span> bình luận
                <div class="tiktok-close" data-bs-dismiss="modal">&times;</div>
            </div>
            
            <div class="cmt-list-container" id="cmt-scroll-area">
                <div id="tiktok-cmt-list"></div>
                <div id="load-more-container" class="pb-3"></div>
            </div>

            <div class="tiktok-footer">
                <div class="reply-indicator" id="replying-bar">
                    <span>Đang trả lời <b id="reply-user-name">...</b></span>
                    <i class='bx bx-x cursor-pointer' onclick="cancelReply()"></i>
                </div>
                
                <div class="tiktok-input-group">
                    <input type="hidden" id="modal-post-id">
                    <input type="hidden" id="modal-parent-id" value="0">
                    <input type="text" class="tiktok-input" id="modal-input" placeholder="Thêm bình luận..." autocomplete="off">
                    <button class="tiktok-send" onclick="sendTikTokComment()"><i class='bx bxs-send fs-4'></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// KIỂM TRA BẰNG BIẾN $CMS ĐÃ FIX
if (!empty($CMS['homepage_popup'])): 
?>
<div class="modal fade" id="homePagePopup" tabindex="-1" aria-labelledby="homePagePopupLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger fw-bold" id="homePagePopupLabel">📢 THÔNG BÁO QUAN TRỌNG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo $CMS['homepage_popup'] ?? 'Nội dung thông báo đang được cập nhật.'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button> 
                
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="btn-hide-two-hours">Ẩn 2 giờ</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Đảm bảo Bootstrap đã load (tương đương với việc dùng DOMContentLoaded)
    if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
        console.error("Bootstrap 5 JS không được load.");
        return;
    }

    const popupKey = 'popup_seen_timestamp';
    const twoHours = 2 * 60 * 60 * 1000; // 7,200,000 miligiây (2 tiếng)
    const now = new Date().getTime();

    // 1. LOGIC HIỂN THỊ 
    const lastSeen = localStorage.getItem(popupKey);

    if (!lastSeen || (now - lastSeen) > twoHours) {
        // Hiển thị Modal
        const myModal = new bootstrap.Modal(document.getElementById('homePagePopup'));
        myModal.show();
    }


    // 2. LOGIC NÚT BẤM: Gán hành động lưu timestamp vào nút "Ẩn 2 giờ"
    const hideBtn = document.getElementById('btn-hide-two-hours');
    if (hideBtn) {
        hideBtn.addEventListener('click', function() {
            // Lưu timestamp vào Local Storage
            localStorage.setItem(popupKey, now);
        });
    }
});
</script>
<?php endif; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
var csrfToken = $('#global_csrf').val();
var currentOffset = 0;
var currentLimit = 10;
var currentPid = 0;

// VẼ SKELETON
function renderSkeleton() {
    let html = '';
    for(let i=0; i<5; i++) {
        html += `<div class="sk-item"><div class="sk-avatar skeleton"></div><div class="sk-lines"><div class="sk-line skeleton"></div><div class="sk-line short skeleton"></div></div></div>`;
    }
    return html;
}

// MỞ MODAL
function openTikTokModal(pid, count) {
    currentPid = pid;
    $('#modal-post-id').val(pid);
    $('#tiktok-count').text(count);
    
    // Skeleton Loading 1s
    $('#tiktok-cmt-list').html(renderSkeleton());
    $('#load-more-container').html('');
    $('#tiktokModal').modal('show');
    
    setTimeout(function() {
        currentOffset = 0;
        loadComments(pid, 0, currentLimit);
    }, 1000);
}

// TẢI DATA
function loadComments(pid, offset, limit) {
    $.ajax({
        url: 'ajax_social.php', type: 'POST', dataType: 'json',
        data: { action: 'load_comments', post_id: pid, offset: offset, limit: limit, csrf_token: csrfToken },
        success: function(data) {
            if(data.status) {
                if(offset == 0) $('#tiktok-cmt-list').html('');
                if(data.html) $('#tiktok-cmt-list').append(data.html);
                else if (offset == 0) $('#tiktok-cmt-list').html('<div class="text-center py-5 text-muted">Chưa có bình luận nào.</div>');

                if(data.remaining > 0) {
                    $('#load-more-container').html(`<span class="btn-load-more" onclick="loadMore()">Xem thêm ${data.remaining} bình luận cũ hơn...</span>`);
                } else {
                    $('#load-more-container').html('');
                }
            }
        },
        error: function() { $('#tiktok-cmt-list').html('<div class="text-center text-danger py-5">Lỗi mạng!</div>'); }
    });
}

function loadMore() {
    currentOffset += currentLimit;
    $('#load-more-container').html('<div class="text-center small text-muted py-2"><i class="bx bx-loader-alt bx-spin"></i> Đang tải thêm...</div>');
    loadComments(currentPid, currentOffset, 5);
}

// GỬI & REPLY
function replyCmt(cmtId, username) {
    $('#modal-parent-id').val(cmtId);
    $('#reply-user-name').text(username);
    $('#replying-bar').css('display', 'flex');
    $('#modal-input').focus();
}
function cancelReply() {
    $('#modal-parent-id').val(0);
    $('#replying-bar').hide();
    $('#modal-input').attr('placeholder', 'Thêm bình luận...');
}
function sendTikTokComment() {
    var pid = $('#modal-post-id').val();
    var content = $('#modal-input').val().trim();
    var parentId = $('#modal-parent-id').val();
    if(content == '') return;

    $('.tiktok-send').prop('disabled', true);

    $.ajax({
        url: 'ajax_social.php', type: 'POST', dataType: 'json',
        data: { action: 'comment', post_id: pid, content: content, parent_id: parentId, csrf_token: csrfToken },
        success: function(data) {
            $('.tiktok-send').prop('disabled', false);
            if(data.status) {
                // Fake hiện ngay (thêm chữ Đang chờ duyệt)
                var myHtml = `<div class="cmt-item animate__animated animate__fadeInUp"><img src="<?php echo $u['avatar']?$u['avatar']:'/assets/img/avatars/1.png'; ?>" class="cmt-avatar"><div class="cmt-body"><div class="cmt-user"><?php echo $u['username']; ?> <span class="badge bg-warning text-dark" style="font-size:9px;">Đang chờ duyệt</span></div><div class="cmt-text">${content}</div><div class="cmt-meta"><span>Vừa xong</span></div></div></div>`;
                
                $('#tiktok-cmt-list').prepend(myHtml);
                $('#cmt-scroll-area').animate({ scrollTop: 0 }, 300);
                $('#modal-input').val('');
                cancelReply();
            } else { alert(data.msg); }
        },
        error: function() { $('.tiktok-send').prop('disabled', false); alert('Lỗi mạng!'); }
    });
}

var lastClick = {}; 
function spamLike(pid) {
    var now = new Date().getTime();
    if (lastClick[pid] && (now - lastClick[pid] < 500)) return;
    lastClick[pid] = now;
    var btn = $('#btn-like-' + pid);
    var countEl = $('#like-text-' + pid);
    var current = parseInt(countEl.text().replace(/\D/g,''));
    btn.addClass('text-danger');
    btn.find('i').removeClass('bx-heart').addClass('bxs-heart heart-anim');
    countEl.html("<i class='bx bxs-heart text-danger'></i> " + (current + 1).toLocaleString());
    setTimeout(function() { btn.find('i').removeClass('heart-anim'); }, 500);
    $.post('ajax_social.php', { action: 'like', post_id: pid, csrf_token: csrfToken });
}
</script>

<script>
    // Khai báo biến
    const musicElement = document.getElementById('backgroundMusic');
    const musicIcon = document.getElementById('musicIcon');
    const musicListContainer = document.getElementById('music-list');
    const musicStopButton = document.getElementById('musicStopBtn');
    
    // Danh sách nhạc (Đã sửa thành MP3)
    const playlist = [
        { id: 1, name: "Nhạc 1: Đang cập nhật", url: "https://vietrust.site/nhac1.mp3" },
        { id: 2, name: "Nhạc 2: Đang cập nhật", url: "https://vietrust.site/nhac2.mp3" },
        { id: 3, name: "Nhạc 3: Đang cập nhật", url: "https://vietrust.site/nhac3.mp3" },
        { id: 4, name: "Nhạc 4: Đang cập nhật", url: "https://vietrust.site/nhac4.mp3" },
        { id: 5, name: "Nhạc 5: Đang cập nhật", url: "https://vietrust.site/nhac5.mp3" },
    ];
    
    let currentTrackIndex = localStorage.getItem('currentTrackIndex') || 0;
    let isPlaying = localStorage.getItem('isMusicPlaying') === 'true';

    // --- FUNCTION CHÍNH ---

    // 1. Dừng nhạc
    function stopMusic() {
        musicElement.pause();
        musicElement.removeAttribute('src');
        musicIcon.classList.remove('bx-volume-full', 'bx-tada-hover');
        musicIcon.classList.add('bx-volume-low');
        isPlaying = false;
        localStorage.setItem('isMusicPlaying', 'false');
        updatePlaylistView();
        $('#musicModal').modal('hide'); 
    }

    // 2. Phát nhạc (theo Index)
    function playMusic(index) {
        currentTrackIndex = index;
        const track = playlist[index];
        
        musicElement.src = track.url;
        musicElement.load();
        
        const playPromise = musicElement.play();
        if (playPromise !== undefined) {
            playPromise.then(_ => {
                musicIcon.classList.remove('bx-volume-low');
                musicIcon.classList.add('bx-volume-full', 'bx-tada-hover');
                isPlaying = true;
                localStorage.setItem('isMusicPlaying', 'true');
                localStorage.setItem('currentTrackIndex', index);
                updatePlaylistView();
            }).catch(error => {
                console.warn("Autoplay bị chặn, cần thêm tương tác người dùng.");
                musicIcon.classList.remove('bx-volume-full', 'bx-tada-hover');
                musicIcon.classList.add('bx-volume-low');
                isPlaying = false;
                localStorage.setItem('isMusicPlaying', 'false');
                updatePlaylistView();
            });
        }
    }
    
    // 3. Cập nhật giao diện List nhạc
    function updatePlaylistView() {
        musicListContainer.innerHTML = '';
        playlist.forEach((track, index) => {
            const isActive = index == currentTrackIndex && isPlaying && !musicElement.paused;
            const li = document.createElement('li');
            li.className = `list-group-item d-flex justify-content-between align-items-center ${isActive ? 'bg-label-primary fw-bold' : ''}`;
            li.innerHTML = `
                <span>${track.name}</span>
                <button class="btn btn-xs ${isActive ? 'btn-primary' : 'btn-outline-primary'}" onclick="playMusic(${index})" data-bs-dismiss="modal">
                    <i class='bx ${isActive ? 'bx-pause' : 'bx-play'}'></i> 
                </button>
            `;
            musicListContainer.appendChild(li);
        });
        
        // Cập nhật nút Stop
        musicStopButton.style.display = isPlaying ? 'inline-block' : 'none';
        
        // Cập nhật Icon nổi
        musicIcon.classList.remove('bx-volume-low', 'bx-volume-full', 'bx-tada-hover');
        if (isPlaying && !musicElement.paused) {
             musicIcon.classList.add('bx-volume-full', 'bx-tada-hover');
        } else {
             musicIcon.classList.add('bx-volume-low');
        }
    }
    
    // 4. Phát bài tiếp theo (TỰ ĐỘNG CHUYỂN BÀI khi hết)
    musicElement.addEventListener('ended', function() {
        currentTrackIndex = (currentTrackIndex + 1) % playlist.length;
        playMusic(currentTrackIndex);
    });

    // 5. Khởi động (Init)
    document.addEventListener('DOMContentLoaded', function() {
        updatePlaylistView();
        
        // Nếu đang phát nhạc từ Local Storage
        if (isPlaying) {
             musicElement.src = playlist[currentTrackIndex].url;
        }

        // Tạo sự kiện click đầu tiên để Play (FIX lỗi chặn Autoplay)
        document.body.addEventListener('click', function attemptAutoPlay() {
            if (isPlaying && musicElement.paused) {
                const playPromise = musicElement.play();
                if (playPromise !== undefined) {
                    playPromise.then(_ => {
                        musicIcon.classList.add('bx-volume-full', 'bx-tada-hover');
                        document.body.removeEventListener('click', attemptAutoPlay);
                    }).catch(error => {
                        // Vẫn bị chặn, chờ user bấm lần nữa
                    });
                }
            }
        });
    });

</script>
<?php require_once '../includes/footer.php'; ?>
