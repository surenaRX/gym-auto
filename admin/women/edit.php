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

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$member_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND gender = 'female'");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $national_code = trim($_POST['national_code'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $sport_type = $_POST['sport_type'] ?? '';
    $insurance = isset($_POST['insurance']) ? 1 : 0;
    $payment_date = trim($_POST['payment_date'] ?? '');
    $payment_duration = (int)($_POST['payment_duration'] ?? 0);

    $errors = [];

    if (empty($first_name)) $errors[] = 'نام را وارد کنید';
    if (empty($last_name)) $errors[] = 'نام خانوادگی را وارد کنید';
    if (empty($father_name)) $errors[] = 'نام پدر را وارد کنید';
    if (empty($birth_date)) $errors[] = 'تاریخ تولد را وارد کنید';
    if (empty($national_code)) $errors[] = 'کدملی را وارد کنید';
    if (empty($phone)) $errors[] = 'شماره موبایل را وارد کنید';
    if (empty($sport_type)) $errors[] = 'نوع ورزش را انتخاب کنید';
    if (empty($payment_date)) $errors[] = 'تاریخ شهریه را وارد کنید';
    if ($payment_duration < 1 || $payment_duration > 3) {
        $errors[] = 'تعداد ماه را انتخاب کنید';
    }

    if (!empty($national_code) && !preg_match('/^\d{10}$/', $national_code)) {
        $errors[] = 'کدملی باید ۱۰ رقم باشد';
    }

    if (!empty($payment_date) && !preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $payment_date)) {
        $errors[] = 'فرمت تاریخ شهریه باید YYYY/MM/DD باشد';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE national_code = ? AND id != ?");
        $stmt->execute([$national_code, $member_id]);
        if ($stmt->fetch()) {
            $errors[] = 'این کدملی قبلاً ثبت شده است';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE phone = ? AND id != ?");
        $stmt->execute([$phone, $member_id]);
        if ($stmt->fetch()) {
            $errors[] = 'این شماره موبایل قبلاً ثبت شده است';
        }
    }

    if (empty($errors)) {
        try {
            $days_to_add = $payment_duration * 30;
            $today = get_today_jalali();
            $diff_days = date_diff_jalali($today, $payment_date);
            
            if ($diff_days > 0) {
                $remaining_days = $days_to_add + $diff_days;
            } else {
                $remaining_days = $days_to_add - abs($diff_days);
            }
            
            $expiry_date = add_days_to_jalali($payment_date, $remaining_days);
            $status = ($remaining_days > 0) ? 'active' : 'inactive';

            $stmt = $pdo->prepare("
                UPDATE members SET
                    first_name = ?,
                    last_name = ?,
                    father_name = ?,
                    birth_date = ?,
                    national_code = ?,
                    phone = ?,
                    sport_type = ?,
                    insurance = ?,
                    payment_date = ?,
                    payment_duration = ?,
                    expiry_date = ?,
                    remaining_days = ?,
                    status = ?
                WHERE id = ?
            ");
            
            // ============================================
            // ✅ تبدیل امن متغیرها با is_array() و fallback
            // ============================================
            $safe_first_name = is_array($first_name) ? '' : (string)$first_name;
            $safe_last_name = is_array($last_name) ? '' : (string)$last_name;
            $safe_father_name = is_array($father_name) ? '' : (string)$father_name;
            $safe_birth_date = is_array($birth_date) ? '' : (string)$birth_date;
            $safe_national_code = is_array($national_code) ? '' : (string)$national_code;
            $safe_phone = is_array($phone) ? '' : (string)$phone;
            $safe_sport_type = is_array($sport_type) ? '' : (string)$sport_type;
            $safe_insurance = is_array($insurance) ? 0 : (int)$insurance;
            $safe_payment_date = is_array($payment_date) ? '' : (string)$payment_date;
            $safe_payment_duration = is_array($payment_duration) ? 0 : (int)$payment_duration;
            $safe_expiry_date = is_array($expiry_date) ? '' : (string)$expiry_date;
            $safe_remaining_days = is_array($remaining_days) ? 0 : (int)$remaining_days;
            $safe_status = is_array($status) ? 'inactive' : (string)$status;
            $safe_member_id = is_array($member_id) ? 0 : (int)$member_id;

            $result = $stmt->execute([
                $safe_first_name,
                $safe_last_name,
                $safe_father_name,
                $safe_birth_date,
                $safe_national_code,
                $safe_phone,
                $safe_sport_type,
                $safe_insurance,
                $safe_payment_date,
                $safe_payment_duration,
                $safe_expiry_date,
                $safe_remaining_days,
                $safe_status,
                $safe_member_id
            ]);
            
            if ($result) {
                $success = 'اطلاعات با موفقیت ویرایش شد!';
                $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
                $stmt->execute([$member_id]);
                $member = $stmt->fetch();
            } else {
                $errors[] = 'خطا در ویرایش اطلاعات';
            }
        } catch (Exception $e) {
            $errors[] = 'خطای سیستمی: ' . $e->getMessage();
        }
    }

    $error = (!empty($errors) && is_array($errors)) ? implode('<br>', $errors) : '';
}

$persian_months = [
    '01' => 'فروردین', '02' => 'اردیبهشت', '03' => 'خرداد',
    '04' => 'تیر', '05' => 'مرداد', '06' => 'شهریور',
    '07' => 'مهر', '08' => 'آبان', '09' => 'آذر',
    '10' => 'دی', '11' => 'بهمن', '12' => 'اسفند'
];
$years = range(1340, 1405);
list($birth_year, $birth_month, $birth_day) = explode('/', $member['birth_date']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش بانوان</title>
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
            max-width: 750px; width: 100%; margin: 0 auto;
            background: rgba(57,62,70,0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px; padding: 30px 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.04);
            position: relative; z-index: 1;
        }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; flex-wrap: wrap; gap: 15px;
        }
        .header h2 {
            font-size: 20px; font-weight: 300; color: #F5F5F5;
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
        .row {
            display: flex; gap: 16px; margin-bottom: 14px; flex-wrap: wrap;
        }
        .col { flex: 1; min-width: 160px; }
        label {
            display: block; color: #ddd; font-weight: 500;
            margin-bottom: 5px; font-size: 13px;
            transition: all 0.4s ease;
        }
        .col:hover label {
            color: #FEEC41;
            text-shadow: 0 0 25px rgba(254,236,65,0.1);
        }
        input, select {
            width: 100%; padding: 11px 14px;
            border: 1px solid rgba(255,255,255,0.07) !important;
            border-radius: 10px;
            background: rgba(255,255,255,0.04) !important;
            color: #F5F5F5 !important;
            font-size: 14px; font-family: inherit;
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
            outline: none;
        }
        input:focus, select:focus {
            border-color: #FEEC41 !important;
            background: rgba(255,255,255,0.08) !important;
            box-shadow: 0 0 20px rgba(254,236,65,0.2),
                        0 0 50px rgba(254,236,65,0.06),
                        inset 0 0 20px rgba(254,236,65,0.03);
            transform: scale(1.02);
        }
        .col:hover input, .col:hover select {
            border-color: #FEEC41 !important;
            box-shadow: 0 0 25px rgba(254,236,65,0.12),
                        0 0 60px rgba(254,236,65,0.04);
            transform: scale(1.01);
        }
        select option {
            background: #222831 !important;
            color: #F5F5F5 !important;
        }
        .flex { display: flex; gap: 10px; }
        .flex select {
            flex: 1;
            background: rgba(255,255,255,0.04) !important;
            border: 1px solid rgba(255,255,255,0.07) !important;
            border-radius: 10px !important;
            padding: 11px 12px !important;
            color: #F5F5F5 !important;
            font-size: 14px !important;
            transition: all 0.4s ease;
            cursor: pointer;
        }
        .flex select option {
            background: #222831 !important;
            color: #F5F5F5 !important;
        }
        .flex select:hover {
            border-color: #FEEC41 !important;
            box-shadow: 0 0 25px rgba(254,236,65,0.12);
            transform: translateY(-2px);
        }
        .flex select:focus {
            border-color: #FEEC41 !important;
            box-shadow: 0 0 30px rgba(254,236,65,0.2);
            transform: translateY(-3px);
            outline: none;
        }
        .checkbox-group {
            display: flex; align-items: center; gap: 10px; padding-top: 22px;
        }
        .checkbox-group input {
            width: auto; transform: scale(1.2);
            accent-color: #FEEC41; cursor: pointer;
        }
        .checkbox-group label { margin-bottom: 0; cursor: pointer; }
        .btn {
            padding: 10px 25px; border: none; border-radius: 10px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; transition: 0.3s;
            text-decoration: none; display: inline-block;
        }
        .btn-success {
            background: #FEEC41; color: #222831;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(254,236,65,0.15);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.6);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1); color: #fff;
        }
        .alert {
            padding: 12px 18px; border-radius: 12px;
            margin-bottom: 18px; font-size: 14px;
        }
        .alert-success {
            background: rgba(46,204,113,0.1);
            color: #2ecc71;
            border: 1px solid rgba(46,204,113,0.05);
        }
        .alert-error {
            background: rgba(231,76,60,0.1);
            color: #e74c3c;
            border: 1px solid rgba(231,76,60,0.05);
        }
        .actions {
            display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap;
        }
        .hint {
            font-size: 11px; color: #555; margin-top: 4px;
        }
        input[readonly] {
            background: rgba(255,255,255,0.02) !important;
            cursor: not-allowed; color: #888 !important;
            border-color: rgba(255,255,255,0.03) !important;
        }
        @media (max-width:600px) {
            .container { padding: 20px; }
            .row { flex-direction: column; }
            .col { min-width: auto; }
            .flex { flex-wrap: wrap; }
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
        <h2>ویرایش اطلاعات</h2>
        <a href="index.php" class="back">بازگشت</a>
    </div>

    <?php if (!empty($error) && is_string($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col">
                <label>نام</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($member['first_name']); ?>" required>
            </div>
            <div class="col">
                <label>نام خانوادگی</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($member['last_name']); ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>نام پدر</label>
                <input type="text" name="father_name" value="<?php echo htmlspecialchars($member['father_name']); ?>" required>
            </div>
            <div class="col">
                <label>کدملی</label>
                <input type="text" name="national_code" value="<?php echo htmlspecialchars($member['national_code']); ?>" maxlength="10" required>
                <div class="hint">فقط عدد - ۱۰ رقم</div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>تاریخ تولد</label>
                <div class="flex">
                    <select name="birth_year" required>
                        <option value="">سال</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($y == $birth_year) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="birth_month" required>
                        <option value="">ماه</option>
                        <?php foreach ($persian_months as $k => $n): ?>
                            <option value="<?php echo $k; ?>" <?php echo ($k == $birth_month) ? 'selected' : ''; ?>><?php echo $n; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="birth_day" required>
                        <option value="">روز</option>
                        <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo (str_pad($i, 2, '0', STR_PAD_LEFT) == $birth_day) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <input type="hidden" name="birth_date" value="<?php echo htmlspecialchars($member['birth_date']); ?>">
            </div>
            <div class="col">
                <label>شماره موبایل</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" maxlength="11" required>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>جنسیت</label>
                <input type="text" value="خانم" readonly>
                <div class="hint">جنسیت قابل تغییر نیست</div>
            </div>
            <div class="col">
                <label>نوع ورزش</label>
                <select name="sport_type" required>
                    <option value="">انتخاب</option>
                    <option value="بدنسازی" <?php echo ($member['sport_type'] == 'بدنسازی') ? 'selected' : ''; ?>>بدنسازی</option>
                    <option value="ایروبیک" <?php echo ($member['sport_type'] == 'ایروبیک') ? 'selected' : ''; ?>>ایروبیک</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="checkbox-group">
                    <input type="checkbox" name="insurance" value="1" <?php echo ($member['insurance']) ? 'checked' : ''; ?>>
                    <label>دارای بیمه ورزشی</label>
                </div>
            </div>
            <div class="col">
                <label>تاریخ شهریه</label>
                <input type="text" name="payment_date" value="<?php echo htmlspecialchars($member['payment_date']); ?>" required placeholder="YYYY/MM/DD">
                <div class="hint">فرمت: YYYY/MM/DD</div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>چند ماهه شهریه</label>
                <select name="payment_duration" required>
                    <option value="">انتخاب</option>
                    <option value="1" <?php echo ($member['payment_duration'] == 1) ? 'selected' : ''; ?>>۱ ماه (۳۰ روز)</option>
                    <option value="2" <?php echo ($member['payment_duration'] == 2) ? 'selected' : ''; ?>>۲ ماه (۶۰ روز)</option>
                    <option value="3" <?php echo ($member['payment_duration'] == 3) ? 'selected' : ''; ?>>۳ ماه (۹۰ روز)</option>
                </select>
            </div>
            <div class="col"></div>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-success">ذخیره</button>
            <a href="index.php" class="btn btn-secondary">بازگشت</a>
        </div>
    </form>
</div>
<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        var by = document.querySelector('select[name="birth_year"]');
        var bm = document.querySelector('select[name="birth_month"]');
        var bd = document.querySelector('select[name="birth_day"]');
        if (by.value && bm.value && bd.value) {
            document.querySelector('input[name="birth_date"]').value = by.value + '/' + bm.value + '/' + bd.value;
        }
    });
</script>
</body>
</html>