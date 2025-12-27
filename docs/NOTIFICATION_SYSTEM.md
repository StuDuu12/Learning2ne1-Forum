# Hướng dẫn cài đặt hệ thống thông báo

## Các thay đổi đã thực hiện:

### 1. ✅ Đã xóa dropdown danh mục trong create_post.php
- Dropdown chọn danh mục đã bị loại bỏ
- Chỉ hiển thị danh mục khi tạo bài viết từ trang danh mục cụ thể (qua URL parameter)

### 2. ✅ Thêm icon chuông thông báo vào trang chủ
- Icon chuông nằm **bên trái** màn hình (đối xứng với nút "Tạo bài viết" bên phải)
- Màu vàng cam nổi bật với hiệu ứng lắc chuông
- Badge đỏ hiển thị số thông báo chưa đọc

### 3. ✅ Popup thông báo đầy đủ chức năng
- Hiển thị 3 loại thông báo:
  * ❤️ **Like** (gộp chung khi nhiều người like cùng bài)
  * 💬 **Comment** (hiển thị riêng từng comment)
  * 📢 **Mention** (hiển thị riêng khi ai đó @ bạn)
- Thông báo chưa đọc có nền màu vàng nhạt
- Click vào thông báo sẽ đánh dấu đã đọc và chuyển đến bài viết
- Nút "Đánh dấu tất cả đã đọc"

### 4. ✅ Tự động cập nhật
- Badge số lượng thông báo cập nhật mỗi 30 giây
- Thông báo được tạo tự động khi:
  * Ai đó like bài viết/comment của bạn
  * Ai đó comment vào bài viết của bạn
  * Ai đó mention (@username) bạn trong bài viết hoặc comment

---

## Các file đã tạo/sửa:

### Tạo mới:
1. `database/add_notifications.sql` - SQL tạo bảng notifications
2. `includes/notifications_ajax.php` - API endpoint xử lý AJAX cho thông báo
3. `includes/notification_helpers.php` - Hàm helper tạo thông báo

### Sửa đổi:
1. `pages/create_post.php` - Xóa dropdown danh mục, thêm mention notification
2. `index.php` - Thêm icon chuông và popup thông báo
3. `assets/css/index.css` - CSS cho chuông và popup thông báo
4. `assets/js/index.js` - JavaScript xử lý popup và AJAX
5. `includes/ajax.php` - Thêm notification trigger cho like và comment

---

## Hướng dẫn cài đặt (QUAN TRỌNG):

### Bước 1: Chạy SQL để tạo bảng notifications

**Mở phpMyAdmin và chạy file SQL:**
```bash
database/add_notifications.sql
```

Hoặc copy và chạy SQL này trong phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Người nhận thông báo',
  `actor_id` int(11) DEFAULT NULL COMMENT 'Người thực hiện hành động',
  `type` enum('like','comment','mention','system') NOT NULL COMMENT 'Loại thông báo',
  `target_type` enum('post','comment') DEFAULT NULL COMMENT 'Đối tượng liên quan',
  `target_id` int(11) DEFAULT NULL COMMENT 'ID của đối tượng',
  `post_id` int(11) DEFAULT NULL COMMENT 'ID bài viết liên quan',
  `content` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nội dung thông báo',
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

CREATE INDEX idx_user_read ON notifications(user_id, is_read, created_at DESC);
```

### Bước 2: Kiểm tra các file đã được tạo
Đảm bảo các file sau tồn tại:
- ✅ `includes/notifications_ajax.php`
- ✅ `includes/notification_helpers.php`
- ✅ `database/add_notifications.sql`

### Bước 3: Test hệ thống thông báo

1. **Đăng nhập với 2 tài khoản khác nhau** (mở 2 trình duyệt/incognito)

2. **Test Like notification:**
   - User A: Tạo bài viết
   - User B: Like bài viết của User A
   - User A: Click vào icon chuông 🔔 → Sẽ thấy thông báo "User B đã thích bài viết của bạn"

3. **Test Comment notification:**
   - User B: Comment vào bài viết của User A
   - User A: Xem thông báo → Sẽ thấy "User B đã bình luận về bài viết của bạn"

4. **Test Mention notification:**
   - User B: Tạo bài viết hoặc comment có nội dung "@userA_username"
   - User A: Xem thông báo → Sẽ thấy "User B đã nhắc đến bạn"

5. **Test Grouped Likes:**
   - User B, User C, User D: Cùng like 1 bài viết của User A
   - User A: Xem thông báo → Sẽ thấy "User B, User C, User D đã thích bài viết của bạn"

---

## Tính năng nổi bật:

### 🔔 Icon chuông thông báo:
- Vị trí: **Góc dưới bên TRÁI** màn hình (fixed position)
- Màu: Vàng cam gradient (`#fdcb6e` → `#f39c12`)
- Hiệu ứng: Lắc chuông liên tục (animation)
- Badge đỏ: Hiển thị số thông báo chưa đọc

