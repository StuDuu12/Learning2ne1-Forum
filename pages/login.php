<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

$errors = $_SESSION['errors_login'] ?? [];
$usernameInput = $_SESSION['login_username'] ?? '';
unset($_SESSION['errors_login'], $_SESSION['login_username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usernameInput === '') {
        $errors['username'] = 'Vui lòng nhập username.';
    }

    if (trim($password) === '') {
        $errors['password'] = 'Vui lòng nhập mật khẩu.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_user, ho_ten, username, password, account_level FROM user WHERE username = ?");
        $stmt->execute([$usernameInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['account_level'];
                $_SESSION['avatar'] = $user['avatar'] ?? null;

                header("Location: ../index.php");
                exit;
            } else {
                $errors['password'] = 'Mật khẩu không chính xác.';
            }
        } else {
            $errors['username'] = 'Username không tồn tại trong hệ thống.';
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors_login'] = $errors;
        $_SESSION['login_username'] = $usernameInput;
        header("Location: login.php");
        exit;
    }

    unset($_POST['username'], $_POST['password']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Diễn đàn Sinh viên</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <h2>Đăng nhập</h2>
                <p>Đăng nhập để tiếp tục và tham gia cộng đồng.</p>
            </div>
            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Nhập username" value="<?php echo htmlspecialchars($usernameInput); ?>">
                    <?php if (!empty($errors['username'])): ?>
                        <p class="field-error"><?php echo $errors['username']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu">
                    <?php if (!empty($errors['password'])): ?>
                        <p class="field-error"><?php echo $errors['password']; ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-login">Đăng nhập</button>
            </form>

            <div class="login-footer">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                <p><a href="#">Quên mật khẩu?</a></p>
                <p><a href="../index.php" class="btn-guest" title="Tiếp tục truy cập với tư cách khách">Tiếp tục với khách</a></p>
            </div>
        </div>
    </div>
</body>

</html>