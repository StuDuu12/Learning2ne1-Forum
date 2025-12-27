<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/helpers.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$current_user = getCurrentUser($pdo);

// Get community tag stats
$community_stats = getTagStats($pdo);

// Get user interest stats
$user_stats = getUserInterests($pdo, $_SESSION['user_id']);

// Get total stats (Admin only)
$total_users = 0;
$total_posts = 0;
$total_comments = 0;
$total_likes = 0;

if ($_SESSION['account_level'] == 0) { // Admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM user");
    $total_users = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    $total_posts = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM comments");
    $total_comments = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM likes");
    $total_likes = $stmt->fetchColumn();
}

// Get user's posts
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           (SELECT COUNT(*) FROM likes WHERE target_id = p.id AND target_type = 'post') as like_count
    FROM posts p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$user_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Diễn đàn sinh viên</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <div class="dashboard-header">
            <h1>📊 Dashboard</h1>
            <p>Xin chào, <?= h($current_user['ho_ten'] ?? 'User') ?>! Đây là tổng quan hoạt động của bạn.</p>
        </div>

        <!-- Admin Stats (Only for Admin) -->
        <?php if ($_SESSION['account_level'] == 0): ?>
            <h2 style="color: var(--primary-mint); margin-bottom: 1rem;">👑 Thống kê hệ thống (Admin)</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?= $total_users ?></div>
                    <div class="stat-label">Tổng người dùng</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value"><?= $total_posts ?></div>
                    <div class="stat-label">Tổng bài viết</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💬</div>
                    <div class="stat-value"><?= $total_comments ?></div>
                    <div class="stat-label">Tổng bình luận</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❤️</div>
                    <div class="stat-value"><?= $total_likes ?></div>
                    <div class="stat-label">Tổng lượt thích</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- User Personal Stats -->
        <h2 style="color: var(--primary-mint); margin-bottom: 1rem;">📈 Thống kê cá nhân</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-value"><?= count($user_posts) ?></div>
                <div class="stat-label">Bài viết của tôi</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $my_comments = $stmt->fetchColumn();
                ?>
                <div class="stat-value"><?= $my_comments ?></div>
                <div class="stat-label">Bình luận của tôi</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">❤️</div>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $my_likes = $stmt->fetchColumn();
                ?>
                <div class="stat-value"><?= $my_likes ?></div>
                <div class="stat-label">Đã thích</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <?php
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM likes l
                    JOIN posts p ON l.target_id = p.id
                    WHERE l.target_type = 'post' AND p.user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $received_likes = $stmt->fetchColumn();
                ?>
                <div class="stat-value"><?= $received_likes ?></div>
                <div class="stat-label">Nhận được lượt thích</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <!-- Community Trends Chart -->
            <div class="chart-container">
                <h3 class="chart-title">🌐 Xu hướng cộng đồng</h3>
                <p style="text-align: center; color: #636e72; margin-bottom: 1rem; font-size: 0.9rem;">
                    Các chủ đề được quan tâm nhất trong cộng đồng
                </p>
                <canvas id="communityChart"></canvas>
            </div>

            <!-- Personal Interests Chart -->
            <div class="chart-container">
                <h3 class="chart-title">⭐ Xu hướng cá nhân</h3>
                <p style="text-align: center; color: #636e72; margin-bottom: 1rem; font-size: 0.9rem;">
                    Sở thích và hoạt động của bạn theo tag
                </p>
                <canvas id="personalChart"></canvas>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="posts-list">
            <h3 style="color: var(--primary-mint); margin-bottom: 1rem;">📝 Bài viết gần đây của tôi</h3>
            <?php if (empty($user_posts)): ?>
                <div style="text-align: center; padding: 2rem; color: #636e72;">
                    <div style="font-size: 3rem;">📭</div>
                    <p>Bạn chưa có bài viết nào. <a href="create_post.php" style="color: var(--primary-mint); font-weight: bold;">Tạo bài viết đầu tiên!</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($user_posts as $post): ?>
                    <a href="post.php?id=<?= $post['id'] ?>" class="post-item">
                        <div>
                            <div class="post-item-title"><?= h($post['title']) ?></div>
                            <div class="post-item-meta">
                                <?= timeAgo($post['created_at']) ?> •
                                <span style="color: <?= $post['status'] === 'solved' ? '#00b894' : '#fdcb6e' ?>">
                                    <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                </span>
                            </div>
                        </div>
                        <div class="post-item-stats">
                            <span>❤️ <?= $post['like_count'] ?></span>
                            <span>💬 <?= $post['comment_count'] ?></span>
                            <span>👁️ <?= $post['views'] ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer style="text-align: center; padding: 2rem; margin-top: 3rem; background: var(--bg-grey); border-radius: 15px;">
        <p style="color: #636e72; margin: 0;">
            📊 Dashboard được xem bởi <strong><?= h($current_user['ho_ten'] ?? 'User') ?></strong> •
            <?= date('d/m/Y H:i') ?>
        </p>
    </footer>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Prepare data for Chart.js (from PHP)
        const communityData = <?= json_encode($community_stats) ?>;
        const userData = <?= json_encode($user_stats) ?>;
    </script>
</body>

</html>