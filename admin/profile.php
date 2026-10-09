<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$user_id = get_current_user_id();
$full_name = get_current_user_fullname();
$username = $_SESSION['username'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'تمامی فیلدها را پر کنید';
    } elseif ($new_password !== $confirm_password) {
        $error = 'رمز جدید و تکرار آن مطابقت ندارند';
    } elseif (strlen($new_password) < 4) {
        $error = 'رمز جدید باید حداقل ۴ کاراکتر باشد';
    } else {
        // بررسی رمز فعلی
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$new_hash, $user_id]);

            if ($result) {
                $success = ' رمز عبور با موفقیت تغییر کرد!';
            } else {
                $error = ' خطا در تغییر رمز عبور';
            }
        } else {
            $error = ' رمز فعلی اشتباه است';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغییر رمز عبور</title>
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
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h2 {
            font-size: 20px;
            font-weight: 300;
            color: #F5F5F5;
        }
        .header h2 span {
            color: #FEEC41;
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

        .user-info {
            text-align: center;
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            padding: 14px 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.03);
        }
        .user-info .name {
            color: #F5F5F5;
            font-weight: 500;
            font-size: 16px;
        }
        .user-info .username {
            color: rgba(255,255,255,0.3);
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            color: #ddd;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 13px;
        }
        label .star {
            color: #FEEC41;
        }
        input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            color: #F5F5F5;
            font-size: 14px;
            font-family: inherit;
            transition: 0.3s;
        }
        input:focus {
            border-color: #FEEC41;
            outline: none;
            background: rgba(255,255,255,0.07);
            box-shadow: 0 0 0 3px rgba(254, 236, 65, 0.05);
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            background: #FEEC41;
            color: #222831;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(254, 236, 65, 0.15);
        }

        .alert {
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.05);
        }
        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.05);
        }

        .hint {
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            margin-top: 4px;
        }

        @media (max-width: 600px) {
            .container { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="bg-glow"></div>
<div class="bg-glow-2"></div>

<div class="container">
    <div class="header">
        <h2>🔑 تغییر <span>رمز عبور</span></h2>
        <a href="dashboard.php" class="back">← بازگشت</a>
    </div>

    <div class="user-info">
        <div class="name"><?php echo htmlspecialchars($full_name); ?></div>
        <div class="username">@<?php echo htmlspecialchars($username); ?></div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>رمز فعلی <span class="star">*</span></label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label>رمز جدید <span class="star">*</span></label>
            <input type="password" name="new_password" required minlength="4">
            <div class="hint">حداقل ۴ کاراکتر</div>
        </div>
        <div class="form-group">
            <label>تکرار رمز جدید <span class="star">*</span></label>
            <input type="password" name="confirm_password" required minlength="4">
        </div>

        <button type="submit" class="btn">💾 تغییر رمز</button>
    </form>
</div>

</body>
</html>