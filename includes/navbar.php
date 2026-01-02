<?php
$in_pages_folder = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$path = $in_pages_folder ? '../' : './';

if (!isset($current_user) && isset($_SESSION['user_id'])) {
    $current_user = [
        'id_user' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'User',
        'ho_ten' => $_SESSION['user_fullname'] ?? ($_SESSION['username'] ?? 'User')
    ];
}
$current_page = basename($_SERVER['PHP_SELF']);

?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
<link href='https://cdn.boxicons.com/3.0.6/fonts/basic/boxicons.min.css' rel='stylesheet'>

<nav class="navbar">
    <div class="navbar-container">

        <a href="<?= $path ?>index.php" class="navbar-brand">
            <span style="font-size: 2.2rem;"><i class='bx  bx-education'></i></span>
            <span class="brand-text">Learning2ne1</span>
        </a>

        <button class="hamburger" onclick="toggleMobileMenu(this)">
            <span></span><span></span><span></span>
        </button>

        <div class="navbar-menu" id="navbarMenu">

            <a href="<?= $path ?>index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>"><i class='bx  bx-home-alt-3'></i> Trang chủ</a>
            <a href="<?= $path ?>pages/resources.php" class="nav-link <?= $current_page == 'resources.php' ? 'active' : '' ?>"><i class='bx  bx-book-library'></i> Học liệu</a>
            <a href="<?= $path ?>pages/dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class='bx  bx-chart-bar-columns'></i> Xu hướng</a>

            <?php
            $accountLevel = $_SESSION['account_level'] ?? $_SESSION['role'] ?? null;
            if (isset($_SESSION['user_id']) && $accountLevel === 0) { ?>
                <a href="<?= $path ?>pages/admin_users.php" class="nav-link <?= $current_page == 'admin_users.php' ? 'active' : '' ?>"><i class='bx  bx-community'></i> Quản lý thành viên</a>
            <?php } ?>

            <?php if (isset($current_user['id_user'])) { ?>
                <div class="user-section">
                    <div class="user-menu" style="display: flex; align-items: center; gap: 5px;">
                        <a href="<?= $path ?>pages/profile.php?username=<?= urlencode($current_user['username']) ?>" class="user-profile-link">
                            <div class="user-avatar-small">
                                <?= strtoupper(mb_substr($current_user['ho_ten'], 0, 1)) ?>
                            </div>
                            <span style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($current_user['ho_ten']) ?></span>
                        </a>
                        <a href="<?= $path ?>pages/logout.php" class="nav-link"><i class='bx  bx-arrow-in-right-stroke-circle-half'></i> Đăng xuất</a>
                    </div>
                </div>

            <?php } else { ?>
                <a href="<?= $path ?>pages/login.php" class="nav-link" style="background: white; color: #00796b;"><i class='bx  bx-arrow-in-left-stroke-circle-half'></i> Đăng nhập</a>
            <?php } ?>
        </div>
    </div>
</nav>

<script>
    function toggleMobileMenu(hamburger) {
        hamburger.classList.toggle('active');
        document.getElementById('navbarMenu').classList.toggle('active');
    }
</script>

<style>
    @media (min-width: 769px) {
        .hamburger {
            display: none !important;
        }

        #navbarMenu {
            display: flex !important;
        }

        #navbarMenu.active {
            display: flex;
        }
    }
</style>