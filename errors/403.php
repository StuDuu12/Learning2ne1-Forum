<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Truy Cập Bị Từ Chối</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

        .lock-icon {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .lock-icon:nth-child(1) {
            font-size: 60px;
            top: 15%;
            left: 15%;
            animation-delay: 0s;
        }

        .lock-icon:nth-child(2) {
            font-size: 80px;
            top: 70%;
            left: 75%;
            animation-delay: 3s;
        }

        .lock-icon:nth-child(3) {
            font-size: 50px;
            top: 50%;
            left: 85%;
            animation-delay: 6s;
        }

        .lock-icon:nth-child(4) {
            font-size: 70px;
            top: 80%;
            left: 20%;
            animation-delay: 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            25% {
                transform: translateY(-40px) rotate(10deg);
            }

            50% {
                transform: translateY(-80px) rotate(-10deg);
            }

            75% {
                transform: translateY(-40px) rotate(5deg);
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
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .error-icon {
            font-size: 100px;
            margin-bottom: 20px;
            display: inline-block;
            animation: swing 3s infinite;
        }

        @keyframes swing {

            0%,
            100% {
                transform: rotate(0deg);
            }

            25% {
                transform: rotate(20deg);
            }

            75% {
                transform: rotate(-20deg);
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
            color: #f5576c;
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
        <div class="lock-icon">🔒</div>
        <div class="lock-icon">🔒</div>
        <div class="lock-icon">🔒</div>
        <div class="lock-icon">🔒</div>
    </div>

    <div class="error-container">
        <div class="error-icon">🚫</div>
        <div class="error-code">403</div>
        <h1 class="error-title">Truy Cập Bị Từ Chối</h1>
        <p class="error-message">
            Xin lỗi! Bạn không có quyền truy cập vào trang này.
            <br>
            Vui lòng liên hệ với quản trị viên nếu bạn nghĩ đây là một lỗi.
        </p>
        <a href="/" class="btn-home">🏠 Về Trang Chủ</a>
        <div class="error-details">
            <p>Mã lỗi: 403 Forbidden</p>
        </div>
    </div>
</body>

</html>