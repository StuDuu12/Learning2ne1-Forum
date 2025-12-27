-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 19, 2025 at 01:19 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bikvyzpx_k69_nhom1`
--

-- --------------------------------------------------------

--
-- Table structure for table `chu_de`
--

DROP TABLE IF EXISTS `chu_de`;
CREATE TABLE `chu_de` (
  `id_chu_de` int NOT NULL AUTO_INCREMENT,
  `ten_chu_de` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `diem_vuot_qua` int NOT NULL,
  `id_khoa_hoc` int NOT NULL,
  `so_cau` int NOT NULL,
  `thoi_gian_lam` int NOT NULL,
  `hide_status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_chu_de`),
  KEY `chu_de_ibfk_1` (`id_khoa_hoc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chu_de`
--

INSERT INTO `chu_de` (`id_chu_de`, `ten_chu_de`, `diem_vuot_qua`, `id_khoa_hoc`, `so_cau`, `thoi_gian_lam`, `hide_status`) VALUES
(1, 'HTML 5', 80, 1, 10, 5, 1),
(2, 'CSS', 80, 1, 10, 5, 1),
(3, 'Javascript', 80, 1, 10, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `khoa_hoc`
--

DROP TABLE IF EXISTS `khoa_hoc`;
CREATE TABLE `khoa_hoc` (
  `id_khoa_hoc` int NOT NULL AUTO_INCREMENT,
  `ten_khoa_hoc` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `anh` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_khoa_hoc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `khoa_hoc_chi_tiet`;
CREATE TABLE `khoa_hoc_chi_tiet` (
  `id_khoa_hoc_chi_tiet` int NOT NULL AUTO_INCREMENT,
  `lop` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `id_khoa_hoc` int NOT NULL,
  `hoc_ki` int NOT NULL,
  `nam_hoc` int NOT NULL,
  `ten_hoc_phan` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_khoa_hoc_chi_tiet`),
  KEY `khoa_hoc_chi_tiet_ibfk_1` (`id_khoa_hoc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `khoa_hoc_chi_tiet`
--

INSERT INTO `khoa_hoc_chi_tiet` (`id_khoa_hoc_chi_tiet`, `lop`, `id_khoa_hoc`, `hoc_ki`, `nam_hoc`, `ten_hoc_phan`) VALUES
(1, 'K70.1', 1, 2, 2022, NULL);

--
-- Table structure for table `thanh_vien_khoa_hoc`
--

DROP TABLE IF EXISTS `thanh_vien_khoa_hoc`;
CREATE TABLE `thanh_vien_khoa_hoc` (
  `id_user` int NOT NULL,
  `id_khoa_hoc_chi_tiet` int NOT NULL,
  `so_lan_lam` int NOT NULL,
  `diem_cc` float DEFAULT NULL,
  `diem_gk` float DEFAULT NULL,
  PRIMARY KEY (`id_user`,`id_khoa_hoc_chi_tiet`),
  KEY `id_khoa_hoc_chi_tiet` (`id_khoa_hoc_chi_tiet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thanh_vien_khoa_hoc`
--

INSERT INTO `thanh_vien_khoa_hoc` (`id_user`, `id_khoa_hoc_chi_tiet`, `so_lan_lam`, `diem_cc`, `diem_gk`) VALUES
(0, 3, 13, NULL, NULL);

--
-- Indexes for dumped tables
-- Indexes for table `chu_de`
--
ALTER TABLE `chu_de`
  MODIFY `id_chu_de` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- Indexes for table `khoa_hoc`
--
ALTER TABLE `khoa_hoc`
  MODIFY `id_khoa_hoc` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Indexes for table `khoa_hoc_chi_tiet`
--
ALTER TABLE `khoa_hoc_chi_tiet`
  MODIFY `id_khoa_hoc_chi_tiet` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `ho_ten` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `account_level` int NOT NULL DEFAULT '2',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ho_ten`, `username`, `password`, `account_level`) VALUES
