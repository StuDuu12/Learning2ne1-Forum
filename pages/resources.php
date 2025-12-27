<?php
require_once '../config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = getCurrentUser($pdo);
if (!$current_user) {
    header('Location: login.php');
    exit;
}
$can_upload = ($current_user['account_level'] <= 1); // Admin or Teacher

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category']) && $can_upload) {
    $ten_danh_muc = trim($_POST['ten_danh_muc']);

    if (empty($ten_danh_muc)) {
        $error = "Vui lòng nhập tên danh mục";
    } else {
        $stmt = $pdo->prepare("INSERT INTO danh_muc_tai_lieu (ten_danh_muc) VALUES (?)");
        if ($stmt->execute([$ten_danh_muc])) {
            $success = "Tạo danh mục thành công!";
            // Refresh categories
            header("Location: resources.php");
            exit;
        } else {
            $error = "Có lỗi khi tạo danh mục";
        }
    }
}

// Handle category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category']) && $can_upload) {
    $id_danh_muc = (int)$_POST['id_danh_muc'];

    // Check if category has resources
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tai_lieu WHERE id_danh_muc = ?");
    $stmt->execute([$id_danh_muc]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $error = "Không thể xóa danh mục đang có tài liệu. Vui lòng xóa tài liệu trước.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM danh_muc_tai_lieu WHERE id_danh_muc = ?");
        if ($stmt->execute([$id_danh_muc])) {
            $success = "Xóa danh mục thành công!";
            header("Location: resources.php");
            exit;
        } else {
            $error = "Có lỗi khi xóa danh mục";
        }
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource']) && $can_upload) {
    $tieu_de = trim($_POST['tieu_de']);
    $mo_ta = trim($_POST['mo_ta']);
    $id_danh_muc = (int)$_POST['id_danh_muc'];

    if (empty($tieu_de) || empty($id_danh_muc)) {
        $error = "Vui lòng điền đầy đủ thông tin";
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Vui lòng chọn file để upload";
    } else {
        $file = $_FILES['file'];
        $allowed_extensions = ['pdf', 'docx', 'doc', 'zip', 'pptx', 'ppt', 'xlsx', 'xls', 'txt', 'rar'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            $error = "Chỉ chấp nhận file: " . implode(', ', $allowed_extensions);
        } else {
            // Create upload directory if not exists
            $upload_dir = '../uploads/resources/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save to database
                $stmt = $pdo->prepare("
                    INSERT INTO tai_lieu (id_danh_muc, id_user, tieu_de, mo_ta, duong_dan_file, ngay_upload) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                if ($stmt->execute([$id_danh_muc, $_SESSION['user_id'], $tieu_de, $mo_ta, $filename])) {
                    $success = "Upload tài liệu thành công!";
                } else {
                    $error = "Có lỗi khi lưu vào database";
                    unlink($filepath); // Delete uploaded file
                }
            } else {
                $error = "Có lỗi khi upload file";
            }
        }
    }
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    $id_tai_lieu = (int)$_POST['id_tai_lieu'];

    // Check permission
    $stmt = $pdo->prepare("SELECT * FROM tai_lieu WHERE id_tai_lieu = ?");
    $stmt->execute([$id_tai_lieu]);
    $resource = $stmt->fetch();

    if ($resource && ($resource['id_user'] == $_SESSION['user_id'] || $current_user['account_level'] == 0)) {
        // Delete file
        $filepath = '../uploads/resources/' . $resource['duong_dan_file'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM tai_lieu WHERE id_tai_lieu = ?");
        if ($stmt->execute([$id_tai_lieu])) {
            $success = "Xóa tài liệu thành công!";
        } else {
            $error = "Có lỗi khi xóa tài liệu";
        }
    } else {
        $error = "Bạn không có quyền xóa tài liệu này";
    }
}

// Get all categories
$stmt = $pdo->query("SELECT * FROM danh_muc_tai_lieu ORDER BY ten_danh_muc ASC");
$categories = $stmt->fetchAll();

// Get selected category (default to first)
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : ($categories[0]['id_danh_muc'] ?? 0);

// Get resources by category
if ($selected_category > 0) {
    $stmt = $pdo->prepare("
        SELECT t.*, u.ho_ten, u.account_level
        FROM tai_lieu t
        JOIN user u ON t.id_user = u.id_user
        WHERE t.id_danh_muc = ?
        ORDER BY t.ngay_upload DESC
    ");
    $stmt->execute([$selected_category]);
    $resources = $stmt->fetchAll();
} else {
    $resources = [];
}

// Get category name
$category_name = '';
if ($selected_category > 0) {
    foreach ($categories as $cat) {
        if ($cat['id_danh_muc'] == $selected_category) {
            $category_name = $cat['ten_danh_muc'];
            break;
        }
    }
}

// File icon helper
function getFileIcon($filename)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'zip' => '🗜️',
        'rar' => '🗜️',
        'ppt' => '📊',
        'pptx' => '📊',
        'xls' => '📊',
        'xlsx' => '📊',
        'txt' => '📃'
    ];
    return $icons[$ext] ?? '📎';
}

