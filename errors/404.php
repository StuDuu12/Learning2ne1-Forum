<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không Tìm Thấy Trang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* overflow: hidden; */
            position: relative;
        }

        /* Animated background */
        .bg-animation {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        .star:nth-child(1) {
            width: 3px;
            height: 3px;
            top: 20%;
            left: 25%;
            animation-delay: 0s;
        }

        .star:nth-child(2) {
            width: 2px;
            height: 2px;
            top: 60%;
            left: 80%;
            animation-delay: 1s;
        }

        .star:nth-child(3) {
            width: 4px;
            height: 4px;
            top: 80%;
            left: 30%;
            animation-delay: 2s;
        }

        .star:nth-child(4) {
            width: 3px;
            height: 3px;
            top: 40%;
            left: 70%;
            animation-delay: 1.5s;
        }

        .star:nth-child(5) {
            width: 2px;
            height: 2px;
            top: 15%;
            left: 60%;
            animation-delay: 0.5s;
        }

        .star:nth-child(6) {
            width: 3px;
            height: 3px;
            top: 70%;
            left: 15%;
            animation-delay: 2.5s;
        }

        @keyframes twinkle {

            0%,
            100% {
                opacity: 0.3;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.5);
            }
        }

        .error-container {
            text-align: center;
            color: white;
            z-index: 1;
            padding: 20px;
            max-width: 600px;
        }

        .error-code {
            font-size: 150px;
            font-weight: bold;
            text-shadow: 4px 4px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            animation: float-404 3s infinite ease-in-out;
        }

        @keyframes float-404 {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            25% {
                transform: translateY(-20px) rotate(2deg);
            }

            50% {
                transform: translateY(-40px) rotate(-2deg);
            }

            75% {
                transform: translateY(-20px) rotate(2deg);
            }
        }

        .error-icon {
            font-size: 100px;
            margin-bottom: 20px;
            display: inline-block;
            animation: search 2s infinite;
        }

        @keyframes search {

            0%,
            100% {
                transform: rotate(-10deg);
            }

            50% {
                transform: rotate(10deg);
            }
        }

        .error-title {
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .btn-home {
            display: inline-block;
            padding: 15px 40px;
            background: white;
            color: #4facfe;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: #f8f9fa;
        }

        .error-details {
            margin-top: 20px;
            font-size: 14px;
            opacity: 0.8;
        }

        .suggestions {
            margin-top: 25px;
            text-align: left;
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .suggestions h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .suggestions ul {
            list-style: none;
            padding: 0;
        }

        .suggestions li {
            padding: 5px 0;
            font-size: 14px;
        }

        .suggestions li:before {
            content: "→ ";
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <div class="bg-animation">
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
    </div>

    <div class="error-container">
        <div class="error-icon">🔍</div>
        <div class="error-code">404</div>
        <h1 class="error-title">Không Tìm Thấy Trang</h1>
        <p class="error-message">
            Rất tiếc! Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.
            <br>
            Có thể URL bị nhập sai hoặc trang đã bị xóa.
        </p>
        <a href="/" class="btn-home">🏠 Về Trang Chủ</a>

        <div class="error-details">
            <p>Mã lỗi: 404 Not Found</p>
        </div>
    </div>
</body>

</html>