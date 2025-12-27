<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Dịch Vụ Không Khả Dụng</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #868f96 0%, #596164 100%);
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

        .cloud {
            position: absolute;
            font-size: 60px;
            opacity: 0.2;
            animation: drift 25s infinite linear;
        }

        .cloud:nth-child(1) {
            top: 15%;
            left: -10%;
            animation-duration: 20s;
        }

        .cloud:nth-child(2) {
            top: 40%;
            left: -15%;
            animation-duration: 30s;
            animation-delay: 5s;
        }

        .cloud:nth-child(3) {
            top: 65%;
            left: -10%;
            animation-duration: 25s;
            animation-delay: 10s;
        }

        .cloud:nth-child(4) {
            top: 80%;
            left: -12%;
            font-size: 80px;
            animation-duration: 35s;
            animation-delay: 2s;
        }

        @keyframes drift {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(120vw);
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
            animation: fade 2s infinite;
        }

        @keyframes fade {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .error-icon {
            font-size: 100px;
            margin-bottom: 20px;
            display: inline-block;
            animation: maintenance 3s infinite;
        }

        @keyframes maintenance {

            0%,
            100% {
                transform: rotate(-15deg) translateY(0);
            }

            50% {
                transform: rotate(15deg) translateY(-10px);
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
            color: #596164;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin-right: 10px;
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: #f8f9fa;
        }

        .btn-retry {
            display: inline-block;
            padding: 15px 40px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid white;
        }

        .btn-retry:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.3);
        }

        .error-details {
            margin-top: 20px;
            font-size: 14px;
            opacity: 0.8;
        }

        .status-box {
            margin-top: 25px;
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .status-box h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .status-box p {
            font-size: 14px;
            line-height: 1.6;
        }

        .loading-bar {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            margin-top: 15px;
            overflow: hidden;
        }

        .loading-progress {
            width: 30%;
            height: 100%;
            background: white;
            border-radius: 2px;
            animation: loading 2s infinite;
        }

        @keyframes loading {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(400%);
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation">
        <div class="cloud">☁️</div>
        <div class="cloud">☁️</div>
        <div class="cloud">☁️</div>
        <div class="cloud">☁️</div>
    </div>

    <div class="error-container">
        <div class="error-icon">🛠️</div>
        <div class="error-code">503</div>
        <h1 class="error-title">Dịch Vụ Không Khả Dụng</h1>
        <p class="error-message">
            Hệ thống hiện đang được bảo trì hoặc quá tải.
            <br>
            Vui lòng thử lại sau vài phút.
        </p>

        <div>
            <a href="/" class="btn-home">🏠 Về Trang Chủ</a>
        </div>

        <div class="status-box">
            <h3>⏱️ Thông Tin Bảo Trì</h3>
            <p>
                Chúng tôi đang nâng cấp hệ thống để mang đến trải nghiệm tốt hơn cho bạn.
                Dự kiến hoàn thành trong thời gian sớm nhất.
            </p>
            <div class="loading-bar">
                <div class="loading-progress"></div>
            </div>
        </div>

        <div class="error-details">
            <p>Mã lỗi: 503 Service Unavailable</p>
            <p>Cảm ơn sự kiên nhẫn của bạn! 🙏</p>
        </div>
    </div>
</body>

</html>