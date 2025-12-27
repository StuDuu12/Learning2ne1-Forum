<?php
require_once '../config.php';
require_once 'functions.php';
require_once 'notification_helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'get_post_detail') {
    $post_id = (int)$_GET['post_id'];

    ob_start();
    include '../pages/post_detail_ajax.php';
    $html = ob_get_clean();

    echo $html;
    exit;
}

// Search users for mention (no login required for search)
if (isset($_GET['action']) && $_GET['action'] === 'search_users') {
    header('Content-Type: application/json');
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';

    // Allow empty query to show all users
    $stmt = $pdo->prepare("
        SELECT id_user, ho_ten, username 
        FROM user 
        WHERE username LIKE ? OR ho_ten LIKE ?
        LIMIT 10
    ");
    $search = '%' . $query . '%';
    $stmt->execute([$search, $search]);
    $users = $stmt->fetchAll();

    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if (!isLoggedIn() && $action !== 'check_login') {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit;
}

switch ($action) {
    case 'toggle_like':
        $target_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : (int)$_POST['target_id'];
        $target_type = isset($_POST['target_type']) ? $_POST['target_type'] : 'post';

        // Validate user exists
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'User ID không hợp lệ']);
            break;
        }

        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập lại']);
            break;
        }

        try {
            // Check if already liked
            $stmt = $pdo->prepare("
                SELECT id FROM likes 
                WHERE user_id = ? AND target_id = ? AND target_type = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $target_id, $target_type]);

            if ($stmt->fetch()) {
                // Unlike
                $stmt = $pdo->prepare("
                    DELETE FROM likes 
                    WHERE user_id = ? AND target_id = ? AND target_type = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $target_id, $target_type]);
                $liked = false;
            } else {
                // Like
                $stmt = $pdo->prepare("
                    INSERT INTO likes (user_id, target_id, target_type) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $target_id, $target_type]);
                $liked = true;

                // Create notification
                if ($target_type === 'post') {
                    notifyPostLike($pdo, $target_id, $_SESSION['user_id']);
                } elseif ($target_type === 'comment') {
                    notifyCommentLike($pdo, $target_id, $_SESSION['user_id']);
                }
            }

            // Get new count
            $like_count = getLikeCount($pdo, $target_id, $target_type);

            echo json_encode([
                'success' => true,
                'liked' => $liked,
                'like_count' => $like_count
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xử lý like']);
        }
        break;

    case 'change_privacy':
        $post_id = (int)$_POST['post_id'];
        $privacy = $_POST['privacy'];

        // Check if user owns the post
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();

        if ($post && $post['user_id'] == $_SESSION['user_id']) {
            $stmt = $pdo->prepare("UPDATE posts SET privacy = ? WHERE id = ?");
            $stmt->execute([$privacy, $post_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa']);
        }
        break;

    case 'report_post':
        $post_id = (int)$_POST['post_id'];
        $reason = $_POST['reason'];

        // Save report to database (you can create a reports table)
        // For now, just return success
        // TODO: Implement reports table and save functionality

        echo json_encode(['success' => true, 'message' => 'Đã gửi báo cáo']);
        break;

    case 'vote_poll':
        $option_id = (int)$_POST['option_id'];

        // Validate user exists
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'User ID không hợp lệ']);
            break;
        }

        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập lại']);
            break;
        }

        try {
            // Check if already voted
            $stmt = $pdo->prepare("
                SELECT pv.id FROM poll_votes pv
                JOIN poll_options po ON pv.option_id = po.id
                JOIN polls p ON po.poll_id = p.id
                WHERE pv.user_id = ? AND p.id = (
                    SELECT poll_id FROM poll_options WHERE id = ?
                )
            ");
            $stmt->execute([$_SESSION['user_id'], $option_id]);

            if ($stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Bạn đã bỏ phiếu rồi'
                ]);
                break;
            }

            // Insert vote
            $stmt = $pdo->prepare("
                INSERT INTO poll_votes (option_id, user_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$option_id, $_SESSION['user_id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Đã ghi nhận phiếu bầu của bạn'
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi ghi nhận phiếu bầu']);
        }
        break;

    case 'get_poll_results':
        header('Content-Type: application/json');

        $poll_id = isset($_GET['poll_id']) ? (int)$_GET['poll_id'] : 0;

        if ($poll_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Poll không hợp lệ']);
            exit;
        }

        try {
            // Get poll options with vote counts
            $stmt = $pdo->prepare("
                SELECT 
                    po.id,
                    po.option_text,
                    COUNT(pv.id) as vote_count
                FROM poll_options po
                LEFT JOIN poll_votes pv ON po.id = pv.option_id
                WHERE po.poll_id = ?
                GROUP BY po.id
                ORDER BY po.id ASC
            ");
            $stmt->execute([$poll_id]);
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'poll' => [
                    'id' => $poll_id,
                    'options' => $options
                ]
            ]);
        } catch (PDOException $e) {
            error_log("Get poll results error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
        }
        break;

    case 'mark_solved':
        $post_id = (int)$_POST['post_id'];

        // Check if user owns the post
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();

        if ($post && $post['user_id'] == $_SESSION['user_id']) {
            $stmt = $pdo->prepare("UPDATE posts SET status = 'solved' WHERE id = ?");
            $stmt->execute([$post_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Đã đánh dấu bài viết là đã giải quyết'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Bạn không có quyền thực hiện hành động này'
            ]);
        }
        break;

    case 'add_comment':
        $post_id = (int)$_POST['post_id'];
        $content = trim($_POST['comment_content'] ?? $_POST['content'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if (empty($content)) {
            echo json_encode([
                'success' => false,
                'message' => 'Nội dung bình luận không được để trống'
            ]);
            break;
        }

        // Validate user exists
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'User ID không hợp lệ']);
            break;
        }

        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập lại']);
            break;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO comments (post_id, user_id, content, parent_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$post_id, $_SESSION['user_id'], $content, $parent_id]);

            $comment_id = $pdo->lastInsertId();

            // Create notification for post owner
            notifyPostComment($pdo, $post_id, $_SESSION['user_id'], $content);

            // Check for mentions and create notifications
            notifyMentions($pdo, $content, $_SESSION['user_id'], 'comment', $comment_id, $post_id);

            // Get comment with user info
            $stmt = $pdo->prepare("
                SELECT c.*, u.ho_ten, u.username 
                FROM comments c
                JOIN user u ON c.user_id = u.id_user
                WHERE c.id = ?
            ");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm bình luận',
                'comment' => $comment
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm bình luận']);
        }
        break;

    case 'edit_comment':
        $comment_id = (int)$_POST['comment_id'];
        $content = trim($_POST['content'] ?? '');

        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Nội dung không được để trống']);
            break;
        }

        // Check if user owns the comment
        $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();

        if ($comment && $comment['user_id'] == $_SESSION['user_id']) {
            try {
                $stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
                $stmt->execute([$content, $comment_id]);
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật bình luận']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật bình luận']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa']);
        }
        break;

    case 'delete_comment':
        $comment_id = (int)$_POST['comment_id'];

        // Check if user owns the comment
        $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();

        if ($comment && $comment['user_id'] == $_SESSION['user_id']) {
            try {
                // Delete comment and all replies (cascade)
                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? OR parent_id = ?");
                $stmt->execute([$comment_id, $comment_id]);
                echo json_encode(['success' => true, 'message' => 'Đã xóa bình luận']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa bình luận']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa']);
        }
        break;

    case 'check_login':
        echo json_encode([
            'logged_in' => isLoggedIn(),
            'user_id' => isLoggedIn() ? $_SESSION['user_id'] : null
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Hành động không hợp lệ'
        ]);
}
