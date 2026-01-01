<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bikvyzpx_k69_nhom1');



define('APP_NAME', 'Learning2ne1');
define('POSTS_PER_PAGE', 10);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); 


define('UPLOAD_DIR', __DIR__ . '/uploads/');
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
