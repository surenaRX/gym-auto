<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

    //    حذف کاربر  
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != get_current_user_id()) {
        // چک کن کاربر مخفی نباشه
        $stmt = $pdo->prepare("SELECT is_hidden FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user && $user['is_hidden'] == 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success = '✅ کاربر با موفقیت حذف شد!';
        } else {
            $error = '❌ نمی‌توانید کاربر مخفی را حذف کنید!';
        }
    } else {
        $error = '❌ نمی‌توانید خودتان را حذف کنید!';
    }
    log_action('delete_user', $username, 'حذف توسط: ' . get_current_user_fullname());
}

// اضافه کردن کاربر
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');

    if (empty($username) || empty($password) || empty($role) || empty($full_name)) {
        $error = 'تمامی فیلدها را پر کنید';
    } elseif (strlen($password) < 4) {
        $error = 'رمز عبور باید حداقل ۴ کاراکتر باشد';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = '❌ این نام کاربری قبلاً ثبت شده است';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, is_hidden) VALUES (?, ?, ?, ?, 0)");
            $result = $stmt->execute([$username, $hash, $role, $full_name]);

            if ($result) {
                $success = '✅ کاربر با موفقیت اضافه شد!';
                log_action('delete_user', $username, 'حذف توسط: ' . get_current_user_fullname());

            } else {
                $error = '❌ خطا در اضافه کردن کاربر';
            }

        }
    }
    
}

// دریافت لیست کاربران (فقط کاربران عادی، نه مخفی)
$stmt = $pdo->query("SELECT * FROM users WHERE is_hidden = 0 ORDER BY id ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Tahoma, sans-serif;
            background: #222831;
            color: #F5F5F5;
            min-height: 100vh;
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
            max-width: 1000px;
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
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h2 {
            font-size: 22px;
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
            display: inline-block;
            margin-bottom: 5px;
        }
        .back:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .section-title {
            font-size: 16px;
            color: #F5F5F5;
            margin: 25px 0 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }

        .row {
            display: flex;
            gap: 16px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .col {
            flex: 1;
            min-width: 160px;
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
        input, select {
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
        input:focus, select:focus {
            border-color: #FEEC41;
            outline: none;
            background: rgba(255,255,255,0.07);
            box-shadow: 0 0 0 3px rgba(254, 236, 65, 0.05);
        }
        select option {
            background: #222831;
            color: #F5F5F5;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-success {
            background: #FEEC41;
            color: #222831;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(254, 236, 65, 0.15);
        }
        .btn-danger {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }
        .btn-danger:hover {
            background: rgba(231, 76, 60, 0.25);
        }
        .btn-sm {
            padding: 4px 12px;
            font-size: 12px;
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

        .table-wrap {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: right;
            padding: 12px 12px;
            background: rgba(255,255,255,0.02);
            color: rgba(255,255,255,0.4);
            font-weight: 500;
            font-size: 13px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        td {
            padding: 12px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            color: #F5F5F5;
            font-size: 14px;
        }
        tr:hover td {
            background: rgba(255,255,255,0.02);
        }

        .badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-admin {
            background: rgba(231, 76, 60, 0.12);
            color: #e74c3c;
        }
        .badge-coach_m {
            background: rgba(52, 152, 219, 0.12);
            color: #3498db;
        }
        .badge-coach_f {
            background: rgba(232, 62, 140, 0.12);
            color: #e83e8c;
        }

        .actions {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .self-label {
            color: rgba(255,255,255,0.2);
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            .header h2 { font-size: 18px; }
            .row { flex-direction: column; }
            .col { min-width: auto; }
        }
    </style>
</head>
<body>

<div class="bg-glow"></div>
<div class="bg-glow-2"></div>

<div class="container">
    <div class="header">
        <div>
            <a href="../dashboard.php" class="back">← بازگشت</a>
            <h2>👥 مدیریت <span>کاربران</span></h2>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- فرم افزودن کاربر -->
    <h3 class="section-title">➕ افزودن کاربر جدید</h3>
    <form method="POST">
        <div class="row">
            <div class="col">
                <label>نام کاربری <span class="star">*</span></label>
                <input type="text" name="username" required>
            </div>
            <div class="col">
                <label>نام کامل <span class="star">*</span></label>
                <input type="text" name="full_name" required>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <label>رمز عبور <span class="star">*</span></label>
                <input type="text" name="password" required minlength="4">
                <div style="font-size:11px;color:rgba(255,255,255,0.2);margin-top:4px;">حداقل ۴ کاراکتر</div>
            </div>
            <div class="col">
                <label>نقش <span class="star">*</span></label>
                <select name="role" required>
                    <option value="">انتخاب</option>
                    <option value="admin">مدیر سیستم</option>
                    <option value="coach_m">مربی آقایان</option>
                    <option value="coach_f">مربی بانوان</option>
                </select>
            </div>
        </div>
        <button type="submit" name="add_user" class="btn btn-success">➕ افزودن کاربر</button>
    </form>

    <!-- لیست کاربران -->
    <h3 class="section-title">📋 لیست کاربران</h3>
    <?php if (empty($users)): ?>
        <div style="text-align:center;padding:30px 0;color:rgba(255,255,255,0.2);">هیچ کاربری ثبت نشده است</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>نام کاربری</th>
                        <th>نام کامل</th>
                        <th>نقش</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $u['role']; ?>">
                                <?php
                                $roles = ['admin' => 'مدیر سیستم', 'coach_m' => 'مربی آقایان', 'coach_f' => 'مربی بانوان'];
                                echo $roles[$u['role']] ?? $u['role'];
                                ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if ($u['id'] != get_current_user_id()): ?>
                                    <a href="index.php?delete=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('آیا مطمئن هستید؟')">🗑️ حذف</a>
                                <?php else: ?>
                                    <span class="self-label">(خودتان)</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>