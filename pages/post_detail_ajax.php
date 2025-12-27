<?php
// This file is included by ajax.php, $post_id is already set
// $pdo, functions are already loaded

$current_user = getCurrentUser($pdo);

// Get post
$post = getPost($pdo, $post_id);

if (!$post) {
    echo '<div style="text-align: center; padding: 3rem;"><h3>❌ Bài viết không tồn tại</h3></div>';
    return;
}

// Check privacy
if ($post['privacy'] === 'private') {
    if (!isLoggedIn() || ($_SESSION['user_id'] != $post['user_id'] && $_SESSION['account_level'] != 0)) {
        echo '<div style="text-align: center; padding: 3rem;"><h3>🔒 Bạn không có quyền xem bài viết này</h3></div>';
        return;
    }
}

// Track view and interest if logged in
if (isLoggedIn() && isset($_SESSION['user_id'])) {
    incrementViews($pdo, $post_id);
    // Chỉ track interest nếu user_id hợp lệ
    if ($_SESSION['user_id'] > 0) {
        trackInterests($pdo, $_SESSION['user_id'], $post['tags']);
    }
}

// Get attachments, poll, comments
$attachments = getAttachments($pdo, $post_id);
$poll = getPoll($pdo, $post_id);
$comments = getComments($pdo, $post_id);
$like_count = getLikeCount($pdo, $post_id, 'post');
$user_liked = isLoggedIn() ? hasLiked($pdo, $_SESSION['user_id'], $post_id, 'post') : false;
?>

