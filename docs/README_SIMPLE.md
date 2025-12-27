# 📚 Cấu trúc Project - Diễn đàn Sinh viên

## 📁 Cấu trúc thư mục đơn giản

```
Prj Diễn đàn/
│
├── index.php              # Trang chủ
├── config.php             # Cấu hình database và constants
│
├── includes/              # Các file PHP dùng chung
│   ├── functions.php      # Các hàm xử lý nghiệp vụ
│   ├── helpers.php        # Các hàm helper đơn giản
│   ├── navbar.php         # Menu điều hướng
│   └── ajax.php           # Xử lý AJAX requests
│
├── pages/                 # Các trang chức năng
│   ├── login.php          # Đăng nhập / Đăng ký
│   ├── logout.php         # Đăng xuất
│   ├── dashboard.php      # Trang dashboard người dùng
│   ├── profile.php        # Trang profile
│   ├── create_post.php    # Tạo bài viết mới
│   ├── post.php           # Chi tiết bài viết
│   └── post_detail_ajax.php  # Load bài viết qua AJAX
│
├── assets/               # CSS, JS, images
│   ├── css/
│   │   └── style.css
│   └── js/
│
├── database/             # File SQL
│   └── bikvyzpx_k69_nhom1.sql
│
├── uploads/              # File upload từ người dùng
│
└── docs/                 # Tài liệu
    └── README.md         # File này
```

## 🚀 Cách sử dụng

### 1. Cài đặt

1. Copy project vào `htdocs` hoặc `www` của server
2. Import file SQL từ `database/bikvyzpx_k69_nhom1.sql`
3. Cập nhật config trong `config.php` nếu cần:
    ```php
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'bikvyzpx_k69_nhom1');
    ```

### 2. Truy cập

-   Trang chủ: `http://localhost/Prj%20Diễn%20đàn/`
-   Đăng nhập: `http://localhost/Prj%20Diễn%20đàn/pages/login.php`

## 📖 Giải thích các file chính

### config.php

-   Kết nối database với PDO
-   Định nghĩa các constants (BASE_URL, UPLOAD_DIR, etc.)
-   Khởi động session

### includes/functions.php

-   Các hàm xử lý nghiệp vụ chính:
    -   `da_dang_nhap()` - Kiểm tra đã đăng nhập chưa
    -   `lay_nguoi_dung_dang_nhap()` - Lấy thông tin user hiện tại
    -   `lay_bai_viet_trending()` - Lấy bài viết trending
    -   `dau_ra_an_toan()` - Escape HTML để tránh XSS
    -   Và nhiều hàm khác...

### includes/helpers.php

-   `url($path)` - Tạo URL tuyệt đối
-   `asset($path)` - Tạo URL cho CSS/JS
-   `redirect_to($path)` - Chuyển hướng trang

### includes/ajax.php

-   Xử lý các request AJAX:
    -   `get_post_detail` - Load chi tiết bài viết
    -   `search_users` - Tìm kiếm user để mention
    -   `toggle_like` - Like/unlike bài viết
    -   `add_comment` - Thêm comment
    -   Và nhiều actions khác...

## 💡 Các tính năng chính

-   ✅ Đăng nhập / Đăng ký
-   ✅ Tạo, sửa, xóa bài viết
-   ✅ Comment và reply comment
-   ✅ Like bài viết và comment
-   ✅ Mention người dùng (@username)
-   ✅ Upload file đính kèm
-   ✅ Tạo poll/khảo sát
-   ✅ Trending posts
-   ✅ Tags cho bài viết
-   ✅ Privacy settings (public/private)
-   ✅ Responsive design

## 🔐 Bảo mật

1. **SQL Injection**: Dùng Prepared Statements cho tất cả queries
2. **XSS**: Dùng `dau_ra_an_toan()` (htmlspecialchars) cho output
3. **File Upload**: Validate file type và size
4. **Password**: Hash bằng `password_hash()` và verify bằng `password_verify()`

## 📝 Code Style

-   **Procedural PHP**: Không dùng OOP classes phức tạp
-   **Tiếng Việt**: Tên hàm và biến dùng tiếng Việt có dấu
-   **Comments**: Giải thích rõ ràng bằng tiếng Việt
-   **Simple**: Code đơn giản, dễ hiểu cho người mới học

## 🎯 Ví dụ sử dụng

### Tạo link đến trang khác

```php
<a href="<?= url('pages/profile.php?username=john') ?>">Profile</a>
```

### Load CSS/JS

```php
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
```

### Kiểm tra đăng nhập

```php
if (!da_dang_nhap()) {
    redirect_to('pages/login.php');
}
```

### Lấy thông tin user

```php
$current_user = lay_nguoi_dung_dang_nhap($pdo);
echo dau_ra_an_toan($current_user['ho_ten']);
```

## 🛠️ Cơ sở dữ liệu

### Bảng chính

-   `user` - Thông tin người dùng
-   `posts` - Bài viết
-   `comments` - Bình luận
-   `likes` - Lượt thích
-   `attachments` - File đính kèm
-   `polls` - Khảo sát
-   `poll_options` - Các lựa chọn trong poll
-   `poll_votes` - Phiếu bầu
-   `user_interests` - Sở thích người dùng
-   `reports` - Báo cáo vi phạm

## 📞 Hỗ trợ

Nếu gặp lỗi:

1. Kiểm tra PHP error log
2. Kiểm tra console browser (F12)
3. Đảm bảo đã import đúng database
4. Kiểm tra config.php đúng thông tin database

## 📚 Học thêm

Để hiểu rõ hơn về code:

1. Đọc comments trong từng file PHP
2. Xem cấu trúc database trong file .sql
3. Debug bằng `var_dump()` và `print_r()`
4. Sử dụng browser DevTools để xem AJAX requests

---

**Version**: 2.0 (Simplified)  
**Ngày cập nhật**: 25/12/2025  
**Phù hợp cho**: Người học PHP cơ bản đến trung cấp
