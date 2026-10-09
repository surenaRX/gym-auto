<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../dashboard.php');
    exit;
}

// دریافت لاگ‌ها
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$logs = get_logs($limit);

// پاک کردن لاگ‌های قدیمی
if (isset($_GET['clear']) && $_GET['clear'] == 'old') {
    clear_old_logs(30);
    header('Location: index.php?cleared=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارشات سیستم</title>
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
            position: fixed; top: -150px; right: -150px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(254,236,65,0.15) 0%, transparent 70%);
            border-radius: 50%; z-index: 0; pointer-events: none;
        }
        .bg-glow-2 {
            position: fixed; bottom: -200px; left: -200px;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(254,236,65,0.08) 0%, transparent 70%);
            border-radius: 50%; z-index: 0; pointer-events: none;
        }

        .container {
            max-width: 1200px; margin: 0 auto;
            background: rgba(57,62,70,0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.04);
            position: relative; z-index: 1;
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
            font-size: 22px; font-weight: 300; color: #F5F5F5;
        }
        .header h2 span { color: #FEEC41; }
        .back {
            color: #aaa; text-decoration: none;
            padding: 6px 16px;
            background: rgba(255,255,255,0.04);
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s; font-size: 13px;
        }
        .back:hover { background: rgba(255,255,255,0.08); color: #fff; }

        .filters {
            display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .filters select, .filters input {
            padding: 8px 14px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 8px;
            color: #F5F5F5;
            font-size: 13px;
        }
        .filters select option { background: #222831; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: right; padding: 10px 12px;
            background: rgba(255,255,255,0.02);
            color: rgba(255,255,255,0.4);
            font-weight: 500; font-size: 13px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            color: #F5F5F5;
            font-size: 13px;
        }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .badge {
            padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;
        }
        .badge-add { background: rgba(46,204,113,0.15); color: #2ecc71; }
        .badge-delete { background: rgba(231,76,60,0.15); color: #e74c3c; }
        .badge-edit { background: rgba(241,196,15,0.15); color: #f1c40f; }
        .badge-renew { background: rgba(52,152,219,0.15); color: #3498db; }
        .badge-login { background: rgba(155,89,182,0.15); color: #9b59b6; }
        .badge-user { background: rgba(255,255,255,0.05); color: #aaa; }

        .actions {
            display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;
        }
        .btn {
            padding: 8px 20px; border: none; border-radius: 8px;
            font-size: 13px; font-weight: 500; cursor: pointer; transition: 0.3s;
            text-decoration: none; display: inline-block;
        }
        .btn-danger { background: rgba(231,76,60,0.15); color: #e74c3c; }
        .btn-danger:hover { background: rgba(231,76,60,0.25); }
        .btn-secondary { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.6); }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); color: #fff; }

        .clear-link {
            color: #e74c3c; text-decoration: none; font-size: 13px;
        }
        .clear-link:hover { text-decoration: underline; }

        .stats {
            display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .stat-box {
            background: rgba(255,255,255,0.02); padding: 10px 20px;
            border-radius: 10px; border: 1px solid rgba(255,255,255,0.03);
        }
        .stat-box .num { font-weight: 700; font-size: 18px; color: #F5F5F5; }
        .stat-box .label { color: #aaa; font-size: 13px; margin-right: 8px; }

        @media (max-width:768px) {
            .container { padding: 20px; }
            .filters { flex-direction: column; }
            td, th { padding: 6px 8px; font-size: 12px; }
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
            <h2>📋 <span>گزارشات</span> سیستم</h2>
        </div>
        <div>
            <span style="color:rgba(255,255,255,0.2);font-size:13px;">
                <?php echo count($logs); ?> رویداد اخیر
            </span>
        </div>
    </div>

    <?php if (isset($_GET['cleared'])): ?>
        <div style="background:rgba(46,204,113,0.1);color:#2ecc71;padding:12px 18px;border-radius:12px;margin-bottom:18px;border:1px solid rgba(46,204,113,0.05);">
            ✅ لاگ‌های قدیمی (بیشتر از ۳۰ روز) پاک شدند
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-box">
            <span class="num"><?php echo count($logs); ?></span>
            <span class="label">تعداد کل رویدادها</span>
        </div>
        <div class="stat-box">
            <span class="num">
                <?php
                $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM logs");
                echo $stmt->fetch()['total'];
                ?>
            </span>
            <span class="label">کاربران فعال</span>
        </div>
    </div>

    <div class="filters">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;">
            <input type="number" name="limit" placeholder="تعداد نمایش" value="<?php echo $limit; ?>" style="width:120px;">
            <button type="submit" class="btn btn-secondary">اعمال</button>
        </form>
        <a href="?clear=old" class="clear-link" onclick="return confirm('آیا مطمئن هستید؟')">🗑️ پاک کردن لاگ‌های قدیمی (۳۰ روز)</a>
    </div>

    <?php if (empty($logs)): ?>
        <div style="text-align:center;padding:50px 0;color:rgba(255,255,255,0.2);">
            <span style="font-size:50px;display:block;margin-bottom:15px;">📭</span>
            <p>هیچ گزارشی ثبت نشده است</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کاربر</th>
                        <th>عملیات</th>
                        <th>هدف</th>
                        <th>جزئیات</th>
                        <th>آی‌پی</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                            <span style="color:rgba(255,255,255,0.2);font-size:11px;">(ID: <?php echo $log['user_id']; ?>)</span>
                        </td>
                        <td>
                            <?php
                            $action_labels = [
                                'add_member' => 'افزودن عضو',
                                'edit_member' => 'ویرایش عضو',
                                'delete_member' => 'حذف عضو',
                                'renew_member' => 'تمدید شهریه',
                                'add_user' => 'افزودن کاربر',
                                'delete_user' => 'حذف کاربر',
                                'login' => 'ورود',
                                'logout' => 'خروج'
                            ];
                            $badge_classes = [
                                'add_member' => 'badge-add',
                                'edit_member' => 'badge-edit',
                                'delete_member' => 'badge-delete',
                                'renew_member' => 'badge-renew',
                                'add_user' => 'badge-user',
                                'delete_user' => 'badge-delete',
                                'login' => 'badge-login',
                                'logout' => 'badge-user'
                            ];
                            $label = $action_labels[$log['action']] ?? $log['action'];
                            $class = $badge_classes[$log['action']] ?? 'badge-user';
                            ?>
                            <span class="badge <?php echo $class; ?>"><?php echo $label; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($log['target'] ?? '-'); ?></td>
                        <td style="max-width:200px;word-wrap:break-word;"><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                        <td style="font-size:12px;color:rgba(255,255,255,0.3);"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        <td style="font-size:12px;color:rgba(255,255,255,0.3);">
                            <?php echo date('Y/m/d H:i', strtotime($log['created_at'])); ?>
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