<style>
    .post-detail-modal {
        font-family: inherit;
    }

    .post-detail-modal .post-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .post-detail-modal .author-section {
        display: flex;
        gap: 1rem;
    }

    .post-detail-modal .author-avatar {
        width: 60px;
        height: 60px;
        background: var(--primary-mint);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .post-detail-modal .post-title {
        font-size: 1.75rem;
        margin-bottom: 1rem;
        color: var(--text-dark);
    }

    .post-detail-modal .post-content {
        line-height: 1.8;
        margin: 1.5rem 0;
        white-space: pre-wrap;
    }

    .post-detail-modal .attachments {
        margin: 1.5rem 0;
    }

    .post-detail-modal .attachment-item img {
        max-width: 100%;
        border-radius: 8px;
        margin: 0.5rem 0;
    }

    .post-detail-modal .post-actions {
        padding: 1rem 0;
        border-top: 2px solid var(--bg-grey);
        border-bottom: 2px solid var(--bg-grey);
        display: flex;
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .post-detail-modal .btn-action {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .post-detail-modal .btn-like-post {
        background: #ff6b6b;
        color: white;
    }

    .post-detail-modal .btn-like-post.liked {
        background: #ee5a6f;
    }

    .post-detail-modal .comment {
        background: var(--bg-grey);
        padding: 1rem;
        border-radius: 8px;
        margin: 0.5rem 0;
    }

    .post-detail-modal .comment.reply {
        margin-left: 2rem;
        background: white;
        border: 2px solid var(--bg-grey);
    }
</style>

<div class="post-detail-modal">
    <div class="post-header">
        <div class="author-section">
            <div class="author-avatar">
                <?= strtoupper(mb_substr($post['ho_ten'], 0, 1)) ?>
            </div>
            <div>
                <a href="<?= $base_url ?>/pages/profile.php?username=<?= urlencode($post['username']) ?>" style="text-decoration: none; color: inherit;" onmouseover="this.style.color='var(--primary-mint)'" onmouseout="this.style.color='inherit'">
                    <h3 style="margin: 0;"><?= h($post['ho_ten']) ?></h3>
                </a>
                <div style="color: #636e72; font-size: 0.9rem;">
                    @<?= h($post['username']) ?> •
                    <?= timeAgo($post['created_at']) ?> •
                    👁️ <?= $post['views'] ?> lượt xem
                </div>
            </div>
        </div>
        <span class="post-status status-<?= $post['status'] ?>" style="padding: 0.5rem 1rem; border-radius: 20px; height: fit-content; <?= $post['status'] === 'solved' ? 'background: #00b894; color: white;' : 'background: #fdcb6e; color: #2d3436;' ?>">
            <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : '❓ Chưa giải quyết' ?>
        </span>
    </div>

    <h1 class="post-title"><?= h($post['title']) ?></h1>

    <?php if ($post['tags']): ?>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
            <?php foreach (explode(',', $post['tags']) as $tag): ?>
                <span style="background: var(--bg-grey); padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.85rem; color: var(--primary-mint); font-weight: 600;">
                    <?= h(trim($tag)) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="post-content"><?= nl2br(formatMentions(h($post['content']))) ?></div>

    <?php if (!empty($attachments)): ?>
        <div class="attachments">
            <h3>📎 File đính kèm:</h3>
            <?php foreach ($attachments as $att): ?>
                <div class="attachment-item" style="margin: 1rem 0;">
                    <?php if (strpos($att['file_type'], 'image') !== false): ?>
                        <img src="<?= h($att['file_path']) ?>" alt="Attachment" style="max-width: 100%; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <?php else: ?>
                        <a href="<?= h($att['file_path']) ?>" target="_blank" style="color: var(--primary-mint); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: var(--bg-grey); border-radius: 8px;">
                            📄 Download <?= pathinfo($att['file_path'], PATHINFO_EXTENSION) ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($poll):
        $poll_type = $poll['poll_type'] ?? 'single';
        $input_type = $poll_type === 'multiple' ? 'checkbox' : 'radio';
    ?>
        <div style="background: var(--bg-grey); padding: 1.5rem; border-radius: 12px; margin: 1.5rem 0;">
            <h3 style="color: var(--primary-mint); margin-bottom: 0.5rem;">📊 <?= h($poll['question']) ?></h3>
            <p style="font-size: 0.85rem; color: #636e72; margin-bottom: 1rem;">
                <?= $poll_type === 'multiple' ? '☑️ Có thể chọn nhiều' : '◉ Chỉ chọn một' ?>
            </p>

            <?php if (isLoggedIn() && !hasVoted($pdo, $_SESSION['user_id'], $poll['id'])): ?>
                <!-- Voting Form -->
                <div id="poll-voting-<?= $poll['id'] ?>">
                    <form id="modal-poll-form-<?= $poll['id'] ?>" onsubmit="return submitModalPollVote(event, <?= $poll['id'] ?>);">
                        <?php foreach ($poll['options'] as $option): ?>
                            <div style="margin: 0.75rem 0;">
                                <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: white; border: 2px solid var(--bg-grey); border-radius: 8px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--primary-mint)'" onmouseout="this.style.borderColor='var(--bg-grey)'">
                                    <input type="<?= $input_type ?>" name="poll_options[]" value="<?= $option['id'] ?>" style="width: 20px; height: 20px; cursor: pointer;">
                                    <span style="font-weight: 600;"><?= h($option['option_text']) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" style="width: 100%; padding: 0.75rem; margin-top: 1rem; background: var(--primary-mint); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#00a37a'" onmouseout="this.style.background='var(--primary-mint)'">
                            ✓ Gửi câu trả lời
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Poll Results -->
                <?php
                $total_votes = array_sum(array_column($poll['options'], 'vote_count'));
                foreach ($poll['options'] as $option):
                    $percentage = $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0;

                    // Get voters for this option
                    $stmt_voters = $pdo->prepare("
                        SELECT u.ho_ten, u.username 
                        FROM poll_votes pv
                        JOIN user u ON pv.user_id = u.id_user
                        WHERE pv.option_id = ?
                        ORDER BY pv.created_at DESC
                        LIMIT 5
                    ");
                    $stmt_voters->execute([$option['id']]);
                    $voters = $stmt_voters->fetchAll();
                ?>
                    <div style="margin: 0.75rem 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                            <span style="font-weight: 600;"><?= h($option['option_text']) ?></span>
                            <span style="color: #636e72;"><?= $option['vote_count'] ?> phiếu (<?= $percentage ?>%)</span>
                        </div>
                        <div style="background: white; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: var(--primary-mint); height: 100%; width: <?= $percentage ?>%; transition: width 0.3s;"></div>
                        </div>
                        <?php if (!empty($voters)): ?>
                            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #636e72;">
                                <span style="font-weight: 600;">👥 </span>
                                <?php
                                $voter_names = array_map(function ($v) {
                                    return h($v['ho_ten']);
                                }, $voters);
                                echo implode(', ', $voter_names);
                                if ($option['vote_count'] > count($voters)) {
                                    echo ' và ' . ($option['vote_count'] - count($voters)) . ' người khác';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <p style="text-align: right; color: #636e72; font-size: 0.9rem; margin-top: 1rem;">
                    Tổng: <?= $total_votes ?> phiếu bầu
                </p>
            <?php endif; ?>
        </div>

        <script>
            window.submitModalPollVote = function(event, pollId) {
                event.preventDefault();

                const form = event.target;
                const formData = new FormData(form);
                const selectedOptions = formData.getAll('poll_options[]');

                if (selectedOptions.length === 0) {
                    alert('Vui lòng chọn ít nhất một lựa chọn');
                    return false;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Đang gửi...';

                // Submit all selected options
                Promise.all(selectedOptions.map(optionId =>
                        fetch('../includes/ajax.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'action=vote_poll&option_id=' + optionId
                        }).then(res => res.json())
                    ))
                    .then(results => {
                        const anySuccess = results.some(r => r.success);
                        if (anySuccess) {
                            // Reload modal content
                            if (typeof openPostModal === 'function' && currentPostId) {
                                openPostModal(currentPostId);
                            } else {
                                location.reload();
                            }
                        } else {
                            throw new Error(results[0].message || 'Có lỗi xảy ra');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert(error.message || 'Có lỗi xảy ra khi vote');
                        submitBtn.disabled = false;
                        submitBtn.textContent = '✓ Gửi câu trả lời';
                    });

                return false;
            };

            function votePoll(optionId) {
                fetch('includes/ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=vote_poll&option_id=' + optionId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload modal to show results
                            openPostModal(<?= $post_id ?>);
                        } else {
                            alert(data.message || 'Có lỗi xảy ra');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra');
                    });
            }
        </script>
    <?php endif; ?>

    <div class="post-actions">
        <?php if (isLoggedIn()): ?>
            <button class="btn-action btn-like-post <?= $user_liked ? 'liked' : '' ?>" onclick="likePostInModal(<?= $post_id ?>, this)">
                ❤️ <span class="like-count-modal"><?= $like_count ?></span>
            </button>
        <?php else: ?>
            <button class="btn-action" disabled title="Đăng nhập để thích">
                ❤️ <?= $like_count ?>
            </button>
        <?php endif; ?>
        <button class="btn-action" style="background: #dfe6e9; color: #2d3436;">
            👁️ <?= $post['views'] ?> lượt xem
        </button>
    </div>

    <!-- Comments Section -->
    <div id="comments-section" style="margin-top: 2rem;">
        <h2 style="color: var(--primary-mint);">💬 Bình luận (<?= count($comments) ?>)</h2>

        <?php if (isLoggedIn()): ?>
            <form method="POST" action="pages/post.php?id=<?= $post_id ?>" style="margin: 1rem 0;">
                <textarea id="main-comment-textarea" name="comment_content" placeholder="Chia sẻ suy nghĩ của bạn... (Gõ @ để mention người dùng)" required style="width: 100%; padding: 0.75rem; border: 2px solid var(--bg-grey); border-radius: 8px; min-height: 80px;"></textarea>
                <button type="submit" name="add_comment" class="btn-action" style="background: var(--primary-mint); color: white; margin-top: 0.5rem;">Gửi bình luận</button>
            </form>
            <script>
                setTimeout(function() {
                    console.log('Trying to init mention for main comment textarea...');
                    console.log('initMentionAutocomplete exists?', typeof initMentionAutocomplete);
                    if (typeof initMentionAutocomplete === 'function') {
                        const textarea = document.getElementById('main-comment-textarea');
                        console.log('Textarea found?', textarea);
                        if (textarea) {
                            initMentionAutocomplete(textarea);
                        } else {
                            console.error('Textarea main-comment-textarea not found!');
                        }
                    } else {
                        console.error('initMentionAutocomplete function not found!');
                    }
                }, 100);
            </script>
        <?php else: ?>
            <p style="text-align: center; color: #636e72;">
                Vui lòng <a href="pages/login.php" style="color: var(--primary-mint); font-weight: bold;">đăng nhập</a> để bình luận
            </p>
        <?php endif; ?>

        <!-- Display Comments -->
        <?php foreach ($comments as $comment):
            $replies = getComments($pdo, $post_id, $comment['id']);
        ?>
            <div class="comment" id="comment-<?= $comment['id'] ?>">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <a href="<?= $base_url ?>/pages/profile.php?username=<?= urlencode($comment['username']) ?>" style="text-decoration: none; font-weight: bold;" onmouseover="this.style.color='var(--primary-mint)'" onmouseout="this.style.color='var(--primary-mint)'">
                        <strong style="color: var(--primary-mint);"><?= h($comment['ho_ten']) ?></strong>
                    </a>
                    <span style="color: #636e72; font-size: 0.85rem;"><?= timeAgo($comment['created_at']) ?></span>
                </div>
                <div id="comment-content-<?= $comment['id'] ?>"><?= nl2br(parseMentions(h($comment['content']))) ?></div>
                <div id="edit-form-<?= $comment['id'] ?>" style="display: none; margin-top: 0.5rem;">
                    <textarea id="edit-textarea-<?= $comment['id'] ?>" style="width: 100%; padding: 0.5rem; border: 2px solid var(--bg-grey); border-radius: 8px; min-height: 60px;"><?= h($comment['content']) ?></textarea>
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                        <button onclick="saveEditComment(<?= $comment['id'] ?>)" class="btn-action" style="background: var(--primary-mint); color: white; padding: 0.5rem 1rem;">Lưu</button>
                        <button onclick="cancelEditComment(<?= $comment['id'] ?>)" class="btn-action" style="background: #dfe6e9; color: #2d3436; padding: 0.5rem 1rem;">Hủy</button>
                    </div>
                </div>

                <?php if (isLoggedIn()): ?>
                    <div style="margin-top: 0.5rem;">
                        <?php if ($_SESSION['user_id'] != $comment['user_id']): ?>
                            <button onclick="toggleReplyForm(<?= $comment['id'] ?>)" style="background: none; border: none; color: var(--primary-mint); cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                💬 Trả lời
                            </button>
                        <?php endif; ?>
                        <?php if ($_SESSION['user_id'] == $comment['user_id']): ?>
                            <button onclick="editComment(<?= $comment['id'] ?>)" style="background: none; border: none; color: #f39c12; cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                ✏️ Sửa
                            </button>
                            <button onclick="deleteComment(<?= $comment['id'] ?>)" style="background: none; border: none; color: #e74c3c; cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                🗑️ Xóa
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Reply Form -->
                    <div id="reply-form-<?= $comment['id'] ?>" style="display: none; margin-top: 1rem; padding-left: 1rem; border-left: 3px solid var(--primary-mint);">
                        <form method="POST" action="pages/post.php?id=<?= $post_id ?>" onsubmit="return submitReply(event, <?= $comment['id'] ?>, <?= $post_id ?>)">
                            <textarea id="reply-textarea-<?= $comment['id'] ?>" name="comment_content" placeholder="Viết phản hồi... (Gõ @ để mention người dùng)" required style="width: 100%; padding: 0.5rem; border: 2px solid var(--bg-grey); border-radius: 8px; min-height: 60px;"></textarea>
                            <script>
                                setTimeout(function() {
                                    if (typeof initMentionAutocomplete === 'function') {
                                        const textarea = document.getElementById('reply-textarea-<?= $comment['id'] ?>');
                                        if (textarea) {
                                            initMentionAutocomplete(textarea);
                                        }
                                    }
                                }, 100);
                            </script>
                            <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                <button type="submit" name="add_comment" class="btn-action" style="background: var(--primary-mint); color: white; padding: 0.5rem 1rem;">Gửi</button>
                                <button type="button" onclick="toggleReplyForm(<?= $comment['id'] ?>)" class="btn-action" style="background: #dfe6e9; color: #2d3436; padding: 0.5rem 1rem;">Hủy</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Replies -->
                <div id="replies-<?= $comment['id'] ?>">
                    <?php foreach ($replies as $reply): ?>
                        <div class="comment reply" id="comment-<?= $reply['id'] ?>">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <a href="<?= $base_url ?>/pages/profile.php?username=<?= urlencode($reply['username']) ?>" style="text-decoration: none; font-weight: bold;" onmouseover="this.style.color='var(--primary-mint)'" onmouseout="this.style.color='var(--primary-mint)'">
                                    <strong style="color: var(--primary-mint);">↳ <?= h($reply['ho_ten']) ?></strong>
                                </a>
                                <span style="color: #636e72; font-size: 0.85rem;"><?= timeAgo($reply['created_at']) ?></span>
                            </div>
                            <div id="comment-content-<?= $reply['id'] ?>"><?= nl2br(parseMentions(h($reply['content']))) ?></div>
                            <div id="edit-form-<?= $reply['id'] ?>" style="display: none; margin-top: 0.5rem;">
                                <textarea id="edit-textarea-<?= $reply['id'] ?>" style="width: 100%; padding: 0.5rem; border: 2px solid var(--bg-grey); border-radius: 8px; min-height: 60px;"><?= h($reply['content']) ?></textarea>
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                    <button onclick="saveEditComment(<?= $reply['id'] ?>)" class="btn-action" style="background: var(--primary-mint); color: white; padding: 0.5rem 1rem;">Lưu</button>
                                    <button onclick="cancelEditComment(<?= $reply['id'] ?>)" class="btn-action" style="background: #dfe6e9; color: #2d3436; padding: 0.5rem 1rem;">Hủy</button>
                                </div>
                            </div>

                            <?php if (isLoggedIn()): ?>
                                <div style="margin-top: 0.5rem;">
                                    <?php if ($_SESSION['user_id'] != $reply['user_id']): ?>
                                        <button onclick="toggleReplyForm(<?= $reply['id'] ?>)" style="background: none; border: none; color: var(--primary-mint); cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                            💬 Trả lời
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['user_id'] == $reply['user_id']): ?>
                                        <button onclick="editComment(<?= $reply['id'] ?>)" style="background: none; border: none; color: #f39c12; cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                            ✏️ Sửa
                                        </button>
                                        <button onclick="deleteComment(<?= $reply['id'] ?>)" style="background: none; border: none; color: #e74c3c; cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                            🗑️ Xóa
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Reply to Reply Form -->
                                <div id="reply-form-<?= $reply['id'] ?>" style="display: none; margin-top: 1rem; padding-left: 1rem; border-left: 3px solid var(--primary-mint);">
                                    <form method="POST" action="pages/post.php?id=<?= $post_id ?>" onsubmit="return submitReply(event, <?= $reply['id'] ?>, <?= $post_id ?>)">
                                        <textarea id="reply-textarea-<?= $reply['id'] ?>" name="comment_content" placeholder="Viết phản hồi... (Gõ @ để mention người dùng)" required style="width: 100%; padding: 0.5rem; border: 2px solid var(--bg-grey); border-radius: 8px; min-height: 60px;"></textarea>
                                        <script>
                                            setTimeout(function() {
                                                if (typeof initMentionAutocomplete === 'function') {
                                                    const textarea = document.getElementById('reply-textarea-<?= $reply['id'] ?>');
                                                    if (textarea) {
                                                        initMentionAutocomplete(textarea);
                                                    }
                                                }
                                            }, 100);
                                        </script>
                                        <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                            <button type="submit" name="add_comment" class="btn-action" style="background: var(--primary-mint); color: white; padding: 0.5rem 1rem;">Gửi</button>
                                            <button type="button" onclick="toggleReplyForm(<?= $reply['id'] ?>)" class="btn-action" style="background: #dfe6e9; color: #2d3436; padding: 0.5rem 1rem;">Hủy</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($comments)): ?>
            <div style="text-align: center; padding: 2rem; color: #636e72;">
                <div style="font-size: 3rem;">💭</div>
                <p>Chưa có bình luận nào. Hãy là người đầu tiên!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Functions defined in index.php global scope -->