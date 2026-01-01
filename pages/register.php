<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

$errors = $_SESSION['errors_register'] ?? [];
$success_message = '';
$username = $_SESSION['register_username'] ?? '';
unset($_SESSION['errors_register'], $_SESSION['register_username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '') {
        $errors['username'] = 'Vui lòng nhập username.';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username phải có ít nhất 3 ký tự.';
    }

    if (trim($password) === '') {
        $errors['password'] = 'Vui lòng nhập mật khẩu.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if (trim($confirm_password) === '') {
        $errors['confirm_password'] = 'Vui lòng nhập lại mật khẩu.';
    } elseif ($confirm_password !== $password) {
        $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $errors['username'] = 'Username đã tồn tại!';
        } else {
            $accountLevel = 2;
            $hoTen = $username;

            $insert_stmt = $pdo->prepare("INSERT INTO user (ho_ten, username, password, account_level) VALUES (?, ?, ?, ?)");
            if ($insert_stmt->execute([$hoTen, $username, $password, $accountLevel])) {
                $success_message = "Đăng ký thành công! Đang chuyển hướng...";
                $username = '';
                header("refresh:2;url=login.php");
            } else {
                $errors['general'] = 'Có lỗi xảy ra, vui lòng thử lại!';
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors_register'] = $errors;
        $_SESSION['register_username'] = $username;
        header("Location: register.php");
        exit;
    }

    unset($_POST['username'], $_POST['password'], $_POST['confirm_password']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Diễn đàn Sinh viên</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <h2>Đăng Ký Tài Khoản</h2>
                <p>Tham gia cộng đồng sinh viên ngay hôm nay!</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Tên đăng nhập" value="<?php echo htmlspecialchars($username); ?>">
                    <?php if (!empty($errors['username'])): ?>
                        <p class="field-error"><?php echo $errors['username']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Ít nhất 6 ký tự" value="">
                    <?php if (!empty($errors['password'])): ?>
                        <p class="field-error"><?php echo $errors['password']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" value="">
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <p class="field-error"><?php echo $errors['confirm_password']; ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-login">Đăng Ký</button>
            </form>

            <div class="login-footer">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>
</body>

</html>