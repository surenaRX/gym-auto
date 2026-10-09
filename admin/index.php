<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
    log_action('login', $username, 'ورود موفق');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'لطفاً نام کاربری و رمز عبور را وارد کنید';
    } else {
        if (login_user($username, $password, $pdo)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'نام کاربری یا رمز عبور اشتباه است';
        }
    }
}

$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>guardfitnes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Tahoma, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            transition: 0.5s ease;
            background: #272829;
            color: #fff;
            overflow: hidden;
            position: relative;
            cursor: default;
        }

        /* ===== گلوله‌های بزرگ آزاد ===== */
        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(50px);
            opacity: 0.6;
            transition: left 0.8s cubic-bezier(0.34, 1.56, 0.64, 1), 
                        top 0.8s cubic-bezier(0.34, 1.56, 0.64, 1),
                        width 0.8s ease, height 0.8s ease;
            will-change: left, top, width, height;
        }

        /* ===== تم دارک (گلوله‌های زرد) ===== */
        body.dark .orb-1 { background: radial-gradient(circle, rgba(254, 236, 65, 0.3), rgba(254, 236, 65, 0.05)); }
        body.dark .orb-2 { background: radial-gradient(circle, rgba(254, 236, 65, 0.2), rgba(254, 236, 65, 0.03)); }
        body.dark .orb-3 { background: radial-gradient(circle, rgba(254, 236, 65, 0.15), rgba(254, 236, 65, 0.02)); }
        body.dark .orb-4 { background: radial-gradient(circle, rgba(254, 236, 65, 0.25), rgba(254, 236, 65, 0.04)); }
        body.dark .orb-5 { background: radial-gradient(circle, rgba(254, 236, 65, 0.1), rgba(254, 236, 65, 0.01)); }

        /* ===== تم لایت (گلوله‌های مشکی/خاکستری) ===== */
        body.light .orb-1 { background: radial-gradient(circle, rgba(39, 40, 41, 0.2), rgba(39, 40, 41, 0.03)); }
        body.light .orb-2 { background: radial-gradient(circle, rgba(39, 40, 41, 0.15), rgba(39, 40, 41, 0.02)); }
        body.light .orb-3 { background: radial-gradient(circle, rgba(39, 40, 41, 0.1), rgba(39, 40, 41, 0.01)); }
        body.light .orb-4 { background: radial-gradient(circle, rgba(39, 40, 41, 0.18), rgba(39, 40, 41, 0.02)); }
        body.light .orb-5 { background: radial-gradient(circle, rgba(39, 40, 41, 0.08), rgba(39, 40, 41, 0.01)); }

        /* ===== تم لایت ===== */
        body.light {
            background: #D8D9DA;
            color: #272829;
        }
        body.light .login-box {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-color: rgba(255,255,255,0.2);
        }
        body.light .login-box h1 { color: #272829; }
        body.light .login-box p { color: #61677A; }
        body.light .form-group label { color: #272829; }
        body.light .form-group input {
            background: rgba(255,255,255,0.3);
            border-color: rgba(0,0,0,0.08);
            color: #272829;
        }
        body.light .form-group input:focus {
            border-color: #61677A;
            background: rgba(255,255,255,0.5);
        }
        body.light .form-group input::placeholder { color: #888; }
        body.light .btn-login {
            background: #61677A;
            color: #fff;
        }
        body.light .btn-login:hover {
            background: #272829;
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(39, 40, 41, 0.2);
        }
        body.light .alert-error {
            background: rgba(231, 76, 60, 0.08);
            color: #c0392b;
            border-color: rgba(231, 76, 60, 0.12);
        }
        body.light .footer { color: #61677A; }
        body.light .theme-toggle {
            background: rgba(0,0,0,0.05);
            color: #272829;
        }
        body.light .theme-toggle:hover {
            background: rgba(0,0,0,0.08);
        }
        body.light .toggle-password img {
            filter: brightness(0.2);
        }

        /* ===== تم دارک ===== */
        body.dark .login-box {
            background: rgba(57, 62, 70, 0.35);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-color: rgba(255,255,255,0.06);
        }
        body.dark .login-box h1 { color: #fff; }
        body.dark .login-box p { color: rgba(255,255,255,0.4); }
        body.dark .form-group label { color: #ddd; }
        body.dark .form-group input {
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.07);
            color: #F5F5F5;
        }
        body.dark .form-group input:focus {
            border-color: #FEEC41;
            background: rgba(255,255,255,0.07);
        }
        body.dark .form-group input::placeholder { color: #555; }
        body.dark .btn-login {
            background: #FEEC41;
            color: #222831;
        }
        body.dark .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(254, 236, 65, 0.2);
        }
        body.dark .alert-error {
            background: rgba(231, 76, 60, 0.08);
            color: #e74c3c;
            border-color: rgba(231, 76, 60, 0.05);
        }
        body.dark .footer { color: rgba(255,255,255,0.2); }
        body.dark .theme-toggle {
            background: rgba(255,255,255,0.04);
            color: #fff;
        }
        body.dark .theme-toggle:hover {
            background: rgba(255,255,255,0.08);
        }
        body.dark .toggle-password img {
            filter: brightness(1);
        }

        /* ===== کادر لاگین شیشه‌ای ===== */
        .login-box {
            max-width: 400px;
            width: 100%;
            border-radius: 24px;
            padding: 40px 35px;
            border: 1px solid rgba(255,255,255,0.06);
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            position: relative;
            transition: 0.5s ease;
            z-index: 1;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 18px;
            border: none;
            border-radius: 14px;
            font-size: 20px;
            cursor: pointer;
            transition: 0.3s ease;
            z-index: 100;
            font-family: inherit;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.04);
            color: #fff;
        }
        .theme-toggle:hover {
            background: rgba(255,255,255,0.08);
        }
        body.light .theme-toggle {
            background: rgba(0,0,0,0.05);
            color: #272829;
        }
        body.light .theme-toggle:hover {
            background: rgba(0,0,0,0.08);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header .logo {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }
        .login-header h1 {
            font-size: 24px;
            font-weight: 300;
            transition: 0.5s ease;
        }
        .login-header p {
            font-size: 14px;
            transition: 0.5s ease;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 14px;
            transition: 0.5s ease;
        }
        .form-group label i {
            margin-left: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: 0.4s ease;
            border: 1px solid transparent;
        }
        .form-group input::placeholder {
            transition: 0.4s ease;
        }

        /* ===== چشم نمایش پسورد ===== */
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-left: 48px;
        }
        .toggle-password {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            z-index: 2;
            transition: 0.3s;
        }
        .toggle-password:hover {
            transform: translateY(-50%) scale(1.1);
        }
        .toggle-password img {
            width: 24px;
            height: 24px;
            display: block;
            transition: 0.3s;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.4s ease;
            font-family: inherit;
        }

        .alert-error {
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid transparent;
            transition: 0.5s ease;
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
            transition: 0.5s ease;
        }

        @media (max-width: 500px) {
            .login-box { padding: 30px 20px; }
            .theme-toggle { top: 12px; right: 12px; padding: 10px 16px; font-size: 17px; }
            .orb { display: none; }
            .toggle-password img { width: 20px; height: 20px; }
            .password-wrapper input { padding-left: 40px; }
        }
    </style>
    
</head>
<body class="<?php echo $theme; ?>">

<!-- گلوله‌های بزرگ آزاد -->
<div class="orb orb-1" id="orb1" style="width:400px;height:400px;left:5%;top:10%;"></div>
<div class="orb orb-2" id="orb2" style="width:300px;height:300px;left:75%;top:5%;"></div>
<div class="orb orb-3" id="orb3" style="width:500px;height:500px;left:60%;top:55%;"></div>
<div class="orb orb-4" id="orb4" style="width:250px;height:250px;left:10%;top:70%;"></div>
<div class="orb orb-5" id="orb5" style="width:350px;height:350px;left:45%;top:30%;"></div>

<button class="theme-toggle" onclick="toggleTheme()" title="تغییر تم">
    <i class="fas <?php echo ($theme === 'dark') ? 'fa-sun' : 'fa-moon'; ?>"></i>
</button>

<div class="login-box">
    <div class="login-header">
        <span class="logo"></span>
        <h1>ورود به سیستم</h1>
        <p>Guard_Fitnes_Club</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="form-group">
            <label><i class="fas fa-user"></i> نام کاربری</label>
            <input type="text" name="username" placeholder="نام کاربری خود را وارد کنید" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock"></i> رمز عبور</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="رمز عبور خود را وارد کنید" required>
                <button type="button" class="toggle-password" onclick="togglePassword()" title="روم اونوره">
                    <img src="../assets/images/eye-48.png" alt="چشم" id="eyeIcon">
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> ورود
        </button>
    </form>

    <!-- <div class="footer">
        کاربران: admin / coach_m / coach_f<br>
        رمز عبور: 123456
    </div> -->
</div>

<script>
    // ===== گلوله‌های بزرگ =====
    const orbs = [
        document.getElementById('orb1'),
        document.getElementById('orb2'),
        document.getElementById('orb3'),
        document.getElementById('orb4'),
        document.getElementById('orb5')
    ];

    const initialPositions = [
        { x: 5, y: 10 },
        { x: 75, y: 5 },
        { x: 60, y: 55 },
        { x: 10, y: 70 },
        { x: 45, y: 30 }
    ];

    let mouseX = -1000;
    let mouseY = -1000;
    let isHovering = false;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX / window.innerWidth * 100;
        mouseY = e.clientY / window.innerHeight * 100;
        isHovering = true;
        updateOrbs();
    });

    document.addEventListener('mouseleave', () => {
        isHovering = false;
        orbs.forEach((orb, index) => {
            orb.style.left = initialPositions[index].x + '%';
            orb.style.top = initialPositions[index].y + '%';
            orb.style.width = (300 + Math.random() * 200) + 'px';
            orb.style.height = orb.style.width;
        });
    });

    function updateOrbs() {
        if (!isHovering) return;
        
        orbs.forEach((orb, index) => {
            const rect = orb.getBoundingClientRect();
            const orbX = (rect.left + rect.width/2) / window.innerWidth * 100;
            const orbY = (rect.top + rect.height/2) / window.innerHeight * 100;
            
            const dx = mouseX - orbX;
            const dy = mouseY - orbY;
            const dist = Math.sqrt(dx*dx + dy*dy);
            
            const strength = Math.max(0, Math.min(30, 100 - dist));
            const angle = Math.atan2(dy, dx);
            
            const moveX = Math.cos(angle) * strength * 0.15;
            const moveY = Math.sin(angle) * strength * 0.15;
            
            let newX = parseFloat(orb.style.left) + moveX;
            let newY = parseFloat(orb.style.top) + moveY;
            
            newX = Math.max(-20, Math.min(120, newX));
            newY = Math.max(-20, Math.min(120, newY));
            
            orb.style.left = newX + '%';
            orb.style.top = newY + '%';
            
            const baseSize = 250 + index * 50;
            const sizeFactor = 1 + (strength / 100) * 0.5;
            const newSize = baseSize * sizeFactor;
            orb.style.width = newSize + 'px';
            orb.style.height = newSize + 'px';
        });
    }

    let animationId = null;
    function animateOrbs() {
        if (isHovering) {
            updateOrbs();
        }
        animationId = requestAnimationFrame(animateOrbs);
    }
    animateOrbs();

    // ===== نمایش/مخفی کردن پسورد =====
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.style.opacity = '0.5';
            eyeIcon.style.transform = 'scale(0.9)';
        } else {
            passwordInput.type = 'password';
            eyeIcon.style.opacity = '1';
            eyeIcon.style.transform = 'scale(1)';
        }
    }

    // ===== تغییر تم =====
    function toggleTheme() {
        const body = document.body;
        const isDark = body.classList.contains('dark');
        
        if (isDark) {
            body.classList.remove('dark');
            body.classList.add('light');
            document.cookie = "theme=light; path=/; max-age=31536000";
        } else {
            body.classList.remove('light');
            body.classList.add('dark');
            document.cookie = "theme=dark; path=/; max-age=31536000";
        }
        
        const icon = document.querySelector('.theme-toggle i');
        if (body.classList.contains('dark')) {
            icon.className = 'fas fa-sun';
        } else {
            icon.className = 'fas fa-moon';
        }
    }
</script>

</body>
</html>