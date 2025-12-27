<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/helpers.php';

$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if (empty($username)) {
    redirect('index.php');
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
$stmt->execute([$username]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    die('Người dùng không tồn tại');
}

// Get user's posts
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

// Get user stats
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
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($profile_user['ho_ten']) ?> - Hồ sơ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(mb_substr($profile_user['ho_ten'], 0, 1)) ?>
            </div>
            <h1><?= h($profile_user['ho_ten']) ?></h1>
            <p>@<?= h($profile_user['username']) ?></p>
            <p style="margin-top: 0.5rem; opacity: 0.9;">
                <?= $account_types[$profile_user['account_level']] ?>
            </p>

            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value"><?= count($user_posts) ?></div>
                    <div class="stat-label">Bài viết</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $comment_count ?></div>
                    <div class="stat-label">Bình luận</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $received_likes ?></div>
                    <div class="stat-label">Lượt thích</div>
                </div>
            </div>
        </div>

        <div class="posts-section">
            <h2 style="color: var(--primary-mint); margin-bottom: 1.5rem;">📝 Bài viết công khai</h2>

            <?php if (empty($user_posts)): ?>
                <div style="text-align: center; padding: 3rem; color: #636e72;">
                    <div style="font-size: 3rem;">📭</div>
                    <p>Người dùng chưa có bài viết công khai nào.</p>
                </div>
            <?php else: ?>
                <?php foreach ($user_posts as $post):
                    $user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post['id'], 'post') : false;
                ?>
                    <div class="post-card">
                        <div onclick="openPostModal(<?= $post['id'] ?>)" style="cursor: pointer;">
                            <div class="post-header" style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; background: var(--primary-mint); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        <?= strtoupper(mb_substr($profile_user['ho_ten'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-dark);"><?= h($profile_user['ho_ten']) ?></div>
                                        <span style="font-size: 0.85rem; color: #636e72;"><?= timeAgo($post['created_at']) ?></span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <span class="post-status status-<?= $post['status'] ?>" style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; background: <?= $post['status'] === 'solved' ? '#00b894' : '#fdcb6e' ?>; color: <?= $post['status'] === 'solved' ? 'white' : 'var(--text-dark)' ?>;">
                                        <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                    </span>
                                    <?php if (isLoggedIn()): ?>
                                        <div class="post-menu" onclick="event.stopPropagation();">
                                            <button class="btn-menu" onclick="toggleMenu(<?= $post['id'] ?>)">⋮</button>
                                            <div class="dropdown-menu" id="menu-<?= $post['id'] ?>">
                                                <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                                                    <button class="dropdown-item" onclick="editPost(<?= $post['id'] ?>)">
                                                        ✏️ Chỉnh sửa
                                                    </button>
                                                    <button class="dropdown-item" onclick="setPrivacy(<?= $post['id'] ?>, 'public')">
                                                        🌍 Đặt công khai
                                                    </button>
                                                    <button class="dropdown-item" onclick="setPrivacy(<?= $post['id'] ?>, 'private')">
                                                        🔒 Đặt riêng tư
                                                    </button>
                                                <?php endif; ?>
                                                <button class="dropdown-item" onclick="hidePost(<?= $post['id'] ?>)">
                                                    🚫 Ẩn bài viết
                                                </button>
                                                <button class="dropdown-item danger" onclick="reportPost(<?= $post['id'] ?>)">
                                                    🚩 Báo cáo
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <h3 class="post-title"><?= h($post['title']) ?></h3>
                            <p style="color: #636e72; margin: 0.75rem 0; line-height: 1.6;">
                                <?= h(mb_substr(strip_tags($post['content']), 0, 150)) ?>...
                            </p>

                            <?php if (!empty($post['tags'])): ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                                    <?php
                                    $tags = explode(',', $post['tags']);
                                    foreach ($tags as $tag):
                                    ?>
                                        <span class="tag" style="background: var(--bg-grey); padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.85rem; color: var(--primary-mint); font-weight: 600;"><?= h(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="post-footer" style="display: flex; gap: 1rem; padding-top: 1rem; border-top: 2px solid var(--bg-grey);">
                            <button class="btn-action btn-like <?= $user_liked ? 'liked' : '' ?>" onclick="toggleLike(<?= $post['id'] ?>, this)" <?= !isLoggedIn() ? 'disabled title="Đăng nhập để like"' : '' ?>>
                                ❤️ <span class="like-count"><?= $post['like_count'] ?></span>
                            </button>
                            <button class="btn-action btn-comment" onclick="openPostModal(<?= $post['id'] ?>, true)">
                                💬 <?= $post['comment_count'] ?>
                            </button>
                            <span style="display: flex; align-items: center; gap: 0.25rem; color: #636e72; font-size: 0.9rem; margin-left: auto;">
                                👁️ <?= $post['views'] ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Post Detail Modal -->
    <div id="postModal" class="post-modal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 style="margin: 0; color: var(--primary-mint);">📝 Chi tiết bài viết</h2>
                <button class="modal-close" onclick="closePostModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="loading-spinner">⏳ Đang tải...</div>
            </div>
        </div>
    </div>

    <script src="../assets/js/index.js"></script>
</body>

</html>