('Nguyễn Văn A', 'nva', 'c4ca4238a0b923820dcc509a6f75849b', 1),
('Admin', 'admin', '21232f297a57a5a743894a0e4a801fc3', 0),
('Giảng Viên B', 'gvb', '5f4dcc3b5aa765d61d8327deb882cf99', 1),
('Sinh Viên C', 'svc', '5f4dcc3b5aa765d61d8327deb882cf99', 2);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4266;

-- --------------------------------------------------------
-- NEW TABLES FOR DISCUSSION FORUM SYSTEM
-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `icon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '📁',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`name`, `description`, `icon`) VALUES
('💻 Lập trình Web', 'HTML, CSS, JavaScript, PHP, Frameworks', '💻'),
('🗄️ Cơ sở dữ liệu', 'MySQL, MongoDB, SQL, Database Design', '🗄️'),
('🔐 Bảo mật Web', 'Security, Authentication, HTTPS, XSS, SQL Injection', '🔐'),
('🎨 Thiết kế UI/UX', 'Design, User Interface, User Experience', '🎨'),
('📱 Mobile Development', 'React Native, Flutter, Android, iOS', '📱'),
('🚀 DevOps & Deploy', 'Git, Docker, CI/CD, Server Management', '🚀'),
('💡 Thảo luận chung', 'Trao đổi, chia sẻ kinh nghiệm, hỏi đáp', '💡');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `tags` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `privacy` enum('public','private') COLLATE utf8mb4_general_ci DEFAULT 'public',
  `status` enum('solved','unsolved') COLLATE utf8mb4_general_ci DEFAULT 'unsolved',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `views` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `created_at` (`created_at`),
  KEY `tags` (`tags`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `likes`
--

DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `target_id` int NOT NULL,
  `target_type` enum('post','comment') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_id`,`target_id`,`target_type`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `polls`
--

DROP TABLE IF EXISTS `polls`;
CREATE TABLE `polls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `question` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `polls_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `poll_options`
--

DROP TABLE IF EXISTS `poll_options`;
CREATE TABLE `poll_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `poll_id` int NOT NULL,
  `option_text` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`),
  CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `poll_votes`
--

DROP TABLE IF EXISTS `poll_votes`;
CREATE TABLE `poll_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `option_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`option_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `poll_votes_ibfk_1` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `poll_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `user_interests`
--

DROP TABLE IF EXISTS `user_interests`;
CREATE TABLE `user_interests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tag` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `score` int DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_interest` (`user_id`,`tag`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_interests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `danh_muc_tai_lieu`
--

DROP TABLE IF EXISTS `danh_muc_tai_lieu`;
CREATE TABLE `danh_muc_tai_lieu` (
  `id_danh_muc` int NOT NULL AUTO_INCREMENT,
  `ten_danh_muc` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_danh_muc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `danh_muc_tai_lieu`
--

INSERT INTO `danh_muc_tai_lieu` (`id_danh_muc`, `ten_danh_muc`) VALUES
(1, 'Lập trình Web'),
(2, 'Cơ sở dữ liệu'),
(3, 'Cấu trúc dữ liệu & Giải thuật'),
(4, 'Mạng máy tính'),
(5, 'Hệ điều hành'),
(6, 'Đồ án & Khóa luận'),
(7, 'Tài liệu khác');

-- --------------------------------------------------------

--
-- Table structure for table `tai_lieu`
--

DROP TABLE IF EXISTS `tai_lieu`;
CREATE TABLE `tai_lieu` (
  `id_tai_lieu` int NOT NULL AUTO_INCREMENT,
  `id_danh_muc` int NOT NULL,
  `id_user` int NOT NULL,
  `tieu_de` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `mo_ta` text COLLATE utf8mb4_general_ci,
  `duong_dan_file` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `ngay_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tai_lieu`),
  KEY `id_danh_muc` (`id_danh_muc`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `tai_lieu_ibfk_1` FOREIGN KEY (`id_danh_muc`) REFERENCES `danh_muc_tai_lieu` (`id_danh_muc`) ON DELETE CASCADE,
  CONSTRAINT `tai_lieu_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

