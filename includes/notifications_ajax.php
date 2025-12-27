<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'fetch';

// Fetch notifications
if ($action === 'fetch') {
    try {
        // Get notifications with grouping for likes
        $stmt = $pdo->prepare("
            SELECT 
                n.id,
                n.type,
                n.target_type,
                n.target_id,
                n.post_id,
                n.content,
                n.is_read,
                n.created_at,
                u.ho_ten as actor_name,
                u.username as actor_username,
                p.title as post_title,
                CASE 
                    WHEN n.type = 'like' THEN (
                        SELECT COUNT(*) 
                        FROM notifications n2 
                        WHERE n2.user_id = n.user_id 
                        AND n2.type = 'like' 
                        AND n2.post_id = n.post_id 
                        AND n2.is_read = 0
                        AND n2.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    )
                    ELSE 1
                END as like_count
            FROM notifications n
            LEFT JOIN user u ON n.actor_id = u.id_user
            LEFT JOIN posts p ON n.post_id = p.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll();

        // Group likes by post
        $grouped_notifications = [];
        $processed_like_posts = [];

        foreach ($notifications as $notif) {
            if ($notif['type'] === 'like') {
                $post_id = $notif['post_id'];

                // If we haven't processed likes for this post yet
                if (!isset($processed_like_posts[$post_id])) {
                    // Get all actors who liked this post
                    $stmt = $pdo->prepare("
                        SELECT u.ho_ten, u.username
                        FROM notifications n
                        JOIN user u ON n.actor_id = u.id_user
                        WHERE n.user_id = ? AND n.type = 'like' AND n.post_id = ?
                        AND n.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        ORDER BY n.created_at DESC
                        LIMIT 5
                    ");
                    $stmt->execute([$user_id, $post_id]);
                    $likers = $stmt->fetchAll();

                    $notif['likers'] = $likers;
                    $notif['like_count'] = count($likers);
                    $grouped_notifications[] = $notif;
                    $processed_like_posts[$post_id] = true;
                }
            } else {
                // Comments and mentions are not grouped
                $grouped_notifications[] = $notif;
            }
        }

        // Count unread notifications
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $unread_count = $stmt->fetch()['count'];

        echo json_encode([
            'success' => true,
            'notifications' => $grouped_notifications,
            'unread_count' => $unread_count
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Mark notification as read
elseif ($action === 'mark_read') {
    $notification_id = $_POST['notification_id'] ?? 0;

    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Mark all notifications as read
elseif ($action === 'mark_all_read') {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Get unread count only
elseif ($action === 'count') {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $unread_count = $stmt->fetch()['count'];

        echo json_encode(['success' => true, 'unread_count' => $unread_count]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
