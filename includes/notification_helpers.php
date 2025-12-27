<?php

/**
 * Notification Helper Functions
 * Functions to create and manage notifications
 */

/**
 * Create a notification
 * @param PDO $pdo Database connection
 * @param int $user_id User who will receive the notification
 * @param int $actor_id User who performed the action
 * @param string $type Type of notification (like, comment, mention, system)
 * @param string $target_type Type of target (post, comment)
 * @param int $target_id ID of the target
 * @param int $post_id ID of the related post
 * @param string $content Optional content for the notification
 * @return bool Success status
 */
function createNotification($pdo, $user_id, $actor_id, $type, $target_type = null, $target_id = null, $post_id = null, $content = null)
{
    try {
        // Don't notify yourself
        if ($user_id == $actor_id) {
            return false;
        }

        // Check if similar notification already exists (for likes only)
        if ($type === 'like' && $post_id) {
            $stmt = $pdo->prepare("
                SELECT id FROM notifications 
                WHERE user_id = ? AND actor_id = ? AND type = 'like' AND post_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$user_id, $actor_id, $post_id]);
            if ($stmt->fetch()) {
                // Already notified about this like in last 24 hours
                return false;
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, actor_id, type, target_type, target_id, post_id, content, is_read)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");

        return $stmt->execute([$user_id, $actor_id, $type, $target_type, $target_id, $post_id, $content]);
    } catch (Exception $e) {
        error_log("Notification creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify post owner about a like
 */
function notifyPostLike($pdo, $post_id, $liker_id)
{
    // Get post owner
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post) {
        createNotification($pdo, $post['user_id'], $liker_id, 'like', 'post', $post_id, $post_id);
    }
}

/**
 * Notify comment owner about a like
 */
function notifyCommentLike($pdo, $comment_id, $liker_id)
{
    // Get comment owner and related post
    $stmt = $pdo->prepare("SELECT user_id, post_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if ($comment) {
        createNotification($pdo, $comment['user_id'], $liker_id, 'like', 'comment', $comment_id, $comment['post_id']);
    }
}

/**
 * Notify post owner about a comment
 */
function notifyPostComment($pdo, $post_id, $commenter_id, $comment_content)
{
    // Get post owner
    $stmt = $pdo->prepare("SELECT user_id, title FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post) {
        // Get first 100 chars of comment
        $preview = mb_substr($comment_content, 0, 100);
        createNotification($pdo, $post['user_id'], $commenter_id, 'comment', 'post', $post_id, $post_id, $preview);
    }
}

/**
 * Notify mentioned users
 * @param PDO $pdo Database connection
 * @param string $content Content that may contain mentions (@username)
 * @param int $actor_id User who created the mention
 * @param string $context_type Type of context (post, comment)
 * @param int $context_id ID of the post or comment
 * @param int $post_id Related post ID
 */
function notifyMentions($pdo, $content, $actor_id, $context_type, $context_id, $post_id)
{
    // Find all @mentions in content
    preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);

    if (!empty($matches[1])) {
        $usernames = array_unique($matches[1]);

        foreach ($usernames as $username) {
            // Get user ID from username
            $stmt = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // Get preview of content (first 100 chars)
                $preview = mb_substr($content, 0, 100);
                createNotification($pdo, $user['id_user'], $actor_id, 'mention', $context_type, $context_id, $post_id, $preview);
            }
        }
    }
}
