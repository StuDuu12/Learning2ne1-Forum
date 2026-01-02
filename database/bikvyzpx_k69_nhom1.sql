-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 29, 2025 at 09:31 AM
-- Server version: 5.7.39
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `bikvyzpx_k69_nhom1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bikvyzpx_k69_nhom1`;
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bikvyzpx_k69_nhom1`
--

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `chu_de`
--

CREATE TABLE `chu_de` (
  `id_chu_de` int(11) NOT NULL,
  `ten_chu_de` varchar(100) NOT NULL,
  `diem_vuot_qua` int(11) NOT NULL,
  `id_khoa_hoc` int(11) NOT NULL,
  `so_cau` int(11) NOT NULL,
  `thoi_gian_lam` int(11) NOT NULL,
  `hide_status` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chu_de`
--

INSERT INTO `chu_de` (`id_chu_de`, `ten_chu_de`, `diem_vuot_qua`, `id_khoa_hoc`, `so_cau`, `thoi_gian_lam`, `hide_status`) VALUES
(1, 'HTML 5', 80, 1, 10, 5, 1),
(2, 'CSS', 80, 1, 10, 5, 1),
(3, 'Javascript', 80, 1, 10, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `danh_muc_tai_lieu`
--

CREATE TABLE `danh_muc_tai_lieu` (
  `id_danh_muc` int(11) NOT NULL,
  `ten_danh_muc` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `danh_muc_tai_lieu`
--

INSERT INTO `danh_muc_tai_lieu` (`id_danh_muc`, `ten_danh_muc`) VALUES
(1, 'Lập trình Web'),
(2, 'Cơ sở dữ liệu'),
(3, 'Cấu trúc dữ liệu & Giải thuật'),
(4, 'Mạng máy tính'),
(5, 'Hệ điều hành'),
(6, 'Đồ án & Khóa luận');

-- --------------------------------------------------------

--
-- Table structure for table `khoa_hoc`
--

CREATE TABLE `khoa_hoc` (
  `id_khoa_hoc` int(11) NOT NULL,
  `ten_khoa_hoc` varchar(100) NOT NULL,
  `anh` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `khoa_hoc`
--

INSERT INTO `khoa_hoc` (`id_khoa_hoc`, `ten_khoa_hoc`, `anh`) VALUES
(1, 'Nền tảng PT Web & Lập trình mạng & Network Programming', 'ntw.gif'),
(2, 'Công nghệ Web', 'cnw.gif'),
(3, 'Nhập môn<br>Khoa học máy tính', 'nhapmonkhmt.jpg'),
(4, 'Lập trình<br>hướng đối tượng', 'oop.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `khoa_hoc_chi_tiet`
--

CREATE TABLE `khoa_hoc_chi_tiet` (
  `id_khoa_hoc_chi_tiet` int(11) NOT NULL,
  `lop` varchar(100) NOT NULL,
  `id_khoa_hoc` int(11) NOT NULL,
  `hoc_ki` int(11) NOT NULL,
  `nam_hoc` int(11) NOT NULL,
  `ten_hoc_phan` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `khoa_hoc_chi_tiet`
--

INSERT INTO `khoa_hoc_chi_tiet` (`id_khoa_hoc_chi_tiet`, `lop`, `id_khoa_hoc`, `hoc_ki`, `nam_hoc`, `ten_hoc_phan`) VALUES
(1, 'K70.1', 1, 2, 2022, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `target_type` enum('post','comment') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Người nhận thông báo',
  `actor_id` int(11) DEFAULT NULL COMMENT 'Người thực hiện hành động (có thể NULL cho system notifications)',
  `type` enum('like','comment','mention','system') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại thông báo',
  `target_type` enum('post','comment') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đối tượng liên quan',
  `target_id` int(11) DEFAULT NULL COMMENT 'ID của đối tượng',
  `post_id` int(11) DEFAULT NULL COMMENT 'ID bài viết liên quan',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT 'Nội dung thông báo (cho mention)',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Đã đọc chưa',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `privacy` enum('public','private') DEFAULT 'public',
  `status` enum('solved','unsolved') DEFAULT 'unsolved',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `views` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `title`, `content`, `tags`, `privacy`, `status`, `created_at`, `views`) VALUES
(16, 1, 'Kỹ thuật tối ưu HTML5', 'Nội dung về các thẻ semantic trong HTML5...', '#HTML5,#WebDesign', 'public', 'unsolved', '2025-12-29 09:23:25', 50),
(17, 2, 'Quản lý Transaction trong MySQL', 'Cách sử dụng COMMIT và ROLLBACK...', '#MySQL,#Database', 'public', 'unsolved', '2025-12-29 09:23:25', 80),
(18, 4, 'Phòng chống tấn công CSRF', 'Hướng dẫn sử dụng Token để bảo vệ form...', '#Security,#CSRF', 'public', 'solved', '2025-12-29 09:23:25', 120);

-- --------------------------------------------------------

--
-- Table structure for table `tai_lieu`
--

CREATE TABLE `tai_lieu` (
  `id_tai_lieu` int(11) NOT NULL,
  `id_danh_muc` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tieu_de` varchar(500) NOT NULL,
  `mo_ta` text,
  `duong_dan_file` varchar(500) NOT NULL,
  `ngay_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `thanh_vien_khoa_hoc`
--

CREATE TABLE `thanh_vien_khoa_hoc` (
  `id_user` int(11) NOT NULL,
  `id_khoa_hoc_chi_tiet` int(11) NOT NULL,
  `so_lan_lam` int(11) NOT NULL,
  `diem_cc` float DEFAULT NULL,
  `diem_gk` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `thanh_vien_khoa_hoc`
--

INSERT INTO `thanh_vien_khoa_hoc` (`id_user`, `id_khoa_hoc_chi_tiet`, `so_lan_lam`, `diem_cc`, `diem_gk`) VALUES
(0, 3, 13, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `ho_ten` varchar(1000) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `account_level` int(11) NOT NULL DEFAULT '2'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `ho_ten`, `username`, `password`, `account_level`) VALUES
(1, 'Nguyễn Văn A', 'nva', 'c4ca4238a0b923820dcc509a6f75849b', 1),
(2, 'Quản trị viên', 'admin', 'c4ca4238a0b923820dcc509a6f75849b', 0),
(4, 'Sinh viên C', 'svc', 'c4ca4238a0b923820dcc509a6f75849b', 2),
(9, 'Trần Thị D', 'ttd_gv', 'e10adc3949ba59abbe56e057f20f883e', 1),
(10, 'Lê Văn E', 'lve_sv', 'e10adc3949ba59abbe56e057f20f883e', 2),
(11, 'Phạm Hoàng F', 'phf_sv', 'e10adc3949ba59abbe56e057f20f883e', 2),
(12, 'Hoàng Thị G', 'htg_gv', 'e10adc3949ba59abbe56e057f20f883e', 1),
(13, 'duy', 'duy', '123456', 2),
(18, 'Trần Thị D', 'ttd_gv_new', 'e10adc3949ba59abbe56e057f20f883e', 1),
(19, 'Lê Văn E', 'lve_sv_new', 'e10adc3949ba59abbe56e057f20f883e', 2),
(20, 'Phạm Hoàng F', 'phf_sv_new', 'e10adc3949ba59abbe56e057f20f883e', 2),
(21, 'Nguyễn Văn G', 'nvg_admin_new', 'e10adc3949ba59abbe56e057f20f883e', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_interests`
--

CREATE TABLE `user_interests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tag` varchar(50) NOT NULL,
  `score` int(11) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `chu_de`
--
ALTER TABLE `chu_de`
  ADD PRIMARY KEY (`id_chu_de`),
  ADD KEY `chu_de_ibfk_1` (`id_khoa_hoc`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `danh_muc_tai_lieu`
--
ALTER TABLE `danh_muc_tai_lieu`
  ADD PRIMARY KEY (`id_danh_muc`);

--
-- Indexes for table `khoa_hoc`
--
ALTER TABLE `khoa_hoc`
  ADD PRIMARY KEY (`id_khoa_hoc`);

--
-- Indexes for table `khoa_hoc_chi_tiet`
--
ALTER TABLE `khoa_hoc_chi_tiet`
  ADD PRIMARY KEY (`id_khoa_hoc_chi_tiet`),
  ADD KEY `khoa_hoc_chi_tiet_ibfk_1` (`id_khoa_hoc`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`user_id`,`target_id`,`target_type`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `actor_id` (`actor_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `tags` (`tags`);

--
-- Indexes for table `tai_lieu`
--
ALTER TABLE `tai_lieu`
  ADD PRIMARY KEY (`id_tai_lieu`),
  ADD KEY `id_danh_muc` (`id_danh_muc`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `thanh_vien_khoa_hoc`
--
ALTER TABLE `thanh_vien_khoa_hoc`
  ADD PRIMARY KEY (`id_user`,`id_khoa_hoc_chi_tiet`),
  ADD KEY `id_khoa_hoc_chi_tiet` (`id_khoa_hoc_chi_tiet`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_interest` (`user_id`,`tag`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chu_de`
--
ALTER TABLE `chu_de`
  MODIFY `id_chu_de` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `danh_muc_tai_lieu`
--
ALTER TABLE `danh_muc_tai_lieu`
  MODIFY `id_danh_muc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `khoa_hoc`
--
ALTER TABLE `khoa_hoc`
  MODIFY `id_khoa_hoc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `khoa_hoc_chi_tiet`
--
ALTER TABLE `khoa_hoc_chi_tiet`
  MODIFY `id_khoa_hoc_chi_tiet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tai_lieu`
--
ALTER TABLE `tai_lieu`
  MODIFY `id_tai_lieu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `user_interests`
--
ALTER TABLE `user_interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`actor_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `tai_lieu`
--
ALTER TABLE `tai_lieu`
  ADD CONSTRAINT `tai_lieu_ibfk_1` FOREIGN KEY (`id_danh_muc`) REFERENCES `danh_muc_tai_lieu` (`id_danh_muc`) ON DELETE CASCADE,
  ADD CONSTRAINT `tai_lieu_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD CONSTRAINT `user_interests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
