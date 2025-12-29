<?php
// Tự động xác định đường dẫn tương đối
$path = '../';
require_once $path . 'config.php';
require_once $path . 'includes/functions.php';
require_once $path . 'includes/helpers.php';

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Đánh dấu tất cả là đã xem (Vì user đã vào trang này)
try {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
} catch (PDOException $e) {
    // Nếu bảng chưa tồn tại, bỏ qua
}

// 2. Lấy danh sách thông báo (Mới nhất lên đầu)
$stmt = $pdo->prepare(" 
    SELECT n.*, u.ho_ten as actor_name, u.username as actor_username, p.title as post_title
    FROM notifications n
    LEFT JOIN user u ON n.actor_id = u.id_user
    LEFT JOIN posts p ON n.post_id = p.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

require_once $path . 'includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thông báo - Learning2ne1</title>
    <link rel="stylesheet" href="<?= $path ?>assets/css/style.css">
    <style>
        .notif-container {
            max_width: 700px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .notif-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .notif-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            text-decoration: none;
            color: #333;
            transition: background 0.2s;
        }

        .notif-item:last-child {
            border-bottom: none;
        }

        .notif-item:hover {
            background: #f9f9f9;
        }

        .notif-item.unread {
            background: #e0f2f1;
        }

        /* Màu nền cho tin vừa mới đọc xong */
        .notif-item.read {
            background: #f6f7f8;
            opacity: 0.85;
        }

        .notif-avatar {
            width: 40px;
            height: 40px;
            background: #009688;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .notif-content {
            flex: 1;
        }

        .notif-text {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .notif-time {
            font-size: 0.8rem;
            color: #888;
            margin-top: 4px;
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
            opacity: 0.3;
        }
    </style>
</head>

<body>

    <div class="notif-container">
        <h2 style="margin-bottom: 20px; color: #00796b;">🔔 Thông báo của bạn</h2>

        <div class="notif-list">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                    <?php
                    // Determine link and read state
                    $link = !empty($notif['post_id']) ? "post.php?id=" . (int)$notif['post_id'] : '#';
                    $is_unread = isset($notif['is_read']) && $notif['is_read'] == 0;
                    $item_class = 'notif-item' . ($is_unread ? ' unread' : ' read');

                    // Build human readable message
                    $actor = h($notif['actor_name'] ?? 'System');
                    $post_title = h($notif['post_title'] ?? 'Bài viết');
                    $content_preview = h(mb_substr($notif['content'] ?? '', 0, 120));
                    switch ($notif['type']) {
                        case 'like':
                            $message = "<strong>$actor</strong> đã thích bài viết của bạn: &quot;$post_title&quot;";
                            break;
                        case 'comment':
                            $message = "<strong>$actor</strong> đã bình luận về: &quot;$post_title&quot;";
                            break;
                        case 'mention':
                            $message = "<strong>$actor</strong> đã nhắc đến bạn: \"$content_preview\"";
                            break;
                        default:
                            $message = $notif['content'] ? h($notif['content']) : 'Thông báo hệ thống';
                            break;
                    }
                    ?>
                    <a href="<?= $link ?>" class="<?= $item_class ?>">
                        <div class="notif-avatar">
                            <?= strtoupper(mb_substr($notif['actor_name'] ?? 'S', 0, 1)) ?>
                        </div>
                        <div class="notif-content">
                            <p class="notif-text"><?= $message ?></p>
                            <span class="notif-time"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-icon">🔕</span>
                    <p>Bạn chưa có thông báo nào.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>