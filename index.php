<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/helpers.php';

$current_user = isLoggedIn() ? getCurrentUser($pdo) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        header("Location: pages/login.php");
        exit;
    }

    if ($_POST['action'] === 'hide_post' && isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
        if (!isset($_SESSION['hidden_posts'])) {
            $_SESSION['hidden_posts'] = [];
        }
        if (!in_array($post_id, $_SESSION['hidden_posts'])) {
            $_SESSION['hidden_posts'][] = $post_id;
        }
        header("Location: index.php#posts-section");
        exit;
    }

    if ($_POST['action'] === 'unhide_all') {
        $_SESSION['hidden_posts'] = [];
        header("Location: index.php");
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

        $params = $_GET;
        $query_string = http_build_query($params);
        $redirect_url = 'index.php' . ($query_string ? '?' . $query_string : '');

        header("Location: " . $redirect_url, true, 303);
        exit;
    }

    if ($_POST['action'] === 'vote_poll' && isset($_POST['poll_id'], $_POST['option_id'], $_POST['post_id'])) {
        $poll_id = intval($_POST['poll_id']);
        $option_id = intval($_POST['option_id']);
        $post_id = intval($_POST['post_id']);
        $user_id = $_SESSION['user_id'];

        $check = $pdo->prepare("
            SELECT pv.id FROM poll_votes pv
            JOIN poll_options po ON pv.option_id = po.id
            WHERE po.poll_id = ? AND pv.user_id = ?
        ");
        $check->execute([$poll_id, $user_id]);

        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO poll_votes (option_id, user_id) VALUES (?, ?)");
            $stmt->execute([$option_id, $user_id]);
        }

        $params = $_GET;
        $query_string = http_build_query($params);
        $redirect_url = 'index.php' . ($query_string ? '?' . $query_string : '') . '#post-' . $post_id;

        header("Location: " . $redirect_url, true, 303);
        exit;
    }
}

$trending_posts = getTrending($pdo, 5);
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$filter_time = isset($_GET['time']) ? $_GET['time'] : '';

if (isLoggedIn()) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.ho_ten, u.username,
            (SELECT COUNT(*) FROM likes WHERE target_id = p.id AND target_type = 'post') as like_count,
            (SELECT COUNT(DISTINCT c.id) FROM comments c WHERE c.post_id = p.id) as comment_count
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
            (SELECT COUNT(DISTINCT c.id) FROM comments c WHERE c.post_id = p.id) as comment_count
        FROM posts p
        JOIN user u ON p.user_id = u.id_user
        WHERE p.privacy = 'public'
        ORDER BY p.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
}
$all_posts = $stmt->fetchAll();

$hidden_posts = isset($_SESSION['hidden_posts']) ? $_SESSION['hidden_posts'] : [];
if (!empty($hidden_posts)) {
    $all_posts = array_filter($all_posts, function ($post) use ($hidden_posts) {
        return !in_array($post['id'], $hidden_posts);
    });
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search_query !== '') {
    $all_posts = array_filter($all_posts, function ($post) use ($search_query) {
        $q = mb_strtolower($search_query);
        return (
            mb_stripos($post['title'], $q) !== false ||
            mb_stripos($post['content'], $q) !== false ||
            mb_stripos($post['tags'] ?? '', $q) !== false ||
            mb_stripos($post['ho_ten'], $q) !== false
        );
    });
}

if ($filter_status !== '') {
    $all_posts = array_filter($all_posts, function ($post) use ($filter_status) {
        return $post['status'] === $filter_status;
    });
}

if ($filter_time !== '') {
    $now = time();
    $all_posts = array_filter($all_posts, function ($post) use ($filter_time, $now) {
        $post_time = strtotime($post['created_at']);
        switch ($filter_time) {
            case 'today':
                return date('Y-m-d', $post_time) === date('Y-m-d', $now);
            case 'week':
                return ($now - $post_time) <= 7 * 24 * 60 * 60;
            case 'month':
                return ($now - $post_time) <= 30 * 24 * 60 * 60;
            default:
                return true;
        }
    });
}

