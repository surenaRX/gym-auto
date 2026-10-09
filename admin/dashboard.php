<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$role = get_current_user_role();
$full_name = get_current_user_fullname();

// دریافت آمار کل
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
$total_members = $stmt->fetch()['total'];

// ============================================
// آمار بانوان
// ============================================
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE gender = 'female'");
$total_women = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE gender = 'female' AND remaining_days > 0");
$active_women = $stmt->fetch()['total'];
$inactive_women = $total_women - $active_women;

// ============================================
// آمار آقایان
// ============================================
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE gender = 'male'");
$total_men = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE gender = 'male' AND remaining_days > 0");
$active_men = $stmt->fetch()['total'];

// ============================================
// محاسبه غیرفعال‌ها
// ============================================
$inactive_men = $total_men - $active_men;
$inactive_women = $total_women - $active_women;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد</title>
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
            background: radial-gradient(circle, rgba(254, 236, 65, 0.25) 0%, transparent 70%);
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
            background: radial-gradient(circle, rgba(254, 236, 65, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            background: rgba(57, 62, 70, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 35px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
            z-index: 1;
        }

        /* ===== منوی کشویی ===== */
        .menu-toggle {
            position: fixed;
            top: 25px;
            right: 25px;
            background: #393E46;
            color: #EEEEEE;
            border: none;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 22px;
            cursor: pointer;
            z-index: 1001;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .menu-toggle:hover { background: #4a4f5a; }

        .side-menu {
            position: fixed;
            top: 0;
            right: -320px;
            width: 300px;
            height: 100%;
            background: #393E46;
            padding: 30px 25px;
            transition: 0.4s ease;
            z-index: 1000;
            box-shadow: -5px 0 30px rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        .side-menu.open { right: 0; }
        .side-menu .close-btn {
            background: none;
            border: none;
            color: #EEEEEE;
            font-size: 28px;
            cursor: pointer;
            float: left;
        }
        .side-menu .close-btn:hover { color: #FEEC41; }
        .side-menu .brand {
            text-align: center;
            padding: 20px 0 30px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 20px;
        }
        .side-menu .brand .logo { font-size: 40px; display: block; }
        .side-menu .brand h3 { color: #EEEEEE; font-weight: normal; font-size: 18px; }
        .side-menu .brand small { color: #aaa; font-size: 12px; }
        .side-menu a {
            display: block;
            padding: 14px 18px;
            color: #EEEEEE;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 5px;
            transition: 0.3s;
            font-size: 15px;
        }
        .side-menu a i { margin-left: 12px; width: 22px; }
        .side-menu a:hover { background: rgba(254, 236, 65, 0.1); color: #FEEC41; }
        .side-menu a.active { background: rgba(254, 236, 65, 0.15); color: #FEEC41; }
        .side-menu .divider { height: 1px; background: rgba(255,255,255,0.05); margin: 15px 0; }
        .side-menu .logout { color: #ff6b6b; }
        .side-menu .logout:hover { background: rgba(255,107,107,0.1); color: #ff6b6b; }

        /* ===== هدر ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { font-size: 24px; font-weight: 300; color: #F5F5F5; }
        .header h1 span { color: #FEEC41; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.05);
            padding: 8px 18px 8px 8px;
            border-radius: 50px;
        }
        .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #FEEC41;
            color: #222831;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        .user-info .name { color: #F5F5F5; font-weight: 500; font-size: 14px; }
        .user-info .role { color: #aaa; font-size: 12px; }

        /* ===== لوگو ===== */
        .sidebar-brand {
            text-align: center;
            margin-bottom: 25px;
        }
        .sidebar-brand img {
            width: 150px;
            height: auto;
            display: block;
            margin: 0 auto 10px;
            border-radius: 8px;
        }

        /* ===== ردیف اصلی ===== */
        .main-row {
            display: flex;
            gap: 25px;
            justify-content: center;
            align-items: stretch;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        /* ===== باکس آقایان و بانوان (زرد) ===== */
        .rect-box {
            width: 300px;
            height: 400px;
            background: #FFD400;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            text-decoration: none;
            color: #222831;
            transition: 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            padding: 20px 20px 15px 20px;
            position: relative;
        }
        .rect-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(255, 212, 0, 0.35);
        }

        .rect-box .content {
            width: 100%;
            text-align: right;
            z-index: 2;
        }
        .rect-box .content h3 {
            font-size: 26px;
            font-weight: 700;
            color: #222831;
            margin-bottom: 2px;
        }
        .rect-box .content p {
            font-size: 14px;
            opacity: 0.7;
            color: #222831;
        }

        .rect-box img {
            width: 200px;
            height: auto;
            object-fit: contain;
            display: block;
            position: absolute;
            bottom: 15px;
            left: 15px;
            opacity: 0.9;
            border-radius: 8px;
        }

        /* ===== مربع وسط (ثبت‌نام) ===== */
        .center-box {
            width: 420px;
            height: 400px;
            background: #393E46;
            border-radius: 24px;
            padding: 30px 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.04);
        }
        .center-box h2 { font-size: 22px; font-weight: 300; color: #F5F5F5; margin-bottom: 8px; }
        .center-box h2 span { color: #FEEC41; }
        .center-box p { color: #aaa; font-size: 14px; margin-bottom: 20px; }
        .center-box .btn-row {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            width: 100%;
        }
        .center-box .btn-row a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 25px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: 0.3s;
            flex: 1;
            min-width: 120px;
            max-width: 200px;
        }
        .btn-register {
            background: #FEEC41;
            color: #222831;
        }
        .btn-register:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(254, 236, 65, 0.3); }

        /* ===== ۷ کارت آمار ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
            margin-top: 10px;
        }
        .stat-card {
            background: #393E46;
            border-radius: 16px;
            padding: 18px 10px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.04);
            transition: 0.3s;
            min-height: 85px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.2); }
        .stat-card .number { font-size: 26px; font-weight: 700; color: #F5F5F5; }
        .stat-card .label { color: #aaa; font-size: 13px; margin-top: 4px; }
        .stat-card.clickable { cursor: pointer; }
        .stat-card.clickable:hover { border-color: #FEEC41; }

        .stat-card.total {
            transform: scale(1.05);
            border: 2px solid rgba(254, 236, 65, 0.12);
        }
        .stat-card.total .number {
            font-size: 32px;
            color: #FEEC41;
        }

        .stat-card .number.green { color: #2ecc71; }
        .stat-card .number.red { color: #e74c3c; }

        /* ===== ریسپانسیو ===== */
        @media (max-width: 992px) {
            .main-row {
                flex-direction: column;
                align-items: center;
            }
            .rect-box {
                width: 100%;
                max-width: 400px;
                height: 250px;
                padding: 18px 20px 12px 20px;
            }
            .rect-box img {
                width: 150px;
                bottom: 10px;
                left: 15px;
            }
            .center-box {
                width: 100%;
                max-width: 400px;
                height: auto;
                padding: 25px 20px;
            }
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            .stat-card.total {
                transform: scale(1);
            }
        }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            .header h1 { font-size: 18px; }
            .header { flex-direction: column; align-items: stretch; }
            .user-info { justify-content: center; }
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            .stat-card {
                padding: 12px 6px;
                min-height: 70px;
            }
            .stat-card .number { font-size: 20px; }
            .stat-card .label { font-size: 11px; }
            .stat-card.total .number { font-size: 24px; }
            .rect-box {
                height: 200px;
                max-width: 100%;
            }
            .rect-box .content h3 { font-size: 20px; }
            .rect-box .content p { font-size: 12px; }
            .rect-box img {
                width: 120px;
                bottom: 8px;
                left: 12px;
            }
            .center-box .btn-row a {
                min-width: 100px;
                padding: 12px 18px;
                font-size: 14px;
            }
            .menu-toggle {
                top: 15px;
                right: 15px;
                padding: 10px 14px;
                font-size: 18px;
            }
        }

        @media (max-width: 500px) {
            body { padding: 10px; }
            .container { padding: 15px; }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            .stat-card {
                padding: 10px 4px;
                min-height: 60px;
            }
            .stat-card .number { font-size: 18px; }
            .stat-card .label { font-size: 10px; }
            .stat-card.total .number { font-size: 20px; }
            .rect-box {
                height: 160px;
                max-width: 100%;
                padding: 14px 16px 8px 16px;
            }
            .rect-box .content h3 { font-size: 17px; }
            .rect-box .content p { font-size: 11px; }
            .rect-box img {
                width: 90px;
                bottom: 6px;
                left: 10px;
            }
            .center-box h2 { font-size: 18px; }
            .center-box p { font-size: 13px; }
            .center-box .btn-row a {
                min-width: 80px;
                padding: 10px 14px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="bg-glow"></div>
<div class="bg-glow-2"></div>

<button class="menu-toggle" onclick="toggleMenu()">
    <i class="fas fa-bars"></i>
</button>

<div class="side-menu" id="sideMenu">
    <button class="close-btn" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
    <div class="brand">
        <span class="logo"></span>
        <h3>GUARD</h3>
        <small>سیستم مدیریت</small>
    </div>

    <a href="dashboard.php" class="active"><i class="fas fa-chart-pie"></i> داشبورد</a>
    <a href="members/add.php"><i class="fas fa-user-plus"></i> ثبت‌نام</a>

    <?php if (is_admin() || is_coach_m()): ?>
    <a href="men/index.php"><i class="fas fa-male"></i> لیست آقایان</a>
    <?php endif; ?>

    <?php if (is_admin() || is_coach_f()): ?>
    <a href="women/index.php"><i class="fas fa-female"></i> لیست بانوان</a>
    <?php endif; ?>

    <div class="divider"></div>

    <?php if (is_admin()): ?>
    <a href="stats/index.php"><i class="fas fa-chart-bar"></i> آمار</a>
    <a href="users/index.php"><i class="fas fa-users-cog"></i> مدیریت کاربران</a>
    <a href="export/excel.php"><i class="fas fa-file-excel"></i> خروجی اکسل</a>
    <a href="export/phones.php"><i class="fas fa-phone"></i> خروجی شماره‌ها</a>
    <div class="divider"></div>
    <?php endif; ?>

    <a href="profile.php"><i class="fas fa-user-cog"></i> تغییر رمز</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> خروج</a>
</div>

<div class="container">
    <div class="header">
        <h1>داشبورد</h1>
        <div class="user-info">
            <div class="avatar"><?php echo mb_substr($full_name, 0, 1); ?></div>
            <div>
                <div class="name"><?php echo htmlspecialchars($full_name); ?></div>
                <div class="role">
                    <?php
                    $role_names = ['admin' => 'مدیر سیستم', 'coach_m' => 'مربی آقایان', 'coach_f' => 'مربی بانوان'];
                    echo $role_names[$role] ?? $role;
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-brand">
        <img src="../assets/images/gym_logo.jpg" alt="لوگو باشگاه">
    </div>

    <!-- ===== ردیف اصلی ===== -->
    <div class="main-row">
        <a href="women/index.php" class="rect-box">
            <div class="content">
                <h3>بانوان</h3>
                <p>مشاهده لیست بانوان</p>
            </div>
            <img src="../assets/images/women_fitnes.png" alt="بانوان">
        </a>

        <div class="center-box">
            <h2>ثبت‌نام <span>عضو جدید</span></h2>
            <p>ثبت‌نام عضو جدید در سیستم</p>
            <div class="btn-row">
                <a href="members/add.php" class="btn-register">ثبت‌نام</a>
            </div>
        </div>

        <a href="men/index.php" class="rect-box">
            <div class="content">
                <h3>آقایان</h3>
                <p>مشاهده لیست آقایان</p>
            </div>
            <img src="../assets/images/men_fitnes.png" alt="آقایان">
        </a>
    </div>

    <!-- ===== ۷ کارت آمار ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?php echo $total_women; ?></div>
            <div class="label">کل بانوان</div>
        </div>

        <?php if (is_admin() || is_coach_f()): ?>
        <div class="stat-card clickable" onclick="openMemberList('active', 'female')">
            <div class="number green"><?php echo $active_women; ?></div>
            <div class="label">بانوان فعال</div>
        </div>
        <?php else: ?>
        <div class="stat-card">
            <div class="number green"><?php echo $active_women; ?></div>
            <div class="label">بانوان فعال</div>
        </div>
        <?php endif; ?>

        <?php if (is_admin() || is_coach_f()): ?>
        <div class="stat-card clickable" onclick="openMemberList('inactive', 'female')">
            <div class="number red"><?php echo $inactive_women; ?></div>
            <div class="label">بانوان غیرفعال</div>
        </div>
        <?php else: ?>
        <div class="stat-card">
            <div class="number red"><?php echo $inactive_women; ?></div>
            <div class="label">بانوان غیرفعال</div>
        </div>
        <?php endif; ?>

        <div class="stat-card total">
            <div class="number"><?php echo $total_members; ?></div>
            <div class="label">کل اعضا</div>
        </div>

        <!-- آقایان غیرفعال -->
        <?php if (is_admin() || is_coach_m()): ?>
        <div class="stat-card clickable" onclick="openMemberList('inactive', 'male')">
            <div class="number red"><?php echo $inactive_men; ?></div>
            <div class="label">آقایان غیرفعال</div>
        </div>
        <?php else: ?>
        <div class="stat-card">
            <div class="number red"><?php echo $inactive_men; ?></div>
            <div class="label">آقایان غیرفعال</div>
        </div>
        <?php endif; ?>

        <!-- آقایان فعال -->
        <?php if (is_admin() || is_coach_m()): ?>
        <div class="stat-card clickable" onclick="openMemberList('active', 'male')">
            <div class="number green"><?php echo $active_men; ?></div>
            <div class="label">آقایان فعال</div>
        </div>
        <?php else: ?>
        <div class="stat-card">
            <div class="number green"><?php echo $active_men; ?></div>
            <div class="label">آقایان فعال</div>
        </div>
        <?php endif; ?>

        <!-- کل آقایان -->
        <div class="stat-card">
            <div class="number"><?php echo $total_men; ?></div>
            <div class="label">کل آقایان</div>
        </div>
    </div>
</div>

<script>
    function toggleMenu() {
        document.getElementById('sideMenu').classList.toggle('open');
    }

    function openMemberList(status, gender) {
        var overlay = document.createElement('div');
        overlay.id = 'modalOverlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(8px);';
        
        var iframe = document.createElement('iframe');
        iframe.src = 'stats/member_list_modal.php?status=' + status + '&gender=' + gender;
        iframe.style.cssText = 'width:100%;max-width:600px;height:70vh;border:none;border-radius:16px;background:#222831;box-shadow:0 20px 60px rgba(0,0,0,0.6);';
        
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

    document.addEventListener('click', function(e) {
        var menu = document.getElementById('sideMenu');
        var toggle = document.querySelector('.menu-toggle');
        if (menu.classList.contains('open') && !menu.contains(e.target) && !toggle.contains(e.target)) {
            menu.classList.remove('open');
        }
    });
</script>

</body>
</html>