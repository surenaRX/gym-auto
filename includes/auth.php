<?php
// ============================================
// توابع احراز هویت
// ============================================

function login_user($username, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        return true;
    }
    return false;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_current_user_role() {
    return $_SESSION['role'] ?? null;
}

function get_current_user_fullname() {
    return $_SESSION['full_name'] ?? 'کاربر';
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? 0;
}

function check_access($required_role = null) {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role && $_SESSION['role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

function check_gender_access($gender) {
    $role = get_current_user_role();
    if ($role === 'admin') return true;
    if ($role === 'coach_m' && $gender === 'male') return true;
    if ($role === 'coach_f' && $gender === 'female') return true;
    return false;
}

function logout_user() {
    session_destroy();
    header('Location: index.php');
    exit;
}

function get_allowed_genders_for_registration() {
    $role = get_current_user_role();
    if ($role === 'admin') return ['male', 'female'];
    if ($role === 'coach_m') return ['male'];
    if ($role === 'coach_f') return ['female'];
    return [];
}

function is_admin() {
    return get_current_user_role() === 'admin';
}

function is_coach_m() {
    return get_current_user_role() === 'coach_m';
}

function is_coach_f() {
    return get_current_user_role() === 'coach_f';
}

function can_edit_member($member_gender) {
    $role = get_current_user_role();
    if ($role === 'admin') return true;
    if ($role === 'coach_m' && $member_gender === 'male') return true;
    if ($role === 'coach_f' && $member_gender === 'female') return true;
    return false;
}

function can_delete_member() {
    return is_admin();
}

function can_renew_member($member_gender) {
    $role = get_current_user_role();
    if ($role === 'admin') return true;
    if ($role === 'coach_m' && $member_gender === 'male') return true;
    if ($role === 'coach_f' && $member_gender === 'female') return true;
    return false;
}

function get_role_label($role) {
    $labels = [
        'admin' => 'مدیر سیستم',
        'coach_m' => 'مربی آقایان',
        'coach_f' => 'مربی بانوان'
    ];
    return $labels[$role] ?? $role;
}

function get_gender_label($gender) {
    $labels = [
        'male' => 'آقا',
        'female' => 'خانم'
    ];
    return $labels[$gender] ?? $gender;
}

function get_status_label($status) {
    $labels = [
        'active' => 'فعال',
        'inactive' => 'غیرفعال'
    ];
    return $labels[$status] ?? $status;
}
?>