if ($filter_sort === 'oldest') {
    usort($all_posts, function ($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
} elseif ($filter_sort === 'most_liked') {
    usort($all_posts, function ($a, $b) {
        return $b['like_count'] - $a['like_count'];
    });
} elseif ($filter_sort === 'most_commented') {
    usort($all_posts, function ($a, $b) {
        return $b['comment_count'] - $a['comment_count'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning2ne1 - Trang chủ diễn đàn</title>
    <link rel="stylesheet" href="assets/css/base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/index.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800;900&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <?php if (!isLoggedIn()): ?>
            <div class="guest-notice">
                <strong><i class='bx bx-waving-hand'></i> Chào mừng!</strong> Bạn đang ở chế độ khách.
                <a href="pages/login.php" style="color: var(--primary-mint); font-weight: bold;">Đăng nhập</a>
                để tương tác và tạo bài viết.
            </div>
        <?php else: ?>
            <div class="hero" style="text-align: center; margin-bottom: 2rem;">
                <h1 style="font-family: 'Poppins', sans-serif; font-size: 3.2rem; font-weight: 900; color: white; letter-spacing: 2px; margin: 0; text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); background: linear-gradient(135deg, #ffffff 0%, #e8f8f5 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    Learning2ne1 <span style="color: #00b894; font-weight: 800; letter-spacing: 5px;"> FORUM</span>
                </h1>
                <h3 style="color: rgba(255, 255, 255, 0.95); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.3rem; font-weight: 600; margin-top: 1rem; letter-spacing: 0.5px;"><i class='bx bx-wink-smile'></i> Xin chào, <?= h($current_user['username']) ?>!</h3>
            </div>
        <?php endif; ?>


        <div class="search-filter-section">

            <form method="GET" class="search-form">
                <input type="hidden" name="tab" value="<?= h($current_tab) ?>">
                <input type="hidden" name="status" value="<?= h($filter_status) ?>">
                <input type="hidden" name="sort" value="<?= h($filter_sort) ?>">
                <input type="hidden" name="time" value="<?= h($filter_time) ?>">
                <i class="bx bx-search search-icon"></i>
                <input type="text" name="search" placeholder="Tìm kiếm bài viết theo tiêu đề, nội dung, tag..."
                    value="<?= h($search_query) ?>" class="search-input">
                <button type="submit" class="search-btn">
                    <i class='bx bx-search'></i> Tìm
                </button>
            </form>

            <?php if ($search_query !== ''): ?>
                <div class="search-result-info">
                    <span>Kết quả tìm kiếm cho: <strong>"<?= h($search_query) ?>"</strong></span>
                    <a href="index.php?tab=<?= h($current_tab) ?>&status=<?= h($filter_status) ?>&sort=<?= h($filter_sort) ?>&time=<?= h($filter_time) ?>">✕ Xóa tìm kiếm</a>
                </div>
            <?php endif; ?>


            <div class="filter-bar">
                <div class="filter-label">
                    <i class='bx bx-filter-alt'></i> Lọc:
                </div>

                <form method="GET" class="filter-form" id="filterForm">
                    <input type="hidden" name="tab" value="<?= h($current_tab) ?>">
                    <input type="hidden" name="search" value="<?= h($search_query) ?>">


                    <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="" <?= $filter_status === '' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                        <option value="solved" <?= $filter_status === 'solved' ? 'selected' : '' ?>>Đã giải quyết</option>
                        <option value="unsolved" <?= $filter_status === 'unsolved' ? 'selected' : '' ?>>Chưa giải quyết</option>
                    </select>


                    <select name="time" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="" <?= $filter_time === '' ? 'selected' : '' ?>>Mọi thời gian</option>
                        <option value="today" <?= $filter_time === 'today' ? 'selected' : '' ?>>Hôm nay</option>
                        <option value="week" <?= $filter_time === 'week' ? 'selected' : '' ?>>Tuần này</option>
                        <option value="month" <?= $filter_time === 'month' ? 'selected' : '' ?>>Tháng này</option>
                    </select>


                    <select name="sort" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="" <?= $filter_sort === '' ? 'selected' : '' ?>>Sắp xếp theo</option>
                        <option value="newest" <?= $filter_sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="oldest" <?= $filter_sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                        <option value="most_liked" <?= $filter_sort === 'most_liked' ? 'selected' : '' ?>>Nhiều like nhất</option>
                        <option value="most_commented" <?= $filter_sort === 'most_commented' ? 'selected' : '' ?>>Nhiều bình luận nhất</option>
                    </select>
                </form>

                <?php if ($filter_status !== '' || $filter_time !== '' || $filter_sort !== ''): ?>
                    <a href="index.php?tab=<?= h($current_tab) ?>&search=<?= urlencode($search_query) ?>" class="filter-clear">
                        <i class='bx bx-x'></i> Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </div>


            <?php if ($filter_status !== '' || $filter_time !== '' || $filter_sort !== ''): ?>
                <div class="active-filters">
                    <?php if ($filter_status !== ''): ?>
                        <span class="filter-tag">
                            <i class='bx bx-check-circle'></i>
                            <?= $filter_status === 'solved' ? 'Đã giải quyết' : 'Chưa giải quyết' ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($filter_time !== ''): ?>
                        <span class="filter-tag">
                            <i class='bx bx-time'></i>
                            <?php
                            switch ($filter_time) {
                                case 'today':
                                    echo 'Hôm nay';
                                    break;
                                case 'week':
                                    echo 'Tuần này';
                                    break;
                                case 'month':
                                    echo 'Tháng này';
                                    break;
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($filter_sort !== ''): ?>
                        <span class="filter-tag">
                            <i class='bx bx-sort-alt-2'></i>
                            <?php
                            switch ($filter_sort) {
                                case 'newest':
                                    echo 'Mới nhất';
                                    break;
                                case 'oldest':
                                    echo 'Cũ nhất';
                                    break;
                                case 'most_liked':
                                    echo 'Nhiều like nhất';
                                    break;
                                case 'most_commented':
                                    echo 'Nhiều bình luận nhất';
                                    break;
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                    <span class="filter-result-count">
                        <i class='bx bx-list-ul'></i> <?= count($all_posts) ?> bài viết
                    </span>
                </div>
            <?php endif; ?>


            <div class="tabs-container">
                <a href="index.php?tab=all&status=<?= h($filter_status) ?>&sort=<?= h($filter_sort) ?>&time=<?= h($filter_time) ?>"
                    class="tab-btn <?= $current_tab === 'all' ? 'active' : '' ?>">
                    <i class='bx bx-globe'></i> Tất cả
                </a>
                <a href="index.php?tab=trending&status=<?= h($filter_status) ?>&sort=<?= h($filter_sort) ?>&time=<?= h($filter_time) ?>"
                    class="tab-btn <?= $current_tab === 'trending' ? 'active' : '' ?>">
                    <i class='bx bx-trending-up'></i> Trending
                </a>
            </div>

            <?php if (isLoggedIn() && !empty($_SESSION['hidden_posts'])): ?>
                <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: linear-gradient(135deg, #ffeaa7, #fdcb6e); border-radius: 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="color: #2d3436; font-weight: 600;">
                        <i class='bx bx-hide'></i> Bạn đã ẩn <?= count($_SESSION['hidden_posts']) ?> bài viết
                    </span>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="unhide_all">
                        <button type="submit" style="padding: 0.4rem 0.8rem; background: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: #2d3436; transition: all 0.2s;">
                            <i class='bx bx-show'></i> Hiện lại tất cả
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($current_tab === 'all'): ?>

            <h2 class="section-title"><i class='bx bx-globe'></i> Tất cả bài viết</h2>

            <?php if (empty($all_posts)): ?>
                <div style="text-align: center; padding: 3rem; color: #636e72; background: white; border-radius: 15px;">
                    <div style="font-size: 3rem;"><i class='bx bx-search'></i></div>
                    <p>Không tìm thấy bài viết nào.</p>
                </div>
            <?php else: ?>
                <div class="posts-grid" id="posts-section">
                    <?php foreach ($all_posts as $post):
                        $user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post['id'], 'post') : false;
                        $likes = getLikeCount($pdo, $post['id'], 'post');

                        $is_trending = false;
                        foreach ($trending_posts as $trending) {
                            if ($trending['id'] === $post['id']) {
                                $is_trending = true;
                                break;
                            }
                        }
                    ?>
                        <div class="post-card" id="post-<?= $post['id'] ?>">
                            <div class="post-header">
                                <a href="pages/profile.php?username=<?= urlencode($post['username']) ?>" class="post-author" style="text-decoration: none; color: inherit;" onclick="event.stopPropagation();">
                                    <div class="author-avatar">
                                        <?= strtoupper(mb_substr($post['ho_ten'], 0, 1)) ?>
                                    </div>
                                    <div class="author-info">
                                        <span class="author-name"><?= h($post['ho_ten']) ?></span>
                                        <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                                    </div>
                                </a>
                                <div class="post-status-container">
                                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                        <?php if ($is_trending): ?>
                                            <span class="trending-badge"><i class='bx bx-trending-up'></i> Trending</span>
                                        <?php endif; ?>
                                        <?php if ($post['privacy'] === 'private'): ?>
                                            <span class="post-privacy privacy-private">
                                                <i class='bx bx-lock-alt'></i> Riêng tư
                                            </span>
                                        <?php else: ?>
                                            <span class="post-privacy privacy-public">
                                                <i class='bx bx-globe'></i> Công khai
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="post-status status-<?= $post['status'] ?>">
                                        <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                    </span>
                                </div>
                            </div>


                            <a href="pages/post.php?id=<?= $post['id'] ?>" style="text-decoration: none; color: inherit; display: block;">
                                <h3 class="post-title"><?= h($post['title']) ?></h3>
                                <p class="post-excerpt"><?= h(mb_substr(strip_tags($post['content']), 0, 150)) ?>...</p>

                                <?php if (!empty($post['tags'])): ?>
                                    <div class="post-tags">
                                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                            <span class="tag"><?= h(trim($tag)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
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

                            <?php if (!empty($images)): ?>
                                <div class="post-preview-images" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1rem 0;">
                                    <?php foreach (array_slice($images, 0, 4) as $img): ?>
                                        <div style="border-radius: 8px; overflow: hidden; background: #f5f6fa; max-width: 200px; max-height: 150px;">
                                            <img src="<?= h($img['file_path']) ?>" alt="Ảnh" style="width: 100%; height: 100%; object-fit: contain; max-height: 200px;">
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($images) > 4): ?>
                                        <div style="border-radius: 8px; background: linear-gradient(135deg, var(--primary-mint), #00a37a); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; width: 60px; height: 60px;">
                                            +<?= count($images) - 4 ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($files)): ?>
                                <div class="post-preview-files" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.75rem 0;">
                                    <?php foreach ($files as $file):
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
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php

                            $poll_detail = getPoll($pdo, $post['id']);
                            if ($poll_detail):
                                $user_voted = isLoggedIn() ? hasVoted($pdo, $_SESSION['user_id'], $poll_detail['id']) : true;
                                $total_votes = array_sum(array_column($poll_detail['options'], 'vote_count'));
                            ?>
                                <div style="background: linear-gradient(135deg, var(--light-mint) 0%, #e8f8f5 100%); padding: 1rem; border-radius: 10px; margin: 1rem 0; border-left: 4px solid var(--primary-mint);">
                                    <h4 style="color: var(--primary-mint); margin-bottom: 0.75rem; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class='bx bx-bar-chart-alt-2'></i> <?= h($poll_detail['question']) ?>
                                    </h4>

                                    <?php if (isLoggedIn() && !$user_voted): ?>

                                        <form method="POST">
                                            <input type="hidden" name="action" value="vote_poll">
                                            <input type="hidden" name="poll_id" value="<?= $poll_detail['id'] ?>">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <?php foreach ($poll_detail['options'] as $option): ?>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem; margin: 0.4rem 0; background: white; border: 2px solid #e8e8e8; border-radius: 8px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--primary-mint)'" onmouseout="this.style.borderColor='#e8e8e8'">
                                                    <input type="radio" name="option_id" value="<?= $option['id'] ?>" required style="width: 18px; height: 18px;">
                                                    <span style="font-weight: 600; font-size: 0.9rem;"><?= h($option['option_text']) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                            <button type="submit" style="width: 100%; padding: 0.6rem; margin-top: 0.5rem; background: linear-gradient(135deg, var(--primary-mint), #00a37a); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                                                <i class='bx bx-check-circle'></i> Gửi câu trả lời
                                            </button>
                                        </form>
                                    <?php else: ?>

                                        <?php foreach ($poll_detail['options'] as $option):
                                            $percentage = $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0;
                                        ?>
                                            <div style="margin: 0.5rem 0;">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem; font-size: 0.85rem;">
                                                    <span style="font-weight: 600;"><?= h($option['option_text']) ?></span>
                                                    <span style="color: #636e72; font-weight: 600;"><?= $percentage ?>%</span>
                                                </div>
                                                <div style="background: white; height: 8px; border-radius: 4px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                                                    <div style="background: linear-gradient(90deg, var(--primary-mint), #2ecc71); height: 100%; width: <?= $percentage ?>%; transition: width 0.5s ease;"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <p style="text-align: right; color: #636e72; font-size: 0.8rem; margin-top: 0.5rem;">
                                            Tổng: <?= $total_votes ?> phiếu
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="post-footer">

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_like">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="btn-action btn-like <?= $user_liked ? 'liked' : '' ?>" <?= !isLoggedIn() ? 'disabled title="Đăng nhập để like"' : '' ?>>
                                        <i class='bx bx-like'></i> <span class="like-count"><?= $likes ?></span>
                                    </button>
                                </form>
                                <a href="pages/post.php?id=<?= $post['id'] ?>#comments" class="btn-action btn-comment" style="text-decoration: none;">
                                    <i class='bx bx-message'></i> <?= $post['comment_count'] ?? 0 ?>
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <form method="POST" style="display: inline; margin-left: auto;">
                                        <input type="hidden" name="action" value="hide_post">
                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="btn-action btn-hide" title="Ẩn bài viết này">
                                            <i class='bx bx-hide'></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <h2 class="section-title"><i class='bx bx-trending-up'></i> Bài viết xu hướng (7 ngày qua)</h2>

            <?php if (empty($trending_posts)): ?>
                <div style="text-align: center; padding: 3rem; color: #636e72; background: white; border-radius: 15px;">
                    <div style="font-size: 3rem;"><i class='bx  bx-chart-trend'></i></div>
                    <p>Không có bài viết trending trong 7 ngày qua.</p>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($trending_posts as $post):
                        $user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post['id'], 'post') : false;
                    ?>
                        <div class="post-card" id="post-<?= $post['id'] ?>">
                            <div class="post-header">
                                <a href="pages/profile.php?username=<?= urlencode($post['username']) ?>" class="post-author" style="text-decoration: none; color: inherit;" onclick="event.stopPropagation();">
                                    <div class="author-avatar">
                                        <?= strtoupper(mb_substr($post['username'], 0, 1)) ?>
                                    </div>
                                    <div class="author-info">
                                        <span class="author-name"><?= h($post['username']) ?></span>
                                        <span class="post-time"><?= timeAgo($post['created_at']) ?></span>
                                    </div>
                                </a>
                                <div class="post-status-container">
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <span class="trending-badge"><i class='bx bx-trending-up'></i> Trending</span>
                                        <?php if ($post['privacy'] === 'private'): ?>
                                            <span class="post-privacy privacy-private">
                                                <i class='bx bx-lock-alt'></i> Riêng tư
                                            </span>
                                        <?php else: ?>
                                            <span class="post-privacy privacy-public">
                                                <i class='bx bx-globe'></i> Công khai
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="post-status status-<?= $post['status'] ?>">
                                        <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                    </span>
                                </div>
                            </div>

                            <a href="pages/post.php?id=<?= $post['id'] ?>" style="text-decoration: none; color: inherit; display: block;">
                                <h3 class="post-title"><?= h($post['title']) ?></h3>
                                <p class="post-excerpt"><?= h(mb_substr(strip_tags($post['content']), 0, 150)) ?>...</p>

                                <?php if (!empty($post['tags'])): ?>
                                    <div class="post-tags">
                                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                            <span class="tag"><?= h(trim($tag)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
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

                            <?php if (!empty($images)): ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.75rem 0;">
                                    <?php foreach (array_slice($images, 0, 4) as $img): ?>
                                        <div style="border-radius: 8px; overflow: hidden; background: #f5f6fa; max-width: 150px; max-height: 120px;">
                                            <img src="<?= h($img['file_path']) ?>" alt="Ảnh" style="width: 100%; height: 100%; object-fit: contain; max-height: 120px;">
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($images) > 4): ?>
                                        <div style="border-radius: 8px; background: linear-gradient(135deg, var(--primary-mint), #00a37a); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; width: 50px; height: 50px; font-size: 0.9rem;">
                                            +<?= count($images) - 4 ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($files)): ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.5rem 0;">
                                    <?php foreach ($files as $file):
                                        $ext = strtoupper(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                        $icon = '📄';
                                        if (in_array($ext, ['PDF'])) $icon = '📕';
                                        elseif (in_array($ext, ['DOC', 'DOCX'])) $icon = '📘';
                                        elseif (in_array($ext, ['XLS', 'XLSX'])) $icon = '📗';
                                    ?>
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.6rem; background: #f5f6fa; border-radius: 6px; font-size: 0.75rem; color: #636e72;">
                                            <?= $icon ?> <?= $ext ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="post-footer">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_like">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="btn-action btn-like <?= $user_liked ? 'liked' : '' ?>" <?= !isLoggedIn() ? 'disabled title="Đăng nhập để like"' : '' ?>>
                                        <i class='bx bx-like'></i> <span class="like-count"><?= $post['like_count'] ?></span>
                                    </button>
                                </form>
                                <a href="pages/post.php?id=<?= $post['id'] ?>#comments" class="btn-action btn-comment" style="text-decoration: none;">
                                    <i class='bx bx-message'></i> <?= $post['comment_count'] ?? 0 ?>
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <form method="POST" style="display: inline; margin-left: auto;">
                                        <input type="hidden" name="action" value="hide_post">
                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="btn-action btn-hide" title="Ẩn bài viết này">
                                            <i class='bx bx-hide'></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if (isLoggedIn()): ?>
        <a href="pages/create_post.php" class="btn-create" title="Tạo bài viết mới">
            <span class="btn-create-icon"><i class='bx bx-edit'></i></span>
        </a>
    <?php endif; ?>

    <footer style="text-align: center; padding: 2rem; margin-top: 3rem; background: var(--bg-grey); border-radius: 15px;">
        <p style="color: #636e72; margin: 0;">
            <strong>Learning2ne1 - Diễn đàn sinh viên</strong><br>
            Được tạo bởi <strong>Chu Quang Duy</strong>
        </p>
    </footer>
</body>

</html>