<?php

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'xblacker_gym');
define('DB_USER', 'xblacker_admin');
define('DB_PASS', 'Sardar8415');

// اتصال به دیتابیس
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// تنظیم timezone
date_default_timezone_set('Asia/Tehran');

try {
    // کاهش ۱ روز از remaining_days
    $stmt = $pdo->prepare("
        UPDATE members 
        SET remaining_days = remaining_days - 1,
            status = CASE 
                WHEN remaining_days - 1 <= 0 THEN 'inactive' 
                ELSE 'active' 
            END
        WHERE remaining_days > 0
    ");
    
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    $log = date('Y-m-d H:i:s') . " - تعداد به‌روز شده: " . $affected . "\n";
    file_put_contents(__DIR__ . '/daily_update.log', $log, FILE_APPEND);
    
    echo "✅ " . $affected . " عضو به‌روز شد.\n";
    
} catch (Exception $e) {
    $log = date('Y-m-d H:i:s') . " - خطا: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/daily_update.log', $log, FILE_APPEND);
    echo "❌ خطا: " . $e->getMessage() . "\n";
}
?>