<?php
require_once __DIR__ . '/../../includes/config.php';

if (!is_logged_in()) {
    exit;
}

$status = $_GET['status'] ?? '';
$gender = $_GET['gender'] ?? '';

if (!in_array($status, ['active', 'inactive'])) {
    exit;
}

if (!in_array($gender, ['male', 'female'])) {
    exit;
}

// ============================================
// ساخت کوئری بر اساس وضعیت و جنسیت
// ============================================
$query = "SELECT first_name, last_name, remaining_days FROM members WHERE 1=1";
$params = [];

if ($status == 'active') {
    $query .= " AND remaining_days > 0";
} else {
    $query .= " AND remaining_days <= 0";
}

if ($gender == 'male') {
    $query .= " AND gender = 'male'";
} elseif ($gender == 'female') {
    $query .= " AND gender = 'female'";
}

$query .= " ORDER BY first_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

$title = ($status == 'active') ? 'فعال' : 'غیرفعال';
$genderTitle = ($gender == 'male') ? 'آقایان' : 'بانوان';
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>لیست اعضای <?php echo $genderTitle . ' ' . $title; ?></title>
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
        }

        .modal-box {
            width: 100%;
            max-width: 580px;
            background: rgba(57, 62, 70, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 25px 30px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 12px;
            margin-bottom: 15px;
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
            color: rgba(255,255,255,0.3);
            font-size: 22px;
            cursor: pointer;
            transition: 0.3s;
            padding: 4px 8px;
            border-radius: 8px;
        }
        .close-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.04);
        }

        .count {
            color: rgba(255,255,255,0.4);
            font-size: 14px;
            margin-bottom: 15px;
        }
        .count span {
            color: #fff;
            font-weight: 700;
        }

        .member-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .member-list::-webkit-scrollbar {
            width: 4px;
        }
        .member-list::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.02);
            border-radius: 10px;
        }
        .member-list::-webkit-scrollbar-thumb {
            background: rgba(254, 236, 65, 0.3);
            border-radius: 10px;
        }

        .member-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            transition: 0.3s;
            border-radius: 8px;
        }
        .member-list li:hover {
            background: rgba(255,255,255,0.03);
        }
        .member-list li:last-child {
            border-bottom: none;
        }
        .member-list .name {
            color: #fff;
            font-size: 14px;
        }
        .member-list .days {
            font-size: 12px;
            padding: 2px 12px;
            border-radius: 20px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.03);
        }
        .member-list .days.active {
            color: #2ecc71;
            background: rgba(46, 204, 113, 0.06);
        }
        .member-list .days.inactive {
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.06);
        }

        .empty {
            text-align: center;
            color: rgba(255,255,255,0.25);
            padding: 30px 0;
            font-size: 15px;
        }

        @media (max-width: 500px) {
            .modal-box { padding: 18px; }
            .member-list li { flex-direction: column; align-items: flex-start; gap: 4px; }
        }
    </style>
</head>
<body>
<div class="modal-box">
    <div class="header">
        <h3>
            لیست اعضای <span><?php echo $genderTitle . ' ' . $title; ?></span>
        </h3>
        <button class="close-btn" onclick="parent.closeModal()">✕</button>
    </div>

    <div class="count">
        تعداد: <span><?php echo count($members); ?></span> نفر
    </div>

    <?php if (empty($members)): ?>
        <div class="empty">
            هیچ عضو <?php echo $genderTitle . ' ' . $title; ?> ای وجود ندارد
        </div>
    <?php else: ?>
        <ul class="member-list">
            <?php foreach ($members as $m): ?>
                <li>
                    <span class="name"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></span>
                    <span class="days <?php echo ($status == 'active') ? 'active' : 'inactive'; ?>">
                        <?php echo $m['remaining_days']; ?> روز
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
</body>
</html>