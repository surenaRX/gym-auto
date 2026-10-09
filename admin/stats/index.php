<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

// دریافت آمار به ساده‌ترین شکل
$total_members = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$total_men = $pdo->query("SELECT COUNT(*) FROM members WHERE gender = 'male'")->fetchColumn();
$total_women = $pdo->query("SELECT COUNT(*) FROM members WHERE gender = 'female'")->fetchColumn();
$active_members = $pdo->query("SELECT COUNT(*) FROM members WHERE remaining_days > 0")->fetchColumn();
$inactive_members = $total_members - $active_members;
$expiring_soon = $pdo->query("SELECT COUNT(*) FROM members WHERE remaining_days > 0 AND remaining_days <= 7")->fetchColumn();

// اعضای در حال انقضا
$expiring_members = $pdo->query("SELECT first_name, last_name, phone, remaining_days FROM members WHERE remaining_days > 0 AND remaining_days <= 7 ORDER BY remaining_days ASC")->fetchAll();

// ۵ عضو آخر
$recent_members = $pdo->query("SELECT first_name, last_name, gender, created_at FROM members ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آمار و گزارشات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Tahoma, sans-serif;
            background: #222831;
            color: #F5F5F5;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(57,62,70,0.45);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .header h2 { color: #F5F5F5; }
        .header h2 span { color: #FEEC41; }
        .back {
            color: #aaa;
            text-decoration: none;
            padding: 6px 16px;
            background: rgba(255,255,255,0.04);
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .back:hover { background: rgba(255,255,255,0.08); color: #fff; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255,255,255,0.02);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.04);
        }
        .stat-card .number { font-size: 28px; font-weight: 700; color: #F5F5F5; }
        .stat-card .label { color: rgba(255,255,255,0.4); font-size: 13px; margin-top: 4px; }
        .stat-card .badge-count {
            display: inline-block;
            background: rgba(254,236,65,0.08);
            color: #FEEC41;
            font-size: 11px;
            padding: 2px 12px;
            border-radius: 20px;
            margin-top: 5px;
        }
        .stat-card.danger { border-color: rgba(231,76,60,0.15); }
        
        .section-title {
            font-size: 16px;
            color: #F5F5F5;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        
        .table-wrap {
            overflow-x: auto;
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.03);
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: right;
            padding: 10px 14px;
            background: rgba(255,255,255,0.02);
            color: rgba(255,255,255,0.4);
            font-weight: 500;
            font-size: 13px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        td {
            padding: 10px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            color: #F5F5F5;
            font-size: 14px;
        }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .badge { padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-warning { background: rgba(241,196,15,0.12); color: #f1c40f; }
        .badge-male { background: rgba(52,152,219,0.12); color: #3498db; }
        .badge-female { background: rgba(232,62,140,0.12); color: #e83e8c; }
        .empty { text-align: center; padding: 30px 0; color: rgba(255,255,255,0.2); }
        .row { display: flex; gap: 25px; flex-wrap: wrap; }
        .col { flex: 1; min-width: 300px; }
        
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .row { flex-direction: column; }
            .col { min-width: auto; }
        }
        @media (max-width: 500px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <a href="../dashboard.php" class="back">← بازگشت</a>
            <h2>📊 آمار <span>و گزارشات</span></h2>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?php echo $total_members; ?></div>
            <div class="label">تعداد کل اعضا</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $total_men; ?></div>
            <div class="label">آقایان</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $total_women; ?></div>
            <div class="label">بانوان</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $active_members; ?></div>
            <div class="label">فعال</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $inactive_members; ?></div>
            <div class="label">غیرفعال</div>
        </div>
        <div class="stat-card danger">
            <div class="number"><?php echo $expiring_soon; ?></div>
            <div class="label">در حال انقضا</div>
            <div class="badge-count">کمتر از ۷ روز</div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h3 class="section-title">⚠️ اعضای در حال انقضا</h3>
            <div class="table-wrap">
                <?php if (empty($expiring_members)): ?>
                    <div class="empty">✅ همه اعضا وضعیت عادی دارند</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>نام</th><th>نام خانوادگی</th><th>موبایل</th><th>روز باقی‌مانده</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiring_members as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($m['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($m['phone']); ?></td>
                                    <td><span class="badge badge-warning"><?php echo $m['remaining_days']; ?> روز</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="col">
            <h3 class="section-title">🆕 آخرین اعضای ثبت‌نام شده</h3>
            <div class="table-wrap">
                <?php if (empty($recent_members)): ?>
                    <div class="empty">هیچ عضوی ثبت‌نام نشده</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>نام</th><th>نام خانوادگی</th><th>جنسیت</th><th>تاریخ ثبت</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_members as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($m['last_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $m['gender'] == 'male' ? 'male' : 'female'; ?>">
                                            <?php echo $m['gender'] == 'male' ? 'آقا' : 'خانم'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y/m/d', strtotime($m['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>