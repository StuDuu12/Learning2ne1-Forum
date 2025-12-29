-- Notifications Table for Learning2ne1 Forum
-- This table stores all notifications for users (likes, comments, mentions)

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Người nhận thông báo',
  `actor_id` int(11) DEFAULT NULL COMMENT 'Người thực hiện hành động (có thể NULL cho system notifications)',
  `type` enum('like','comment','mention','system') NOT NULL COMMENT 'Loại thông báo',
  `target_type` enum('post','comment') DEFAULT NULL COMMENT 'Đối tượng liên quan',
  `target_id` int(11) DEFAULT NULL COMMENT 'ID của đối tượng',
  `post_id` int(11) DEFAULT NULL COMMENT 'ID bài viết liên quan',
  `content` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nội dung thông báo (cho mention)',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Đã đọc chưa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `actor_id` (`actor_id`),
  KEY `post_id` (`post_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`actor_id`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for better performance on queries
CREATE INDEX idx_user_read ON notifications(user_id, is_read, created_at DESC);
