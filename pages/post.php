<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/helpers.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_user = getCurrentUser($pdo);

// Get post
$post = getPost($pdo, $post_id);

if (!$post) {
    die('Bài viết không tồn tại');
}

// Check privacy
if ($post['privacy'] === 'private') {
    if (!isLoggedIn() || ($_SESSION['user_id'] != $post['user_id'] && $_SESSION['account_level'] != 0)) {
        die('Bạn không có quyền xem bài viết này');
    }
}

// Track view and interest if logged in
if (isLoggedIn()) {
    incrementViews($pdo, $post_id);
    trackInterests($pdo, $_SESSION['user_id'], $post['tags']);
}

// Handle Like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    // Validate user exists
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
                // Ignore foreign key errors
            }
        }
    }
    redirect("post.php?id=$post_id");
}

// Handle Unlike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlike'])) {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND target_id = ? AND target_type = 'post'");
        $stmt->execute([$_SESSION['user_id'], $post_id]);
    }
    redirect("post.php?id=$post_id");
}

// Handle Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $content = trim($_POST['comment_content']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if ($content && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        // Kiểm tra user có tồn tại không
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);

        if ($stmt->fetch()) {
            // User tồn tại, tiến hành insert comment
            try {
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$post_id, $_SESSION['user_id'], $content, $parent_id]);

                // Redirect to comment section or parent comment
                $anchor = $parent_id ? "#comment-$parent_id" : "#comments-section";
                redirect("post.php?id=$post_id$anchor");
            } catch (PDOException $e) {
                // Lỗi foreign key - user có thể bị xóa trong lúc submit
                $_SESSION['error'] = 'Không thể gửi bình luận. Vui lòng đăng nhập lại.';
                redirect('login.php');
            }
        } else {
            // User không tồn tại - yêu cầu đăng nhập lại
            session_destroy();
            redirect('login.php');
        }
    }

    redirect("post.php?id=$post_id#comments-section");
}

// Handle Edit Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {
    if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);

        // Process tags: split by comma/space, trim, add # prefix if not present
        $tags_input = isset($_POST['tags']) ? trim($_POST['tags']) : '';
        if (!empty($tags_input)) {
            $tags_array = preg_split('/[,\s]+/', $tags_input);
            $tags_array = array_filter(array_map('trim', $tags_array));
            $tags_array = array_map(function ($tag) {
                return (strpos($tag, '#') !== 0) ? '#' . $tag : $tag;
            }, $tags_array);
            $tags = implode(',', $tags_array);
        } else {
            $tags = '';
        }

        if ($title && $content) {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, tags = ? WHERE id = ?");
            $stmt->execute([$title, $content, $tags, $post_id]);
        }
    }
    redirect("post.php?id=$post_id");
}

// Handle Delete Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (isLoggedIn() && ($_SESSION['user_id'] == $post['user_id'] || $_SESSION['account_level'] == 0)) {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        redirect("../index.php");
    }
}

// Handle Toggle Status (Solved/Unsolved)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']) {
        $new_status = $post['status'] === 'solved' ? 'unsolved' : 'solved';
        $stmt = $pdo->prepare("UPDATE posts SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $post_id]);
    }
    redirect("post.php?id=$post_id");
}

// Handle Poll Vote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_poll'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $option_id = (int)$_POST['option_id'];
    $stmt = $pdo->prepare("INSERT INTO poll_votes (option_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->execute([$option_id, $_SESSION['user_id']]);

    redirect("post.php?id=$post_id");
}

// Get attachments
$attachments = getAttachments($pdo, $post_id);

// Get poll
$poll = getPoll($pdo, $post_id);

// Get comments
$comments = getComments($pdo, $post_id);

// Get likes
$like_count = getLikeCount($pdo, $post_id, 'post');
$user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post_id, 'post') : false;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($post['title']) ?> - Diễn đàn sinh viên</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/post.css">
</head>

