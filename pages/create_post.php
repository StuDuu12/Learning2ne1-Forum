<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/helpers.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề bài viết';
    } elseif (strlen($title) < 5) {
        $error = 'Tiêu đề phải có ít nhất 5 ký tự';
    } elseif (strlen($title) > 200) {
        $error = 'Tiêu đề không được vượt quá 200 ký tự';
    }

    if (empty($content)) {
        $error = 'Vui lòng nhập nội dung bài viết';
    } elseif (strlen($content) < 10) {
        $error = 'Nội dung phải có ít nhất 10 ký tự';
    } elseif (strlen($content) > 5000) {
        $error = 'Nội dung không được vượt quá 5000 ký tự';
    }

    $tags_input = trim($_POST['tags'] ?? '');
    if (!empty($tags_input)) {
        if (strlen($tags_input) > 100) {
            $error = 'Tag không được vượt quá 100 ký tự';
        } else {
            $tags_array = preg_split('/[,\s]+/', $tags_input);
            $tags_array = array_filter(array_map('trim', $tags_array));

            if (count($tags_array) > 5) {
                $error = 'Không được nhập quá 5 tag';
            } else {
                $tags_array = array_map(function ($tag) {
                    return (strpos($tag, '#') !== 0) ? '#' . $tag : $tag;
                }, $tags_array);
                $tags = implode(',', $tags_array);
            }
        }
    } else {
        $tags = '';
    }

    $privacy = $_POST['privacy'] ?? 'public';
    if (!in_array($privacy, ['public', 'private'])) {
        $error = 'Quyền riêng tư không hợp lệ';
    }

    if (isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $allowed_types = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        $max_file_size = 100 * 1024 * 1024;

        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['attachments']['error'][$key] !== UPLOAD_ERR_OK && $_FILES['attachments']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $error = 'Có lỗi khi upload file: ' . $_FILES['attachments']['name'][$key];
                break;
            }

            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                if ($_FILES['attachments']['size'][$key] > $max_file_size) {
                    $error = 'File ' . $_FILES['attachments']['name'][$key] . ' vượt quá 100MB';
                    break;
                }

                if (!in_array($_FILES['attachments']['type'][$key], $allowed_types)) {
                    $error = 'File ' . $_FILES['attachments']['name'][$key] . ' không được hỗ trợ. Chỉ hỗ trợ: ảnh, PDF, Word, Excel';
                    break;
                }
            }
        }
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO posts (user_id, title, content, tags, privacy, status)
                VALUES (?, ?, ?, ?, ?, 'unsolved')
            ");
            $stmt->execute([$_SESSION['user_id'], $title, $content, $tags, $privacy]);
            $post_id = $pdo->lastInsertId();

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

            $pdo->commit();
            $success = 'Bài viết đã được tạo thành công!';
            header("refresh:1;url=../index.php");
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài viết - Diễn đàn sinh viên</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/create_post.css">
    <link href="https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="create-page">
        <div class="create-post-wrapper container">
            <div class="create-header">
                <h1><i class='bx bx-edit'></i> Tạo bài viết mới</h1>
                <p>Chia sẻ câu hỏi, kinh nghiệm hoặc thảo luận với cộng đồng</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class='bx bx-error-circle'></i> <?= h($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class='bx bx-check-circle'></i> <?= h($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-section">
                    <h3><i class='bx bx-info-circle'></i> Thông tin cơ bản</h3>

                    <div class="form-group">
                        <label><i class='bx bx-heading'></i> Tiêu đề bài viết</label>
                        <input type="text" name="title" required minlength="5" maxlength="200" placeholder="Ví dụ: Làm thế nào để tạo animation với CSS?">
                        <small class="field-hint">Từ 5-200 ký tự</small>
                    </div>

                    <div class="form-group">
                        <label><i class='bx bx-text'></i> Nội dung bài viết</label>
                        <textarea name="content" required minlength="10" maxlength="5000" placeholder="Mô tả chi tiết câu hỏi hoặc nội dung của bạn..."></textarea>
                        <small class="field-hint">Từ 10-5000 ký tự</small>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class='bx bx-hashtag'></i> Hashtag</h3>
                    <div class="form-group">
                        <label><i class='bx bx-tag'></i> Nhập tag</label>
                        <input type="text" name="tags" maxlength="200" placeholder="Ví dụ: php, mysql, web hoặc php mysql web">
                        <small class="field-hint">Tối đa 10 tag, 200 ký tự</small>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class='bx bx-lock-alt'></i> Quyền riêng tư</h3>
                    <div class="privacy-options">
                        <div class="privacy-option">
                            <input type="radio" name="privacy" value="public" id="privacy-public" checked>
                            <label for="privacy-public" class="privacy-label">
                                <div class="privacy-icon"><i class="bx bx-globe"></i></div>
                                <div>
                                    <div class="privacy-title">Công khai</div>
                                    <small>Mọi người đều có thể xem</small>
                                </div>
                            </label>
                        </div>
                        <div class="privacy-option">
                            <input type="radio" name="privacy" value="private" id="privacy-private">
                            <label for="privacy-private" class="privacy-label">
                                <div class="privacy-icon"><i class="bx bx-lock"></i></div>
                                <div>
                                    <div class="privacy-title">Riêng tư</div>
                                    <small>Chỉ bạn và admin xem được</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class='bx bx-paperclip'></i> File đính kèm</h3>
                    <div class="form-group">
                        <label class="file-upload" for="file-input">
                            <div class="file-upload-icon"><i class='bx  bx-arrow-in-up-square-half'></i></div>
                            <div class="file-upload-text">Click để chọn file hoặc kéo thả vào đây</div>
                            <div><small>Hỗ trợ: Ảnh, PDF, Word, Excel... (Tối đa 100MB mỗi file)</small></div>
                        </label>
                        <input type="file" name="attachments[]" id="file-input" multiple style="display: none;">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="../index.php" class="btn-secondary">
                        <i class='bx bx-arrow-back'></i> Quay lại
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class='bx bx-rocket'></i> Đăng bài viết
                    </button>
                </div>
            </form>
        </div>
    </div>


</body>

</html>