# Student Discussion Forum - Hệ thống Diễn đàn Sinh viên

## 📋 Giới thiệu

Hệ thống diễn đàn sinh viên được xây dựng bằng **PHP thuần (Procedural)** với MySQL và PDO. Hệ thống cho phép sinh viên chia sẻ câu hỏi, thảo luận, và học hỏi lẫn nhau.

## 🚀 Tính năng chính

### 1. **Quản lý người dùng**

-   ✅ Đăng ký / Đăng nhập
-   ✅ 3 cấp độ: Admin (0), Giáo viên (1), Sinh viên (2)
-   ✅ Hồ sơ người dùng với thống kê

### 2. **Bài viết (Posts)**

-   ✅ Tạo bài viết với tiêu đề, nội dung, tags
-   ✅ Hỗ trợ @mention người dùng trong nội dung
-   ✅ Đính kèm file (Ảnh/PDF)
-   ✅ Tạo khảo sát (Poll) kèm bài viết
-   ✅ Chế độ riêng tư (Public/Private)
-   ✅ Đánh dấu "Đã giải quyết" / "Chưa giải quyết"

### 3. **Tương tác**

-   ✅ Like bài viết và bình luận
-   ✅ Bình luận có phân cấp (Parent/Child)
-   ✅ Bỏ phiếu trong khảo sát
-   ✅ Đếm lượt xem bài viết

### 4. **Thuật toán thông minh**

-   ✅ **Trending Algorithm**: Top 5 bài viết có nhiều like nhất trong 7 ngày
-   ✅ **Recommendation Algorithm**: Đề xuất bài viết dựa trên tag mà user quan tâm
-   ✅ Tracking sở thích người dùng tự động

### 5. **Dashboard & Thống kê**

-   ✅ Chart.js để hiển thị biểu đồ
-   ✅ So sánh "Xu hướng cộng đồng" vs "Sở thích cá nhân"
-   ✅ Thống kê hệ thống cho Admin
-   ✅ Thống kê cá nhân cho mỗi user

### 6. **Bảo mật**

-   ✅ PDO Prepared Statements (chống SQL Injection)
-   ✅ htmlspecialchars() cho mọi output (chống XSS)
-   ✅ Mật khẩu MD5 (tương thích với hệ thống cũ)
-   ✅ Kiểm tra quyền truy cập

## 📁 Cấu trúc file

```
Prj Diễn đàn/
├── bikvyzpx_k69_nhom1.sql    # Database schema (đã cập nhật)
├── config.php                 # Cấu hình database và session
├── functions.php              # Các hàm helper (procedural)
├── style.css                  # CSS styling
│
├── login.php                  # Đăng nhập / Đăng ký
├── logout.php                 # Đăng xuất
├── index.php                  # Trang chủ (Feed)
├── create_post.php            # Tạo bài viết mới
├── post.php                   # Chi tiết bài viết
├── dashboard.php              # Dashboard thống kê
├── profile.php                # Hồ sơ người dùng
├── navbar.php                 # Navigation bar
│
└── uploads/                   # Thư mục lưu file upload
```

## ⚙️ Cài đặt

### 1. Cấu hình Database

**Bước 1:** Import database

```sql
-- Import file: bikvyzpx_k69_nhom1.sql vào MySQL
```

**Bước 2:** Cập nhật thông tin database trong `config.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bikvyzpx_k69_nhom1');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 2. Cấu hình Web Server

**Laragon (Đã có):**

-   Copy folder vào: `d:\laragon\www\Prj Diễn đàn`
-   Truy cập: `http://localhost/Prj%20Diễn%20đàn/`

**XAMPP:**

-   Copy folder vào: `C:\xampp\htdocs\`
-   Truy cập: `http://localhost/Prj Diễn đàn/`

### 3. Tạo thư mục uploads

Thư mục `uploads/` sẽ tự động được tạo khi upload file lần đầu.

## 👥 Tài khoản mẫu

Bạn có thể tạo user mới hoặc thêm user mẫu vào database:

```sql
INSERT INTO user (ho_ten, username, password, account_level) VALUES
('Admin User', 'admin', 'c4ca4238a0b923820dcc509a6f75849b', 0),  -- password: 1
('Teacher One', 'teacher1', 'c4ca4238a0b923820dcc509a6f75849b', 1),
('Student One', 'student1', 'c4ca4238a0b923820dcc509a6f75849b', 2);
```

## 📊 Database Schema

### Tables mới được thêm:

1. **`posts`** - Lưu bài viết
2. **`attachments`** - File đính kèm
3. **`comments`** - Bình luận
4. **`likes`** - Lượt thích
5. **`polls`** - Khảo sát
6. **`poll_options`** - Các lựa chọn khảo sát
7. **`poll_votes`** - Phiếu bầu
8. **`user_interests`** - Tracking sở thích user

## 🎨 Màu sắc chủ đạo

-   **Primary Mint:** `#00bfa5` (Green)
-   **Light Mint:** `#55efc4`
-   **Accent Yellow:** `#ffd740`
-   **Pop Yellow:** `#fdcb6e`

## 🔧 Các thuật toán chính

### Trending Algorithm

```php
// Top 5 posts với nhiều likes nhất trong 7 ngày
get_trending_posts($pdo, 5);
```

### Recommendation Algorithm

```php
// Đề xuất dựa trên tag user quan tâm
get_recommended_posts($pdo, $user_id, 10);
```

### Interest Tracking

```php
// Tự động track khi user xem bài viết
track_user_interest($pdo, $user_id, $tags);
```

## 📝 Hướng dẫn sử dụng

### Cho Sinh viên (Level 2):

1. Đăng ký/Đăng nhập
2. Tạo bài viết với tags
3. Like, comment các bài viết
4. Tham gia khảo sát
5. Xem Dashboard cá nhân

### Cho Admin (Level 0):

1. Xem tất cả bài viết (kể cả private)
2. Dashboard với thống kê hệ thống
3. Quản lý toàn bộ hoạt động

### Cho Khách (Guest):

1. Xem bài viết public
2. Không thể tương tác (like/comment)
3. Cần đăng nhập để tham gia

## 🛠️ Mở rộng trong tương lai

-   [ ] Tìm kiếm bài viết
-   [ ] Thông báo real-time
-   [ ] Upload avatar cho user
-   [ ] Export thống kê ra Excel
-   [ ] Dark mode
-   [ ] Mobile app version

## 👨‍💻 Kỹ thuật

-   **Backend:** PHP 7.4+ (Procedural Only)
-   **Database:** MySQL 8.0+ with PDO
-   **Frontend:** HTML5, CSS3, Vanilla JavaScript
-   **Charts:** Chart.js 3.x
-   **Security:** Prepared Statements, XSS Protection

## 📄 License

Educational project for university coursework.

## 📧 Support

Nếu có vấn đề, vui lòng kiểm tra:

1. Database đã import đúng chưa
2. Thông tin config.php có chính xác không
3. PHP version >= 7.4
4. PDO extension đã bật chưa

---

**Made with ❤️ for Student Community**
