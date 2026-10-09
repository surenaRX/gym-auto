<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$role = get_current_user_role();
if ($role === 'coach_f') {
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $renew_type = $_POST['renew_type'] ?? 'auto'; // auto یا manual
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
    $manual_date = trim($_POST['manual_date'] ?? '');

    $errors = [];

    if ($renew_type === 'auto') {
        // ============================================
        // حالت خودکار (همون قبلی)
        // ============================================
        if ($duration < 1 || $duration > 3) {
            $errors[] = 'لطفاً تعداد ماه را انتخاب کنید';
        } else {
            $days_to_add = $duration * 30;
            $today = get_today_jalali();
            if (!is_string($today) || empty($today)) {
                $today = date('Y/m/d');
            }
            
            $current_remaining = (int)$member['remaining_days'];

            if ($current_remaining <= 0) {
                $new_payment_date = $today;
                $new_remaining = $days_to_add;
            } else {
                $new_payment_date = $member['payment_date'];
                $new_remaining = $current_remaining + $days_to_add;
            }

            $new_expiry_date = add_days_to_jalali($new_payment_date, $new_remaining);
            if (!is_string($new_expiry_date) || empty($new_expiry_date)) {
                $new_expiry_date = $new_payment_date;
            }
        }
    } else {
        // ============================================
        // حالت دستی (کاربر تاریخ رو وارد میکنه)
        // ============================================
        if (empty($manual_date)) {
            $errors[] = 'لطفاً تاریخ شهریه جدید را وارد کنید';
        } elseif (!preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $manual_date)) {
            $errors[] = 'فرمت تاریخ باید YYYY/MM/DD باشد';
        } else {
            $new_payment_date = $manual_date;
            
            // محاسبه روزهای باقی‌مانده بر اساس تاریخ وارد شده
            $today = get_today_jalali();
            if (!is_string($today) || empty($today)) {
                $today = date('Y/m/d');
            }
            
            // تعداد روزهای گذشته از تاریخ شهریه تا امروز
            $diff_days = date_diff_jalali($new_payment_date, $today);
            
            // تعداد روزهای خریداری شده (بر اساس payment_duration فعلی یا انتخاب کاربر)
            if ($duration >= 1 && $duration <= 3) {
                $days_to_add = $duration * 30;
            } else {
                // اگه کاربر تعداد ماه رو انتخاب نکرده، از مقدار قبلی استفاده کن
                $days_to_add = (int)$member['payment_duration'] * 30;
            }
            
            // محاسبه روز باقی‌مانده
            $new_remaining = $days_to_add - abs($diff_days);
            if ($new_remaining < 0) $new_remaining = 0;
            
            $new_expiry_date = add_days_to_jalali($new_payment_date, $new_remaining);
            if (!is_string($new_expiry_date) || empty($new_expiry_date)) {
                $new_expiry_date = $new_payment_date;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE members 
            SET payment_duration = ?,
                payment_date = ?,
                expiry_date = ?,
                remaining_days = ?,
                status = 'active'
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            (int)$duration,
            (string)$new_payment_date,
            (string)$new_expiry_date,
            (int)$new_remaining,
            (int)$member_id
        ]);

        if ($result) {
            $success = '✅ تمدید با موفقیت انجام شد!';
            $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
        } else {
            $errors[] = 'خطا در تمدید شهریه';
        }
    }

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تمدید شهریه</title>
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
            max-width: 600px;
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

        .info-box {
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.03);
        }
        .info-box .row-info {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255,255,255,0.02);
        }
        .info-box .row-info:last-child {
            border-bottom: none;
        }
        .info-box .label-info {
            color: rgba(255,255,255,0.4);
            font-size: 13px;
        }
        .info-box .value-info {
            color: #fff;
            font-weight: 500;
            font-size: 14px;
        }
        .info-box .value-info .badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success {
            background: rgba(46, 204, 113, 0.12);
            color: #2ecc71;
        }
        .badge-danger {
            background: rgba(231, 76, 60, 0.12);
            color: #e74c3c;
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
        select, input {
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
        select:focus, input:focus {
            border-color: #FEEC41;
            outline: none;
            background: rgba(255,255,255,0.07);
            box-shadow: 0 0 0 3px rgba(254, 236, 65, 0.05);
        }
        select option {
            background: #222831;
            color: #F5F5F5;
        }
        input::placeholder {
            color: #555;
        }

        .renew-type {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(255,255,255,0.02);
            border-radius: 10px;
        }
        .renew-type label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #ddd;
        }
        .renew-type input[type="radio"] {
            width: auto;
            accent-color: #FEEC41;
            transform: scale(1.2);
        }

        .manual-fields {
            display: none;
            padding: 15px;
            background: rgba(255,255,255,0.02);
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.03);
        }
        .manual-fields.show {
            display: block;
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
        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.6);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
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

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        @media (max-width: 600px) {
            .container { padding: 20px; }
            .actions { flex-direction: column; }
            .btn { width: 100%; text-align: center; }
            .renew-type { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

<div class="bg-glow"></div>
<div class="bg-glow-2"></div>

<div class="container">
    <div class="header">
        <h2>🔄 تمدید <span>شهریه</span></h2>
        <a href="index.php" class="back">← بازگشت</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="info-box">
        <div class="row-info">
            <span class="label-info">نام و نام خانوادگی</span>
            <span class="value-info"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></span>
        </div>
        <div class="row-info">
            <span class="label-info">کدملی</span>
            <span class="value-info"><?php echo htmlspecialchars($member['national_code']); ?></span>
        </div>
        <div class="row-info">
            <span class="label-info">تاریخ شهریه فعلی</span>
            <span class="value-info"><?php echo convert_to_persian_date($member['payment_date']); ?></span>
        </div>
        <div class="row-info">
            <span class="label-info">روز باقی‌مانده</span>
            <span class="value-info"><strong><?php echo $member['remaining_days']; ?></strong> روز</span>
        </div>
        <div class="row-info">
            <span class="label-info">تاریخ انقضا</span>
            <span class="value-info"><?php echo convert_to_persian_date($member['expiry_date']); ?></span>
        </div>
        <div class="row-info">
            <span class="label-info">وضعیت</span>
            <span class="value-info">
                <span class="badge badge-<?php echo $member['remaining_days'] > 0 ? 'success' : 'danger'; ?>">
                    <?php echo $member['remaining_days'] > 0 ? 'فعال' : 'غیرفعال'; ?>
                </span>
            </span>
        </div>
    </div>

    <?php if (!$success): ?>
    <form method="POST">
        <!-- ============================================ -->
        <!-- انتخاب نوع تمدید -->
        <!-- ============================================ -->
        <div class="renew-type">
            <label>
                <input type="radio" name="renew_type" value="auto" checked onchange="toggleRenewType()">
                 تمدید خودکار (با انتخاب ماه)
            </label>
            <label>
                <input type="radio" name="renew_type" value="manual" onchange="toggleRenewType()">
                 تمدید دستی (وارد کردن تاریخ)
            </label>
        </div>

        <!-- ============================================ -->
        <!-- حالت خودکار -->
        <!-- ============================================ -->
        <div id="autoFields">
            <div class="form-group">
                <label>تعداد ماه برای تمدید <span class="star">*</span></label>
                <select name="duration" required>
                    <option value="">انتخاب کنید</option>
                    <option value="1">۱ ماه (۳۰ روز)</option>
                    <option value="2">۲ ماه (۶۰ روز)</option>
                    <option value="3">۳ ماه (۹۰ روز)</option>
                </select>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- حالت دستی -->
        <!-- ============================================ -->
        <div id="manualFields" class="manual-fields">
            <div class="form-group">
                <label>تاریخ شهریه جدید <span class="star">*</span></label>
                <input type="text" name="manual_date" placeholder="YYYY/MM/DD" value="<?php echo get_today_jalali(); ?>">
                <div style="font-size:11px;color:rgba(255,255,255,0.2);margin-top:4px;">فرمت: YYYY/MM/DD (مثال: 1404/04/25)</div>
            </div>
            <div class="form-group">
                <label>تعداد ماه (اختیاری - خالی = همان مقدار قبلی)</label>
                <select name="duration">
                    <option value="">همان مقدار قبلی (<?php echo $member['payment_duration']; ?> ماه)</option>
                    <option value="1">۱ ماه (۳۰ روز)</option>
                    <option value="2">۲ ماه (۶۰ روز)</option>
                    <option value="3">۳ ماه (۹۰ روز)</option>
                </select>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-success">✅ تمدید</button>
            <a href="index.php" class="btn btn-secondary">🔙 بازگشت</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function toggleRenewType() {
    const autoRadio = document.querySelector('input[name="renew_type"][value="auto"]');
    const manualRadio = document.querySelector('input[name="renew_type"][value="manual"]');
    const autoFields = document.getElementById('autoFields');
    const manualFields = document.getElementById('manualFields');
    
    if (autoRadio.checked) {
        autoFields.style.display = 'block';
        manualFields.classList.remove('show');
        // غیرفعال کردن فیلدهای دستی
        document.querySelectorAll('#manualFields input, #manualFields select').forEach(el => el.disabled = true);
        document.querySelectorAll('#autoFields select').forEach(el => el.disabled = false);
    } else {
        autoFields.style.display = 'none';
        manualFields.classList.add('show');
        // فعال کردن فیلدهای دستی
        document.querySelectorAll('#manualFields input, #manualFields select').forEach(el => el.disabled = false);
        document.querySelectorAll('#autoFields select').forEach(el => el.disabled = true);
    }
}

// اجرا در شروع
document.addEventListener('DOMContentLoaded', function() {
    toggleRenewType();
});
</script>

</body>
</html>