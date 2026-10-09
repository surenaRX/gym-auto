<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    exit;
}

$role = get_current_user_role();
if ($role === 'coach_f') {
    exit;
}

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$member_id) {
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND gender = 'male'");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM member_details WHERE member_id = ?");
$stmt->execute([$member_id]);
$details = $stmt->fetch();

if (!$details) {
    $stmt = $pdo->prepare("INSERT INTO member_details (member_id) VALUES (?)");
    $stmt->execute([$member_id]);
    $details = [
        'height' => null, 'weight' => null, 'waist' => null,
        'chest' => null, 'arm' => null, 'thigh' => null, 'hip' => null,
        'goal' => null, 'health_conditions' => null,
        'past_injuries' => null, 'medications' => null
    ];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_data = [
        'height' => !empty($_POST['height']) ? (float)$_POST['height'] : null,
        'weight' => !empty($_POST['weight']) ? (float)$_POST['weight'] : null,
        'waist' => !empty($_POST['waist']) ? (float)$_POST['waist'] : null,
        'chest' => !empty($_POST['chest']) ? (float)$_POST['chest'] : null,
        'arm' => !empty($_POST['arm']) ? (float)$_POST['arm'] : null,
        'thigh' => !empty($_POST['thigh']) ? (float)$_POST['thigh'] : null,
        'hip' => !empty($_POST['hip']) ? (float)$_POST['hip'] : null,
        'goal' => trim($_POST['goal'] ?? ''),
        'health_conditions' => trim($_POST['health_conditions'] ?? ''),
        'past_injuries' => trim($_POST['past_injuries'] ?? ''),
        'medications' => trim($_POST['medications'] ?? ''),
    ];

    try {
        $stmt = $pdo->prepare("
            UPDATE member_details SET
                height = ?, weight = ?, waist = ?, chest = ?, arm = ?, thigh = ?, hip = ?,
                goal = ?, health_conditions = ?, past_injuries = ?, medications = ?
            WHERE member_id = ?
        ");
        
        $result = $stmt->execute([
            $update_data['height'],
            $update_data['weight'],
            $update_data['waist'],
            $update_data['chest'],
            $update_data['arm'],
            $update_data['thigh'],
            $update_data['hip'],
            $update_data['goal'],
            $update_data['health_conditions'],
            $update_data['past_injuries'],
            $update_data['medications'],
            $member_id
        ]);
        
        if ($result) {
            $success = '✅ ذخیره شد!';
            $stmt = $pdo->prepare("SELECT * FROM member_details WHERE member_id = ?");
            $stmt->execute([$member_id]);
            $details = $stmt->fetch();
        } else {
            $error = '❌ خطا';
        }
    } catch (Exception $e) {
        $error = '❌ خطا';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Tahoma, sans-serif;
            background: #222831;
            color: #F5F5F5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .modal-box {
            max-width: 550px;
            width: 100%;
            background: rgba(57, 62, 70, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px 28px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header h3 {
            font-size: 18px;
            font-weight: 300;
            color: #fff;
        }
        .header h3 span {
            color: #FEEC41;
        }
        .close-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.4);
            font-size: 22px;
            cursor: pointer;
            transition: 0.3s;
            padding: 4px 8px;
            border-radius: 8px;
        }
        .close-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.05);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 12px;
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
            border: 1px solid rgba(255,255,255,0.02);
        }
        .info-item {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
        }
        .info-item span {
            color: #fff;
            font-weight: 500;
            display: block;
            font-size: 14px;
            margin-top: 1px;
        }
        .info-item .badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success {
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
        }
        .badge-danger {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }

        .section-title {
            font-size: 14px;
            color: rgba(255,255,255,0.4);
            margin: 14px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }

        .row {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }
        .col {
            flex: 1;
        }
        label {
            display: block;
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            font-weight: 500;
            margin-bottom: 2px;
        }
        input, textarea {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 8px;
            background: rgba(255,255,255,0.04);
            color: #F5F5F5;
            font-size: 13px;
            font-family: inherit;
            transition: 0.3s;
        }
        input:focus, textarea:focus {
            border-color: #FEEC41;
            outline: none;
            background: rgba(255,255,255,0.06);
        }
        textarea {
            resize: vertical;
            min-height: 40px;
            font-size: 12px;
        }

        .btn {
            padding: 6px 18px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
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
            box-shadow: 0 6px 20px rgba(254, 236, 65, 0.15);
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
            gap: 8px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 10px;
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

        @media (max-width: 500px) {
            .modal-box { padding: 18px; }
            .info-grid { grid-template-columns: 1fr; }
            .row { flex-direction: column; }
            .actions { flex-direction: column; }
            .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
<div class="modal-box">
    <div class="header">
        <h3>👤 <span><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></span></h3>
        <button class="close-btn" onclick="parent.closeModal()">✕</button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="info-grid">
        <div class="info-item">کدملی <span><?php echo htmlspecialchars($member['national_code']); ?></span></div>
        <div class="info-item">موبایل <span><?php echo htmlspecialchars($member['phone']); ?></span></div>
        <div class="info-item">تاریخ تولد <span><?php echo convert_to_persian_date($member['birth_date']); ?></span></div>
        <div class="info-item">نوع ورزش <span><?php echo htmlspecialchars($member['sport_type']); ?></span></div>
        <div class="info-item">بیمه <span><?php echo $member['insurance'] ? 'دارد' : 'ندارد'; ?></span></div>
        <div class="info-item">روز باقی‌مانده <span><?php echo $member['remaining_days']; ?> روز</span></div>
        <div class="info-item">وضعیت <span>
            <span class="badge badge-<?php echo $member['remaining_days'] > 0 ? 'success' : 'danger'; ?>">
                <?php echo $member['remaining_days'] > 0 ? 'فعال' : 'غیرفعال'; ?>
            </span>
        </span></div>
        <div class="info-item">تاریخ شهریه <span><?php echo convert_to_persian_date($member['payment_date']); ?></span></div>
    </div>

    <form method="POST">
        <div class="section-title">📊 اطلاعات تکمیلی</div>

        <div class="row">
            <div class="col">
                <label>قد (سانتی‌متر)</label>
                <input type="number" step="0.1" name="height" value="<?php echo htmlspecialchars($details['height'] ?? ''); ?>">
            </div>
            <div class="col">
                <label>وزن (کیلوگرم)</label>
                <input type="number" step="0.1" name="weight" value="<?php echo htmlspecialchars($details['weight'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>دور کمر</label>
                <input type="number" step="0.1" name="waist" value="<?php echo htmlspecialchars($details['waist'] ?? ''); ?>">
            </div>
            <div class="col">
                <label>دور سینه</label>
                <input type="number" step="0.1" name="chest" value="<?php echo htmlspecialchars($details['chest'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>دور بازو</label>
                <input type="number" step="0.1" name="arm" value="<?php echo htmlspecialchars($details['arm'] ?? ''); ?>">
            </div>
            <div class="col">
                <label>دور ران</label>
                <input type="number" step="0.1" name="thigh" value="<?php echo htmlspecialchars($details['thigh'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>دور باسن</label>
                <input type="number" step="0.1" name="hip" value="<?php echo htmlspecialchars($details['hip'] ?? ''); ?>">
            </div>
            <div class="col">
                <label>هدف</label>
                <input type="text" name="goal" value="<?php echo htmlspecialchars($details['goal'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>بیماری‌های خاص</label>
                <textarea name="health_conditions"><?php echo htmlspecialchars($details['health_conditions'] ?? ''); ?></textarea>
            </div>
            <div class="col">
                <label>آسیب‌های قبلی</label>
                <textarea name="past_injuries"><?php echo htmlspecialchars($details['past_injuries'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label>داروهای مصرفی</label>
                <textarea name="medications"><?php echo htmlspecialchars($details['medications'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-success">💾 ذخیره</button>
            <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-secondary" target="_parent">✏️ ویرایش</a>
            <a href="renew.php?id=<?php echo $member['id']; ?>" class="btn btn-secondary" target="_parent">🔄 تمدید</a>
            <a href="javascript:void(0)" class="btn btn-secondary" onclick="parent.closeModal()">✕ بستن</a>
        </div>
    </form>
</div>
</body>
</html>