<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/helpers.php';
require_once '../includes/notification_helpers.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get category_id from URL (if creating from category page)
$category_id_from_url = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// Get category info if creating from category page
$category = null;
if ($category_id_from_url) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id_from_url]);
    $category = $stmt->fetch();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    // Get category_id from POST or from URL
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : $category_id_from_url;

    // Process tags: split by comma/space, trim, add # prefix if not present
    $tags_input = trim($_POST['tags']);
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

    $privacy = $_POST['privacy'];

    // Validation
    if (empty($title) || empty($content)) {
        $error = 'Vui lòng nhập đầy đủ tiêu đề và nội dung';
    } else {
        try {
            $pdo->beginTransaction();

            // Insert post with category_id
            $stmt = $pdo->prepare("
                INSERT INTO posts (user_id, category_id, title, content, tags, privacy, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'unsolved')
            ");
            $stmt->execute([$_SESSION['user_id'], $category_id, $title, $content, $tags, $privacy]);
            $post_id = $pdo->lastInsertId();

            // Check for mentions and create notifications
            notifyMentions($pdo, $content, $_SESSION['user_id'], 'post', $post_id, $post_id);

            // Handle file uploads
            if (isset($_FILES['attachments'])) {
                foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['attachments']['name'][$key],
                            'type' => $_FILES['attachments']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['attachments']['error'][$key],
                            'size' => $_FILES['attachments']['size'][$key]
                        ];

                        $result = handleUpload($file);
                        if ($result['success']) {
                            $stmt = $pdo->prepare("
                                INSERT INTO attachments (post_id, file_path, file_type) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$post_id, $result['path'], $result['type']]);
                        }
                    }
                }
            }

            // Handle poll creation
            if (!empty($_POST['poll_question'])) {
                $poll_question = trim($_POST['poll_question']);
                $poll_type = isset($_POST['poll_type']) ? $_POST['poll_type'] : 'single'; // single or multiple
                $poll_options = array_filter(array_map('trim', $_POST['poll_options']));

                if (count($poll_options) >= 2) {
                    // Insert poll with type
                    $stmt = $pdo->prepare("INSERT INTO polls (post_id, question, poll_type) VALUES (?, ?, ?)");
                    $stmt->execute([$post_id, $poll_question, $poll_type]);
                    $poll_id = $pdo->lastInsertId();

                    // Insert options
                    $stmt = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
                    foreach ($poll_options as $option) {
                        $stmt->execute([$poll_id, $option]);
                    }
                }
            }

            $pdo->commit();
            $success = 'Bài viết đã được tạo thành công!';

            // Redirect to homepage after 1 second
            header("refresh:1;url=../index.php");
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài viết - Diễn đàn sinh viên</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/create_post.css">
</head>

<body>

    </head>

    <body>
        <?php include '../includes/navbar.php'; ?>

        <div class="container">
            <div class="create-header">
                <h1>✍️ Tạo bài viết mới</h1>
                <p>Chia sẻ câu hỏi, kinh nghiệm hoặc thảo luận với cộng đồng</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= h($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3>📝 Thông tin cơ bản</h3>

                    <?php if ($category): ?>
                        <!-- Category is pre-selected from URL -->
                        <div style="background: linear-gradient(135deg, var(--primary-mint) 0%, #2ecc71 100%); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                            <strong>Danh mục:</strong> <?= h($category['icon']) ?> <?= h($category['name']) ?>
                            <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Tiêu đề *</label>
                        <input type="text" name="title" required placeholder="Ví dụ: Làm thế nào để tạo animation với CSS?">
                    </div>

                    <div class="form-group">
                        <label>Nội dung * (Hỗ trợ @username để mention)</label>
                        <textarea id="create-post-content" name="content" required placeholder="Mô tả chi tiết câu hỏi hoặc nội dung của bạn... (Gõ @ để mention người dùng)"></textarea>

                        <!-- Tags -->
                        <div class="form-section">
                            <h3>🏷️ Hashtag</h3>
                            <div class="form-group">
                                <label>Nhập tag</label>
                                <input type="text" name="tags" class="tag-input" placeholder="Ví dụ: php, mysql, web hoặc php mysql web">
                            </div>
                        </div>

                        <!-- Privacy -->
                        <div class="form-section">
                            <h3>🔒 Quyền riêng tư</h3>
                            <div class="privacy-options">
                                <div class="privacy-option">
                                    <input type="radio" name="privacy" value="public" id="privacy-public" checked>
                                    <label for="privacy-public">
                                        <div style="font-size: 2rem;">🌍</div>
                                        <div>Công khai</div>
                                        <small>Mọi người đều có thể xem</small>
                                    </label>
                                </div>
                                <div class="privacy-option">
                                    <input type="radio" name="privacy" value="private" id="privacy-private">
                                    <label for="privacy-private">
                                        <div style="font-size: 2rem;">🔒</div>
                                        <div>Riêng tư</div>
                                        <small>Chỉ bạn và admin xem được</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="form-section">
                            <h3>📎 Đính kèm file (Ảnh/PDF)</h3>
                            <div class="form-group">
                                <label class="file-upload" for="file-input">
                                    <div style="font-size: 3rem;">📤</div>
                                    <div>Click để chọn file hoặc kéo thả vào đây</div>
                                    <small>Hỗ trợ: JPG, PNG, GIF, PDF (Max 5MB mỗi file)</small>
                                </label>
                                <input type="file" name="attachments[]" id="file-input" multiple accept="image/*,.pdf" style="display: none;">
                                <div id="image-preview" class="image-preview-container"></div>
                            </div>
                        </div>

                        <!-- Poll (Optional) -->
                        <div class="form-section">
                            <h3>📊 Tạo khảo sát (Tùy chọn)</h3>

                            <div class="form-group">
                                <label>Câu hỏi khảo sát</label>
                                <input type="text" name="poll_question" placeholder="Ví dụ: Framework nào bạn thích nhất?">
                            </div>

                            <div class="form-group">
                                <label>Loại khảo sát</label>
                                <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                        <input type="radio" name="poll_type" value="single" checked>
                                        <span>Chọn một</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                        <input type="radio" name="poll_type" value="multiple">
                                        <span>Chọn nhiều</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Các lựa chọn (Tối thiểu 2)</label>
                                <div class="poll-options-container" id="poll-options">
                                    <input type="text" name="poll_options[]" placeholder="Lựa chọn 1" class="form-control">
                                    <input type="text" name="poll_options[]" placeholder="Lựa chọn 2" class="form-control">
                                </div>
                                <button type="button" class="btn-add-option" onclick="addPollOption()" style="margin-top: 0.5rem;">+ Thêm lựa chọn</button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">🚀 Đăng bài viết</button>
            </form>
        </div>

        <script src="../assets/js/create_post.js"></script>
    </body>

</html>
<script>
    // Initialize mention autocomplete for create post
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initMentionAutocomplete === 'function') {
            const textarea = document.getElementById('create-post-content');
            if (textarea) {
                initMentionAutocomplete(textarea);
            }
        }
    });
</script>
</body>

</html>