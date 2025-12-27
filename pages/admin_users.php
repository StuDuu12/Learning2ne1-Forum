<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['account_level'] != 0) {
    header('Location: ../index.php');
    exit;
}

$current_user = getCurrentUser($pdo);

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $ho_ten = trim($_POST['ho_ten']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $account_level = (int)$_POST['account_level'];

    // Validate
    if (empty($ho_ten) || empty($username) || empty($password)) {
        $error = "Vui lòng điền đầy đủ thông tin";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username đã tồn tại";
        } else {
            // Create user
            $hashed_password = md5($password);
            $stmt = $pdo->prepare("INSERT INTO user (ho_ten, username, password, account_level) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$ho_ten, $username, $hashed_password, $account_level])) {
                $success = "Tạo tài khoản thành công";
            } else {
                $error = "Có lỗi xảy ra khi tạo tài khoản";
            }
        }
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = (int)$_POST['new_role'];

    // Don't allow changing own role
    if ($user_id == $_SESSION['user_id']) {
        $error = "Không thể thay đổi vai trò của chính mình";
    } else {
        $stmt = $pdo->prepare("UPDATE user SET account_level = ? WHERE id_user = ?");
        if ($stmt->execute([$new_role, $user_id])) {
            $success = "Cập nhật vai trò thành công";
        } else {
            $error = "Có lỗi xảy ra khi cập nhật vai trò";
        }
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];

    // Don't allow deleting own account
    if ($user_id == $_SESSION['user_id']) {
        $error = "Không thể xóa tài khoản của chính mình";
    } else {
        // Delete user's posts, comments, likes, etc. (cascade delete)
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM likes WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM posts WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM user WHERE id_user = ?")->execute([$user_id]);
            $pdo->commit();
            $success = "Xóa tài khoản thành công";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Có lỗi xảy ra khi xóa tài khoản: " . $e->getMessage();
        }
    }
}

// Get all users
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as post_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM user u
    LEFT JOIN posts p ON u.id_user = p.user_id
    LEFT JOIN comments c ON u.id_user = c.user_id
    GROUP BY u.id_user
    ORDER BY u.account_level ASC, u.ho_ten ASC
");
$stmt->execute();
$users = $stmt->fetchAll();

$role_names = [
    0 => 'Admin',
    1 => 'Giảng viên',
    2 => 'Sinh viên'
];

$role_colors = [
    0 => '#e74c3c',
    1 => '#3498db',
    2 => '#2ecc71'
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin_users.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="admin-container">
        <div class="admin-header">
            <h1>🛡️ Quản lý người dùng</h1>
            <p>Tạo tài khoản, phân quyền và quản lý người dùng trong hệ thống</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= count(array_filter($users, fn($u) => $u['account_level'] == 0)) ?></h3>
                <p>👑 Admin</p>
            </div>
            <div class="stat-card">
                <h3><?= count(array_filter($users, fn($u) => $u['account_level'] == 1)) ?></h3>
                <p>👨‍🏫 Giảng viên</p>
            </div>
            <div class="stat-card">
                <h3><?= count(array_filter($users, fn($u) => $u['account_level'] == 2)) ?></h3>
                <p>🎓 Sinh viên</p>
            </div>
            <div class="stat-card">
                <h3><?= count($users) ?></h3>
                <p>📊 Tổng người dùng</p>
            </div>
        </div>

        <!-- Create User Form -->
        <div class="create-user-section">
            <h2>➕ Tạo tài khoản mới</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ho_ten">Họ và tên *</label>
                        <input type="text" id="ho_ten" name="ho_ten" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="account_level">Vai trò *</label>
                        <select id="account_level" name="account_level" required>
                            <option value="2">🎓 Sinh viên</option>
                            <option value="1">👨‍🏫 Giảng viên</option>
                            <option value="0">👑 Admin</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_user" class="btn btn-primary">✓ Tạo tài khoản</button>
            </form>
        </div>

        <!-- Users List -->
        <div class="users-section">
            <h2>👥 Danh sách người dùng (<?= count($users) ?>)</h2>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ và tên</th>
                        <th>Username</th>
                        <th>Vai trò</th>
                        <th>Bài viết</th>
                        <th>Bình luận</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id_user'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['ho_ten']) ?></strong>
                                <?php if ($user['id_user'] == $_SESSION['user_id']): ?>
                                    <span style="color: #3498db; font-size: 0.875rem;">(Bạn)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <?php if ($user['id_user'] == $_SESSION['user_id']): ?>
                                    <span class="role-badge" style="background: <?= $role_colors[$user['account_level']] ?>">
                                        <?= $role_names[$user['account_level']] ?>
                                    </span>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id_user'] ?>">
                                        <select name="new_role" class="role-select" onchange="this.form.submit()">
                                            <?php foreach ($role_names as $level => $name): ?>
                                                <option value="<?= $level ?>" <?= $user['account_level'] == $level ? 'selected' : '' ?>>
                                                    <?= $name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><?= $user['post_count'] ?></td>
                            <td><?= $user['comment_count'] ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="profile.php?username=<?= urlencode($user['username']) ?>" class="btn btn-sm btn-primary">Xem</a>
                                    <?php if ($user['id_user'] != $_SESSION['user_id']): ?>
                                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?')">
                                            <input type="hidden" name="user_id" value="<?= $user['id_user'] ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger">Xóa</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>