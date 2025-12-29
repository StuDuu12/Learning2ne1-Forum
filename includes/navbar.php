<?php
// --- LOGIC XỬ LÝ ĐƯỜNG DẪN TỰ ĐỘNG ---
$in_pages_folder = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$path = $in_pages_folder ? '../' : './';

// Đảm bảo user session
if (!isset($current_user) && isset($_SESSION['user_id'])) {
    $current_user = [
        'id_user' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'ho_ten' => $_SESSION['user_fullname'] ?? 'User'
    ];
}
$current_page = basename($_SERVER['PHP_SELF']);

// --- ĐẾM SỐ THÔNG BÁO CHƯA ĐỌC ---
$unread_count = 0;
if (isset($current_user['id_user'])) {
    try {
        // Bảng notifications theo schema trong database/bikvyzpx_k69_nhom1.sql
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt_count->execute([$current_user['id_user']]);
        $unread_count = (int) $stmt_count->fetchColumn();
    } catch (PDOException $e) {
        $unread_count = 0; // Tránh lỗi nếu bảng notifications chưa tồn tại
    }
}
?>

<!-- Import Font & Styles -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
<style>
    /* Navbar Styles */
    .navbar {
        background: linear-gradient(135deg, #00bfa5 0%, #00796b 100%);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 0.8rem 0;
        margin-bottom: 2rem;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-container {
        max-width: 100%;
        margin: 0;
        padding: 0 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Logo */
    .navbar-brand {
        font-family: 'Poppins', sans-serif;
        font-size: 2rem;
        font-weight: 900;
        letter-spacing: -1px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: white;
    }

    .brand-text {
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.3s;
    }

    .navbar-brand:hover .brand-text {
        transform: translateY(-2px);
        text-shadow: 0 5px 10px rgba(0, 0, 0, 0.3);
    }

    /* Menu & Links */
    .navbar-menu {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .nav-link {
        text-decoration: none;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .nav-link:hover,
    .nav-link.active {
        background: white;
        color: #00796b;
        transform: translateY(-1px);
    }

    /* CSS cho chuông thông báo */
    .nav-icon-btn {
        position: relative;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        transition: background 0.3s;
        margin-right: 5px;
        /* Khoảng cách với avatar */
    }

    .nav-icon-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .nav-icon-symbol {
        font-size: 1.3rem;
        color: rgba(255, 255, 255, 0.9);
    }

    .badge-count {
        position: absolute;
        top: 0;
        right: 0;
        background: #ff5252;
        color: white;
        font-size: 0.65rem;
        font-weight: bold;
        padding: 1px 4px;
        border-radius: 10px;
        min-width: 16px;
        text-align: center;
        border: 2px solid #fff;
    }

    /* User Profile Section */
    .user-section {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: 15px;
        padding-left: 15px;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
    }

    .user-profile-link {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        transition: background 0.3s;
    }

    .user-profile-link:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .user-avatar-small {
        width: 32px;
        height: 32px;
        background: white;
        color: #00796b;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    /* Mobile Responsive */
    .hamburger {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        flex-direction: column;
        gap: 4px;
    }

    .hamburger span {
        width: 25px;
        height: 3px;
        background: white;
        border-radius: 2px;
    }

    @media (max-width: 768px) {
        .hamburger {
            display: flex;
        }

        .navbar-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: #00796b;
            flex-direction: column;
            padding: 1rem;
            gap: 10px;
        }

        .navbar-menu.active {
            display: flex;
        }

        .nav-link {
            width: 100%;
            text-align: center;
        }

        /* Mobile adjustments */
        .user-section {
            border-left: none;
            margin-left: 0;
            padding-left: 0;
            justify-content: center;
            width: 100%;
            margin-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 10px;
        }
    }
</style>

<nav class="navbar">
    <div class="navbar-container">
        <!-- Logo -->
        <a href="<?= $path ?>index.php" class="navbar-brand">
            <span style="font-size: 2.2rem;">🎓</span>
            <span class="brand-text">Learning2ne1</span>
        </a>

        <button class="hamburger" onclick="toggleMobileMenu(this)">
            <span></span><span></span><span></span>
        </button>

        <div class="navbar-menu" id="navbarMenu">
            <!-- Main Links -->
            <a href="<?= $path ?>index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">🏠 Trang chủ</a>
            <a href="<?= $path ?>pages/resources.php" class="nav-link <?= $current_page == 'resources.php' ? 'active' : '' ?>">📚 Học liệu</a>

            <?php if (isset($current_user['id_user'])): ?>
                <a href="<?= $path ?>pages/dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>

                <!-- User & Notifications Section -->
                <div class="user-section">



                    <div class="user-menu" style="display: flex; align-items: center; gap: 5px;">
                        <a href="<?= $path ?>pages/profile.php?username=<?= urlencode($current_user['username']) ?>" class="user-profile-link">
                            <div class="user-avatar-small">
                                <?= strtoupper(mb_substr($current_user['ho_ten'], 0, 1)) ?>
                            </div>
                            <span style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($current_user['ho_ten']) ?></span>
                        </a>
                        <a href="<?= $path ?>pages/logout.php" class="nav-link">Đăng xuất</a>
                    </div>
                </div>

            <?php else: ?>
                <a href="<?= $path ?>pages/login.php" class="nav-link" style="background: white; color: #00796b;">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    function toggleMobileMenu(hamburger) {
        hamburger.classList.toggle('active');
        document.getElementById('navbarMenu').classList.toggle('active');
    }
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.getElementById('navbarMenu').classList.remove('active');
        }
    });
</script>