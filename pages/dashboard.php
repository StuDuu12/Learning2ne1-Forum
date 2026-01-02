<?php
require_once '../config.php';
require_once '../includes/functions.php';

$current_user = isLoggedIn() ? getCurrentUser($pdo) : null;
$accountLevel = $current_user['account_level'] ?? ($_SESSION['role'] ?? null);
if (!isset($_SESSION['account_level']) && $accountLevel !== null) {
    $_SESSION['account_level'] = $accountLevel;
}

$community_stats = getTagStats($pdo);

$user_stats = isLoggedIn() ? getUserInterests($pdo, $_SESSION['user_id']) : [];

$total_users = 0;
$total_posts = 0;
$total_comments = 0;
$total_likes = 0;

$isAdmin = isset($_SESSION['account_level']) ? $_SESSION['account_level'] == 0 : ($accountLevel === 0);
if ($isAdmin) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM user");
    $total_users = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    $total_posts = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM comments");
    $total_comments = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM likes");
    $total_likes = $stmt->fetchColumn();
}

$user_posts = [];
if (isLoggedIn()) {
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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Diễn đàn sinh viên</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class='bx bx-bar-chart-square'></i> Thống kê xu hướng</h1>
        </div>

        <?php if ($isAdmin) { ?>
            <h2 style="color: var(--primary-mint); margin-bottom: 1rem;"><i class='bx  bx-bar-chart-square'></i> Thống kê hệ thống (Admin)</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-file'></i></div>
                    <div class="stat-value"><?= $total_posts ?></div>
                    <div class="stat-label">Tổng bài viết</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-message'></i></div>
                    <div class="stat-value"><?= $total_comments ?></div>
                    <div class="stat-label">Tổng bình luận</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bx-like'></i></div>
                    <div class="stat-value"><?= $total_likes ?></div>
                    <div class="stat-label">Tổng lượt thích</div>
                </div>
            </div>
        <?php } ?>

        <h2 style="color: var(--primary-mint); margin-bottom: 1rem;"><i class='bx bx-line-chart'></i> Thống kê cá nhân</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class='bx bx-file'></i></div>
                <div class="stat-value"><?= count($user_posts) ?></div>
                <div class="stat-label">Bài viết của tôi</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class='bx bx-message'></i></div>
                <?php
                if (isLoggedIn()) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $my_comments = $stmt->fetchColumn();
                } else {
                    $my_comments = 0;
                }
                ?>
                <div class="stat-value"><?= $my_comments ?></div>
                <div class="stat-label">Bình luận của tôi</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class='bx bx-like'></i></div>
                <?php
                if (isLoggedIn()) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $my_likes = $stmt->fetchColumn();
                } else {
                    $my_likes = 0;
                }
                ?>
                <div class="stat-value"><?= $my_likes ?></div>
                <div class="stat-label">Đã thích</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class='bx  bx-medal-star-alt-2'></i></div>
                <?php
                if (isLoggedIn()) {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM likes l
                        JOIN posts p ON l.target_id = p.id
                        WHERE l.target_type = 'post' AND p.user_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $received_likes = $stmt->fetchColumn();
                } else {
                    $received_likes = 0;
                }
                ?>
                <div class="stat-value"><?= $received_likes ?></div>
                <div class="stat-label">Nhận được lượt thích</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <div class="chart-container">
                <h3 class="chart-title"><i class='bx bx-globe'></i> Xu hướng cộng đồng</h3>
                <p style="text-align: center; color: #636e72; margin-bottom: 1rem; font-size: 0.9rem;">
                    Các chủ đề được quan tâm nhất trong cộng đồng
                </p>
                <canvas id="communityChart"></canvas>
            </div>

            <div class="chart-container">
                <h3 class="chart-title"><i class='bx bx-star'></i> Xu hướng cá nhân</h3>
                <p style="text-align: center; color: #636e72; margin-bottom: 1rem; font-size: 0.9rem;">
                    Sở thích và hoạt động của bạn theo tag
                </p>
                <canvas id="personalChart"></canvas>
            </div>
        </div>
        <footer style="text-align: center; padding: 2rem; margin-top: 3rem; background: var(--bg-grey); border-radius: 15px;">
            <p style="color: #636e72; margin: 0;">
                <strong>Learning2ne1 Forum</strong><br>
                Được tạo bởi <strong>Chu Quang Duy</strong>
            </p>
        </footer>

        <script>
            const communityData = <?= json_encode($community_stats) ?>;
            const userData = <?= json_encode($user_stats) ?>;
        </script>
        <script src="../assets/js/dashboard.js"></script>
</body>

</html>