function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chia sẻ tài liệu - Diễn đàn Sinh viên</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/resources.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="resources-container">
        <!-- Sidebar: Categories -->
        <aside class="sidebar">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0;">📚 Danh mục</h3>
                <?php if ($can_upload): ?>
                    <button onclick="document.getElementById('createCategoryModal').style.display='flex'"
                        style="background: var(--primary-mint); color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        ➕
                    </button>
                <?php endif; ?>
            </div>
            <ul class="category-list">
                <?php foreach ($categories as $cat): ?>
                    <li class="category-item">
                        <a href="?category=<?= $cat['id_danh_muc'] ?>"
                            class="category-link <?= $selected_category == $cat['id_danh_muc'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['ten_danh_muc']) ?>
                        </a>
                        <?php if ($can_upload): ?>
                            <form method="POST" onsubmit="return confirm('Xác nhận xóa danh mục này?')">
                                <input type="hidden" name="id_danh_muc" value="<?= $cat['id_danh_muc'] ?>">
                                <button type="submit" name="delete_category"
                                    style="background: none; border: none; color: #e74c3c; cursor: pointer; padding: 0.5rem; font-size: 1rem;"
                                    title="Xóa danh mục">
                                    🗑️
                                </button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Main Content: Resources Grid -->
        <main class="content-area">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="content-header">
                <h2><?= $category_name ? htmlspecialchars($category_name) : 'Chọn danh mục' ?></h2>
                <?php if ($can_upload): ?>
                    <button class="btn-upload" onclick="openUploadModal()">
                        ➕ Upload tài liệu
                    </button>
                <?php endif; ?>
            </div>

            <?php if (empty($resources)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>Chưa có tài liệu nào trong danh mục này</p>
                </div>
            <?php else: ?>
                <div class="resources-grid">
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-card">
                            <div class="resource-icon">
                                <?= getFileIcon($resource['duong_dan_file']) ?>
                            </div>
                            <h3 class="resource-title"><?= htmlspecialchars($resource['tieu_de']) ?></h3>

                            <?php if ($resource['mo_ta']): ?>
                                <p class="resource-meta"><?= htmlspecialchars($resource['mo_ta']) ?></p>
                            <?php endif; ?>

                            <div class="resource-uploader">
                                <span>👤 <?= htmlspecialchars($resource['ho_ten']) ?></span>
                                <?php
                                $role_class = ['badge-admin', 'badge-teacher', 'badge-student'][$resource['account_level']] ?? 'badge-student';
                                $role_name = ['Admin', 'GV', 'SV'][$resource['account_level']] ?? 'SV';
                                ?>
                                <span class="uploader-badge <?= $role_class ?>"><?= $role_name ?></span>
                            </div>

                            <div class="resource-meta">
                                📅 <?= date('d/m/Y', strtotime($resource['ngay_upload'])) ?>
                            </div>

                            <?php
                            $filepath = '../uploads/resources/' . $resource['duong_dan_file'];
                            if (file_exists($filepath)) {
                                $filesize = formatFileSize(filesize($filepath));
                                echo '<div class="resource-meta">💾 ' . $filesize . '</div>';
                            }
                            ?>

                            <div class="resource-actions">
                                <a href="../uploads/resources/<?= urlencode($resource['duong_dan_file']) ?>"
                                    download
                                    class="btn-download">
                                    ⬇️ Tải xuống
                                </a>

                                <?php if ($resource['id_user'] == $_SESSION['user_id'] || $current_user['account_level'] == 0): ?>
                                    <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa tài liệu này?')" style="margin: 0;">
                                        <input type="hidden" name="id_tai_lieu" value="<?= $resource['id_tai_lieu'] ?>">
                                        <button type="submit" name="delete_resource" class="btn-delete">🗑️</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Upload Modal -->
    <?php if ($can_upload): ?>
        <div class="modal" id="uploadModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>📤 Upload tài liệu mới</h3>
                    <button class="close-modal" onclick="closeUploadModal()">&times;</button>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="tieu_de">Tiêu đề *</label>
                        <input type="text" id="tieu_de" name="tieu_de" required>
                    </div>

                    <div class="form-group">
                        <label for="mo_ta">Mô tả</label>
                        <textarea id="mo_ta" name="mo_ta"></textarea>
                    </div>

                    <?php if ($selected_category > 0): ?>
                        <!-- Danh mục đã được chọn từ URL -->
                        <div style="background: linear-gradient(135deg, var(--primary-mint) 0%, #2ecc71 100%); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                            <strong>Danh mục:</strong> <?= htmlspecialchars($category_name) ?>
                            <input type="hidden" name="id_danh_muc" value="<?= $selected_category ?>">
                        </div>
                    <?php else: ?>
                        <!-- Hiển thị dropdown chọn danh mục -->
                        <div class="form-group">
                            <label for="id_danh_muc">Danh mục *</label>
                            <select id="id_danh_muc" name="id_danh_muc" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id_danh_muc'] ?>">
                                        <?= htmlspecialchars($cat['ten_danh_muc']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="file">File tài liệu *</label>
                        <input type="file" id="file" name="file" required
                            accept=".pdf,.doc,.docx,.zip,.rar,.ppt,.pptx,.xls,.xlsx,.txt">
                        <small style="color: #7f8c8d; display: block; margin-top: 0.5rem;">
                            Chấp nhận: PDF, DOC, DOCX, ZIP, RAR, PPT, PPTX, XLS, XLSX, TXT
                        </small>
                    </div>

                    <button type="submit" name="upload_resource" class="btn-upload" style="width: 100%;">
                        ✓ Upload
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Create Category Modal -->
    <?php if ($can_upload): ?>
        <div class="modal" id="createCategoryModal" style="display: none;">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3>➕ Tạo danh mục mới</h3>
                    <button class="close-modal" onclick="document.getElementById('createCategoryModal').style.display='none'">&times;</button>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label for="ten_danh_muc">Tên danh mục *</label>
                        <input type="text" id="ten_danh_muc" name="ten_danh_muc" required placeholder="Ví dụ: Lập trình Python">
                    </div>

                    <button type="submit" name="create_category" class="btn-upload" style="width: 100%; background: var(--primary-mint); color: white; padding: 0.75rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        ✓ Tạo danh mục
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="../assets/js/resources.js"></script>
</body>

</html>