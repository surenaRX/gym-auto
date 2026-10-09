<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    log_action('delete_member', $member['first_name'] . ' ' . $member['last_name'], 'کدملی: ' . $member['national_code']);
    header('Location: ../index.php');
    exit;
}

if (!is_admin()) {
    header('Location: ../dashboard.php');
    exit;
}

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$member_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND gender = 'male'");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        header('Location: index.php?deleted=1');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف عضو</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Tahoma, sans-serif;
            background: #222831;
            color: #F5F5F5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        .bg-glow {
            position: fixed;
            top: -150px;
            right: -150px;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(254, 236, 65, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }
        .bg-glow-2 {
            position: fixed;
            bottom: -200px;
            left: -200px;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(254, 236, 65, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }

        .container {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
            background: rgba(57, 62, 70, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.04);
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h2 {
            font-size: 20px;
            font-weight: 300;
            color: #F5F5F5;
        }
        .header h2 span {
            color: #e74c3c;
        }
        .back {
            color: #aaa;
            text-decoration: none;
            padding: 6px 16px;
            background: rgba(255,255,255,0.04);
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
            font-size: 13px;
        }
        .back:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .warning-icon {
            font-size: 60px;
            color: #e74c3c;
            margin-bottom: 15px;
            display: block;
        }

        .warning-text {
            font-size: 18px;
            color: #F5F5F5;
            margin-bottom: 8px;
        }
        .warning-text span {
            color: #e74c3c;
        }

        .member-info {
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            padding: 16px 20px;
            margin: 18px 0 25px;
            border: 1px solid rgba(255,255,255,0.03);
        }
        .member-info .row-info {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid rgba(255,255,255,0.02);
        }
        .member-info .row-info:last-child {
            border-bottom: none;
        }
        .member-info .label-info {
            color: rgba(255,255,255,0.4);
            font-size: 13px;
        }
        .member-info .value-info {
            color: #fff;
            font-weight: 500;
            font-size: 14px;
        }

        .btn {
            padding: 10px 30px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.2);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.6);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        @media (max-width: 600px) {
            .container { padding: 20px; }
            .actions { flex-direction: column; }
            .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

<div class="bg-glow"></div>
<div class="bg-glow-2"></div>

<div class="container">
    <div class="header">
        <h2>🗑️ <span>حذف</span> عضو</h2>
        <a href="index.php" class="back">← بازگشت</a>
    </div>

    <span class="warning-icon">⚠️</span>
    <div class="warning-text">
        آیا از <span>حذف</span> این عضو مطمئن هستید؟
    </div>
    <p style="color:rgba(255,255,255,0.3);font-size:13px;margin-bottom:5px;">
        این عمل غیرقابل بازگشت است
    </p>

    <div class="member-info">
        <div class="row-info">
            <span class="label-info">نام و نام خانوادگی</span>
            <span class="value-info"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></span>
        </div>
        <div class="row-info">
            <span class="label-info">کدملی</span>
            <span class="value-info"><?php echo htmlspecialchars($member['national_code']); ?></span>
        </div>
        <div class="row-info">
            <span class="label-info">موبایل</span>
            <span class="value-info"><?php echo htmlspecialchars($member['phone']); ?></span>
        </div>
        <div class="row-info">
            <span class="label-info">وضعیت</span>
            <span class="value-info">
                <span style="color:<?php echo $member['remaining_days'] > 0 ? '#2ecc71' : '#e74c3c'; ?>">
                    <?php echo $member['remaining_days'] > 0 ? 'فعال' : 'غیرفعال'; ?>
                </span>
            </span>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <div class="actions">
            <button type="submit" class="btn btn-danger">🗑️ حذف</button>
            <a href="index.php" class="btn btn-secondary">🔙 انصراف</a>
        </div>
    </form>
</div>

</body>
</html>