</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container">
        <a href="javascript:history.back()" class="btn-back">
            ← Quay lại
        </a>

        <div class="post-detail">
            <div class="post-header">
                <div class="author-section">
                    <div class="author-avatar">
                        <?= strtoupper(mb_substr($post['ho_ten'], 0, 1)) ?>
                    </div>
                    <div class="author-info">
                        <h3><?= h($post['ho_ten']) ?></h3>
                        <div class="post-meta-info">
                            @<?= h($post['username']) ?> •
                            <?= timeAgo($post['created_at']) ?> •
                            👁️ <?= $post['views'] ?> lượt xem
                        </div>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                    <span class="post-status status-<?= $post['status'] ?>">
                        <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
                    </span>
                    <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                        <div class="post-actions">
                            <button onclick="openEditModal()" class="btn-edit">✏️ Sửa</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa bài viết này?');">
                                <button type="submit" name="delete_post" class="btn-delete">🗑️ Xóa</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="toggle_status" class="btn-toggle-status">
                                    <?= $post['status'] === 'solved' ? '↩️ Đánh dấu chưa giải quyết' : '✓ Đánh dấu đã giải quyết' ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <h1 class="post-title"><?= h($post['title']) ?></h1>

            <?php if ($post['tags']): ?>
                <div class="post-tags">
                    <?php foreach (explode(',', $post['tags']) as $tag): ?>
                        <span class="tag"><?= h(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="post-content">
                <?= nl2br(formatMentions(h($post['content']))) ?>
            </div>

            <!-- Attachments -->
            <?php if (!empty($attachments)): ?>
                <div class="attachments">
                    <h3>📎 File đính kèm:</h3>
                    <?php foreach ($attachments as $att): ?>
                        <div class="attachment-item">
                            <?php if (strpos($att['file_type'], 'image') !== false): ?>
                                <img src="../<?= h($att['file_path']) ?>" alt="Attachment">
                            <?php else: ?>
                                <a href="../<?= h($att['file_path']) ?>" target="_blank" class="btn-interact">
                                    📄 Download <?= pathinfo($att['file_path'], PATHINFO_EXTENSION) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Poll -->
            <?php if ($poll): ?>
                <div class="poll-section">
                    <div class="poll-title">📊 <?= h($poll['question']) ?></div>
                    <?php
                    $user_voted = isLoggedIn() ? hasVoted($pdo, $_SESSION['user_id'], $poll['id']) : false;
                    $total_votes = array_sum(array_column($poll['options'], 'vote_count'));
                    ?>

                    <?php if ($user_voted || !isLoggedIn()): ?>
                        <!-- Show results -->
                        <?php foreach ($poll['options'] as $option): ?>
                            <div class="poll-option">
                                <div style="flex: 1;">
                                    <div><?= h($option['option_text']) ?></div>
                                    <div class="poll-progress">
                                        <div class="poll-progress-bar" style="width: <?= $total_votes > 0 ? ($option['vote_count'] / $total_votes * 100) : 0 ?>%"></div>
                                    </div>
                                </div>
                                <div style="font-weight: 600; color: var(--primary-mint);">
                                    <?= $option['vote_count'] ?> phiếu
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="text-align: center; color: #636e72; margin-top: 1rem;">
                            Tổng: <?= $total_votes ?> phiếu bầu
                        </div>
                    <?php else: ?>
                        <!-- Show voting form -->
                        <form method="POST">
                            <?php foreach ($poll['options'] as $option): ?>
                                <button type="submit" name="vote_poll" value="<?= $option['id'] ?>" class="poll-option" style="width: 100%; border: 2px solid var(--bg-grey);">
                                    <input type="hidden" name="option_id" value="<?= $option['id'] ?>">
                                    <?= h($option['option_text']) ?>
                                </button>
                            <?php endforeach; ?>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Interaction Bar -->
            <div class="interaction-bar">
                <form method="POST" style="display: inline;">
                    <?php if ($user_liked): ?>
                        <button type="submit" name="unlike" class="btn-interact btn-liked">
                            ❤️ Đã thích (<?= $like_count ?>)
                        </button>
                    <?php else: ?>
                        <button type="submit" name="like" class="btn-interact">
                            🤍 Thích (<?= $like_count ?>)
                        </button>
                    <?php endif; ?>
                </form>

                <button class="btn-interact" onclick="document.getElementById('comment-form').scrollIntoView({behavior: 'smooth'})">
                    💬 Bình luận (<?= count($comments) ?>)
                </button>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comments-section" id="comments-section">
            <h2 style="color: var(--primary-mint); margin-bottom: 1rem;">💬 Bình luận</h2>

            <?php if (isLoggedIn()): ?>
                <div class="comment-form" id="comment-form">
                    <form method="POST">
                        <textarea name="comment_content" placeholder="Chia sẻ suy nghĩ của bạn..." required></textarea>
                        <button type="submit" name="add_comment" class="btn-submit">Gửi bình luận</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="comment-form">
                    <p style="text-align: center; color: #636e72;">
                        <a href="login.php" style="color: var(--primary-mint); font-weight: bold;">Đăng nhập</a>
                        để bình luận
                    </p>
                </div>
            <?php endif; ?>

            <!-- Display Comments -->
            <?php foreach ($comments as $comment):
                $replies = getComments($pdo, $post_id, $comment['id']);
                $reply_count = count($replies);
            ?>
                <div class="comment comment-parent" id="comment-<?= $comment['id'] ?>">
                    <div class="comment-header">
                        <span class="comment-author">
                            👤 <?= h($comment['ho_ten']) ?>
                            <span style="color: #636e72; font-weight: normal;">@<?= h($comment['username']) ?></span>
                            <?php if ($reply_count > 0): ?>
                                <span class="reply-indicator">💬 <?= $reply_count ?> trả lời</span>
                            <?php endif; ?>
                        </span>
                        <span class="comment-time"><?= timeAgo($comment['created_at']) ?></span>
                    </div>
                    <div class="comment-content">
                        <?= nl2br(formatMentions(h($comment['content']))) ?>
                    </div>

                    <div class="comment-actions">
                        <?php if (isLoggedIn()): ?>
                            <button class="btn-reply" onclick="showReplyForm(<?= $comment['id'] ?>)">↩️ Trả lời</button>
                        <?php endif; ?>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <div id="reply-form-<?= $comment['id'] ?>" style="display: none; margin-top: 1rem;">
                            <form method="POST">
                                <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                                <textarea name="comment_content" placeholder="💭 Viết câu trả lời cho <?= h($comment['ho_ten']) ?>..." required style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 2px solid var(--warning-yellow); font-family: inherit;"></textarea>
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                    <button type="submit" name="add_comment" class="btn-submit">✉️ Gửi trả lời</button>
                                    <button type="button" onclick="showReplyForm(<?= $comment['id'] ?>)" style="padding: 0.75rem 1.5rem; background: #dfe6e9; color: #2d3436; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Hủy</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Nested Replies -->
                    <?php if ($reply_count > 0): ?>
                        <div style="margin-top: 1rem;">
                            <?php foreach ($replies as $reply): ?>
                                <div class="comment reply">
                                    <div class="comment-header">
                                        <span class="comment-author">
                                            ↳ <?= h($reply['ho_ten']) ?>
                                            <span style="color: #636e72; font-weight: normal;">@<?= h($reply['username']) ?></span>
                                            <span style="color: #95a5a6; font-size: 0.8rem; margin-left: 0.5rem;">→ trả lời <?= h($comment['ho_ten']) ?></span>
                                        </span>
                                        <span class="comment-time"><?= timeAgo($reply['created_at']) ?></span>
                                    </div>
                                    <div class="comment-content">
                                        <?= nl2br(formatMentions(h($reply['content']))) ?>
                                    </div>

                                    <div class="comment-actions">
                                        <?php if (isLoggedIn()): ?>
                                            <button class="btn-reply" onclick="showReplyForm(<?= $reply['id'] ?>, true, '<?= h($reply['ho_ten']) ?>')">↩️ Trả lời</button>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (isLoggedIn()): ?>
                                        <div id="reply-form-<?= $reply['id'] ?>" style="display: none; margin-top: 1rem;">
                                            <form method="POST">
                                                <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                                                <textarea name="comment_content" placeholder="💭 Trả lời @<?= h($reply['username']) ?>..." required style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 2px solid var(--warning-yellow); font-family: inherit;"></textarea>
                                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                                    <button type="submit" name="add_comment" class="btn-submit">✉️ Gửi trả lời</button>
                                                    <button type="button" onclick="showReplyForm(<?= $reply['id'] ?>)" style="padding: 0.75rem 1.5rem; background: #dfe6e9; color: #2d3436; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Hủy</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (empty($comments)): ?>
                <div style="text-align: center; padding: 3rem; color: #636e72;">
                    <div style="font-size: 3rem;">💭</div>
                    <p>Chưa có bình luận nào. Hãy là người đầu tiên!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Post Modal -->
    <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
        <div id="editModal" class="modal">
            <div class="modal-content">
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
                <h2 style="color: var(--primary-mint); margin-bottom: 1.5rem;">✏️ Chỉnh sửa bài viết</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Tiêu đề</label>
                        <input type="text" name="title" value="<?= h($post['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nội dung</label>
                        <textarea name="content" required><?= h($post['content']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Tags</label>
                        <input type="text" name="tags" value="<?= h($post['tags']) ?>" placeholder="HTML, CSS, JavaScript hoặc HTML CSS JavaScript">
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="edit_post" class="btn-submit" style="flex: 1;">💾 Lưu thay đổi</button>
                        <button type="button" onclick="closeEditModal()" style="flex: 1; background: #dfe6e9; color: #2d3436; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 600; cursor: pointer;">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <footer style="text-align: center; padding: 2rem; margin-top: 3rem; background: var(--bg-grey); border-radius: 15px;">
        <p style="color: #636e72; margin: 0;">
            📝 Bài viết được tạo bởi <strong><?= h($post['ho_ten']) ?></strong> •
            <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
        </p>
    </footer>

    <script src="../assets/js/post.js"></script>
</body>

</html>