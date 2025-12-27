<?php
$current_user = isLoggedIn() ? getCurrentUser($pdo) : null;
$current_page = basename($_SERVER['PHP_SELF']);

// Determine if we're in pages subdirectory
$in_pages = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false);
$base_path = $in_pages ? '../' : '';
?>
<style>
    .navbar {
        background: linear-gradient(135deg, #29e2a8ff 0%, #44e9adff 40%, #6ed5acff 70%, #5ddbcaff 100%);
        box-shadow: 0 6px 18px rgba(7, 55, 46, 0.08);
        border-bottom: 1px solid rgba(255,255,255,0.06);
        padding: 1rem 0;
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

    .navbar-brand {
        font-family: 'Oswald', sans-serif;
        font-size: 2rem;
        font-weight: 900;
        color: var(--primary-mint);
        letter-spacing: -1px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .navbar-menu {
        display: flex;
        gap: 0.2rem;
        align-items: center;
        flex: 1;
        justify-content: flex-end;
    }

    .nav-link {
        text-decoration: none;
        color: var(--text-dark);
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s;
        min-width: 140px;
        text-align: center;
    }

    .nav-link:hover {
        background: var(--primary-mint);
        color: white;
    }

    .nav-link.active {
        background: white;
        color: var(--primary-mint);
    }

    /* Profile link: align with nav-link hover/active but keep avatar layout */
    .nav-link.profile-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: auto;
        text-align: left;
        padding: 0.5rem 0.75rem;
    }

    .nav-link.profile-link:hover {
        background: var(--primary-mint);
        color: white;
    }

    .nav-link.profile-link.active {
        background: white;
        color: var(--primary-mint);
    }

    /* CSS Riêng cho Logo trong Navbar */
    .navbar-brand {
        font-family: 'Oswald', sans-serif;
        font-size: 2rem;
        /* Tăng kích thước thêm một chút */
        font-weight: 900;
        /* Độ đậm tối đa (Black) */
        letter-spacing: -1px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    /* Chữ Learning2ne1 - Màu trắng & Hiệu ứng */
    .brand-text {
        color: #ffffff;
        /* Màu trắng tinh */

        /* Đổ bóng nhẹ ban đầu để tách biệt khỏi nền */
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);

        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        /* Hiệu ứng chuyển động mượt */
    }

    /* Hiệu ứng HOVER: Nổi lên và Bóng đậm rõ nét */
    .navbar-brand:hover .brand-text {
        /* Tạo bóng đậm và xa hơn để tạo cảm giác nổi 3D */
        text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);

        /* Nhấc chữ lên và phóng to nhẹ */
        transform: translateY(-3px) scale(1.02);
    }

    /* Icon mũ cử nhân */
    .brand-icon {
        font-size: 2.4rem;
        color: #ffffff;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        transition: transform 0.4s ease;
    }

    .navbar-brand:hover .brand-icon {
        transform: rotate(-15deg) scale(1.1) translateY(-2px);
    }


    .user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-menu a:hover {
        background: var(--bg-grey);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        background: var(--primary-mint);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }

    .btn-logout {
        background: #ff7675;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }

    .btn-logout:hover {
        background: #d63031;
    }

    .btn-login {
        background: var(--primary-mint);
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-login:hover {
        background: var(--light-mint);
    }

    .hamburger {
        display: none;
        flex-direction: column;
        gap: 4px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.5rem;
    }

    .hamburger span {
        width: 25px;
        height: 3px;
        background: var(--primary-mint);
        transition: all 0.3s;
        border-radius: 2px;
    }

    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }

    /* Responsive Navbar */
    @media (max-width: 768px) {
        .navbar {
            padding: 0.75rem 0;
        }

        .navbar-container {
            flex-wrap: wrap;
        }

        .hamburger {
            display: flex;
        }

        .navbar-menu {
            flex-direction: column;
            width: 100%;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            gap: 0;
        }

        .navbar-menu.active {
            max-height: 500px;
            margin-top: 1rem;
        }

        .navbar-menu a,
        .navbar-menu .user-menu {
            width: 100%;
            text-align: center;
            padding: 0.75rem;
            border-bottom: 1px solid var(--bg-grey);
        }

        .user-menu {
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn-logout {
            width: 100%;
        }
    }
</style>

<nav class="navbar">
    <div class="navbar-container">
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
            <!-- Icon riêng -->
            <span class="brand-icon">🎓</span>
            <!-- Text riêng để áp dụng font đẹp và gradient -->
            <span class="brand-text"><?= APP_NAME ?></span>
        </a>

        <button class="hamburger" onclick="toggleMobileMenu(this)">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="navbar-menu" id="navbarMenu">
            <a href="<?= $base_path ?>index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
                🏠 Trang chủ
            </a>

            <?php if (isLoggedIn()): ?>
                <a href="<?= $base_path ?>pages/resources.php" class="nav-link <?= $current_page === 'resources.php' ? 'active' : '' ?>">
                    📚 Chia sẻ tài liệu
                </a>

                <a href="<?= $base_path ?>pages/dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                    📊 Dashboard
                </a>

                <?php if (isset($_SESSION['account_level']) && $_SESSION['account_level'] == 0): ?>
                    <a href="<?= $base_path ?>pages/admin_users.php" class="nav-link <?= $current_page === 'admin_users.php' ? 'active' : '' ?>">
                        🛡️ Quản lý Users
                    </a>
                <?php endif; ?>

                <div class="user-menu">
                    <a href="<?= $base_path ?>pages/profile.php?username=<?= urlencode($current_user['username'] ?? '') ?>" class="nav-link profile-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
                        <div class="user-avatar">
                            <?= strtoupper(mb_substr($current_user['ho_ten'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span style="font-weight: 600;">
                            <?= h($current_user['ho_ten'] ?? 'User') ?>
                        </span>
                    </a>
                    <a href="<?= $base_path ?>pages/logout.php" class="btn-logout">Đăng xuất</a>
                </div>
            <?php else: ?>
                <a href="<?= $base_path ?>pages/login.php" class="btn-login">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    function toggleMobileMenu(hamburger) {
        hamburger.classList.toggle('active');
        const menu = document.getElementById('navbarMenu');
        menu.classList.toggle('active');
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        const navbar = document.querySelector('.navbar');
        const menu = document.getElementById('navbarMenu');
        const hamburger = document.querySelector('.hamburger');

        if (!navbar.contains(event.target) && menu.classList.contains('active')) {
            menu.classList.remove('active');
            hamburger.classList.remove('active');
        }
    });

    // Close menu when window is resized to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.getElementById('navbarMenu').classList.remove('active');
            document.querySelector('.hamburger').classList.remove('active');
        }
    });
</script>