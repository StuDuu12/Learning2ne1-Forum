# 🎓 Hướng dẫn cho người mới học PHP

## 📚 Cấu trúc project đơn giản

### Files quan trọng nhất:

1. **config.php** - Nơi kết nối database
2. **includes/functions.php** - Các hàm xử lý
3. **includes/helpers.php** - Các hàm helper đơn giản
4. **index.php** - Trang chủ
5. **pages/\*.php** - Các trang khác

## 🔧 Các khái niệm cơ bản

### 1. Kết nối Database (config.php)

```php
// Tạo kết nối PDO
$pdo = new PDO("mysql:host=localhost;dbname=ten_db", "user", "pass");

// PDO giúp chống SQL Injection
// Luôn dùng prepared statements:
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$user_id]);
```

### 2. Session (Lưu trạng thái đăng nhập)

```php
// Bắt đầu session (đã có trong config.php)
session_start();

// Lưu dữ liệu vào session
$_SESSION['user_id'] = 123;

// Đọc dữ liệu từ session
if (isset($_SESSION['user_id'])) {
    echo "Đã đăng nhập";
}

// Xóa session (logout)
session_destroy();
```

### 3. Functions (Hàm)

```php
// Định nghĩa hàm
function tinh_tong($a, $b) {
    return $a + $b;
}

// Gọi hàm
$ket_qua = tinh_tong(5, 3); // = 8

// Hàm với tham số mặc định
function chao($ten = "Khách") {
    return "Xin chào, " . $ten;
}
```

### 4. Include files

```php
// Include file khác vào file hiện tại
require_once 'config.php';  // Chỉ load 1 lần
include 'header.php';       // Có thể load nhiều lần

// require vs include:
// require - Dừng chương trình nếu file không tồn tại
// include - Cảnh báo nhưng vẫn chạy tiếp
```

## 💡 Các pattern thường dùng

### Pattern 1: Kiểm tra đăng nhập

```php
// Trong functions.php
function da_dang_nhap() {
    return isset($_SESSION['user_id']);
}

// Sử dụng
if (!da_dang_nhap()) {
    header("Location: pages/login.php");
    exit;
}
```

### Pattern 2: Lấy dữ liệu từ database

```php
// Prepared statement với placeholder (?)
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(); // Lấy 1 dòng

// Hoặc lấy nhiều dòng
$posts = $stmt->fetchAll(); // Trả về array

// Với tên placeholder
$stmt = $pdo->prepare("SELECT * FROM user WHERE username = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();
```

### Pattern 3: Xử lý form

```php
// Kiểm tra form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate
    if (empty($username) || empty($password)) {
        $error = "Vui lòng điền đầy đủ thông tin";
    } else {
        // Xử lý login...
    }
}
```

### Pattern 4: Bảo mật output (chống XSS)

```php
// LUÔN escape khi hiển thị dữ liệu từ database
function dau_ra_an_toan($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Sử dụng trong HTML
<h1><?= dau_ra_an_toan($post['title']) ?></h1>

// KHÔNG làm thế này (nguy hiểm):
<h1><?= $post['title'] ?></h1> ❌
```

### Pattern 5: Hash password

```php
// Khi đăng ký - Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Lưu vào database
$stmt = $pdo->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
$stmt->execute([$username, $hashed]);

// Khi login - Verify password
$stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // Đăng nhập thành công
    $_SESSION['user_id'] = $user['id'];
}
```

## 🎯 Luồng hoạt động của trang

### Ví dụ: Trang tạo bài viết (create_post.php)

```php
<?php
// 1. Load config và functions
require_once '../config.php';
require_once '../includes/functions.php';

// 2. Kiểm tra đăng nhập
if (!da_dang_nhap()) {
    redirect('login.php');
}

// 3. Xử lý form (nếu có)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    // Validate
    if (empty($title)) {
        $error = "Vui lòng nhập tiêu đề";
    } else {
        // Insert vào database
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $content]);

        $success = "Đã tạo bài viết!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tạo bài viết</title>
</head>
<body>
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="title" placeholder="Tiêu đề">
        <textarea name="content" placeholder="Nội dung"></textarea>
        <button type="submit">Đăng bài</button>
    </form>
</body>
</html>
```

## 🔍 Debug Tips

### 1. Hiển thị lỗi PHP

```php
// Thêm vào đầu file khi dev
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### 2. Debug biến

```php
// Xem nội dung biến
var_dump($variable);

// Xem array dễ đọc hơn
echo '<pre>';
print_r($array);
echo '</pre>';

// Dừng chương trình để debug
die("Dừng ở đây");
```

### 3. Debug SQL query

```php
try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
```

## 📝 Best Practices

1. ✅ Luôn dùng prepared statements (chống SQL Injection)
2. ✅ Luôn escape output với `htmlspecialchars()` (chống XSS)
3. ✅ Validate input từ user
4. ✅ Hash password với `password_hash()`
5. ✅ Kiểm tra đăng nhập trước khi truy cập trang protected
6. ✅ Dùng `exit()` sau `header("Location: ...")`
7. ✅ Giữ code đơn giản, dễ đọc

## 🚫 Common Mistakes

1. ❌ Quên `exit()` sau redirect

    ```php
    header("Location: login.php");
    exit(); // QUAN TRỌNG!
    ```

2. ❌ SQL Injection

    ```php
    // SAI:
    $sql = "SELECT * FROM user WHERE id = $id";

    // ĐÚNG:
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$id]);
    ```

3. ❌ XSS Vulnerability

    ```php
    // SAI:
    echo $user_input;

    // ĐÚNG:
    echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
    ```

4. ❌ Session chưa start

    ```php
    // SAI:
    $_SESSION['user_id'] = 123; // Lỗi nếu chưa session_start()

    // ĐÚNG:
    session_start();
    $_SESSION['user_id'] = 123;
    ```

## 📚 Đọc thêm

-   PHP Manual: https://www.php.net/manual/en/
-   PDO Tutorial: https://www.php.net/manual/en/book.pdo.php
-   Security Best Practices: https://www.php.net/manual/en/security.php

---

**Chúc bạn học tốt! 🎉**
