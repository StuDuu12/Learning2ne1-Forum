<?php
require_once '../config.php';
require_once '../includes/functions.php';

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
if ($username === '') {
    redirect('index.php');
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
$stmt->execute([$username]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    die('Người dùng không tồn tại');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }

    if ($_POST['action'] === 'toggle_like' && isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
        $user_id = $_SESSION['user_id'];

        $check = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND target_id = ? AND target_type = 'post'");
        $check->execute([$user_id, $post_id]);

        if ($check->fetch()) {

            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND target_id = ? AND target_type = 'post'");
            $stmt->execute([$user_id, $post_id]);
        } else {

            $stmt = $pdo->prepare("INSERT INTO likes (user_id, target_id, target_type) VALUES (?, ?, 'post')");
            $stmt->execute([$user_id, $post_id]);
        }

        header("Location: profile.php?username=" . urlencode($username), true, 303);
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           (SELECT COUNT(*) FROM likes WHERE target_id = p.id AND target_type = 'post') as like_count
    FROM posts p
    WHERE p.user_id = ? AND p.privacy = 'public'
    ORDER BY p.created_at DESC
");
$stmt->execute([$profile_user['id_user']]);
$user_posts = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$profile_user['id_user']]);
$comment_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM likes l
    JOIN posts p ON l.target_id = p.id
    WHERE l.target_type = 'post' AND p.user_id = ?
");
$stmt->execute([$profile_user['id_user']]);
$received_likes = $stmt->fetchColumn();

$account_types = [
    0 => 'Quản trị viên',
    1 => 'Giáo viên',
    2 => 'Sinh viên'
];

$display_name = (isset($profile_user['ho_ten']) && trim($profile_user['ho_ten']) !== '') ? trim($profile_user['ho_ten']) : ($profile_user['username'] ?? 'User');
$avatar_initial = strtoupper(mb_substr($display_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($display_name) ?> - Hồ sơ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= $avatar_initial ?>
            </div>
            <h1><?= h($display_name) ?></h1>
            <p>@<?= h($profile_user['username']) ?></p>
            <p style="margin-top: 0.5rem; opacity: 0.9;">
                <?= $account_types[$profile_user['account_level']] ?? 'Người dùng' ?>
            </p>
        </div>

        <div class="posts-section">
            <h2 style="color: var(--primary-mint); margin-bottom: 1.5rem;"><i class='bx  bx-file'></i> Bài viết công khai</h2>

            <?php if (empty($user_posts)) { ?>
                <div style="text-align: center; padding: 3rem; color: #636e72;">
                    <div style="font-size: 3rem;"><i class='bx  bx-inbox'></i></div>
                    <p>Người dùng chưa có bài viết công khai nào.</p>
                </div>
            <?php } else { ?>
                <div class="posts-grid">
                    <?php foreach ($user_posts as $post) {
                        $user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post['id'], 'post') : false;
                        $likes = getLikeCount($pdo, $post['id'], 'post');
                    ?>
                        <div class="post-card" id="post-<?= $post['id'] ?>">
                            <a href="post.php?id=<?= $post['id'] ?>&return_to=profile" style="text-decoration: none; color: inherit; display: block;">
                                <div class="post-header">
                                    <div class="post-author">
                                        <div class="author-avatar">
                                            <?= $avatar_initial ?>
                                        </div>
                                        <div class="author-info">
                                            <span class="author-name"><?= h($display_name) ?></span>
                                            <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                                        </div>
                                    </div>
                                    <div class="post-status-container">
                                        <?php if ($post['privacy'] === 'private') { ?>
                                            <span class="post-privacy privacy-private">
                                                <i class='bx bx-lock-alt'></i> Riêng tư
                                            </span>
                                        <?php } else { ?>
                                            <span class="post-privacy privacy-public">
                                                <i class='bx bx-globe'></i> Công khai
                                            </span>
                                        <?php } ?>
                                        <span class="post-status status-<?= $post['status'] ?>">
                                            <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                        </span>
                                    </div>
                                </div>

                                <h3 class="post-title"><?= h($post['title']) ?></h3>
                                <p class="post-excerpt"><?= h(mb_substr(strip_tags($post['content']), 0, 150)) ?>...</p>

                                <?php if (!empty($post['tags'])) { ?>
                                    <div class="post-tags">
                                        <?php foreach (explode(',', $post['tags']) as $tag) { ?>
                                            <span class="tag"><?= h(trim($tag)) ?></span>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </a>

                            <?php

                            $attachments = getAttachments($pdo, $post['id']);
                            $images = [];
                            $files = [];
                            foreach ($attachments as $att) {
                                if (strpos($att['file_type'], 'image') !== false) {
                                    $images[] = $att;
                                } else {
                                    $files[] = $att;
                                }
                            }
                            ?>

                            <?php if (!empty($images)) { ?>
                                <div class="post-preview-images" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1rem 0;">
                                    <?php foreach (array_slice($images, 0, 4) as $img) { ?>
                                        <div style="border-radius: 8px; overflow: hidden; background: #f5f6fa; max-width: 200px; max-height: 150px;">
                                            <img src="../<?= h($img['file_path']) ?>" alt="Ảnh" style="width: 100%; height: 100%; object-fit: contain; max-height: 200px;">
                                        </div>
                                    <?php } ?>
                                    <?php if (count($images) > 4) { ?>
                                        <div style="border-radius: 8px; background: linear-gradient(135deg, var(--primary-mint), #00a37a); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; width: 60px; height: 60px;">
                                            +<?= count($images) - 4 ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <?php if (!empty($files)) { ?>
                                <div class="post-preview-files" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.75rem 0;">
                                    <?php foreach ($files as $file) {
                                        $ext = strtoupper(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                        $icon = '📄';
                                        if (in_array($ext, ['PDF'])) $icon = '📕';
                                        elseif (in_array($ext, ['DOC', 'DOCX'])) $icon = '📘';
                                        elseif (in_array($ext, ['XLS', 'XLSX'])) $icon = '📗';
                                        elseif (in_array($ext, ['PPT', 'PPTX'])) $icon = '📙';
                                        elseif (in_array($ext, ['ZIP', 'RAR'])) $icon = '📦';
                                    ?>
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.75rem; background: #f5f6fa; border-radius: 6px; font-size: 0.8rem; color: #636e72;">
                                            <?= $icon ?> <?= $ext ?>
                                        </span>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div class="post-footer">

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_like">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="btn-action btn-like <?= $user_liked ? 'liked' : '' ?>" <?= !isLoggedIn() ? 'disabled title="Đăng nhập để like"' : '' ?>>
                                        <i class='bx bx-like'></i> <span class="like-count"><?= $likes ?></span>
                                    </button>
                                </form>
                                <a href="post.php?id=<?= $post['id'] ?>#comments" class="btn-action btn-comment" style="text-decoration: none;">
                                    <i class='bx bx-message'></i> <?= $post['comment_count'] ?? 0 ?>
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
    <footer style="text-align: center; padding: 2rem; margin-top: 3rem; background: var(--bg-grey); border-radius: 15px;">
        <p style="color: #636e72; margin: 0;">
            <strong>Learning2ne1 Forum</strong><br>
            Được tạo bởi <strong>Chu Quang Duy</strong>
        </p>
    </footer>

    <?php if (isLoggedIn()) { ?>
        <a href="create_post.php" class="btn-create" title="Tạo bài viết mới" aria-label="Tạo bài viết mới"><span class="btn-create-icon" aria-hidden="true"><i class='bx bx-edit'></i></span></a>
    <?php } ?>
</body>

</html>