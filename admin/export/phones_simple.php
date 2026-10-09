<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

// دریافت شماره‌های همه اعضا (فقط فعال‌ها)
$stmt = $pdo->query("SELECT phone FROM members WHERE remaining_days > 0 ORDER BY phone ASC");
$phones = $stmt->fetchAll();

// تنظیم هدر برای دانلود TXT
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename=phones_list_' . date('Y-m-d') . '.txt');

// فقط شماره‌ها در هر خط
foreach ($phones as $p) {
    echo $p['phone'] . "\n";
}
exit;
?>