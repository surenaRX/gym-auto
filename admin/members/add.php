<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$allowed_genders = get_allowed_genders_for_registration();
if (empty($allowed_genders)) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

$sports_by_gender = [
    'male' => ['بدنسازی'],
    'female' => ['بدنسازی', 'ایروبیک']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $national_code = trim($_POST['national_code'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // ترکیب تاریخ تولد از سه dropdown
    $birth_year = $_POST['birth_year'] ?? '';
    $birth_month = $_POST['birth_month'] ?? '';
    $birth_day = $_POST['birth_day'] ?? '';
    $birth_date = ($birth_year && $birth_month && $birth_day) ? $birth_year . '/' . $birth_month . '/' . $birth_day : '';
    
    $gender = $_POST['gender'] ?? '';
    $sport_type = $_POST['sport_type'] ?? '';
    $insurance = isset($_POST['insurance']) ? 1 : 0;
    $payment_date = trim($_POST['payment_date'] ?? '');
    $payment_duration = (int)($_POST['payment_duration'] ?? 0);

    $errors = [];

    if (empty($first_name)) $errors[] = 'نام را وارد کنید';
    if (empty($last_name)) $errors[] = 'نام خانوادگی را وارد کنید';
    if (empty($father_name)) $errors[] = 'نام پدر را وارد کنید';
    if (empty($national_code)) $errors[] = 'کدملی را وارد کنید';
    if (empty($phone)) $errors[] = 'شماره موبایل را وارد کنید';
    if (empty($birth_date)) $errors[] = 'تاریخ تولد را وارد کنید';
    if (empty($gender)) $errors[] = 'جنسیت را انتخاب کنید';
    if (empty($sport_type)) $errors[] = 'نوع ورزش را انتخاب کنید';
    if ($payment_duration < 1 || $payment_duration > 3) {
        $errors[] = 'تعداد ماه را انتخاب کنید';
    }

    if (empty($payment_date)) {
        $payment_date = get_today_jalali();
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE national_code = ?");
        $stmt->execute([$national_code]);
        if ($stmt->fetch()) {
            $errors[] = 'این کدملی قبلاً ثبت شده است';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $errors[] = 'این شماره موبایل قبلاً ثبت شده است';
        }
    }

    if (empty($errors)) {
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
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO members (
                    first_name, last_name, father_name, birth_date,
                    national_code, phone, gender, sport_type, insurance,
                    payment_date, payment_duration, expiry_date, remaining_days, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // ============================================
            // ✅ تبدیل امن همه متغیرها قبل از execute
            // ============================================
            $result = $stmt->execute([
                (string)$first_name,
                (string)$last_name,
                (string)$father_name,
                (string)$birth_date,
                (string)$national_code,
                (string)$phone,
                (string)$gender,
                (string)$sport_type,
                (int)$insurance,
                (string)$payment_date,
                (int)$payment_duration,
                is_array($expiry_date) ? '' : (string)$expiry_date,  // ← خط ۱۱۷ اصلاح شد
                (int)$remaining_days,
                (string)$status
            ]);
            
            if ($result) {
                $member_id = (int)$pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO member_details (member_id) VALUES (?)");
                $stmt->execute([$member_id]);
                $pdo->commit();
                
                $success = 'عضویت با موفقیت ثبت شد!';
            } else {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'خطا در ثبت اطلاعات';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'خطای سیستمی: ' . $e->getMessage();
        }
    }

    if (!empty($errors) && is_array($errors)) {
        $error = implode('<br>', $errors);
    } else {
        $error = '';
    }
}

$persian_months = [
    '01' => 'فروردین', '02' => 'اردیبهشت', '03' => 'خرداد',
    '04' => 'تیر', '05' => 'مرداد', '06' => 'شهریور',
    '07' => 'مهر', '08' => 'آبان', '09' => 'آذر',
    '10' => 'دی', '11' => 'بهمن', '12' => 'اسفند'
];

$years = range(1340, 1405);
$current_year = explode('/', get_today_jalali())[0];
$selected_gender = $_POST['gender'] ?? '';
$available_sports = [];
if ($selected_gender && isset($sports_by_gender[$selected_gender])) {
    $available_sports = $sports_by_gender[$selected_gender];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام عضو جدید</title>
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
            background: radial-gradient(circle, rgba(254,236,65,0.2) 0%, transparent 70%);
            border-radius: 50%; z-index: 0; pointer-events: none;
        }
        .bg-glow-2 {
            position: fixed; bottom: -200px; left: -200px;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(254,236,65,0.1) 0%, transparent 70%);
            border-radius: 50%; z-index: 0; pointer-events: none;
        }
        .container {
            max-width: 750px; width: 100%;
            background: rgba(57,62,70,0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px; padding: 35px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.05);
            position: relative; z-index: 1;
        }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; flex-wrap: wrap; gap: 15px;
        }
        .header h2 {
            font-size: 22px; font-weight: 300; color: #F5F5F5;
        }
        .header h2 span { color: #FEEC41; }
        .back {
            color: #aaa; text-decoration: none;
            padding: 8px 18px;
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
        }
        input, select {
            width: 100%; padding: 11px 14px;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            color: #F5F5F5;
            font-size: 14px; font-family: inherit;
            transition: 0.3s;
        }
        input:focus, select:focus {
            border-color: #FEEC41;
            outline: none;
            background: rgba(255,255,255,0.07);
            box-shadow: 0 0 0 3px rgba(254,236,65,0.05);
        }
        input::placeholder { color: #555; }
        select option { background: #222831; color: #F5F5F5; }
        .flex-date { display: flex; gap: 8px; }
        .flex-date select { flex: 1; }
        .checkbox-group {
            display: flex; align-items: center; gap: 10px; padding-top: 25px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto; transform: scale(1.2);
            accent-color: #FEEC41; cursor: pointer;
        }
        .checkbox-group label { margin-bottom: 0; cursor: pointer; }
        .btn-submit {
            width: 100%; padding: 15px;
            background: #FEEC41; color: #222831;
            border: none; border-radius: 12px;
            font-size: 17px; font-weight: 700;
            cursor: pointer; transition: 0.3s; margin-top: 8px;
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(254,236,65,0.2);
        }
        .btn-submit:disabled {
            opacity: 0.6; cursor: not-allowed; transform: none;
        }
        .alert {
            padding: 12px 18px; border-radius: 12px; margin-bottom: 18px; font-size: 14px;
        }
        .alert-success {
            background: rgba(39,174,96,0.15); color: #2ecc71;
            border: 1px solid rgba(39,174,96,0.1);
        }
        .alert-error {
            background: rgba(231,76,60,0.15); color: #e74c3c;
            border: 1px solid rgba(231,76,60,0.1);
        }
        .success-actions {
            display: flex; gap: 15px; justify-content: center; margin-top: 18px; flex-wrap: wrap;
        }
        .success-actions a {
            padding: 10px 28px; border-radius: 10px; text-decoration: none;
            font-weight: 600; color: #222831; background: #FEEC41; transition: 0.3s;
        }
        .success-actions a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(254,236,65,0.15);
        }
        .success-actions a.secondary {
            background: rgba(255,255,255,0.06); color: #F5F5F5;
        }
        .success-actions a.secondary:hover {
            background: rgba(255,255,255,0.1);
        }
        hr { border: none; border-top: 1px solid rgba(255,255,255,0.05); margin: 18px 0; }
        .hint { font-size: 11px; color: #555; margin-top: 4px; }
        @media (max-width:600px) {
            .container { padding: 20px; }
            .row { flex-direction: column; }
            .col { min-width: auto; }
            .flex-date { flex-wrap: wrap; }
            .header h2 { font-size: 18px; }
        }
    </style>
</head>
<body>
<div class="bg-glow"></div>
<div class="bg-glow-2"></div>
<div class="container">
    <div class="header">
        <h2>ثبت‌نام <span>عضو جدید</span></h2>
        <a href="../dashboard.php" class="back">← بازگشت</a>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <div class="success-actions">
            <a href="add.php">ثبت‌نام جدید</a>
            <a href="../dashboard.php" class="secondary">بازگشت</a>
        </div>
    <?php else: ?>
        <form method="POST" id="registerForm">
            <div class="row">
                <div class="col">
                    <label>نام</label>
                    <input type="text" name="first_name" required autocomplete="off">
                </div>
                <div class="col">
                    <label>نام خانوادگی</label>
                    <input type="text" name="last_name" required autocomplete="off">
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label>نام پدر</label>
                    <input type="text" name="father_name" required autocomplete="off">
                </div>
                <div class="col">
                    <label>کدملی</label>
                    <input type="text" name="national_code" maxlength="10" required autocomplete="off" onkeypress="return onlyNumbers(event)">
                    <div class="hint">فقط عدد - ۱۰ رقم</div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label>شماره موبایل</label>
                    <input type="text" name="phone" maxlength="11" required autocomplete="off" onkeypress="return onlyNumbers(event)">
                    <div class="hint">فقط عدد - ۱۱ رقم با ۰۹</div>
                </div>
                <div class="col">
                    <label>تاریخ تولد</label>
                    <div class="flex-date">
                        <select name="birth_year" required>
                            <option value="">سال</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="birth_month" required>
                            <option value="">ماه</option>
                            <?php foreach ($persian_months as $k => $n): ?>
                                <option value="<?php echo $k; ?>"><?php echo $n; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="birth_day" required>
                            <option value="">روز</option>
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                                <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <input type="hidden" name="birth_date" id="birth_date_hidden">
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label>جنسیت</label>
                    <select name="gender" id="gender" required>
                        <option value="">انتخاب کنید</option>
                        <?php if (in_array('male', $allowed_genders)): ?>
                            <option value="male">آقا</option>
                        <?php endif; ?>
                        <?php if (in_array('female', $allowed_genders)): ?>
                            <option value="female">خانم</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col">
                    <label>نوع ورزش</label>
                    <select name="sport_type" id="sport_type" required>
                        <option value="">ابتدا جنسیت را انتخاب کنید</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="checkbox-group">
                        <input type="checkbox" name="insurance" value="1">
                        <label>دارای بیمه ورزشی</label>
                    </div>
                </div>
                <div class="col">
                    <label>تاریخ شهریه (خالی = امروز)</label>
                    <input type="text" name="payment_date" value="" autocomplete="off" placeholder="YYYY/MM/DD">
                    <div class="hint">مثال: 1404/01/01</div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label>چند ماهه شهریه</label>
                    <select name="payment_duration" required>
                        <option value="">انتخاب کنید</option>
                        <option value="1">۱ ماه (۳۰ روز)</option>
                        <option value="2">۲ ماه (۶۰ روز)</option>
                        <option value="3">۳ ماه (۹۰ روز)</option>
                    </select>
                </div>
                <div class="col"></div>
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">ثبت‌نام عضو</button>
        </form>
    <?php endif; ?>
</div>
<script>
    function onlyNumbers(e) {
        var key = e.keyCode || e.which;
        var keyChar = String.fromCharCode(key);
        if (!/^[0-9]$/.test(keyChar)) {
            e.preventDefault();
            return false;
        }
        return true;
    }
    var gender = document.getElementById('gender');
    var sport = document.getElementById('sport_type');
    var sports = {
        male: ['بدنسازی'],
        female: ['بدنسازی', 'ایروبیک']
    };
    gender.addEventListener('change', function() {
        var val = this.value;
        sport.innerHTML = '<option value="">انتخاب کنید</option>';
        if (val && sports[val]) {
            sports[val].forEach(function(s) {
                var opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                sport.appendChild(opt);
            });
        }
    });
    function updateDays(monthSelect, daySelect) {
        var month = parseInt(monthSelect.value);
        var maxDays = 31;
        if (month >= 7 && month <= 11) maxDays = 30;
        else if (month === 12) maxDays = 29;
        var currentValue = daySelect.value;
        daySelect.innerHTML = '<option value="">روز</option>';
        for (var i = 1; i <= maxDays; i++) {
            var opt = document.createElement('option');
            var val = String(i).padStart(2, '0');
            opt.value = val;
            opt.textContent = i;
            if (currentValue && parseInt(currentValue) === i && i <= maxDays) {
                opt.selected = true;
            }
            daySelect.appendChild(opt);
        }
    }
    var birthMonth = document.querySelector('select[name="birth_month"]');
    var birthDay = document.querySelector('select[name="birth_day"]');
    if (birthMonth) {
        birthMonth.addEventListener('change', function() {
            updateDays(this, birthDay);
        });
    }
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        var by = document.querySelector('select[name="birth_year"]');
        var bm = document.querySelector('select[name="birth_month"]');
        var bd = document.querySelector('select[name="birth_day"]');
        if (by.value && bm.value && bd.value) {
            document.getElementById('birth_date_hidden').value = by.value + '/' + bm.value + '/' + bd.value;
        }
        var nc = document.querySelector('input[name="national_code"]');
        if (nc.value && !/^\d{10}$/.test(nc.value)) {
            e.preventDefault();
            alert('کدملی باید ۱۰ رقم باشد');
            return false;
        }
        var ph = document.querySelector('input[name="phone"]');
        if (ph.value && !/^09\d{9}$/.test(ph.value)) {
            e.preventDefault();
            alert('شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود');
            return false;
        }
    });
</script>
</body>
</html>