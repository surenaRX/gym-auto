<?php
require_once __DIR__ . '/includes/config.php';

$password_m = '123456';
$password_f = '123456';
$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);

// بروزرسانی رمز admin
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

// بروزرسانی رمز coach_m
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'coach_m'");
$stmt->execute([$hash]);

// بروزرسانی رمز coach_f
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'coach_f'");
$stmt->execute([$hash]);

echo "✅ رمزهای کاربران با موفقیت به روزرسانی شد!<br>";
echo "🔑 رمز همه کاربران: 123456";
?>