<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Lạc đường rồi!</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* CẤU HÌNH CHUNG */
        body {
            background: #233142; /* Màu xanh đen sang trọng */
            font-family: 'Open Sans', sans-serif;
            color: #fff;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            overflow: hidden;
        }

        /* CON MA (VẼ BẰNG CSS 100%) */
        .ghost {
            animation: float 3s ease-out infinite;
        }

        .ghost .body {
            position: relative;
            width: 120px;
            height: 160px;
            background-color: #fff;
            border-top-left-radius: 60px;
            border-top-right-radius: 60px;
            margin: 0 auto;
            z-index: 10;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .ghost .body .face {
            position: absolute;
            top: 40px;
            left: 20%;
            width: 72px; /* 60% of body */
            display: flex;
            justify-content: space-between;
        }

        .ghost .body .face .eye {
            width: 20px;
            height: 20px;
            background-color: #233142;
            border-radius: 50%;
            animation: blink 4s infinite;
        }

        .ghost .body .face .mouth {
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background-color: #233142;
        }

        .ghost .body .feet {
            position: absolute;
            bottom: -15px;
            width: 100%;
            display: flex;
        }

        .ghost .body .feet .foot {
            flex: 1;
            height: 25px;
            background-color: #fff;
            border-radius: 0 0 15px 15px;
        }

        .ghost .shadow {
            width: 100px;
            height: 20px;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 50%;
            margin: 40px auto 0;
            animation: shrink 3s ease-out infinite;
        }

        /* TEXT VÀ NÚT */
        .content { text-align: center; margin-top: 30px; z-index: 20; }
        
        h1 {
            font-family: 'Fredoka One', cursive;
            font-size: 6rem;
            margin: 0;
            color: #455d7a;
            text-shadow: 3px 3px 0 #f95959;
            line-height: 1;
        }

        h3 {
            font-size: 1.5rem;
            margin: 10px 0 20px;
            color: #f95959;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        p { color: #cfd8dc; margin-bottom: 40px; font-size: 1rem; }

        .btn {
            background-color: #f95959;
            color: #fff;
            padding: 15px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(249, 89, 89, 0.4);
            display: inline-block;
        }

        .btn:hover {
            background-color: #ff7b7b;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(249, 89, 89, 0.6);
        }

        /* ANIMATIONS */
        @keyframes float {
            50% { transform: translateY(-20px); }
        }
        @keyframes shrink {
            50% { transform: scale(0.7); opacity: 0.7; }
        }
        @keyframes blink {
            0%, 10%, 100% { transform: scaleY(1); }
            5% { transform: scaleY(0.1); }
        }
    </style>
</head>
<body>

    <div class="ghost">
        <div class="body">
            <div class="face">
                <div class="eye"></div>
                <div class="eye"></div>
                <div class="mouth"></div>
            </div>
            <div class="feet">
                <div class="foot"></div>
                <div class="foot"></div>
                <div class="foot"></div>
            </div>
        </div>
        <div class="shadow"></div>
    </div>

    <div class="content">
        <h1>404</h1>
        <h3>oidoioi! Lạc đường rồi...</h3>
        <p>
            Trang này không tồn tại hoặc đã bị ma giấu.<br>
            Đừng sợ, quay về nhà thôi nào!
        </p>
        <a href="/app/index.php" class="btn">VỀ TRANG CHỦ (PHẢI CHỊU)</a>
    </div>

</body>
</html>
