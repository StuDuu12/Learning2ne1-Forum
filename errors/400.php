<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>400 - Yêu Cầu Không Hợp Lệ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* overflow: hidden; */
            position: relative;
        }

        /* Animated background circles */
        .bg-animation {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 15s infinite ease-in-out;
        }

        .circle:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 20%;
            animation-delay: 0s;
        }

        .circle:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 70%;
            left: 70%;
            animation-delay: 2s;
        }

        .circle:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 40%;
            left: 80%;
            animation-delay: 4s;
        }

        .circle:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 60%;
            left: 10%;
            animation-delay: 1s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) translateX(0);
            }

            25% {
                transform: translateY(-30px) translateX(30px);
            }

            50% {
                transform: translateY(-60px) translateX(-30px);
            }

            75% {
                transform: translateY(-30px) translateX(-60px);
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
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-30px);
            }

            60% {
                transform: translateY(-15px);
            }
        }

        .error-icon {
            font-size: 100px;
            margin-bottom: 20px;
            display: inline-block;
            animation: shake 3s infinite;
        }

        @keyframes shake {

            0%,
            100% {
                transform: rotate(0deg);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: rotate(-10deg);
            }

            20%,
            40%,
            60%,
            80% {
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
            opacity: 0.9;
        }

        .btn-home {
            display: inline-block;
            padding: 15px 40px;
            background: white;
            color: #667eea;
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
    </style>
</head>

<body>
    <div class="bg-animation">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <div class="error-code">400</div>
        <h1 class="error-title">Yêu Cầu Không Hợp Lệ</h1>
        <p class="error-message">
            Xin lỗi! Yêu cầu của bạn không thể được xử lý do dữ liệu không hợp lệ hoặc thiếu thông tin cần thiết.
            <br>
            Vui lòng kiểm tra lại thông tin và thử lại.
        </p>
        <a href="/" class="btn-home">🏠 Về Trang Chủ</a>
        <div class="error-details">
            <p>Mã lỗi: 400 Bad Request</p>
        </div>
    </div>
</body>

</html>