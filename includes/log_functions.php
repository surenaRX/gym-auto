<?php
// ============================================
// توابع لاگ‌نویسی
// ============================================

function log_action($action, $target = null, $details = null) {
    global $pdo;
    
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $stmt = $pdo->prepare("
        INSERT INTO logs (user_id, username, action, target, details, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $username,
        $action,
        $target,
        $details,
        $ip
    ]);
}

function get_logs($limit = 100, $offset = 0) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM logs 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function get_logs_by_user($user_id, $limit = 100) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

function get_logs_by_action($action, $limit = 100) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM logs 
        WHERE action = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$action, $limit]);
    return $stmt->fetchAll();
}

function clear_old_logs($days = 30) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    return $stmt->execute([$days]);
}
?>