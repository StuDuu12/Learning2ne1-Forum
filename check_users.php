<?php
require_once 'config.php';

echo "=== Kiểm tra user trong database ===\n\n";

$stmt = $pdo->prepare('SELECT username, password, account_level FROM user');
$stmt->execute();
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "Username: " . $user['username'] . "\n";
    echo "Password: " . $user['password'] . "\n";
    echo "Level: " . $user['account_level'] . "\n";
    echo "---\n";
}

echo "\n=== Test MD5 hashes ===\n";
echo "md5('1') = " . md5('1') . "\n";
echo "md5('admin') = " . md5('admin') . "\n";
echo "md5('password') = " . md5('password') . "\n";
