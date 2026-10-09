<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$role = get_current_user_role();
if ($role === 'coach_m') {
    header('Location: ../dashboard.php');
    exit;
}

// ============================================
// جستجو
// ============================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM members WHERE gender = 'female'";
$params = [];

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR national_code LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

$total = count($members);
$active = 0;
foreach ($members as $m) {
    if ($m['remaining_days'] > 0) $active++;
}
$inactive = $total - $active;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست بانوان</title>
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
            top: -150px; right: -150px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(254,236,65,0.15) 0%, transparent 70%);
            border-radius: 50%; z-index: 0; pointer-events: none;
        }
        .bg-glow-2 {
            position: fixed;
            bottom: -200px; left: -200px;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(254,236,65,0.08) 0%, transparent 70%);
            border-radius: 50%; z-index: 0; pointer-events: none;
        }
        .container {
            max-width: 1400px; margin: 0 auto;
            background: rgba(57,62,70,0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px; padding: 25px 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.04);
            position: relative; z-index: 1;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header .back {
            color: #aaa; text-decoration: none;
            padding: 6px 16px;
            background: rgba(255,255,255,0.04);
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s; font-size: 13px;
            display: inline-block;
        }
        .header .back:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .header h2 { font-size: 20px; font-weight: 300; color: #F5F5F5; }
        .header h2 span { color: #FEEC41; }

        .btn-add {
            padding: 8px 20px;
            background: #FEEC41; color: #222831;
            border: none; border-radius: 10px;
            text-decoration: none;
            font-weight: 600; font-size: 14px;
            transition: 0.3s; display: inline-block;
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(254,236,65,0.15); }

        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .search-box input {
            padding: 10px 16px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            color: #F5F5F5;
            font-size: 14px;
            min-width: 250px;
            flex: 1;
        }
        .search-box input:focus {
            border-color: #FEEC41;
            outline: none;
        }
        .search-box .btn-search {
            padding: 10px 24px;
            background: #FEEC41;
            color: #222831;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .search-box .btn-search:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(254,236,65,0.15); }
        .search-box .btn-reset {
            padding: 10px 18px;
            background: rgba(255,255,255,0.05);
            color: #aaa;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            text-decoration: none;
            transition: 0.3s;
        }
        .search-box .btn-reset:hover { background: rgba(255,255,255,0.1); color: #fff; }

        .stats {
            display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .stat-box {
            background: rgba(255,255,255,0.03);
            padding: 8px 18px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.04);
            font-size: 14px;
        }
        .stat-box .num { font-weight: 700; font-size: 18px; color: #F5F5F5; }
        .stat-box .label { color: #aaa; font-size: 13px; margin-right: 6px; }
        .stat-box .green { color: #2ecc71; }
        .stat-box .red { color: #e74c3c; }

        .table-wrap {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        .table-wrap::-webkit-scrollbar { width: 6px; height: 6px; }
        .table-wrap::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); border-radius: 10px; }
        .table-wrap::-webkit-scrollbar-thumb { background: rgba(254,236,65,0.2); border-radius: 10px; }

        table { width: 100%; border-collapse: collapse; min-width: 1200px; table-layout: fixed; }
        th {
            text-align: right; padding: 10px 8px;
            background: rgba(255,255,255,0.03);
            color: rgba(255,255,255,0.4);
            font-weight: 500; font-size: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            position: sticky; top: 0; z-index: 10; background: #222831;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            color: #F5F5F5;
            font-size: 13px;
            vertical-align: middle;
            white-space: nowrap;
            word-break: keep-all;
        }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .col-id { width: 40px; }
        .col-name { width: 90px; }
        .col-lname { width: 90px; }
        .col-father { width: 80px; }
        .col-national { width: 100px; }
        .col-birth { width: 110px; white-space: nowrap; }
        .col-sport { width: 80px; }
        .col-payment { width: 90px; }
        .col-days { width: 70px; }
        .col-status { width: 70px; }
        .col-actions { width: 220px; }

        .badge {
            padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; display: inline-block;
        }
        .badge-success { background: rgba(46,204,113,0.12); color: #2ecc71; }
        .badge-danger { background: rgba(231,76,60,0.12); color: #e74c3c; }

        .btn-sm {
            padding: 4px 10px; border: none; border-radius: 6px;
            font-size: 11px; font-weight: 500; text-decoration: none;
            display: inline-block; cursor: pointer; transition: 0.2s;
            margin: 1px; white-space: nowrap;
        }
        .btn-sm:hover { transform: translateY(-1px); }
        .btn-primary { background: rgba(102,126,234,0.15); color: #667eea; }
        .btn-primary:hover { background: rgba(102,126,234,0.25); }
        .btn-warning { background: rgba(241,196,15,0.15); color: #f1c40f; }
        .btn-warning:hover { background: rgba(241,196,15,0.25); }
        .btn-success { background: rgba(46,204,113,0.15); color: #2ecc71; }
        .btn-success:hover { background: rgba(46,204,113,0.25); }
        .btn-danger { background: rgba(231,76,60,0.15); color: #e74c3c; }
        .btn-danger:hover { background: rgba(231,76,60,0.25); }

        .actions {
            display: flex; flex-direction: row; flex-wrap: nowrap;
            gap: 3px; align-items: center; justify-content: flex-start;
        }

        .empty {
            text-align: center; padding: 40px 0; color: rgba(255,255,255,0.2);
        }
        .empty .icon { font-size: 40px; display: block; margin-bottom: 10px; }

        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header h2 { font-size: 16px; }
            .search-box input { min-width: 150px; }
            .stats { gap: 8px; }
            .stat-box { padding: 5px 12px; font-size: 12px; }
            .stat-box .num { font-size: 15px; }
            th, td { padding: 6px 5px; font-size: 11px; }
            .btn-sm { padding: 3px 6px; font-size: 10px; }
            .col-actions { width: 140px; }
            .actions { gap: 2px; }
            .table-wrap { max-height: 400px; }
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
            <h2>لیست <span>بانوان</span></h2>
        </div>
        <a href="../members/add.php" class="btn-add">+ ثبت‌نام جدید</a>
    </div>

    <!-- ===== جستجو ===== -->
    <div class="search-box">
        <form method="GET" autocomplete="off" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;">
            <input type="text" name="search" placeholder="جستجو در نام، نام خانوادگی، کدملی، موبایل..." 
                   value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
            <button type="submit" class="btn-search"><i class="fas fa-search"></i> جستجو</button>
            <?php if (!empty($search)): ?>
                <a href="index.php" class="btn-reset"><i class="fas fa-times"></i> پاک کردن</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="stats">
        <div class="stat-box">
            <span class="num"><?php echo $total; ?></span>
            <span class="label">کل</span>
        </div>
        <div class="stat-box">
            <span class="num green"><?php echo $active; ?></span>
            <span class="label">فعال</span>
        </div>
        <div class="stat-box">
            <span class="num red"><?php echo $inactive; ?></span>
            <span class="label">غیرفعال</span>
        </div>
        <?php if (!empty($search)): ?>
            <div class="stat-box" style="border-color:rgba(254,236,65,0.2);">
                <span style="color:#FEEC41;">🔍</span>
                <span class="label">نتیجه جستجو برای: <?php echo htmlspecialchars($search); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($members)): ?>
        <div class="empty">
            <span class="icon"><?php echo !empty($search) ? '🔍' : '🏋️‍♀️'; ?></span>
            <p><?php echo !empty($search) ? 'نتیجه‌ای برای جستجوی شما یافت نشد.' : 'هیچ عضوی ثبت‌نام نشده است'; ?></p>
            <?php if (!empty($search)): ?>
                <a href="index.php" class="btn-add" style="display:inline-block;margin-top:12px;">نمایش همه اعضا</a>
            <?php else: ?>
                <a href="../members/add.php" class="btn-add" style="display:inline-block;margin-top:12px;">ثبت‌نام اولین عضو</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-id">#</th>
                        <th class="col-name">نام</th>
                        <th class="col-lname">نام خانوادگی</th>
                        <th class="col-father">نام پدر</th>
                        <th class="col-national">کدملی</th>
                        <th class="col-birth">تاریخ تولد</th>
                        <th class="col-sport">نوع ورزش</th>
                        <th class="col-payment">تاریخ شهریه</th>
                        <th class="col-days">روز باقی</th>
                        <th class="col-status">وضعیت</th>
                        <th class="col-actions">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($members as $m): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($m['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($m['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($m['father_name']); ?></td>
                        <td><?php echo htmlspecialchars($m['national_code']); ?></td>
                        <td><?php echo convert_to_persian_date($m['birth_date']); ?></td>
                        <td><?php echo htmlspecialchars($m['sport_type']); ?></td>
                        <td><?php echo convert_to_persian_date($m['payment_date']); ?></td>
                        <td><?php echo $m['remaining_days']; ?> روز</td>
                        <td>
                            <span class="badge badge-<?php echo $m['remaining_days'] > 0 ? 'success' : 'danger'; ?>">
                                <?php echo $m['remaining_days'] > 0 ? 'فعال' : 'غیرفعال'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="javascript:void(0)" onclick="openModal('details_modal.php?id=<?php echo $m['id']; ?>')" class="btn-sm btn-primary">جزئیات</a>
                                <a href="edit.php?id=<?php echo $m['id']; ?>" class="btn-sm btn-warning">ویرایش</a>
                                <a href="renew.php?id=<?php echo $m['id']; ?>" class="btn-sm btn-success">تمدید</a>
                                <?php if (is_admin()): ?>
                                    <a href="delete.php?id=<?php echo $m['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
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

<script>
function openModal(url) {
    var overlay = document.createElement('div');
    overlay.id = 'modalOverlay';
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(8px);';
    
    var iframe = document.createElement('iframe');
    iframe.src = url;
    iframe.style.cssText = 'width:100%;max-width:600px;height:80vh;border:none;border-radius:16px;background:#222831;box-shadow:0 20px 60px rgba(0,0,0,0.6);';
    
    overlay.appendChild(iframe);
    document.body.appendChild(overlay);
    
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeModal();
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
}

function closeModal() {
    var overlay = document.getElementById('modalOverlay');
    if (overlay) overlay.remove();
}

window.closeModal = closeModal;
</script>

</body>
</html>