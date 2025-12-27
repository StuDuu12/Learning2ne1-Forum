<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/helpers.php';

$error = isset($_SESSION['auth_error']) ? $_SESSION['auth_error'] : '';
$success = isset($_SESSION['auth_success']) ? $_SESSION['auth_success'] : '';
unset($_SESSION['auth_error'], $_SESSION['auth_success']);

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['auth_error'] = 'Vui lòng nhập đầy đủ thông tin';
        redirect('login.php');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Check password with MD5 (as per existing system)
        if ($user && $user['password'] === md5($password)) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['ho_ten'] = $user['ho_ten'];
            $_SESSION['account_level'] = $user['account_level'];

            redirect('../index.php');
        } else {
            // Use flash message + redirect to avoid POST resubmission keeping the message
            $_SESSION['auth_error'] = 'Tên đăng nhập hoặc mật khẩu không đúng';
            redirect('login.php');
        }
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $ho_ten = trim($_POST['ho_ten']);
    $username = trim($_POST['reg_username']);
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($ho_ten) || empty($username) || empty($password)) {
        $_SESSION['auth_error'] = 'Vui lòng nhập đầy đủ thông tin';
        redirect('login.php');
    } elseif ($password !== $confirm_password) {
        $_SESSION['auth_error'] = 'Mật khẩu xác nhận không khớp';
        redirect('login.php');
    } elseif (strlen($password) < 6) {
        $_SESSION['auth_error'] = 'Mật khẩu phải có ít nhất 6 ký tự';
        redirect('login.php');
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $_SESSION['auth_error'] = 'Tên đăng nhập đã tồn tại';
            redirect('login.php');
        } else {
            // Insert new user (default account_level = 2 for student)
            $stmt = $pdo->prepare("
                INSERT INTO user (ho_ten, username, password, account_level) 
                VALUES (?, ?, ?, 2)
            ");

            if ($stmt->execute([$ho_ten, $username, md5($password)])) {
                // Set flash success and redirect to clear POST and show message on GET
                $_SESSION['auth_success'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                redirect('login.php');
            } else {
                $_SESSION['auth_error'] = 'Có lỗi xảy ra. Vui lòng thử lại.';
                redirect('login.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Diễn đàn sinh viên</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
    <div class="auth-container">
        <h1 style="text-align: center; color: var(--primary-mint); margin-bottom: 2rem;">
            🎓 Diễn đàn Sinh viên
        </h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <div class="auth-tabs">
            <button class="auth-tab active" onclick="switchTab('login')">Đăng nhập</button>
            <button class="auth-tab" onclick="switchTab('register')">Đăng ký</button>
        </div>

        <!-- Login Form -->
        <form method="POST" class="auth-form active" id="login-form">
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" name="login" class="btn-submit">Đăng nhập</button>
        </form>

        <!-- Registration Form -->
        <form method="POST" class="auth-form" id="register-form">
            <div class="form-group">
                <label>Họ và tên</label>
                <input type="text" name="ho_ten" required>
            </div>
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="reg_username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="reg_password" required autocomplete="new-password" minlength="6">
            </div>
            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password" required autocomplete="new-password">
            </div>
            <button type="submit" name="register" class="btn-submit">Đăng ký</button>
        </form>

        <div class="guest-link">
            <a href="../index.php">Tiếp tục với tư cách khách →</a>
        </div>
    </div>

    <script src="../assets/js/login.js"></script>
</body>

</html>