### 📋 Popup thông báo:
- Kích thước: 400px rộng, tối đa 500px cao
- Vị trí: Phía trên icon chuông
- Header: Màu vàng cam với nút "Đánh dấu tất cả đã đọc"
- Body: Danh sách thông báo với scroll

### 💡 Thông báo được gộp:
- **Like**: Nhiều người like cùng bài → gộp thành 1 thông báo
  - Ví dụ: "Alice, Bob và 3 người khác đã thích bài viết của bạn"
- **Comment**: Mỗi comment 1 thông báo riêng
- **Mention**: Mỗi mention 1 thông báo riêng

### ⚡ Real-time:
- Tự động check thông báo mới mỗi 30 giây
- Badge cập nhật số lượng tự động

---

## Troubleshooting:

### Lỗi: "Không thể tải thông báo"
→ Kiểm tra:
- File `includes/notifications_ajax.php` tồn tại
- Bảng `notifications` đã được tạo trong database
- Đã đăng nhập (chỉ user đăng nhập mới thấy icon chuông)

### Icon chuông không hiển thị:
→ Kiểm tra:
- Đã đăng nhập chưa? (icon chỉ hiện cho logged-in users)
- CSS trong `assets/css/index.css` đã được load chưa
- Console browser có lỗi JavaScript không?

### Thông báo không được tạo khi like/comment:
→ Kiểm tra:
- File `includes/notification_helpers.php` tồn tại
- File `includes/ajax.php` đã include notification_helpers.php
- Bảng notifications có foreign key constraints đúng

### Badge không cập nhật:
→ Kiểm tra Console:
- Network tab: Request đến `notifications_ajax.php?action=count` có thành công không?
- Response có đúng format JSON không?

---

## Cấu trúc bảng notifications:

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| id | INT | Primary key |
| user_id | INT | ID người nhận thông báo (FK → user.id_user) |
| actor_id | INT | ID người thực hiện hành động (FK → user.id_user) |
| type | ENUM | Loại: 'like', 'comment', 'mention', 'system' |
| target_type | ENUM | Đối tượng: 'post', 'comment' |
| target_id | INT | ID của đối tượng |
| post_id | INT | ID bài viết liên quan (FK → posts.id) |
| content | TEXT | Nội dung thông báo (cho mention) |
| is_read | TINYINT(1) | Đã đọc: 0=chưa, 1=rồi |
| created_at | TIMESTAMP | Thời gian tạo |

---

## Lưu ý quan trọng:

1. **Không tự thông báo cho chính mình**: Nếu bạn like/comment bài viết của chính bạn, sẽ không tạo thông báo.

2. **Like chỉ thông báo 1 lần**: Nếu user A like bài viết của user B nhiều lần trong 24h, chỉ thông báo lần đầu.

3. **Mention cần username chính xác**: Phải gõ `@username` đúng với username trong database.

4. **Thông báo tự động xóa**: Khi xóa bài viết/user, thông báo liên quan sẽ tự động xóa (CASCADE).

---

## Tương lai có thể mở rộng:

- Thêm WebSocket/Server-Sent Events cho real-time không cần polling
- Thêm âm thanh khi có thông báo mới
- Push notification trên mobile
- Email notification
- Lọc thông báo theo loại
- Xóa từng thông báo riêng lẻ

---

✅ **Hoàn tất!** Hệ thống thông báo đã sẵn sàng sử dụng.
