<?php
// 1. KẾT NỐI CONFIG
require_once 'config/database.php';

// Lấy cấu hình web (Nếu chưa có thì dùng mặc định)
if (!isset($CMS)) {
    $CMS = [
        'site_title' => 'VIETRUST MMO',
        'site_name'  => 'VIETRUST MMO',
        'site_description' => 'Hệ thống kiếm tiền online uy tín',
        'site_keywords' => 'mmo, kiem tien',
        'min_withdraw' => 10000,
        'tele_support' => '#'
    ];
}

// Check xem đã đăng nhập chưa để đổi nút trên Menu
$is_login = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="vi" class="light-style layout-menu-fixed" data-theme="theme-default" data-assets-path="/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <title><?php echo htmlspecialchars($CMS['site_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($CMS['site_description']); ?>" />
    
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
    <link rel="stylesheet" href="/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="/assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="/assets/css/demo.css" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        body { overflow-x: hidden; }
        .hero-section { background: linear-gradient(120deg, #696cff 0%, #8592a3 100%); position: relative; overflow: hidden; padding: 120px 0 180px 0; color: white; clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); }
        .shape { position: absolute; opacity: 0.1; animation: float 10s infinite ease-in-out; }
        .shape-1 { top: 10%; left: 5%; font-size: 5rem; animation-duration: 8s; }
        .shape-2 { bottom: 20%; right: 10%; font-size: 8rem; animation-duration: 12s; }
        .shape-3 { top: 40%; right: 40%; font-size: 3rem; animation-duration: 6s; }
        @keyframes float { 0% { transform: translateY(0px) rotate(0deg); } 50% { transform: translateY(-30px) rotate(20deg); } 100% { transform: translateY(0px) rotate(0deg); } }
        
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 15px 40px rgba(0,0,0,0.1); transition: transform 0.3s; background: rgba(255, 255, 255, 1); }
        .stat-card:hover { transform: translateY(-10px); }
        .stats-container { margin-top: -100px; position: relative; z-index: 10; }
        .live-payout-box { max-height: 400px; overflow: hidden; position: relative; }
        .live-row { animation: fadeIn 0.5s ease-in-out; border-bottom: 1px dashed #eee; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .accordion-button:not(.collapsed) { background-color: #e7e7ff; color: #696cff; }
        .accordion-item { border: none; box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12); margin-bottom: 1rem; border-radius: 0.5rem !important; overflow: hidden; }
        #fake-notify { position: fixed; bottom: 20px; left: 20px; z-index: 9999; background: #fff; border-left: 5px solid #696cff; box-shadow: 0 5px 20px rgba(0,0,0,0.15); border-radius: 8px; padding: 15px; display: flex; align-items: center; min-width: 320px; transform: translateX(-150%); transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55); }
        #fake-notify.show { transform: translateX(0); }
        .partner-logo { filter: grayscale(100%); opacity: 0.6; transition: 0.3s; max-height: 40px; margin: 0 20px; }
        .partner-logo:hover { filter: grayscale(0%); opacity: 1; transform: scale(1.1); }
        .img-fluid-custom { max-height: 150px; width: auto; }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary fs-3 d-flex align-items-center" href="/">
                <i class='bx bxs-wallet-alt me-2 display-6'></i> <?php echo $CMS['site_name']; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-ex-2">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbar-ex-2">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-4 d-none d-lg-block">
                        <span class="badge bg-label-success px-3 py-2 rounded-pill"><i class='bx bx-radio-circle-marked bx-flashing'></i> Server Online</span>
                    </li>
                    
                    <?php if ($is_login): ?>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-lg shadow-sm ms-3 px-4 fw-bold" href="/app/index.php">
                                <i class='bx bxs-dashboard'></i> VÀO DASHBOARD
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link fw-bold" href="/login.php">ĐĂNG NHẬP</a></li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-lg shadow-sm ms-3 px-4" href="/register.php">ĐĂNG KÝ NGAY</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section text-center">
        <i class='bx bxs-coin shape shape-1 text-white'></i>
        <i class='bx bxs-wallet shape shape-2 text-warning'></i>
        <i class='bx bxs-rocket shape shape-3 text-info'></i>
        <div class="container position-relative z-index-1" data-aos="zoom-out-up" data-aos-duration="1000">
            <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill fw-bold shadow-sm">🎉 <?php echo $CMS['site_title']; ?></span>
            <h1 class="display-3 fw-bolder mb-3 text-white">BIẾN THỜI GIAN RẢNH <br> THÀNH <span class="text-warning">TIỀN MẶT</span></h1>
            <p class="lead mb-5 opacity-90 mx-auto" style="max-width: 700px;">Tham gia cộng đồng <strong>85,000+</strong> thành viên. Làm nhiệm vụ đơn giản, rút gọn link, tải app. Thu nhập thụ động trọn đời.</p>
            
            <div class="d-flex justify-content-center gap-3">
                <?php if ($is_login): ?>
                    <a href="/app/index.php" class="btn btn-warning btn-lg fw-bold px-5 py-3 shadow-lg fs-5 text-dark"><i class='bx bx-play-circle'></i> KIẾM TIỀN NGAY</a>
                <?php else: ?>
                    <a href="/register.php" class="btn btn-warning btn-lg fw-bold px-5 py-3 shadow-lg fs-5 text-dark"><i class='bx bx-user-plus'></i> BẮT ĐẦU NGAY</a>
                <?php endif; ?>
                <a href="#live-payment" class="btn btn-outline-white btn-lg fw-bold px-5 py-3 fs-5 text-white border-white"><i class='bx bx-show'></i> XEM BẰNG CHỨNG</a>
            </div>
        </div>
    </div>

    <div class="container stats-container">
        <div class="row text-center" id="counter-section">
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card stat-card h-100 p-4">
                    <div class="avatar mx-auto mb-3 bg-label-primary rounded p-2" style="width: 60px; height: 60px;"><i class='bx bxs-user-account fs-2 pt-1'></i></div>
                    <h2 class="fw-bold text-dark counter mb-0" data-target="86421">0</h2>
                    <span class="text-muted fw-semibold">Thành viên</span>
                </div>
            </div>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card stat-card h-100 p-4">
                    <div class="card-body p-0">
                        <div class="avatar mx-auto mb-3 bg-label-success rounded p-2" style="width: 60px; height: 60px;"><i class='bx bxs-bank fs-2 pt-1'></i></div>
                        <h2 class="fw-bold text-success counter mb-0" data-target="2150400000">0</h2>
                        <span class="text-muted fw-semibold">VNĐ Đã chi trả</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card stat-card h-100 p-4">
                    <div class="avatar mx-auto mb-3 bg-label-warning rounded p-2" style="width: 60px; height: 60px;"><i class='bx bxs-time-five fs-2 pt-1'></i></div>
                    <h2 class="fw-bold text-warning counter mb-0" data-target="158209">0</h2>
                    <span class="text-muted fw-semibold">Nhiệm vụ hoàn thành</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" id="live-payment">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4" data-aos="fade-right">
                <h6 class="text-primary fw-bold text-uppercase ls-1">Minh bạch tài chính</h6>
                <h2 class="fw-bold display-6 mb-4">Danh sách rút tiền <span class="text-success">Vừa xong</span></h2>
                <p class="text-muted mb-4">Hệ thống xử lý thanh toán hoàn toàn tự động (Auto Banking). Tiền về tài khoản chỉ sau 30 giây kể từ khi đặt lệnh.</p>
                <a href="/app/wallet.php" class="btn btn-primary btn-lg shadow">Rút tiền ngay <i class='bx bx-right-arrow-alt'></i></a>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold m-0"><i class='bx bxs-circle text-danger bx-flashing me-2'></i>Live Transactions</h5>
                        <small class="text-muted">Cập nhật: Vừa xong</small>
                    </div>
                    <div class="card-body p-0 live-payout-box">
                        <table class="table table-striped mb-0">
                            <thead class="bg-light"><tr><th>Thành viên</th><th>Phương thức</th><th>Số tiền</th><th>Trạng thái</th></tr></thead>
                            <tbody id="payout-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-light py-5">
        <div class="container py-5">
            <div class="text-center mb-5" data-aos="fade-down">
                <h2 class="fw-bold">Kiếm tiền như thế nào?</h2>
                <p class="text-muted">Chỉ với 3 bước đơn giản để tạo thu nhập thụ động</p>
            </div>
            <div class="row text-center">
                <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="100">
                    <div class="bg-white p-4 rounded shadow-sm h-100">
                        <img src="https://vietrust.site/anh.png" class="img-fluid-custom mb-3" alt="Đăng ký">
                        <h4 class="fw-bold">1. Đăng ký & Làm NV</h4>
                        <p class="text-muted">Tạo tài khoản miễn phí. Chọn nhiệm vụ vượt link, tải app hoặc xem video để thực hiện.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="200">
                    <div class="bg-white p-4 rounded shadow-sm h-100">
                        <img src="https://img.icons8.com/clouds/500/monitor.png" class="img-fluid-custom mb-3" alt="Tích lũy">
                        <h4 class="fw-bold">2. Tích lũy xu</h4>
                        <p class="text-muted">Hệ thống tự động cộng tiền vào ví ngay khi hoàn thành nhiệm vụ.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="300">
                    <div class="bg-white p-4 rounded shadow-sm h-100">
                        <img src="https://img.icons8.com/clouds/500/workstation.png" class="img-fluid-custom mb-3" alt="Rút tiền">
                        <h4 class="fw-bold">3. Rút tiền</h4>
                        <p class="text-muted">Đặt lệnh rút tiền về Momo, Ngân hàng hoặc Thẻ cào. Min rút chỉ <?php echo number_format($CMS['min_withdraw']); ?>đ.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Thành viên nói gì về chúng tôi?</h2>
            <p class="text-muted">Hơn 85,000 thành viên đã kiếm được tiền</p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4" data-aos="zoom-in" data-aos-delay="100">
                <div class="card h-100 border-0 shadow-sm p-3">
                    <div class="card-body">
                        <div class="d-flex mb-3 text-warning"><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i></div>
                        <p class="fst-italic text-muted">"Web uy tín nhất mình từng chơi. Rút tiền về Momo cái vèo. Ngày rảnh rỗi cũng kiếm được 50k - 100k tiền cafe."</p>
                        <div class="d-flex align-items-center mt-3">
                            <img src="https://images.unsplash.com/photo-1599566150163-29194dcaad36?w=100&h=100&fit=crop" class="rounded-circle me-3" width="50" alt="Khách hàng">
                            <div><h6 class="fw-bold mb-0">Trần Văn Tú</h6><small class="text-muted">Sinh viên</small></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4" data-aos="zoom-in" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-sm p-3">
                    <div class="card-body">
                        <div class="d-flex mb-3 text-warning"><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i></div>
                        <p class="fst-italic text-muted">"Hỗ trợ nhiệt tình. Nhiệm vụ nhiều làm không hết. Sẽ giới thiệu cho bạn bè cùng chơi."</p>
                        <div class="d-flex align-items-center mt-3">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop" class="rounded-circle me-3" width="50" alt="Khách hàng">
                            <div><h6 class="fw-bold mb-0">Nguyễn Thu Hà</h6><small class="text-muted">Mẹ bỉm sữa</small></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4" data-aos="zoom-in" data-aos-delay="300">
                <div class="card h-100 border-0 shadow-sm p-3">
                    <div class="card-body">
                        <div class="d-flex mb-3 text-warning"><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star-half'></i></div>
                        <p class="fst-italic text-muted">"Min rút thấp, phù hợp với học sinh. Duyệt tiền nhanh hơn người yêu cũ trở mặt. 10 điểm uy tín."</p>
                        <div class="d-flex align-items-center mt-3">
                            <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&h=100&fit=crop" class="rounded-circle me-3" width="50" alt="Khách hàng">
                            <div><h6 class="fw-bold mb-0">Lê Hoàng Nam</h6><small class="text-muted">Freelancer</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-light py-5">
        <div class="container py-5">
            <div class="text-center mb-5" data-aos="fade-down">
                <h2 class="fw-bold">Giải Đáp Thắc Mắc Phổ Biến</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Tất cả những điều bạn cần biết để bắt đầu vượt link và kiếm tiền trên <?php echo $CMS['site_name']; ?>.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item mb-3" data-aos="fade-up" data-aos-delay="100">
                            <h2 class="accordion-header"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1"><i class='bx bx-help-circle me-2 text-primary fs-4'></i> Tôi có cần bỏ vốn để kiếm tiền không?</button></h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Không cần bỏ vốn hay đóng phí. Bạn chỉ cần tài khoản Vietrust, truy cập link được giao và làm nhiệm vụ.</div></div>
                        </div>
                        <div class="accordion-item mb-3" data-aos="fade-up" data-aos-delay="200">
                            <h2 class="accordion-header"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2"><i class='bx bx-wallet me-2 text-primary fs-4'></i> Vậy có cần nạp tiền để rút được không?</button></h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Không cần nạp tiền trước khi rút. Số dư tích lũy từ nhiệm vụ đủ mức tối thiểu là bạn có thể yêu cầu rút ngay.</div></div>
                        </div>
                        <div class="accordion-item mb-3" data-aos="fade-up" data-aos-delay="300">
                            <h2 class="accordion-header"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3"><i class='bx bx-time-five me-2 text-primary fs-4'></i> Làm sao để rút tiền và mất bao lâu?</button></h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Vào mục Ví > Rút tiền, chọn số tiền (tối thiểu <?php echo number_format($CMS['min_withdraw']); ?>đ) và ngân hàng mong muốn. Yêu cầu sẽ được duyệt trong vòng 24 giờ.</div></div>
                        </div>
                        <div class="accordion-item mb-3" data-aos="fade-up" data-aos-delay="400">
                            <h2 class="accordion-header"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4"><i class='bx bx-share-alt me-2 text-primary fs-4'></i> Làm sao để giới thiệu bạn bè?</button></h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Vào web, chọn mục Referrals > bấm sao chép link chia sẻ và gửi cho bạn bè để nhận hoa hồng 5%.</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white pt-5 pb-3">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4"><h4 class="fw-bold text-white mb-3"><?php echo $CMS['site_name']; ?></h4><p class="text-white-50">Hệ thống kiếm tiền online hàng đầu.</p></div>
                <div class="col-md-4 mb-4"><h5 class="fw-bold text-white mb-3">Tham gia ngay</h5><a href="/register.php" class="btn btn-primary w-100 mb-3 fw-bold">ĐĂNG KÝ MIỄN PHÍ</a><p class="text-white-50 small">Copyright © 2025 <?php echo $CMS['site_name']; ?>.</p></div>
            </div>
        </div>
    </footer>

    <div id="fake-notify">
        <img src="https://images.unsplash.com/photo-1527980965255-d3b416303d12?w=100&h=100&fit=crop" class="rounded-circle" width="40" height="40">
        <div class="ms-3"><h6 class="mb-0 fw-bold fs-7" id="notify-name">Nguyễn Văn A</h6><small class="text-success d-block" id="notify-action">vừa rút 50.000đ</small><small class="text-muted" style="font-size: 10px;">Vừa xong</small></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 100 });
        let started = false; const statsSection = document.getElementById('counter-section'); const counters = document.querySelectorAll('.counter'); const DURATION = 6000; const FRAME_RATE = 20;
        function startCounters() { counters.forEach(counter => { const target = +counter.getAttribute('data-target'); const totalFrames = DURATION / FRAME_RATE; const increment = target / totalFrames; let current = 0; const timer = setInterval(() => { current += increment; if (current >= target) { counter.innerText = target.toLocaleString('en-US'); clearInterval(timer); } else { counter.innerText = Math.floor(current).toLocaleString('en-US'); } }, FRAME_RATE); }); }
        const observer = new IntersectionObserver((entries) => { if(entries[0].isIntersecting && !started) { startCounters(); started = true; } }); observer.observe(statsSection);
        const names = ['Hoàng Nam', 'Minh Tú', 'Ngọc Lan', 'Thanh Hằng', 'Quang Dũng', 'Văn Hậu', 'Thị Mận', 'Đức Phúc']; const methods = ['Momo', 'MB Bank', 'ZaloPay', 'VCB', 'Thẻ cào']; const amounts = ['10,000đ', '20,000đ', '50,000đ', '100,000đ'];
        function addLiveRow() { const tbody = document.getElementById('payout-body'); if(!tbody) return; const randName = names[Math.floor(Math.random() * names.length)]; const randMethod = methods[Math.floor(Math.random() * methods.length)]; const randAmount = amounts[Math.floor(Math.random() * amounts.length)]; const maskedName = randName.substring(0, 3) + '***'; const row = document.createElement('tr'); row.classList.add('live-row'); row.innerHTML = `<td><span class="fw-bold">${maskedName}</span></td><td><span class="badge bg-label-secondary">${randMethod}</span></td><td><span class="text-success fw-bold">+${randAmount}</span></td><td><span class="badge bg-success">Thành công</span></td>`; tbody.insertBefore(row, tbody.firstChild); if (tbody.children.length > 6) tbody.removeChild(tbody.lastChild); } setInterval(addLiveRow, 2500); for(let i=0; i<5; i++) addLiveRow();
        function showFakeNotify() { const notify = document.getElementById('fake-notify'); const nameEl = document.getElementById('notify-name'); const actEl = document.getElementById('notify-action'); const randName = names[Math.floor(Math.random() * names.length)]; const randAmount = amounts[Math.floor(Math.random() * amounts.length)]; const randMethod = methods[Math.floor(Math.random() * methods.length)]; nameEl.innerText = "Bạn " + randName; actEl.innerText = `vừa rút ${randAmount} qua ${randMethod}`; notify.classList.add('show'); setTimeout(() => { notify.classList.remove('show'); }, 4000); } setInterval(showFakeNotify, 6000); setTimeout(showFakeNotify, 3000);
    </script>
</body>
</html>
