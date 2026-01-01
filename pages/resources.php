<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

$message = '';
$message_type = '';

if (isset($_SESSION['uploaded'])) {
    $message = $_SESSION['uploaded'];
    $message_type = 'success';
    unset($_SESSION['uploaded']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $resource_id = (int)$_POST['resource_id'];
    $stmt = $pdo->prepare("SELECT * FROM tai_lieu WHERE id_tai_lieu = ?");
    $stmt->execute([$resource_id]);
    $res = $stmt->fetch();

    if ($res) {
        $canDelete = false;
        if ($_SESSION['user_id'] == $res['id_user']) {
            $canDelete = true;
        }
        if (isset($_SESSION['account_level']) && $_SESSION['account_level'] == 0) {
            $canDelete = true;
        }

        if ($canDelete) {
            $filePath = __DIR__ . '/../' . $res['duong_dan_file'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $stmt = $pdo->prepare("DELETE FROM tai_lieu WHERE id_tai_lieu = ?");
            $stmt->execute([$resource_id]);
            $message = 'Đã xóa tài liệu thành công!';
            $message_type = 'success';
        } else {
            $message = 'Bạn không có quyền xóa tài liệu này!';
            $message_type = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resource_file'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $title = trim($_POST['title'] ?? 'Tài liệu không tên');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'Khác');
    $topic = trim($_POST['topic'] ?? '');

    if ($_FILES['resource_file']['error'] == 0) {
        $max_size = 100 * 1024 * 1024;
        if ($_FILES['resource_file']['size'] > $max_size) {
            $message = "File quá lớn (tối đa 100MB).";
            $message_type = 'error';
        } else {
            $target_dir = "../uploads/resources/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES["resource_file"]["name"]));
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES["resource_file"]["tmp_name"], $target_file)) {
                $file_path = "uploads/resources/" . $filename;

                $full_description = '';
                if (!empty($category)) {
                    $full_description .= '[CATEGORY:' . $category . ']';
                }
                if (!empty($topic)) {
                    $full_description .= '[TOPIC:' . $topic . ']';
                }
                if (!empty($description)) {
                    $full_description .= ' ' . $description;
                }

                $id_danh_muc = 1;
                $stmt = $pdo->prepare("INSERT INTO tai_lieu (id_danh_muc, id_user, tieu_de, mo_ta, duong_dan_file, ngay_upload) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$id_danh_muc, $_SESSION['user_id'], $title, trim($full_description), $file_path]);

                $message = 'Upload tài liệu thành công!';
                $message_type = 'success';
            } else {
                $message = "Không thể lưu file. Vui lòng thử lại.";
                $message_type = 'error';
            }
        }
    } else {
        $message = "Vui lòng chọn file để upload.";
        $message_type = 'error';
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$filterCategory = isset($_GET['category']) ? trim($_GET['category']) : '';
$filterTopic = isset($_GET['topic']) ? trim($_GET['topic']) : '';

$sql = "SELECT tl.*, u.username, u.ho_ten FROM tai_lieu tl JOIN user u ON tl.id_user = u.id_user WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (tl.tieu_de LIKE ? OR tl.mo_ta LIKE ? OR u.username LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($filterCategory)) {
    $sql .= " AND tl.mo_ta LIKE ?";
    $params[] = "%[CATEGORY:$filterCategory]%";
}

if (!empty($filterTopic)) {
    $sql .= " AND tl.mo_ta LIKE ?";
    $params[] = "%[TOPIC:$filterTopic]%";
}

if ($sort === 'oldest') {
    $sql .= " ORDER BY tl.ngay_upload ASC";
} else {
    $sql .= " ORDER BY tl.ngay_upload DESC";
}

$stmtList = $pdo->prepare($sql);
$stmtList->execute($params);
$resources = $stmtList->fetchAll(PDO::FETCH_ASSOC);
$totalResources = count($resources);

$fileTypes = [];
$allCategories = [];
$allTopics = [];
foreach ($resources as $res) {
    $ext = strtolower(pathinfo($res['duong_dan_file'], PATHINFO_EXTENSION));
    if (!isset($fileTypes[$ext])) {
        $fileTypes[$ext] = 0;
    }
    $fileTypes[$ext]++;

    if (preg_match('/\[CATEGORY:([^\]]+)\]/', $res['mo_ta'], $matches)) {
        $cat = $matches[1];
        if (!in_array($cat, $allCategories)) {
            $allCategories[] = $cat;
        }
    }
    if (preg_match('/\[TOPIC:([^\]]+)\]/', $res['mo_ta'], $matches)) {
        $top = $matches[1];
        if (!in_array($top, $allTopics)) {
            $allTopics[] = $top;
        }
    }
}
arsort($fileTypes);
sort($allCategories);
sort($allTopics);

$defaultCategories = ['Bài giảng', 'Tài liệu tham khảo', 'Đề thi', 'Bài tập', 'Slide', 'Ebook', 'Video', 'Khác'];

function parseResourceMeta($mo_ta)
{
    $category = '';
    $topic = '';
    $description = $mo_ta;

    if (preg_match('/\[CATEGORY:([^\]]+)\]/', $mo_ta, $matches)) {
        $category = $matches[1];
        $description = str_replace($matches[0], '', $description);
    }
    if (preg_match('/\[TOPIC:([^\]]+)\]/', $mo_ta, $matches)) {
        $topic = $matches[1];
        $description = str_replace($matches[0], '', $description);
    }

    return [
        'category' => $category,
        'topic' => $topic,
        'description' => trim($description)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Kho Tài Liệu Học Tập</title>
    <link rel="stylesheet" href="../assets/css/base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/resources.css?v=<?php echo time(); ?>">
    <link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="resources-page">
        <div class="page-header">
            <h1><i class='bx bx-book-open'></i> Kho Tài Liệu Học Tập</h1>
            <p style="color: #636e72;">Chia sẻ và tải về tài liệu học tập miễn phí</p>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <i class='bx bx-file'></i> Tổng số: <strong><?= $totalResources ?></strong> tài liệu
            </div>
            <?php foreach (array_slice($fileTypes, 0, 4, true) as $ext => $count): ?>
                <div class="stat-item">
                    <i class='bx bx-file-blank'></i> <?= strtoupper($ext) ?>: <strong><?= $count ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="filter-section">
            <h4><i class='bx bx-filter-alt'></i> Tìm kiếm & Lọc tài liệu</h4>
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Từ khóa</label>
                    <input type="text" name="search" placeholder="Tìm theo tên, mô tả..." value="<?= h($search) ?>">
                </div>
                <div class="filter-group">
                    <label>Danh mục</label>
                    <select name="category">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($defaultCategories as $cat): ?>
                            <option value="<?= h($cat) ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Chủ đề</label>
                    <input type="text" name="topic" placeholder="Nhập chủ đề..." value="<?= h($filterTopic) ?>">
                </div>
                <div class="filter-group">
                    <label>Sắp xếp</label>
                    <select name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class='bx bx-search'></i> Tìm kiếm
                </button>
                <?php if (!empty($search) || !empty($filterCategory) || !empty($filterTopic)): ?>
                    <a href="resources.php" class="btn-clear">
                        <i class='bx bx-x'></i> Xóa lọc
                    </a>
                <?php endif; ?>
            </form>

            <?php if (!empty($search) || !empty($filterCategory) || !empty($filterTopic)): ?>
                <div class="active-filters">
                    <?php if (!empty($search)): ?>
                        <span class="filter-tag"><i class='bx bx-search'></i> "<?= h($search) ?>"</span>
                    <?php endif; ?>
                    <?php if (!empty($filterCategory)): ?>
                        <span class="filter-tag"><i class='bx bx-category'></i> <?= h($filterCategory) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($filterTopic)): ?>
                        <span class="filter-tag"><i class='bx bx-book'></i> <?= h($filterTopic) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class='bx bx-<?= $message_type === 'success' ? 'check-circle' : 'error-circle' ?>'></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>


        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="upload-section">
                <h3><i class='bx bx-cloud-upload'></i> Chia sẻ tài liệu mới</h3>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label><i class='bx bx-edit'></i> Tên tài liệu *</label>
                        <input type="text" name="title" required placeholder="Nhập tên tài liệu">
                    </div>
                    <div class="form-group">
                        <label><i class='bx bx-file'></i> Chọn file *</label>
                        <input type="file" name="resource_file" required>
                    </div>
                    <div class="form-group">
                        <label><i class='bx bx-category'></i> Danh mục</label>
                        <select name="category">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($defaultCategories as $cat): ?>
                                <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class='bx bx-book'></i> Chủ đề</label>
                        <input type="text" name="topic" placeholder="VD: Lập trình PHP, Cơ sở dữ liệu...">
                    </div>
                    <div class="form-group full-width">
                        <label><i class='bx bx-text'></i> Mô tả ngắn</label>
                        <textarea name="description" rows="2" placeholder="Mô tả nội dung tài liệu..."></textarea>
                    </div>
                    <div class="full-width" style="text-align: right;">
                        <button type="submit" class="btn-upload">
                            <i class='bx bx-upload'></i> Upload tài liệu
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                <p style="margin: 0; color: #2d3436; font-size: 1.05rem;">
                    <i class='bx bx-lock' style="color: var(--primary-mint);"></i>
                    <a href="login.php">Đăng nhập</a> để chia sẻ tài liệu của bạn!
                </p>
            </div>
        <?php endif; ?>


        <?php if (empty($resources)): ?>
            <div class="empty-state">
                <i class='bx bx-folder-open'></i>
                <h3>Chưa có tài liệu nào</h3>
                <p>Hãy là người đầu tiên chia sẻ tài liệu!</p>
            </div>
        <?php else: ?>
            <div class="resources-grid">
                <?php foreach ($resources as $res): ?>
                    <?php
                    $ext = strtolower(pathinfo($res['duong_dan_file'], PATHINFO_EXTENSION));
                    $iconColor = '#00d2d3';
                    $iconName = 'bx-file';
                    if ($ext === 'pdf') {
                        $iconColor = '#e74c3c';
                        $iconName = 'bxs-file-pdf';
                    } elseif (in_array($ext, ['doc', 'docx'])) {
                        $iconColor = '#3498db';
                        $iconName = 'bxs-file-doc';
                    } elseif (in_array($ext, ['xls', 'xlsx'])) {
                        $iconColor = '#27ae60';
                        $iconName = 'bx-table';
                    } elseif (in_array($ext, ['ppt', 'pptx'])) {
                        $iconColor = '#e67e22';
                        $iconName = 'bxs-slideshow';
                    } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $iconColor = '#9b59b6';
                        $iconName = 'bx-image';
                    } elseif (in_array($ext, ['zip', 'rar', '7z'])) {
                        $iconColor = '#f39c12';
                        $iconName = 'bx-archive';
                    } elseif (in_array($ext, ['mp4', 'avi', 'mkv', 'mov'])) {
                        $iconColor = '#e91e63';
                        $iconName = 'bx-video';
                    }

                    $meta = parseResourceMeta($res['mo_ta']);
                    ?>
                    <div class="resource-card">
                        <div class="resource-header">
                            <div class="resource-info">
                                <div class="resource-icon" style="background: <?= $iconColor ?>15; color: <?= $iconColor ?>;">
                                    <?= strtoupper($ext) ?>
                                </div>
                                <div class="resource-title"><?= htmlspecialchars($res['tieu_de']); ?></div>
                                <div class="resource-tags">
                                    <?php if (!empty($meta['category'])): ?>
                                        <span class="resource-tag"><i class='bx bx-category'></i> <?= h($meta['category']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($meta['topic'])): ?>
                                        <span class="resource-tag topic"><i class='bx bx-book'></i> <?= h($meta['topic']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($meta['description'])): ?>
                            <div class="resource-desc"><?= htmlspecialchars($meta['description']); ?></div>
                        <?php endif; ?>
                        <div class="resource-meta">
                            <span><i class='bx bx-user'></i> <?= htmlspecialchars($res['ho_ten'] ?? $res['username']); ?></span>
                            <span><i class='bx bx-calendar'></i> <?= date('d/m/Y H:i', strtotime($res['ngay_upload'])) ?></span>
                        </div>
                        <div class="resource-actions">
                            <a href="../<?= $res['duong_dan_file']; ?>" class="btn-download" target="_blank">
                                <i class='bx bx-download'></i> Tải xuống
                            </a>
                            <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $res['id_user'] || (isset($_SESSION['account_level']) && $_SESSION['account_level'] == 0))): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa tài liệu này?');">
                                    <input type="hidden" name="resource_id" value="<?= $res['id_tai_lieu'] ?>">
                                    <button type="submit" name="delete_resource" class="btn-delete-res">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>