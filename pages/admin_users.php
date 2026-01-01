<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['account_level']) && isset($_SESSION['role'])) {
    $_SESSION['account_level'] = $_SESSION['role'];
}

if (!isset($_SESSION['account_level']) || $_SESSION['account_level'] != 0) {
    header("Location: ../index.php");
    exit;
}

$errors = [];
$success = '';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = intval($_GET['id']);
    if ($user_id_to_delete === ($_SESSION['user_id'] ?? 0)) {
        header("Location: admin_users.php");
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM user WHERE id_user = ?");
    $stmt->execute([$user_id_to_delete]);
    $success = 'Đã xóa thành viên thành công!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $user_id = intval($_POST['user_id']);
    $new_role = intval($_POST['new_role']);

    if ($user_id === ($_SESSION['user_id'] ?? 0)) {
        $errors['general'] = 'Không thể thay đổi vai trò của chính bạn!';
    } elseif (!in_array($new_role, [0, 1, 2], true)) {
        $errors['general'] = 'Vai trò không hợp lệ!';
    } else {
        $stmt = $pdo->prepare("UPDATE user SET account_level = ? WHERE id_user = ?");
        if ($stmt->execute([$new_role, $user_id])) {
            $success = 'Đã cập nhật vai trò thành công!';
        } else {
            $errors['general'] = 'Có lỗi khi cập nhật vai trò.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $new_username = trim($_POST['username'] ?? '');
    $new_fullname = trim($_POST['ho_ten'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $new_account_level = isset($_POST['account_level']) ? intval($_POST['account_level']) : 2;

    if ($new_username === '' || strlen($new_username) < 3) {
        $errors['username'] = 'Username phải có ít nhất 3 ký tự.';
    }
    if ($new_fullname === '') {
        $errors['ho_ten'] = 'Vui lòng nhập họ tên.';
    }
    if (trim($new_password) === '' || strlen($new_password) < 6) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if (!in_array($new_account_level, [0, 1, 2], true)) {
        $errors['account_level'] = 'Vai trò không hợp lệ.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
        $check->execute([$new_username]);
        if ($check->rowCount() > 0) {
            $errors['username'] = 'Username đã tồn tại.';
        } else {
            $insert = $pdo->prepare("INSERT INTO user (ho_ten, username, password, account_level) VALUES (?, ?, ?, ?)");
            if ($insert->execute([$new_fullname, $new_username, $new_password, $new_account_level])) {
                $success = 'Tạo tài khoản thành công.';
            } else {
                $errors['general'] = 'Có lỗi khi tạo tài khoản.';
            }
        }
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';

$sql = "SELECT id_user, ho_ten, username, account_level FROM user WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR ho_ten LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_role !== '') {
    $sql .= " AND account_level = ?";
    $params[] = intval($filter_role);
}

$sql .= " ORDER BY id_user ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
$adminsCount = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE account_level = 0")->fetchColumn();
$lecturersCount = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE account_level = 1")->fetchColumn();
$studentsCount = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE account_level = 2")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Thành viên</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin_users.css">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <h1 style="color: var(--primary-mint); margin-bottom: 1.5rem;">
            <i class='bx bx-user-circle'></i> Quản lý Thành viên
        </h1>

        <div class="admin-grid">
            <div class="left-col">
                <div class="create-user-section">
                    <h2><i class='bx bx-user-plus'></i> Tạo thành viên mới</h2>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-error" style="background: #ff7675; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                            <?php echo $errors['general']; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success" style="background: #00b894; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                            <i class='bx bx-check-circle'></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="form-grid">
                        <input type="hidden" name="action" value="create_user">
                        <div class="form-group">
                            <label for="username"><i class='bx bx-at'></i> Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Nhập username">
                            <?php if (!empty($errors['username'])): ?><p class="field-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 0.25rem;"><?php echo $errors['username']; ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="ho_ten"><i class='bx bx-user'></i> Họ tên</label>
                            <input type="text" name="ho_ten" id="ho_ten" class="form-control" value="<?php echo htmlspecialchars($_POST['ho_ten'] ?? ''); ?>" placeholder="Nhập họ tên đầy đủ">
                            <?php if (!empty($errors['ho_ten'])): ?><p class="field-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 0.25rem;"><?php echo $errors['ho_ten']; ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password"><i class='bx bx-lock'></i> Mật khẩu</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Ít nhất 6 ký tự">
                            <?php if (!empty($errors['password'])): ?><p class="field-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 0.25rem;"><?php echo $errors['password']; ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="account_level"><i class='bx bx-badge'></i> Vai trò</label>
                            <select name="account_level" id="account_level" class="form-control">
                                <option value="2" <?php echo (isset($_POST['account_level']) && $_POST['account_level'] == '2') ? 'selected' : ''; ?>>Sinh viên</option>
                                <option value="1" <?php echo (isset($_POST['account_level']) && $_POST['account_level'] == '1') ? 'selected' : ''; ?>>Giảng viên</option>
                                <option value="0" <?php echo (isset($_POST['account_level']) && $_POST['account_level'] == '0') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <?php if (!empty($errors['account_level'])): ?><p class="field-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 0.25rem;"><?php echo $errors['account_level']; ?></p><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class='bx bx-plus'></i> Tạo tài khoản
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="right-col">
                <div class="stats-grid mb-2">
                    <div class="stat-card">
                        <h3><?php echo $totalUsers; ?></h3>
                        <p>Tổng người dùng</p>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #00b894;">
                        <h3><?php echo $adminsCount; ?></h3>
                        <p>Admin</p>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #fdcb6e;">
                        <h3><?php echo $lecturersCount; ?></h3>
                        <p>Giảng viên</p>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #7f8c8d;">
                        <h3><?php echo $studentsCount; ?></h3>
                        <p>Sinh viên</p>
                    </div>
                </div>


                <form method="GET" class="search-filter">
                    <input type="text" name="search" placeholder="🔍 Tìm kiếm theo tên hoặc username..." value="<?= htmlspecialchars($search) ?>">
                    <select name="role">
                        <option value="">Tất cả vai trò</option>
                        <option value="0" <?= $filter_role === '0' ? 'selected' : '' ?>>Admin</option>
                        <option value="1" <?= $filter_role === '1' ? 'selected' : '' ?>>Giảng viên</option>
                        <option value="2" <?= $filter_role === '2' ? 'selected' : '' ?>>Sinh viên</option>
                    </select>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--primary-mint); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        <i class='bx bx-search'></i> Lọc
                    </button>
                    <?php if (!empty($search) || $filter_role !== ''): ?>
                        <a href="admin_users.php" style="padding: 0.75rem 1rem; color: #636e72; text-decoration: none;">✕ Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>

                <div class="users-section">
                    <div class="table-responsive">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Họ tên</th>
                                    <th>Vai trò</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr class="<?= $u['id_user'] == ($_SESSION['user_id'] ?? 0) ? 'current-user-row' : '' ?>">
                                        <td><?php echo $u['id_user']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                            <?php if ($u['id_user'] == ($_SESSION['user_id'] ?? 0)): ?>
                                                <small style="color: #f39c12;">(Bạn)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['ho_ten'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            if ($u['account_level'] == 0) {
                                                echo '<span class="role-badge" style="background:#00b894; color:white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem;">Admin</span>';
                                            } elseif ($u['account_level'] == 1) {
                                                echo '<span class="role-badge" style="background:#fdcb6e; color:#2c2c2c; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem;">Giảng viên</span>';
                                            } else {
                                                echo '<span class="role-badge" style="background:#7f8c8d; color:white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem;">Sinh viên</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="user-actions-cell">
                                                <?php if ($u['id_user'] != ($_SESSION['user_id'] ?? 0)): ?>

                                                    <form method="POST" style="display: inline-flex; gap: 0.25rem; align-items: center;">
                                                        <input type="hidden" name="action" value="change_role">
                                                        <input type="hidden" name="user_id" value="<?= $u['id_user'] ?>">
                                                        <select name="new_role" class="role-select">
                                                            <option value="2" <?= $u['account_level'] == 2 ? 'selected' : '' ?>>Sinh viên</option>
                                                            <option value="1" <?= $u['account_level'] == 1 ? 'selected' : '' ?>>Giảng viên</option>
                                                            <option value="0" <?= $u['account_level'] == 0 ? 'selected' : '' ?>>Admin</option>
                                                        </select>
                                                        <button type="submit" class="btn-save-role" title="Lưu vai trò">
                                                            <i class='bx bx-check'></i>
                                                        </button>
                                                    </form>

                                                    <a href="?action=delete&id=<?php echo $u['id_user']; ?>" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Bạn có chắc chắn muốn xóa thành viên này?');"
                                                        style="padding: 0.4rem 0.75rem; background: #e74c3c; color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">
                                                        <i class='bx bx-trash'></i> Xóa
                                                    </a>
                                                <?php else: ?>
                                                    <em style="color: #95a5a6;">Không thể sửa/xóa</em>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (empty($users)): ?>
                        <div style="text-align: center; padding: 2rem; color: #636e72;">
                            <i class='bx bx-user-x' style="font-size: 3rem;"></i>
                            <p>Không tìm thấy thành viên nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>