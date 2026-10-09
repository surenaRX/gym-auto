<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

// دریافت فیلترها
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// ساخت کوئری
$query = "SELECT phone, first_name, last_name FROM members WHERE 1=1";
$params = [];

if ($gender_filter === 'male') {
    $query .= " AND gender = 'male'";
} elseif ($gender_filter === 'female') {
    $query .= " AND gender = 'female'";
}

if ($status_filter === 'active') {
    $query .= " AND remaining_days > 0";
} elseif ($status_filter === 'inactive') {
    $query .= " AND remaining_days <= 0";
}

$query .= " ORDER BY first_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

// تنظیم هدر برای دانلود TXT
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename=phones_export_' . date('Y-m-d') . '.txt');

// نوشتن خروجی
$output = "============================================\n";
$output .= "   لیست شماره موبایل اعضا\n";
$output .= "   تاریخ: " . date('Y/m/d') . "\n";
$output .= "   تعداد: " . count($members) . " نفر\n";
$output .= "============================================\n\n";

foreach ($members as $m) {
    $output .= $m['phone'] . " - " . $m['first_name'] . " " . $m['last_name'] . "\n";
}

$output .= "\n============================================\n";
$output .= "   پایان لیست\n";
$output .= "============================================\n";

echo $output;
exit;
?>