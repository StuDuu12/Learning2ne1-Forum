<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$post_id) {
    redirect('../index.php');
}

$post = getPost($pdo, $post_id);
if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    redirect('../index.php');
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tags_input = trim($_POST['tags'] ?? '');

    $tags = '';
    if (!empty($tags_input)) {
        $tags_array = preg_split('/[,\s]+/', $tags_input);
        $tags_array = array_filter(array_map('trim', $tags_array));
        $tags = implode(', ', $tags_array);
    }

    if (empty($title)) {
        $message = 'Vui lòng nhập tiêu đề!';
        $message_type = 'error';
    } elseif (empty($content)) {
        $message = 'Vui lòng nhập nội dung!';
        $message_type = 'error';
    } else {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, tags = ? WHERE id = ?");
        if ($stmt->execute([$title, $content, $tags, $post_id])) {
            header("Location: post.php?id=$post_id", true, 303);
            exit;
        } else {
            $message = 'Có lỗi xảy ra!';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa bài viết - <?= h($post['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/create_post.css">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <div class="create-header">
            <h1><i class='bx bx-edit'></i> Chỉnh sửa bài viết</h1>
            <p>Cập nhật thông tin bài viết của bạn</p>
        </div>

        <?php if ($message) { ?>
            <div class="alert alert-<?= $message_type ?>" style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background: <?= $message_type === 'error' ? '#ff7675' : '#00b894' ?>; color: white;">
                <i class='bx bx-<?= $message_type === 'error' ? 'error-circle' : 'check-circle' ?>'></i>
                <?= h($message) ?>
            </div>
        <?php } ?>

        <form method="POST" class="create-form">
            <div class="form-section">
                <h3><i class='bx bx-text'></i> Nội dung bài viết</h3>

                <div class="form-group">
                    <label for="title">Tiêu đề bài viết *</label>
                    <input type="text" id="title" name="title" value="<?= h($post['title']) ?>" required placeholder="Nhập tiêu đề bài viết...">
                </div>

                <div class="form-group">
                    <label for="content">Nội dung *</label>
                    <textarea id="content" name="content" required placeholder="Mô tả chi tiết vấn đề của bạn..."><?= h($post['content']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="tags">Tags </label>
                    <input type="text" id="tags" name="tags" value="<?= h($post['tags']) ?>" placeholder="VD: PHP, MySQL, Laravel">
                </div>
            </div>

            <div class="form-actions">
                <a href="post.php?id=<?= $post_id ?>" class="btn-secondary" style="text-decoration: none;">
                    <i class='bx bx-arrow-back'></i> Quay lại
                </a>
                <button type="submit" name="edit_post" class="btn-primary">
                    <i class='bx bx-save'></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</body>

</html>