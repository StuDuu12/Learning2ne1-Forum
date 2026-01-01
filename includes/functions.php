<?php
function h($string)
{
    if ($string === null || $string === false) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getCurrentUser($pdo)
{
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function checkRole($required_level)
{
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['account_level'] <= $required_level;
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function timeAgo($timestamp)
{
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) return "vừa xong";
    if ($diff < 3600) return floor($diff / 60) . " phút trước";
    if ($diff < 86400) return floor($diff / 3600) . " giờ trước";
    if ($diff < 604800) return floor($diff / 86400) . " ngày trước";
    return date('d/m/Y', $time);
}

function getPost($pdo, $post_id)
{
    $stmt = $pdo->prepare("
        SELECT p.*, u.ho_ten, u.username, u.account_level
        FROM posts p
        JOIN user u ON p.user_id = u.id_user
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    return $stmt->fetch();
}

function getTrending($pdo, $limit = 5)
{
    $stmt = $pdo->prepare("
        SELECT p.*, u.ho_ten, u.username, 
               COUNT(DISTINCT l.id) as like_count,
               COUNT(DISTINCT c.id) as comment_count,
               (COUNT(DISTINCT l.id) * 2 + COUNT(DISTINCT c.id) * 3) as engagement_score
        FROM posts p
        JOIN user u ON p.user_id = u.id_user
        LEFT JOIN likes l ON l.target_id = p.id AND l.target_type = 'post'
        LEFT JOIN comments c ON c.post_id = p.id
        WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND p.privacy = 'public'
        GROUP BY p.id
        HAVING (like_count >= 5 AND comment_count >= 5)
        ORDER BY engagement_score DESC, p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getSuggested($pdo, $user_id = null, $limit = 10)
{
    if ($user_id) {

        $stmt = $pdo->prepare("
            SELECT tag FROM user_interests 
            WHERE user_id = ? 
            ORDER BY score DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $top_interest = $stmt->fetchColumn();

        if ($top_interest) {

            $stmt = $pdo->prepare("
                SELECT p.*, u.ho_ten, u.username,
                       COUNT(DISTINCT c.id) as comment_count
                FROM posts p
                JOIN user u ON p.user_id = u.id_user
                LEFT JOIN comments c ON c.post_id = p.id
                WHERE p.privacy = 'public' 
                      AND (p.tags LIKE ? OR p.tags LIKE ? OR p.tags LIKE ?)
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $like_pattern = "%$top_interest%";
            $stmt->execute([$like_pattern, $like_pattern, $like_pattern, $limit]);
            return $stmt->fetchAll();
        }
    }


    $stmt = $pdo->prepare("
        SELECT p.*, u.ho_ten, u.username,
               COUNT(DISTINCT c.id) as comment_count
        FROM posts p
        JOIN user u ON p.user_id = u.id_user
        LEFT JOIN comments c ON c.post_id = p.id
        WHERE p.privacy = 'public'
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getComments($pdo, $post_id, $parent_id = null)
{
    $stmt = $pdo->prepare("
        SELECT c.*, u.ho_ten, u.username
        FROM comments c
        JOIN user u ON c.user_id = u.id_user
        WHERE c.post_id = ? AND " . ($parent_id ? "c.parent_id = ?" : "c.parent_id IS NULL") . "
        ORDER BY c.created_at ASC
    ");

    if ($parent_id) {
        $stmt->execute([$post_id, $parent_id]);
    } else {
        $stmt->execute([$post_id]);
    }

    return $stmt->fetchAll();
}

function getLikeCount($pdo, $target_id, $target_type)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM likes 
        WHERE target_id = ? AND target_type = ?
    ");
    $stmt->execute([$target_id, $target_type]);
    return $stmt->fetchColumn();
}

function hasLiked($pdo, $user_id, $target_id, $target_type)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM likes 
        WHERE user_id = ? AND target_id = ? AND target_type = ?
    ");
    $stmt->execute([$user_id, $target_id, $target_type]);
    return $stmt->fetchColumn() > 0;
}

function trackInterests($pdo, $user_id, $tags_string)
{
    if (!$tags_string) return;


    $stmt = $pdo->prepare("SELECT id_user FROM user WHERE id_user = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        return;
    }

    $tags = explode(',', $tags_string);
    foreach ($tags as $tag) {
        $tag = trim($tag);
        if (!$tag) continue;


        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_interests (user_id, tag, score) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE score = score + 1
            ");
            $stmt->execute([$user_id, $tag]);
        } catch (PDOException $e) {

            continue;
        }
    }
}

function incrementViews($pdo, $post_id)
{
    $stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
}


function getAttachments($pdo, $post_id)
{
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}


function getPoll($pdo, $post_id)
{
    $stmt = $pdo->prepare("SELECT * FROM polls WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $poll = $stmt->fetch();

    if ($poll) {

        $stmt = $pdo->prepare("
            SELECT po.*, COUNT(pv.id) as vote_count
            FROM poll_options po
            LEFT JOIN poll_votes pv ON po.id = pv.option_id
            WHERE po.poll_id = ?
            GROUP BY po.id
        ");
        $stmt->execute([$poll['id']]);
        $poll['options'] = $stmt->fetchAll();
    }

    return $poll;
}

function hasVoted($pdo, $user_id, $poll_id)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM poll_votes pv
        JOIN poll_options po ON pv.option_id = po.id
        WHERE po.poll_id = ? AND pv.user_id = ?
    ");
    $stmt->execute([$poll_id, $user_id]);
    return $stmt->fetchColumn() > 0;
}

function getTagStats($pdo)
{
    $stmt = $pdo->query("
        SELECT 
            TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ',', numbers.n), ',', -1)) as tag,
            COUNT(*) as count
        FROM posts
        CROSS JOIN (
            SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
        ) numbers
        WHERE CHAR_LENGTH(tags) - CHAR_LENGTH(REPLACE(tags, ',', '')) >= numbers.n - 1
              AND tags IS NOT NULL AND tags != ''
        GROUP BY tag
        ORDER BY count DESC
    ");
    return $stmt->fetchAll();
}

function getUserInterests($pdo, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT tag, score FROM user_interests 
        WHERE user_id = ? 
        ORDER BY score DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function handleUpload($file)
{
    if (!defined('UPLOAD_DIR')) {
        return ['success' => false, 'error' => 'Upload directory not configured'];
    }

    $max_size = 100 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File too large (max 100MB)'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'path' => 'uploads/' . $filename,
            'type' => $file['type']
        ];
    }

    return ['success' => false, 'error' => 'Failed to save file'];
}
