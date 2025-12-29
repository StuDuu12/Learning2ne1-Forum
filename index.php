<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/helpers.php';

// Get current user if logged in
$current_user = isLoggedIn() ? getCurrentUser($pdo) : null;

// Get trending posts
$trending_posts = getTrending($pdo, 5);

// Get recommended/latest posts
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$recommended_posts = getSuggested($pdo, $user_id, 10);

// Get all posts for "Tất cả" tab
// Include: all public posts + user's own private posts, with trending posts prioritized
if (isLoggedIn()) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.ho_ten, u.username,
            (SELECT COUNT(*) FROM likes WHERE target_id = p.id AND target_type = 'post') as like_count,
            (SELECT COUNT(DISTINCT c.id) FROM comments c WHERE c.post_id = p.id) as comment_count,
            CASE 
                WHEN p.id IN (
                    SELECT id FROM (
                        SELECT p2.id,
                            (COUNT(DISTINCT l.id) * 2 + COUNT(DISTINCT c2.id) * 3) as engagement
                        FROM posts p2
                        LEFT JOIN likes l ON l.target_id = p2.id AND l.target_type = 'post'
                        LEFT JOIN comments c2 ON c2.post_id = p2.id
                        WHERE p2.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                              AND p2.privacy = 'public'
                        GROUP BY p2.id
                        HAVING (COUNT(DISTINCT l.id) >= 5 AND COUNT(DISTINCT c2.id) >= 5)
                        ORDER BY engagement DESC
                        LIMIT 10
                    ) as trending_posts
                ) THEN 1
                ELSE 0 
            END as trending_score
        FROM posts p
        JOIN user u ON p.user_id = u.id_user
        WHERE p.privacy = 'public' OR p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, u.ho_ten, u.username,
            (SELECT COUNT(*) FROM likes WHERE target_id = p.id AND target_type = 'post') as like_count,
            (SELECT COUNT(DISTINCT c.id) FROM comments c WHERE c.post_id = p.id) as comment_count,
            CASE 
                WHEN p.id IN (
                    SELECT id FROM (
                        SELECT p2.id,
                            (COUNT(DISTINCT l.id) * 2 + COUNT(DISTINCT c2.id) * 3) as engagement
                        FROM posts p2
                        LEFT JOIN likes l ON l.target_id = p2.id AND l.target_type = 'post'
                        LEFT JOIN comments c2 ON c2.post_id = p2.id
                        WHERE p2.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                              AND p2.privacy = 'public'
                        GROUP BY p2.id
                        HAVING (COUNT(DISTINCT l.id) >= 5 AND COUNT(DISTINCT c2.id) >= 5)
                        ORDER BY engagement DESC
                        LIMIT 10
                    ) as trending_posts
                ) THEN 1
                ELSE 0 
            END as trending_score
        FROM posts p
        JOIN user u ON p.user_id = u.id_user
        WHERE p.privacy = 'public'
        ORDER BY p.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
}
$all_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning2ne1 - Trang chủ diễn đàn</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <?php if (!isLoggedIn()): ?>
            <div class="guest-notice">
                <strong>👋 Chào mừng!</strong> Bạn đang ở chế độ khách.
                <a href="pages/login.php" style="color: var(--primary-mint); font-weight: bold;">Đăng nhập</a>
                để tương tác và tạo bài viết.
            </div>

            <!-- Guest Dashboard - Community Trends -->
            <div style="background: white; padding: 1.5rem; border-radius: 15px; box-shadow: var(--shadow); margin-bottom: 2rem;">
                <h2 style="color: var(--primary-mint); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    📊 Xu hướng cộng đồng
                </h2>

                <?php
                // Prepare chart data: top tags from public posts (top 10)
                $stmt = $pdo->prepare("SELECT tags FROM posts WHERE privacy = 'public' AND tags IS NOT NULL AND tags <> ''");
                $stmt->execute();
                $tag_counts = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tags_raw = $row['tags'];
                    $parts = array_filter(array_map('trim', explode(',', $tags_raw)));
                    foreach ($parts as $t) {
                        if ($t === '') continue;
                        // Normalize: ensure starts with # and use lowercase for counting
                        $raw = ltrim($t);
                        if ($raw === '') continue;
                        // remove leading # if any then lowercase
                        $normalized = mb_strtolower(ltrim($raw, '#'), 'UTF-8');
                        $key = '#' . $normalized;
                        if (!isset($tag_counts[$key])) $tag_counts[$key] = 0;
                        $tag_counts[$key]++;
                    }
                }
                arsort($tag_counts);
                $top = array_slice($tag_counts, 0, 10, true);
                $chart_labels = array_map('strtoupper', array_keys($top));
                $chart_data = array_values($top);
                $rows = [];
                foreach ($top as $tag => $cnt) {
                    $rows[] = ['name' => $tag, 'cnt' => $cnt];
                }
                ?>

                <div style="display: grid; grid-template-columns: 1fr 320px; gap: 1rem; align-items: center;">
                    <?php
                    // Thống kê tổng quan
                    $stats = [];

                    // Tổng số bài viết công khai
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts WHERE privacy = 'public'");
                    $stats['posts'] = $stmt->fetch()['count'];

                    // Tổng số người dùng
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
                    $stats['users'] = $stmt->fetch()['count'];

                    // Bài viết hot nhất (7 ngày qua)
                    $stmt = $pdo->query("
                        SELECT COUNT(*) as count 
                        FROM posts 
                        WHERE privacy = 'public' 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ");
                    $stats['hot_posts'] = $stmt->fetch()['count'];

                    // Tổng số bình luận
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM comments");
                    $stats['comments'] = $stmt->fetch()['count'];
                    ?>

                    <div>
                        <canvas id="communityChart" style="max-width:100%; height:260px;"></canvas>
                    </div>
                    <div style="padding: 1rem; border-radius: 8px; background: var(--bg-grey);">
                        <h4 style="margin-top: 0; color: var(--primary-mint);">Top thẻ</h4>
                        <ul style="margin: 0; padding: 0; list-style: none;">
                            <?php foreach ($rows as $r): ?>
                                <li style="padding: 0.35rem 0; border-bottom: 1px solid rgba(0,0,0,0.04); display:flex; justify-content:space-between;">
                                    <span><?= h(strtoupper($r['name'])) ?></span>
                                    <strong><?= (int)$r['cnt'] ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p style="margin-top: 1rem; color: #636e72; font-size: 0.9rem;">Dữ liệu hiển thị số lượng bài viết công khai theo thẻ (tag).</p>
                    </div>
                </div>

                <script>
                    const communityChartLabels = <?= json_encode($chart_labels) ?>;
                    const communityChartData = <?= json_encode($chart_data) ?>;
                </script>

                <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-grey); border-radius: 8px; text-align: center;">
                    <p style="margin: 0; color: #636e72;">
                        💡 <strong>Tham gia ngay</strong> để đặt câu hỏi, thảo luận và kết nối với cộng đồng sinh viên!
                    </p>
                    <a href="pages/login.php" style="display: inline-block; margin-top: 0.75rem; padding: 0.6rem 1.5rem; background: var(--primary-mint); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,210,211,0.4)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                        🚀 Đăng nhập ngay
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="hero">
                <h1>👋 Xin chào, <?= h($current_user['ho_ten'] ?? 'User') ?>!</h1>
                <p>Chào mừng đến với diễn đàn sinh viên. Hãy chia sẻ và học hỏi cùng nhau!</p>
            </div>
        <?php endif; ?>

        <!-- Search and Tabs Section -->
        <div style="background: white; padding: 1.5rem; border-radius: 15px; box-shadow: var(--shadow); margin-bottom: 2rem;">
            <!-- Search Bar -->
            <div style="margin-bottom: 1.5rem;">
                <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm bài viết theo tiêu đề, nội dung, tag..." style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--bg-grey); border-radius: 10px; font-size: 1rem; transition: border-color 0.3s;" oninput="searchPosts(this.value)" onfocus="this.style.borderColor='var(--primary-mint)'" onblur="this.style.borderColor='var(--bg-grey)'">
            </div>

            <!-- Tabs Navigation -->
            <div class="tabs-container">
                <button class="tab-btn active" onclick="switchTab('all', event)">
                    🌐 Tất cả
                </button>
                <button class="tab-btn" onclick="switchTab('trending', event)">
                    🔥 Trending
                </button>
            </div>
        </div>

        <!-- Tab: Tất cả -->
        <div class="tab-content active" id="tab-all">
            <h2 class="section-title">🌐 Tất cả bài viết</h2>
            <div class="posts-grid">
                <?php foreach ($all_posts as $post):
                    $user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post['id'], 'post') : false;
                    $likes = getLikeCount($pdo, $post['id'], 'post');
                ?>
                    <div class="post-card">
                        <div onclick="openPostModal(<?= $post['id'] ?>)" style="cursor: pointer;">
                            <div class="post-header">
                                <div class="post-author">
                                    <div class="author-avatar">
                                        <?= strtoupper(mb_substr($post['ho_ten'], 0, 1)) ?>
                                    </div>
                                    <div class="author-info">
                                        <a href="pages/profile.php?username=<?= urlencode($post['username']) ?>" class="author-name" style="text-decoration: none; color: var(--text-dark); font-weight: 600; transition: color 0.3s;" onclick="event.stopPropagation();" onmouseover="this.style.color='var(--primary-mint)'" onmouseout="this.style.color='var(--text-dark)'"><?= h($post['ho_ten']) ?></a>
                                        <span class="post-time" data-timestamp="<?= strtotime($post['created_at']) ?>">
                                            <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <?php if (isset($post['trending_score']) && $post['trending_score'] == 1): ?>
                                        <span class="trending-badge">🔥 Trending</span>
                                    <?php endif; ?>
                                    <span class="post-status status-<?= $post['status'] ?>">
                                        <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                    </span>
                                    <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                                        <span class="post-privacy" style="background: <?= $post['privacy'] === 'public' ? '#00d2d3' : '#ff7675' ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                            <?= $post['privacy'] === 'public' ? '🌍 Công khai' : '🔒 Riêng tư' ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="post-menu" onclick="event.stopPropagation();">
                                        <button class="btn-menu" onclick="toggleMenu(<?= $post['id'] ?>)">⋮</button>
                                        <div class="dropdown-menu" id="menu-<?= $post['id'] ?>">
                                            <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
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
                                </div>
                            </div>

                            <h3 class="post-title"><?= h($post['title']) ?></h3>
                            <p class="post-excerpt"><?= h(mb_substr($post['content'], 0, 150)) ?>...</p>

                            <?php if (!empty($post['tags'])): ?>
                                <div class="post-tags">
                                    <?php
                                    $tags = explode(',', $post['tags']);
                                    foreach ($tags as $tag):
                                    ?>
                                        <span class="tag"><?= h(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Get full poll details
                            $poll_detail = getPoll($pdo, $post['id']);
                            if ($poll_detail):
                                $user_voted = isLoggedIn() ? hasVoted($pdo, $_SESSION['user_id'], $poll_detail['id']) : true;
                                $total_votes = array_sum(array_column($poll_detail['options'], 'vote_count'));
                                $poll_type = $poll_detail['poll_type'] ?? 'single';
                                $input_type = $poll_type === 'multiple' ? 'checkbox' : 'radio';
                            ?>
                                <div id="poll-container-<?= $post['id'] ?>" style="background: var(--light-mint); padding: 1rem; border-radius: 8px; margin-top: 1rem; border-left: 4px solid var(--primary-mint);" onclick="event.stopPropagation();">
                                    <h4 style="color: var(--primary-mint); margin-bottom: 0.75rem; font-size: 0.95rem;">📊 <?= h($poll_detail['question']) ?></h4>

                                    <?php if (isLoggedIn() && !$user_voted): ?>
                                        <!-- Voting Form -->
                                        <form id="poll-form-<?= $post['id'] ?>" onsubmit="return submitPollVote(event, <?= $poll_detail['id'] ?>, <?= $post['id'] ?>);">
                                            <?php foreach ($poll_detail['options'] as $option): ?>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem; margin: 0.4rem 0; background: white; border: 2px solid var(--bg-grey); border-radius: 6px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--primary-mint)'" onmouseout="this.style.borderColor='var(--bg-grey)'">
                                                    <input type="<?= $input_type ?>" name="poll_options[]" value="<?= $option['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                                    <span style="font-weight: 600; font-size: 0.9rem;"><?= h($option['option_text']) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                            <button type="submit" style="width: 100%; padding: 0.6rem; margin-top: 0.5rem; background: var(--primary-mint); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#00a37a'" onmouseout="this.style.background='var(--primary-mint)'">
                                                ✓ Gửi câu trả lời
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- Poll Results -->
                                        <div id="poll-results-<?= $post['id'] ?>">
                                            <?php foreach ($poll_detail['options'] as $option):
                                                $percentage = $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0;
                                            ?>
                                                <div style="margin: 0.5rem 0;">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem; font-size: 0.85rem;">
                                                        <span style="font-weight: 600;"><?= h($option['option_text']) ?></span>
                                                        <span style="color: #636e72;"><?= $option['vote_count'] ?> (<?= $percentage ?>%)</span>
                                                    </div>
                                                    <div style="background: white; height: 6px; border-radius: 3px; overflow: hidden;">
                                                        <div style="background: var(--primary-mint); height: 100%; width: <?= $percentage ?>%; transition: width 0.3s;"></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <p style="text-align: right; color: #636e72; font-size: 0.8rem; margin-top: 0.5rem;">
                                                Tổng: <?= $total_votes ?> phiếu
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="post-footer">
                            <button class="btn-action btn-like <?= $user_liked ? 'liked' : '' ?>" onclick="toggleLike(<?= $post['id'] ?>, this)">
                                ❤️ <span class="like-count"><?= $likes ?></span>
                            </button>
                            <button class="btn-action btn-comment" onclick="openPostModal(<?= $post['id'] ?>, true)">
                                💬 <?= $post['comment_count'] ?? 0 ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab: Trending -->
        <div class="tab-content" id="tab-trending">
            <?php if (!empty($trending_posts)): ?>
                <h2 class="section-title">🔥 Bài viết xu hướng (7 ngày qua)</h2>
                <div class="posts-grid">
                    <?php foreach ($trending_posts as $post):
                        $user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post['id'], 'post') : false;
                    ?>
                        <div class="post-card">
                            <div onclick="openPostModal(<?= $post['id'] ?>)" style="cursor: pointer;">
                                <div class="post-header">
                                    <div class="post-author">
                                        <div class="author-avatar">
                                            <?= strtoupper(mb_substr($post['ho_ten'], 0, 1)) ?>
                                        </div>
                                        <div class="author-info">
                                            <a href="pages/profile.php?username=<?= urlencode($post['username']) ?>" class="author-name" style="text-decoration: none; color: var(--text-dark); font-weight: 600; transition: color 0.3s;" onclick="event.stopPropagation();" onmouseover="this.style.color='var(--primary-mint)'" onmouseout="this.style.color='var(--text-dark)'"><?= h($post['ho_ten']) ?></a>
                                            <span class="post-time" data-timestamp="<?= strtotime($post['created_at']) ?>">
                                                <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <span class="trending-badge">🔥 Trending</span>
                                        <span class="post-status status-<?= $post['status'] ?>">
                                            <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                        </span>
                                        <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                                            <span class="post-privacy" style="background: <?= $post['privacy'] === 'public' ? '#00d2d3' : '#ff7675' ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                                <?= $post['privacy'] === 'public' ? '🌍 Công khai' : '🔒 Riêng tư' ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="post-menu" onclick="event.stopPropagation();">
                                            <button class="btn-menu" onclick="toggleMenu(<?= $post['id'] ?>)">⋮</button>
                                            <div class="dropdown-menu" id="menu-<?= $post['id'] ?>">
                                                <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
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
                                    </div>
                                </div>

                                <h3 class="post-title"><?= h($post['title']) ?></h3>

                                <p class="post-excerpt">
                                    <?= h(mb_substr(strip_tags($post['content']), 0, 150)) ?>...
                                </p>

                                <?php if ($post['tags']): ?>
                                    <div class="post-tags">
                                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                            <span class="tag"><?= h(trim($tag)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Get full poll details
                                $poll_detail = getPoll($pdo, $post['id']);
                                if ($poll_detail):
                                    $user_voted = isLoggedIn() ? hasVoted($pdo, $_SESSION['user_id'], $poll_detail['id']) : true;
                                    $total_votes = array_sum(array_column($poll_detail['options'], 'vote_count'));
                                    $poll_type = $poll_detail['poll_type'] ?? 'single';
                                    $input_type = $poll_type === 'multiple' ? 'checkbox' : 'radio';
                                ?>
                                    <div id="poll-container-<?= $post['id'] ?>" style="background: var(--light-mint); padding: 1rem; border-radius: 8px; margin-top: 1rem; border-left: 4px solid var(--primary-mint);" onclick="event.stopPropagation();">
                                        <h4 style="color: var(--primary-mint); margin-bottom: 0.75rem; font-size: 0.95rem;">📊 <?= h($poll_detail['question']) ?></h4>

                                        <?php if (isLoggedIn() && !$user_voted): ?>
                                            <!-- Voting Form -->
                                            <form id="poll-form-<?= $post['id'] ?>" onsubmit="return submitPollVote(event, <?= $poll_detail['id'] ?>, <?= $post['id'] ?>);">
                                                <?php foreach ($poll_detail['options'] as $option): ?>
                                                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem; margin: 0.4rem 0; background: white; border: 2px solid var(--bg-grey); border-radius: 6px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--primary-mint)'" onmouseout="this.style.borderColor='var(--bg-grey)'">
                                                        <input type="<?= $input_type ?>" name="poll_options[]" value="<?= $option['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                                        <span style="font-weight: 600; font-size: 0.9rem;"><?= h($option['option_text']) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                                <button type="submit" style="width: 100%; padding: 0.6rem; margin-top: 0.5rem; background: var(--primary-mint); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#00a37a'" onmouseout="this.style.background='var(--primary-mint)'">
                                                    ✓ Gửi câu trả lời
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Poll Results -->
                                            <div id="poll-results-<?= $post['id'] ?>">
                                                <?php foreach ($poll_detail['options'] as $option):
                                                    $percentage = $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0;
                                                ?>
                                                    <div style="margin: 0.5rem 0;">
                                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem; font-size: 0.85rem;">
                                                            <span style="font-weight: 600;"><?= h($option['option_text']) ?></span>
                                                            <span style="color: #636e72;"><?= $option['vote_count'] ?> (<?= $percentage ?>%)</span>
                                                        </div>
                                                        <div style="background: white; height: 6px; border-radius: 3px; overflow: hidden;">
                                                            <div style="background: var(--primary-mint); height: 100%; width: <?= $percentage ?>%; transition: width 0.3s;"></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <p style="text-align: right; color: #636e72; font-size: 0.8rem; margin-top: 0.5rem;">
                                                    Tổng: <?= $total_votes ?> phiếu
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="post-footer">
                                <button class="btn-action btn-like <?= $user_liked ? 'liked' : '' ?>" onclick="toggleLike(<?= $post['id'] ?>, this)" <?= !isLoggedIn() ? 'disabled title="Đăng nhập để like"' : '' ?>>
                                    ❤️ <span class="like-count"><?= $post['like_count'] ?></span>
                                </button>
                                <button class="btn-action btn-comment" onclick="openPostModal(<?= $post['id'] ?>, true)">
                                    💬 <?= $post['comment_count'] ?? 0 ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #636e72; padding: 2rem;">🔍 Không có bài viết trending trong 7 ngày qua.</p>
            <?php endif; ?>
        </div>

        <!-- 'Bài viết của tôi' tab removed -->
    </div>

    <?php if (isLoggedIn()): ?>
        <!-- Notification Bell Button (Left side) -->
        <button class="btn-notification" id="notificationBtn" title="Thông báo">
            <span class="notification-icon">🔔</span>
            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
        </button>

        <!-- Create Post Button (Right side) -->
        <a href="pages/create_post.php" class="btn-create" title="Tạo bài viết mới">✍️</a>
    <?php endif; ?>

    <!-- Notification Popup -->
    <?php if (isLoggedIn()): ?>
        <div id="notificationPopup" class="notification-popup" style="display: none;">
            <div class="notification-header">
                <h3>🔔 Thông báo</h3>
                <button class="mark-all-read" onclick="markAllAsRead()">Đánh dấu tất cả đã đọc</button>
            </div>
            <div class="notification-list" id="notificationList">
                <div class="loading-spinner">⏳ Đang tải thông báo...</div>
            </div>
        </div>
    <?php endif; ?>

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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/index.js"></script>

    <footer style="text-align: center; padding: 2rem; margin-top: 3rem; background: var(--bg-grey); border-radius: 15px;">
        <p style="color: #636e72; margin: 0;">
            🎓 <strong>Learning2ne1 - Diễn đàn sinh viên</strong><br>
            Được tạo bởi <strong>Chu Quang Duy</strong>
        </p>
    </footer>
</body>

</html>