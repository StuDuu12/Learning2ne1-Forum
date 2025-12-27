<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Lỗi Máy Chủ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f12711 0%, #f5af19 100%);
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

        .gear {
            position: absolute;
            font-size: 80px;
            opacity: 0.15;
            animation: rotate 10s infinite linear;
        }

        .gear:nth-child(1) {
            top: 10%;
            left: 15%;
            animation-duration: 8s;
        }

        .gear:nth-child(2) {
            top: 70%;
            left: 80%;
            animation-duration: 12s;
            animation-direction: reverse;
        }

        .gear:nth-child(3) {
            top: 50%;
            left: 5%;
            animation-duration: 10s;
        }

        .gear:nth-child(4) {
            top: 20%;
            left: 75%;
            font-size: 60px;
            animation-duration: 15s;
            animation-direction: reverse;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
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
            animation: glitch 3s infinite;
        }

        @keyframes glitch {

            0%,
            90%,
            100% {
                transform: translate(0);
            }

            92% {
                transform: translate(-5px, 5px);
            }

            94% {
                transform: translate(5px, -5px);
            }

            96% {
                transform: translate(-5px, -5px);
            }

            98% {
                transform: translate(5px, 5px);
            }
        }

        .error-icon {
            font-size: 100px;
            margin-bottom: 20px;
            display: inline-block;
            animation: buzz 0.5s infinite;
        }

        @keyframes buzz {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px) rotate(-5deg);
            }

            75% {
                transform: translateX(5px) rotate(5deg);
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
            color: #f12711;
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

        .info-box {
            margin-top: 25px;
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .info-box p {
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <div class="bg-animation">
        <div class="gear">⚙️</div>
        <div class="gear">⚙️</div>
        <div class="gear">⚙️</div>
        <div class="gear">⚙️</div>
    </div>

    <div class="error-container">
        <div class="error-icon">🔧</div>
        <div class="error-code">500</div>
        <h1 class="error-title">Lỗi Máy Chủ Nội Bộ</h1>
        <p class="error-message">
            Rất xin lỗi! Đã xảy ra lỗi không mong muốn trên máy chủ.
            <br>
            Chúng tôi đang làm việc để khắc phục sự cố này.
        </p>

        <div>
            <a href="/" class="btn-home">🏠 Về Trang Chủ</a>
        </div>

        <div class="info-box">
            <p>
                <strong>Điều gì đã xảy ra?</strong><br>
                Máy chủ gặp phải một tình huống không mong đợi khiến nó không thể hoàn thành yêu cầu của bạn.
                Đội ngũ kỹ thuật đã được thông báo và đang xử lý vấn đề.
            </p>
        </div>

        <div class="error-details">
            <p>Mã lỗi: 500 Internal Server Error</p>
        </div>
    </div>
</body>

</html>