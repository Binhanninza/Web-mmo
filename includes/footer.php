            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  © <script>document.write(new Date().getFullYear());</script>
                  , made with ❤️ by <a href="#" target="_blank" class="footer-link fw-bolder"><?php echo isset($CMS['site_name']) ? $CMS['site_name'] : 'VIETRUST MMO'; ?></a>
                </div>
              </div>
            </footer>
            
            <div class="content-backdrop fade"></div>
          </div> </div> </div> <div class="layout-overlay layout-menu-toggle"></div>
    </div> 
    
    <script src="/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/assets/vendor/libs/popper/popper.js"></script>
    <script src="/assets/vendor/js/bootstrap.js"></script>
    <script src="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/assets/vendor/js/menu.js"></script>
    <script src="/assets/js/main.js"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const themeBtn = document.getElementById('btn-theme-toggle');
        const themeIcon = document.getElementById('icon-theme');
        const html = document.documentElement;
        function updateIcon(isDark) { if (isDark) { themeIcon.classList.remove('bx-moon'); themeIcon.classList.add('bx-sun'); } else { themeIcon.classList.remove('bx-sun'); themeIcon.classList.add('bx-moon'); } }
        if (localStorage.getItem('theme') === 'dark') { updateIcon(true); }
        if (themeBtn) { themeBtn.addEventListener('click', function(e) { e.preventDefault(); if (html.classList.contains('dark-mode')) { html.classList.remove('dark-mode'); localStorage.setItem('theme', 'light'); updateIcon(false); } else { html.classList.add('dark-mode'); localStorage.setItem('theme', 'dark'); updateIcon(true); } }); }
    });
    </script>

    <?php 
    // Load cấu hình lễ hội
    if(!isset($CMS['holiday_mode'])) { global $conn; $CMS = $conn->query("SELECT holiday_mode FROM settings WHERE id=1")->fetch_assoc(); }
    $mode = isset($CMS['holiday_mode']) ? $CMS['holiday_mode'] : 'none';
    
    if ($mode != 'none'):
        $conf = [];
        if ($mode == 'tet') $conf = ["['🌸', '🧧', '💰']", 'https://media4.giphy.com/media/v1.Y2lkPTZjMDliOTUyZ3VkdmlvMTdkenc1b2NqcDduMnhycDQ1YzZ4NnAxajFyYzNqcTQ0eCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/QaYqtStGSYNd21aXQp/giphy.gif'];
        elseif ($mode == 'noel') $conf = ["['❄️', '⛄', '✨']", 'https://media3.giphy.com/media/v1.Y2lkPTZjMDliOTUyY2ZzYWtocjFncGJrdnZ2YzFmZnEyNzcxYjI1YWZ6cHl0dXUwa2p3MyZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/ybS2XyyszCjaHh3Krr/giphy.gif'];
        elseif ($mode == 'val') $conf = ["['❤️', '🌹', '💌']", 'https://media.giphy.com/media/l0HlOaQcLn2hOV6SY/giphy.gif'];
        elseif ($mode == 'halloween') $conf = ["['🕸️', '🦴', '🦇']", 'https://media.giphy.com/media/3o6Zrp5qLd4x4Xq9Ms/giphy.gif'];
        elseif ($mode == 'hbd') $conf = ["['🎊', '🎈', '🎉']", 'https://media.giphy.com/media/3o7TKMt1VVNkHV2PaE/giphy.gif'];
        else $conf = ["['❄️']", ''];
    ?>
    
    <style>
        /* Hiệu ứng rơi */
        .holiday-flake { position: fixed; top: -10px; z-index: 9990; user-select: none; pointer-events: none; animation: fall linear forwards; text-shadow: 0 0 3px rgba(0,0,0,0.3); }
        @keyframes fall { to { transform: translateY(100vh) rotate(720deg); } }
        
        /* Hiệu ứng bay lơ lửng ngẫu nhiên (GIF) */
        .holiday-floater { position: fixed; z-index: 9995; pointer-events: none; width: 60px; height: auto; opacity: 0.9; }
        @media (max-width: 768px) { .holiday-floater { width: 40px; } }
        @keyframes floatUpRight { 0% { bottom: -150px; left: 10%; transform: rotate(0deg); } 100% { bottom: 110vh; left: 90%; transform: rotate(0deg); } }
        @keyframes floatUpLeft { 0% { bottom: -150px; left: 90%; transform: scaleX(-1) rotate(0deg); } 100% { bottom: 110vh; left: 10%; transform: scaleX(-1) rotate(0deg); } }
    </style>

    <script>
    (function() {
        // 1. TẠO TUYẾT RƠI
        const icons = <?php echo isset($conf[0]) ? $conf[0] : "['❄️']"; ?>;
        const container = document.body;
        function createFlake() {
            const f = document.createElement('div');
            f.innerHTML = icons[Math.floor(Math.random() * icons.length)];
            f.classList.add('holiday-flake');
            f.style.left = Math.random() * 100 + 'vw';
            f.style.fontSize = (Math.random() * 20 + 15) + 'px'; 
            f.style.animationDuration = (Math.random() * 3 + 3) + 's'; 
            f.style.opacity = Math.random() * 0.7 + 0.3;
            container.appendChild(f);
            setTimeout(() => { f.remove(); }, 6000);
        }
        setInterval(createFlake, 500);

        // 2. TẠO GIF BAY (Nếu có link)
        const gifUrl = "<?php echo isset($conf[1]) ? $conf[1] : ''; ?>";
        function spawnFloater() {
            if (!gifUrl) return;
            const img = document.createElement('img');
            img.src = gifUrl;
            img.classList.add('holiday-floater');
            // Random bay trái hoặc phải
            const type = Math.random() > 0.5 ? 'floatUpRight' : 'floatUpLeft';
            const duration = Math.floor(Math.random() * 5 + 8) + 's';
            img.style.animation = `${type} ${duration} linear forwards`;
            img.style.left = (Math.random() * 20) + '%';
            container.appendChild(img);
            setTimeout(() => { img.remove(); }, 13000);
        }
        if (gifUrl) { spawnFloater(); setInterval(spawnFloater, 6000); }
    })();
    </script>
    <?php endif; ?>

</body>
</html>
