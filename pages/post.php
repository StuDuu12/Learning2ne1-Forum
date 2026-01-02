<?php
require_once '../config.php';
require_once '../includes/functions.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$return_to = isset($_GET['return_to']) ? trim($_GET['return_to']) : '';
$current_user = getCurrentUser($pdo);

$post = getPost($pdo, $post_id);

if (!$post) {
    die('Bài viết không tồn tại');
}

if ($post['privacy'] === 'private') {
    if (!isLoggedIn() || ($_SESSION['user_id'] != $post['user_id'] && $_SESSION['account_level'] != 0)) {
        die('Bạn không có quyền xem bài viết này');
    }
}

if (isLoggedIn()) {
    incrementViews($pdo, $post_id);
    trackInterests($pdo, $_SESSION['user_id'], $post['tags']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);

        if ($stmt->fetch()) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO likes (user_id, target_id, target_type) VALUES (?, ?, 'post')
                    ON DUPLICATE KEY UPDATE id=id
                ");
                $stmt->execute([$_SESSION['user_id'], $post_id]);
            } catch (PDOException $e) {
            }
        }
    }
    header("Location: post.php?id=$post_id", true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlike'])) {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND target_id = ? AND target_type = 'post'");
        $stmt->execute([$_SESSION['user_id'], $post_id]);
    }
    header("Location: post.php?id=$post_id", true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $content = trim($_POST['comment_content']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if ($content && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);

        if ($stmt->fetch()) {
            try {
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$post_id, $_SESSION['user_id'], $content, $parent_id]);
            } catch (PDOException $e) {
            }
        } else {
            session_destroy();
            redirect('login.php');
        }
    }
    header("Location: post.php?id=$post_id#comments-section", true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    if (isLoggedIn()) {
        $comment_id = (int)$_POST['comment_id'];

        $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();

        $canDelete = false;
        if ($comment) {
            if ($comment['user_id'] == $_SESSION['user_id']) {
                $canDelete = true;
            }
            if ($post['user_id'] == $_SESSION['user_id']) {
                $canDelete = true;
            }
            if (isset($_SESSION['account_level']) && $_SESSION['account_level'] == 0) {
                $canDelete = true;
            }
        }

        if ($canDelete) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? OR parent_id = ?");
            $stmt->execute([$comment_id, $comment_id]);
        }
    }
    header("Location: post.php?id=$post_id#comments-section", true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment'])) {
    if (isLoggedIn()) {
        $comment_id = (int)$_POST['comment_id'];
        $new_content = trim($_POST['edit_content']);

        if (!empty($new_content)) {
            $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch();

            if ($comment && $comment['user_id'] == $_SESSION['user_id']) {
                $stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
                $stmt->execute([$new_content, $comment_id]);
            }
        }
    }
    header("Location: post.php?id=$post_id#comments-section", true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {
    if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);

        $tags_input = isset($_POST['tags']) ? trim($_POST['tags']) : '';
        if (!empty($tags_input)) {
            $tags_array = preg_split('/[,\s]+/', $tags_input);
            $tags_array = array_filter(array_map('trim', $tags_array));
            $tags = implode(',', $tags_array);
        } else {
            $tags = '';
        }

        if ($title && $content) {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, tags = ? WHERE id = ?");
            $stmt->execute([$title, $content, $tags, $post_id]);
        }
    }
    header("Location: post.php?id=$post_id", true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (isLoggedIn() && ($_SESSION['user_id'] == $post['user_id'] || $_SESSION['account_level'] == 0)) {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        redirect("../index.php");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']) {
        $new_status = $post['status'] === 'solved' ? 'unsolved' : 'solved';
        $stmt = $pdo->prepare("UPDATE posts SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $post_id]);
    }
    header("Location: post.php?id=$post_id", true, 303);
    exit;
}

$attachments = getAttachments($pdo, $post_id);

$comment_sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

function getCommentsSorted($pdo, $post_id, $sort = 'newest', $parent_id = null)
{
    $order = $sort === 'oldest' ? 'ASC' : 'DESC';

    $sql = "SELECT c.*, u.ho_ten, u.username
            FROM comments c
            JOIN user u ON c.user_id = u.id_user
            WHERE c.post_id = ? AND " . ($parent_id ? "c.parent_id = ?" : "c.parent_id IS NULL") . "
            ORDER BY c.created_at $order";

    $stmt = $pdo->prepare($sql);

    if ($parent_id) {
        $stmt->execute([$post_id, $parent_id]);
    } else {
        $stmt->execute([$post_id]);
    }

    return $stmt->fetchAll();
}

$comments = getCommentsSorted($pdo, $post_id, $comment_sort);

$like_count = getLikeCount($pdo, $post_id, 'post');
$user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post_id, 'post') : false;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$stmt->execute([$post_id]);
$total_comments = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($post['title']) ?> - Diễn đàn sinh viên</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/post.css">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>

</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <?php
        $back_url = '../index.php#post-' . $post_id;
        if ($return_to === 'profile') {
            $back_url = 'profile.php?username=' . urlencode($post['username']) . '#post-' . $post_id;
        }
        ?>
        <a href="<?= $back_url ?>" class="btn-back">← Quay lại</a>

        <div class="post-detail">
            <div class="post-header">
                <a href="profile.php?username=<?= urlencode($post['username']) ?>" class="author-section" style="text-decoration: none; color: inherit;">
                    <div class="author-avatar">
                        <?= strtoupper(mb_substr($post['ho_ten'], 0, 1)) ?>
                    </div>
                    <div class="author-info">
                        <h3><?= h($post['ho_ten']) ?></h3>
                        <div class="post-meta-info">
                            @<?= h($post['username']) ?> · <?= timeAgo($post['created_at']) ?>
                        </div>
                    </div>
                </a>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                        <?php if ($post['privacy'] === 'private') { ?>
                            <span class="post-privacy privacy-private">
                                <i class='bx bx-lock-alt'></i> Riêng tư
                            </span>
                        <?php } else { ?>
                            <span class="post-privacy privacy-public">
                                <i class='bx bx-globe'></i> Công khai
                            </span>
                        <?php } ?>
                        <span class="post-status status-<?= $post['status'] ?>">
                            <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '? Chưa giải quyết' ?>
                        </span>
                    </div>
                    <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']) { ?>
                        <div class="post-actions">
                            <a href="edit_post.php?id=<?= $post_id ?>" class="btn-edit" style="text-decoration: none;"><i class='bx bx-edit'></i> Sửa</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa bài viết này?');">
                                <button type="submit" name="delete_post" class="btn-delete"><i class='bx bx-trash'></i> Xóa</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="toggle_status" class="btn-toggle-status">
                                    <?= $post['status'] === 'solved' ? '↩ Chưa giải quyết' : '✓ Đã giải quyết' ?>
                                </button>
                            </form>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <h1 class="post-title"><?= h($post['title']) ?></h1>

            <?php if ($post['tags']) { ?>
                <div class="post-tags">
                    <?php foreach (explode(',', $post['tags']) as $tag) { ?>
                        <span class="tag"><?= h(trim($tag)) ?></span>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="post-content" style="margin: 1.5rem 0; line-height: 1.8; color: #2d3436;">
                <?= nl2br(h($post['content'])) ?>
            </div>


            <?php
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
                <div class="post-images">
                    <?php foreach ($images as $img) { ?>
                        <div class="post-image-item">
                            <img src="../<?= h($img['file_path']) ?>" alt="Ảnh đính kèm" onclick="window.open('../<?= h($img['file_path']) ?>', '_blank')">
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if (!empty($files)) { ?>
                <div style="margin: 1rem 0;">
                    <h4><i class='bx bx-paperclip'></i> File đính kèm:</h4>
                    <?php foreach ($files as $file) { ?>
                        <a href="../<?= h($file['file_path']) ?>" target="_blank" class="btn-interact" style="display: inline-block; margin: 0.25rem 0;">
                            <i class='bx bx-file'></i> Download <?= strtoupper(pathinfo($file['file_path'], PATHINFO_EXTENSION)) ?>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>


            <div class="interaction-bar">
                <form method="POST" style="display: inline;">
                    <?php if ($user_liked) { ?>
                        <button type="submit" name="unlike" class="btn-interact btn-liked">
                            <i class='bx bxs-like'></i> Đã thích (<?= $like_count ?>)
                        </button>
                    <?php } else { ?>
                        <button type="submit" name="like" class="btn-interact">
                            <i class='bx bx-like'></i> Thích (<?= $like_count ?>)
                        </button>
                    <?php } ?>
                </form>

                <a href="#comments-section" class="btn-interact" style="text-decoration: none; color: inherit;">
                    <i class='bx bx-message'></i> Bình luận (<?= $total_comments ?>)
                </a>
            </div>
        </div>


        <div class="comments-section" id="comments-section">
            <h2 style="color: var(--primary-mint); margin-bottom: 1rem;">
                <i class='bx bx-message'></i> Bình luận (<?= $total_comments ?>)
            </h2>

            <div class="comment-filter">
                <label><i class='bx bx-filter-alt'></i> Sắp xếp:</label>
                <select onchange="window.location.href='post.php?id=<?= $post_id ?>&sort=' + this.value + '#comments-section'">
                    <option value="newest" <?= $comment_sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="oldest" <?= $comment_sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                </select>
            </div>

            <?php if (isLoggedIn()) { ?>
                <div class="comment-form" id="comment-form">
                    <form method="POST">
                        <input type="hidden" name="parent_id" value="">
                        <textarea name="comment_content" placeholder="Chia sẻ suy nghĩ của bạn..." required style="width: 100%; padding: 0.75rem; border: 2px solid var(--bg-grey); border-radius: 8px; min-height: 100px; font-family: inherit;"></textarea>
                        <div style="margin-top: 0.5rem;">
                            <button type="submit" name="add_comment" class="btn-submit">
                                <i class='bx bx-send'></i> Gửi bình luận
                            </button>
                        </div>
                    </form>
                </div>
            <?php } else { ?>
                <div class="comment-form">
                    <p style="text-align: center; color: #636e72;">
                        <a href="login.php" style="color: var(--primary-mint); font-weight: bold;">Đăng nhập</a> để bình luận
                    </p>
                </div>
            <?php } ?>

            <div id="comments-list" class="comments-list">
                <?php foreach ($comments as $comment) {
                    $replies = getCommentsSorted($pdo, $post_id, 'oldest', $comment['id']);
                    $reply_count = count($replies);
                ?>
                    <div class="comment comment-parent" id="comment-<?= $comment['id'] ?>">
                        <div class="comment-header">
                            <a href="profile.php?username=<?= urlencode($comment['username']) ?>" class="comment-author" style="text-decoration: none; color: inherit;">
                                <span class="comment-author-avatar">
                                    <?= strtoupper(mb_substr($comment['ho_ten'], 0, 1)) ?>
                                </span>
                                <span class="comment-author-name"><?= h($comment['ho_ten']) ?></span>
                                <span class="comment-author-username">@<?= h($comment['username']) ?></span>
                            </a>
                            <span class="comment-meta">
                                <?php if ($reply_count > 0) { ?>
                                    <span class="reply-indicator"><i class='bx bx-message-rounded-dots'></i> <?= $reply_count ?> trả lời</span>
                                <?php } ?>
                                <span class="comment-time"><i class='bx bx-time-five'></i> <?= timeAgo($comment['created_at']) ?></span>
                            </span>
                        </div>

                        <div class="comment-content-text" id="content-<?= $comment['id'] ?>">
                            <?= nl2br(h($comment['content'])) ?>
                        </div>

                        <div class="edit-form-inline" id="edit-form-<?= $comment['id'] ?>" style="display: none;">
                            <form method="POST">
                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                <textarea name="edit_content" required><?= h($comment['content']) ?></textarea>
                                <div class="btn-group">
                                    <button type="submit" name="edit_comment" class="btn-submit" style="padding: 0.5rem 1rem;">
                                        <i class='bx bx-check'></i> Lưu
                                    </button>
                                    <button type="button" onclick="toggleEditForm(<?= $comment['id'] ?>)" style="padding: 0.5rem 1rem; background: #dfe6e9; color: #2d3436; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Hủy</button>
                                </div>
                            </form>
                        </div>

                        <div class="comment-actions">
                            <?php if (isLoggedIn()) { ?>
                                <button class="btn-reply" onclick="toggleReplyForm(<?= $comment['id'] ?>)">
                                    <i class='bx bx-reply'></i> Trả lời
                                </button>
                                <?php if ($_SESSION['user_id'] == $comment['user_id']) { ?>
                                    <button class="btn-reply btn-edit" onclick="toggleEditForm(<?= $comment['id'] ?>)" style="color: #f39c12;">
                                        <i class='bx bx-edit'></i> Sửa
                                    </button>
                                <?php } ?>
                                <?php if ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['user_id'] == $post['user_id'] || (isset($_SESSION['account_level']) && $_SESSION['account_level'] == 0)) { ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Xóa bình luận này?');">
                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                        <button type="submit" name="delete_comment" class="btn-reply btn-delete" style="color: #e74c3c;">
                                            <i class='bx bx-trash'></i> Xóa
                                        </button>
                                    </form>
                                <?php } ?>
                            <?php } ?>
                        </div>

                        <?php if (isLoggedIn()) { ?>
                            <div id="reply-form-<?= $comment['id'] ?>" style="display: none;">
                                <form method="POST">
                                    <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                                    <textarea name="comment_content" placeholder="Viết câu trả lời cho <?= h($comment['ho_ten']) ?>..." required style="width: 100%; padding: 0.85rem 1rem; border-radius: 10px; border: 2px solid rgba(253, 203, 110, 0.4); font-family: inherit; min-height: 80px;"></textarea>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                                        <button type="submit" name="add_comment" class="btn-submit"><i class='bx bx-send'></i> Gửi trả lời</button>
                                        <button type="button" onclick="toggleReplyForm(<?= $comment['id'] ?>)" style="padding: 0.75rem 1.5rem; background: #dfe6e9; color: #2d3436; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Hủy</button>
                                    </div>
                                </form>
                            </div>
                        <?php } ?>

                        <?php if ($reply_count > 0) { ?>
                            <div class="reply-thread">
                                <?php foreach ($replies as $reply) { ?>
                                    <div class="comment reply" id="comment-<?= $reply['id'] ?>">
                                        <div class="comment-header">
                                            <a href="profile.php?username=<?= urlencode($reply['username']) ?>" class="comment-author" style="text-decoration: none; color: inherit;">
                                                <span class="comment-author-avatar" style="background: linear-gradient(135deg, #fdcb6e 0%, #f39c12 100%); width: 28px; height: 28px; font-size: 0.75rem;">
                                                    <?= strtoupper(mb_substr($reply['ho_ten'], 0, 1)) ?>
                                                </span>
                                                <span class="comment-author-name"><?= h($reply['ho_ten']) ?></span>
                                                <span class="comment-author-username">@<?= h($reply['username']) ?></span>
                                            </a>
                                            <span class="comment-time"><i class='bx bx-time-five'></i> <?= timeAgo($reply['created_at']) ?></span>
                                        </div>

                                        <div class="comment-content-text" id="content-<?= $reply['id'] ?>">
                                            <?= nl2br(h($reply['content'])) ?>
                                        </div>

                                        <div class="edit-form-inline" id="edit-form-<?= $reply['id'] ?>" style="display: none;">
                                            <form method="POST">
                                                <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                <textarea name="edit_content" required><?= h($reply['content']) ?></textarea>
                                                <div class="btn-group">
                                                    <button type="submit" name="edit_comment" class="btn-submit" style="padding: 0.5rem 1rem;">
                                                        <i class='bx bx-check'></i> Lưu
                                                    </button>
                                                    <button type="button" onclick="toggleEditForm(<?= $reply['id'] ?>)" style="padding: 0.5rem 1rem; background: #dfe6e9; color: #2d3436; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Hủy</button>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="comment-actions">
                                            <?php if (isLoggedIn()) { ?>
                                                <button class="btn-reply" onclick="toggleReplyForm(<?= $reply['id'] ?>)">
                                                    <i class='bx bx-reply'></i> Trả lời
                                                </button>
                                                <?php if ($_SESSION['user_id'] == $reply['user_id']) { ?>
                                                    <button class="btn-reply btn-edit" onclick="toggleEditForm(<?= $reply['id'] ?>)" style="color: #f39c12;">
                                                        <i class='bx bx-edit'></i> Sửa
                                                    </button>
                                                <?php } ?>
                                                <?php if ($_SESSION['user_id'] == $reply['user_id'] || $_SESSION['user_id'] == $post['user_id'] || (isset($_SESSION['account_level']) && $_SESSION['account_level'] == 0)) { ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Xóa bình luận này?');">
                                                        <input type="hidden" name="comment_id" value="<?= $reply['id'] ?>">
                                                        <button type="submit" name="delete_comment" class="btn-reply btn-delete" style="color: #e74c3c;">
                                                            <i class='bx bx-trash'></i> Xóa
                                                        </button>
                                                    </form>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>

                                        <?php if (isLoggedIn()) { ?>
                                            <div id="reply-form-<?= $reply['id'] ?>" style="display: none;">
                                                <form method="POST">
                                                    <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                                                    <textarea name="comment_content" placeholder="Trả lời @<?= h($reply['username']) ?>..." required style="width: 100%; padding: 0.85rem 1rem; border-radius: 10px; border: 2px solid rgba(253, 203, 110, 0.4); font-family: inherit; min-height: 80px;"></textarea>
                                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                                                        <button type="submit" name="add_comment" class="btn-submit"><i class='bx bx-send'></i> Gửi</button>
                                                        <button type="button" onclick="toggleReplyForm(<?= $reply['id'] ?>)" style="padding: 0.75rem 1.5rem; background: #dfe6e9; color: #2d3436; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Hủy</button>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if (empty($comments)) { ?>
                    <div style="text-align: center; padding: 3rem; color: #636e72;">
                        <div style="font-size: 3rem;"><i class='bx bx-message'></i></div>
                        <p>Chưa có bình luận nào. Hãy là người đầu tiên!</p>
                    </div>
                <?php } ?>
            </div>
        </div>

        <footer style="text-align: center; padding: 2rem; margin-top: 3rem; background: var(--bg-grey); border-radius: 15px;">
            <p style="color: #636e72; margin: 0;">
                <i class='bx bx-note'></i> Bài viết được tạo bởi <strong><?= h($post['ho_ten']) ?></strong> •
                <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
            </p>
        </footer>
    </div>

    <script>
        function toggleForm(formType, commentId) {
            const form = document.getElementById(formType + '-' + commentId);
            if (!form) return;
            document.querySelectorAll('[id^="' + formType + '-"]').forEach(f => {
                if (f !== form) f.style.display = 'none';
            });

            if (formType === 'edit-form') {
                document.querySelectorAll('[id^="content-"]').forEach(c => {
                    c.style.display = 'block';
                });
                const content = document.getElementById('content-' + commentId);
                if (content) content.style.display = form.style.display === 'none' ? 'none' : 'block';
            }
            form.style.display = form.style.display === 'none' ? 'block' : 'none';

            if (form.style.display === 'block') {
                const textarea = form.querySelector('textarea');
                if (textarea) textarea.focus();
            }
        }

        function toggleReplyForm(commentId) {
            toggleForm('reply-form', commentId);
        }

        function toggleEditForm(commentId) {
            toggleForm('edit-form', commentId);
        }
    </script>
</body>

</html>