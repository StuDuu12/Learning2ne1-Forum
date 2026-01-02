<?php
require_once 'config.php';
require_once 'includes/functions.php';

$current_user = isLoggedIn() ? getCurrentUser($pdo) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) {
        header("Location: pages/login.php");
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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800;900&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <?php if (!isLoggedIn()) { ?>
            <div class="guest-notice">
                <strong><i class='bx bx-waving-hand'></i> Chào mừng!</strong> Bạn đang ở chế độ khách.
                <a href="pages/login.php" style="color: var(--primary-mint); font-weight: bold;">Đăng nhập</a>
                để tương tác và tạo bài viết.
            </div>
        <?php } else { ?>
            <div class="hero" style="text-align: center; margin-bottom: 2rem;">
                <h1 style="font-family: 'Poppins', sans-serif; font-size: 3.2rem; font-weight: 900; color: white; letter-spacing: 2px; margin: 0; text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); background: linear-gradient(135deg, #ffffff 0%, #e8f8f5 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    Learning2ne1 <span style="color: #00b894; font-weight: 800; letter-spacing: 5px;"> FORUM</span>
                </h1>
                <h3 style="color: rgba(255, 255, 255, 0.95); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.3rem; font-weight: 600; margin-top: 1rem; letter-spacing: 0.5px;"><i class='bx bx-wink-smile'></i> Xin chào, <?= h($current_user['username']) ?>!</h3>
            </div>
        <?php } ?>


        <div class="search-filter-section">

            <form method="GET" class="search-form">
                <input type="hidden" name="tab" value="<?= h($current_tab) ?>">
                <input type="hidden" name="status" value="<?= h($filter_status) ?>">
                <input type="hidden" name="sort" value="<?= h($filter_sort) ?>">
                <input type="hidden" name="time" value="<?= h($filter_time) ?>">
                <i class="bx bx-search search-icon"></i>
                <input type="text" name="search" placeholder="Tìm kiếm bài viết theo tiêu đề, nội dung, tag"
                    value="<?= h($search_query) ?>" class="search-input">
                <button type="submit" class="search-btn">
                    <i class='bx bx-search'></i> Tìm
                </button>
            </form>

            <?php if ($search_query !== '') { ?>
                <div class="search-result-info">
                    <span>Kết quả tìm kiếm cho: <strong>"<?= h($search_query) ?>"</strong></span>
                    <a href="index.php?tab=<?= h($current_tab) ?>&status=<?= h($filter_status) ?>&sort=<?= h($filter_sort) ?>&time=<?= h($filter_time) ?>">✕ Xóa tìm kiếm</a>
                </div>
            <?php } ?>


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

                <?php if ($filter_status !== '' || $filter_time !== '' || $filter_sort !== '') { ?>
                    <a href="index.php?tab=<?= h($current_tab) ?>&search=<?= urlencode($search_query) ?>" class="filter-clear">
                        <i class='bx bx-x'></i> Xóa bộ lọc
                    </a>
                <?php } ?>
            </div>


            <?php if ($filter_status !== '' || $filter_time !== '' || $filter_sort !== '') { ?>
                <div class="active-filters">
                    <?php if ($filter_status !== '') { ?>
                        <span class="filter-tag">
                            <i class='bx bx-check-circle'></i>
                            <?= $filter_status === 'solved' ? 'Đã giải quyết' : 'Chưa giải quyết' ?>
                        </span>
                    <?php } ?>
                    <?php if ($filter_time !== '') { ?>
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
                    <?php } ?>
                    <?php if ($filter_sort !== '') { ?>
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
                    <?php } ?>
                    <span class="filter-result-count">
                        <i class='bx bx-list-ul'></i> <?= count($all_posts) ?> bài viết
                    </span>
                </div>
            <?php } ?>


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
        </div>

        <?php if ($current_tab === 'all') { ?>

            <h2 class="section-title"><i class='bx bx-globe'></i> Tất cả bài viết</h2>

            <?php if (empty($all_posts)) { ?>
                <div style="text-align: center; padding: 3rem; color: #636e72; background: white; border-radius: 15px;">
                    <div style="font-size: 3rem;"><i class='bx bx-search'></i></div>
                    <p>Không tìm thấy bài viết nào.</p>
                </div>
            <?php } else { ?>
                <div class="posts-grid" id="posts-section">
                    <?php foreach ($all_posts as $post) {
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
                                        <?php if ($is_trending) { ?>
                                            <span class="trending-badge"><i class='bx bx-trending-up'></i> Trending</span>
                                        <?php } ?>
                                        <?php if ($post['privacy'] === 'private') { ?>
                                            <span class="post-privacy privacy-private">
                                                <i class='bx bx-lock-alt'></i> Riêng tư
                                            </span>
                                        <?php } else { ?>
                                            <span class="post-privacy privacy-public">
                                                <i class='bx bx-globe'></i> Công khai
                                            </span>
                                        <?php } ?>
                                    </div>
                                    <span class="post-status status-<?= $post['status'] ?>">
                                        <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                    </span>
                                </div>
                            </div>


                            <a href="pages/post.php?id=<?= $post['id'] ?>" style="text-decoration: none; color: inherit; display: block;">
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
                                            <img src="<?= h($img['file_path']) ?>" alt="Ảnh" style="width: 100%; height: 100%; object-fit: contain; max-height: 200px;">
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
                                <a href="pages/post.php?id=<?= $post['id'] ?>#comments" class="btn-action btn-comment" style="text-decoration: none;">
                                    <i class='bx bx-message'></i> <?= $post['comment_count'] ?? 0 ?>
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

        <?php } else { ?>

            <h2 class="section-title"><i class='bx bx-trending-up'></i> Bài viết xu hướng (7 ngày qua)</h2>

            <?php if (empty($trending_posts)) { ?>
                <div style="text-align: center; padding: 3rem; color: #636e72; background: white; border-radius: 15px;">
                    <div style="font-size: 3rem;"><i class='bx  bx-chart-trend'></i></div>
                    <p>Không có bài viết trending trong 7 ngày qua.</p>
                </div>
            <?php } else { ?>
                <div class="posts-grid">
                    <?php foreach ($trending_posts as $post) {
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
                                        <?php if ($post['privacy'] === 'private') { ?>
                                            <span class="post-privacy privacy-private">
                                                <i class='bx bx-lock-alt'></i> Riêng tư
                                            </span>
                                        <?php } else { ?>
                                            <span class="post-privacy privacy-public">
                                                <i class='bx bx-globe'></i> Công khai
                                            </span>
                                        <?php } ?>
                                    </div>
                                    <span class="post-status status-<?= $post['status'] ?>">
                                        <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                                    </span>
                                </div>
                            </div>

                            <a href="pages/post.php?id=<?= $post['id'] ?>" style="text-decoration: none; color: inherit; display: block;">
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
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.75rem 0;">
                                    <?php foreach (array_slice($images, 0, 4) as $img) { ?>
                                        <div style="border-radius: 8px; overflow: hidden; background: #f5f6fa; max-width: 150px; max-height: 120px;">
                                            <img src="<?= h($img['file_path']) ?>" alt="Ảnh" style="width: 100%; height: 100%; object-fit: contain; max-height: 120px;">
                                        </div>
                                    <?php } ?>
                                    <?php if (count($images) > 4) { ?>
                                        <div style="border-radius: 8px; background: linear-gradient(135deg, var(--primary-mint), #00a37a); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; width: 50px; height: 50px; font-size: 0.9rem;">
                                            +<?= count($images) - 4 ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <?php if (!empty($files)) { ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.5rem 0;">
                                    <?php foreach ($files as $file) {
                                        $ext = strtoupper(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                        $icon = '📄';
                                        if (in_array($ext, ['PDF'])) $icon = '📕';
                                        elseif (in_array($ext, ['DOC', 'DOCX'])) $icon = '📘';
                                        elseif (in_array($ext, ['XLS', 'XLSX'])) $icon = '📗';
                                    ?>
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.6rem; background: #f5f6fa; border-radius: 6px; font-size: 0.75rem; color: #636e72;">
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
                                        <i class='bx bx-like'></i> <span class="like-count"><?= $post['like_count'] ?></span>
                                    </button>
                                </form>
                                <a href="pages/post.php?id=<?= $post['id'] ?>#comments" class="btn-action btn-comment" style="text-decoration: none;">
                                    <i class='bx bx-message'></i> <?= $post['comment_count'] ?? 0 ?>
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <?php if (isLoggedIn()) { ?>
        <a href="pages/create_post.php" class="btn-create" title="Tạo bài viết mới">
            <span class="btn-create-icon"><i class='bx bx-edit'></i></span>
        </a>
    <?php } ?>

    <footer style="text-align: center; padding: 2rem; margin-top: 3rem; background: var(--bg-grey); border-radius: 15px;">
        <p style="color: #636e72; margin: 0;">
            <strong>Learning2ne1 Forum</strong><br>
            Được tạo bởi <strong>Chu Quang Duy</strong>
        </p>
    </footer>
</body>

</html>