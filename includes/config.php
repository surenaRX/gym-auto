<?php
// ============================================
// تنظیمات دیتابیس (مطابق با اطلاعات هاست)
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'xblacker_gym');
define('DB_USER', 'xblacker_admin');
define('DB_PASS', 'Sardar8415');

// ============================================
// تنظیمات Session (برای خروج خودکار با بستن مرورگر)
// ============================================
ini_set('session.cookie_lifetime', 0);          // کوکی سشن با بستن مرورگر پاک میشه
ini_set('session.gc_maxlifetime', 3600);        // یک ساعت (اختیاری)
ini_set('session.use_only_cookies', 1);         // فقط از کوکی استفاده کن
ini_set('session.cookie_httponly', 1);          // جلوگیری از دسترسی جاوااسکریپت به کوکی

// تنظیم پارامترهای کوکی سشن
session_set_cookie_params(0, '/', '', false, true);

// ============================================
// اتصال به دیتابیس با PDO
// ============================================
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// ============================================
// شروع سشن (Session)
// ============================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// تنظیمات منطقه زمانی (برای تاریخ شمسی)
// ============================================
date_default_timezone_set('Asia/Tehran');

// ============================================
// مسیرهای اصلی سایت (برای هاست)
// ============================================
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'https://gym.xblacker.ir');

// ============================================
// شامل کردن فایل‌های مورد نیاز
// ============================================
require_once __DIR__ . '/jdf.php';              // 
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/log_functions.php';   // 
?>