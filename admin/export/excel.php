<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

// تنظیم هدر برای دانلود CSV با UTF-8
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=members_export_' . date('Y-m-d') . '.csv');

// اضافه کردن BOM برای پشتیبانی از UTF-8 در اکسل
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// ستون‌ها
fputcsv($output, [
    'شناسه',
    'نام',
    'نام خانوادگی',
    'نام پدر',
    'تاریخ تولد',
    'کدملی',
    'موبایل',
    'جنسیت',
    'نوع ورزش',
    'بیمه',
    'تاریخ شهریه',
    'چند ماهه',
    'تاریخ انقضا',
    'روز باقی‌مانده',
    'وضعیت'
]);

// دریافت همه اعضا
$stmt = $pdo->query("SELECT * FROM members ORDER BY id ASC");
$members = $stmt->fetchAll();

foreach ($members as $m) {
    fputcsv($output, [
        $m['id'],
        $m['first_name'],
        $m['last_name'],
        $m['father_name'],
        $m['birth_date'],
        $m['national_code'],
        $m['phone'],
        $m['gender'] == 'male' ? 'آقا' : 'خانم',
        $m['sport_type'],
        $m['insurance'] ? 'دارد' : 'ندارد',
        $m['payment_date'],
        $m['payment_duration'] . ' ماه',
        $m['expiry_date'],
        $m['remaining_days'],
        $m['remaining_days'] > 0 ? 'فعال' : 'غیرفعال'
    ]);
}

fclose($output);
exit;
?>