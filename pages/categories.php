<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/helpers.php';

// Get current user
$current_user = isLoggedIn() ? getCurrentUser($pdo) : null;

// Get category_id from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// If category_id is provided, show posts in that category
if ($category_id) {
    // Get category info
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();

    if (!$category) {
        redirect('../index.php');
    }

    // Get posts in this category
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("
            SELECT p.*, u.ho_ten, u.username,
                (SELECT COUNT(*) FROM likes WHERE target_id = p.id AND target_type = 'post') as like_count,
                (SELECT COUNT(DISTINCT c.id) FROM comments c WHERE c.post_id = p.id) as comment_count
            FROM posts p
            JOIN user u ON p.user_id = u.id_user
            WHERE p.category_id = ? AND (p.privacy = 'public' OR p.user_id = ?)
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$category_id, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.*, u.ho_ten, u.username,
                (SELECT COUNT(*) FROM likes WHERE target_id = p.id AND target_type = 'post') as like_count,
                (SELECT COUNT(DISTINCT c.id) FROM comments c WHERE c.post_id = p.id) as comment_count
            FROM posts p
            JOIN user u ON p.user_id = u.id_user
            WHERE p.category_id = ? AND p.privacy = 'public'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$category_id]);
    }
    $posts = $stmt->fetchAll();
} else {
    // Show all categories
    $stmt = $pdo->query("
        SELECT c.*,
            (SELECT COUNT(*) FROM posts WHERE category_id = c.id AND privacy = 'public') as post_count
        FROM categories c
        ORDER BY c.id ASC
    ");
    $categories = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $category_id ? h($category['name']) : 'Danh mục' ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/categories.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <?php if ($category_id): ?>
            <!-- Show posts in category -->
            <a href="categories.php" class="back-link">← Quay lại danh mục</a>

            <div class="category-header">
                <div class="category-header-top">
                    <div>
                        <h1><?= h($category['icon']) ?> <?= h($category['name']) ?></h1>
                        <p><?= h($category['description']) ?></p>
                    </div>
                    <?php if (isLoggedIn()): ?>
                        <a href="create_post.php?category_id=<?= $category_id ?>" class="create-post-btn">
                            ➕ Tạo bài viết mới
                        </a>
                    <?php endif; ?>
                </div>
                <div class="category-stats">
                    <div class="stat-item">
                        <span>📝</span>
                        <span><?= count($posts) ?> bài viết</span>
                    </div>
                </div>
            </div>

            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h2>Chưa có bài viết nào</h2>
                    <p>Hãy là người đầu tiên tạo bài viết trong danh mục này!</p>
                </div>
            <?php else: ?>
                <div class="posts-feed">
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="user-info">
                                    <div class="avatar"><?= strtoupper(mb_substr($post['ho_ten'], 0, 1)) ?></div>
                                    <div>
                                        <div class="user-name"><?= h($post['ho_ten']) ?></div>
                                        <div class="post-time"><?= timeAgo($post['created_at']) ?></div>
                                    </div>
                                </div>
                                <?php if ($post['status'] === 'solved'): ?>
                                    <span class="solved-badge">✅ Đã giải quyết</span>
                                <?php endif; ?>
                            </div>

                            <div class="post-content">
                                <h3 class="post-title">
                                    <a href="post.php?id=<?= $post['id'] ?>"><?= h($post['title']) ?></a>
                                </h3>
                                <div class="post-body">
                                    <?= nl2br(h(mb_substr($post['content'], 0, 300))) ?>
                                    <?php if (mb_strlen($post['content']) > 300): ?>
                                        <a href="post.php?id=<?= $post['id'] ?>" class="read-more">... Xem thêm</a>
                                    <?php endif; ?>
                                </div>

                                <?php if ($post['tags']): ?>
                                    <div class="post-tags">
                                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                            <span class="tag"><?= h($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="post-footer">
                                <div class="post-stats">
                                    <span>👍 <?= $post['like_count'] ?></span>
                                    <span>💬 <?= $post['comment_count'] ?></span>
                                    <span>👁️ <?= $post['views'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Show all categories -->
            <h1 style="margin-bottom: 1rem;">📂 Danh mục diễn đàn</h1>
            <p style="color: var(--text-light); margin-bottom: 2rem;">Chọn danh mục để xem các bài viết hoặc tạo bài viết mới</p>

            <div class="categories-grid">
                <?php foreach ($categories as $cat): ?>
                    <a href="categories.php?id=<?= $cat['id'] ?>" style="text-decoration: none; color: inherit;">
                        <div class="category-card">
                            <div class="category-icon"><?= h($cat['icon']) ?></div>
                            <div class="category-name"><?= h($cat['name']) ?></div>
                            <div class="category-description"><?= h($cat['description']) ?></div>
                            <div class="category-stats">
                                <div class="stat-item">
                                    <span>📝</span>
                                    <span><?= $cat['post_count'] ?> bài viết</span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/index.js"></script>
</body>

</html>