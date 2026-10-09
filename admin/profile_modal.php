<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

if (!is_logged_in()) {
    exit;
}

$user_id = get_current_user_id();
$full_name = get_current_user_fullname();
$username = $_SESSION['username'];
$role = get_current_user_role();

$role_names = [
    'admin' => 'مدیر سیستم',
    'coach_m' => 'مربی آقایان',
    'coach_f' => 'مربی بانوان'
];

// دریافت تاریخ ثبت نام کاربر
$stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$created_at = $user ? convert_datetime_to_jalali($user['created_at']) : 'نامشخص';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اطلاعات کاربر</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Tahoma, sans-serif;
            background: transparent;
            color: #F5F5F5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .modal-box {
            width: 100%;
            max-width: 450px;
            background: rgba(57, 62, 70, 0.25);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 24px;
            padding: 30px 35px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.5);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h3 {
            font-size: 20px;
            font-weight: 300;
            color: #fff;
        }
        .header h3 span {
            color: #FEEC41;
        }
        .close-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.3);
            font-size: 24px;
            cursor: pointer;
            transition: 0.3s;
            padding: 4px 8px;
            border-radius: 8px;
        }
        .close-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.04);
        }

        .avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #FEEC41;
            color: #222831;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-item .label {
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
        }
        .info-item .value {
            color: #fff;
            font-weight: 500;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-admin {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }
        .badge-coach_m {
            background: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }
        .badge-coach_f {
            background: rgba(232, 62, 140, 0.15);
            color: #e83e8c;
        }

        @media (max-width: 500px) {
            .modal-box { padding: 20px; }
        }
    </style>
</head>
<body>
<div class="modal-box">
    <div class="header">
        <h3>👤 <span>اطلاعات کاربر</span></h3>
        <button class="close-btn" onclick="parent.closeModal()">✕</button>
    </div>

    <div class="avatar-large"><?php echo mb_substr($full_name, 0, 1); ?></div>

    <div class="info-item">
        <span class="label">نام کامل</span>
        <span class="value"><?php echo htmlspecialchars($full_name); ?></span>
    </div>

    <div class="info-item">
        <span class="label">نام کاربری</span>
        <span class="value"><?php echo htmlspecialchars($username); ?></span>
    </div>

    <div class="info-item">
        <span class="label">نقش</span>
        <span class="value">
            <span class="badge badge-<?php echo $role; ?>">
                <?php echo $role_names[$role] ?? $role; ?>
            </span>
        </span>
    </div>

    <div class="info-item">
        <span class="label">تاریخ عضویت</span>
        <span class="value"><?php echo $created_at; ?></span>
    </div>
</div>
</body>
</html>