<?php

// ============================================
// توابع تاریخ شمسی (بدون تکرار توابع jdf)
// ============================================

function get_today_jalali() {
    date_default_timezone_set('Asia/Tehran');
    
    // jdate از jdf.php استفاده میکند
    if (function_exists('jdate')) {
        return jdate('Y/m/d');
    }
    
    // اگر jdate وجود نداشت، از تابع جایگزین استفاده کن
    $gy = date('Y');
    $gm = date('m');
    $gd = date('d');
    return gregorian_to_jalali($gy, $gm, $gd);
}

function date_diff_jalali($start_date, $end_date) {
    list($sy, $sm, $sd) = explode('/', $start_date);
    list($ey, $em, $ed) = explode('/', $end_date);
    
    list($gy1, $gm1, $gd1) = jalali_to_gregorian($sy, $sm, $sd);
    list($gy2, $gm2, $gd2) = jalali_to_gregorian($ey, $em, $ed);
    
    $timestamp1 = mktime(0, 0, 0, $gm1, $gd1, $gy1);
    $timestamp2 = mktime(0, 0, 0, $gm2, $gd2, $gy2);
    
    return (int)(($timestamp2 - $timestamp1) / (60 * 60 * 24));
}

function add_days_to_jalali($date, $days) {
    list($year, $month, $day) = explode('/', $date);
    list($gy, $gm, $gd) = jalali_to_gregorian($year, $month, $day);
    
    $timestamp = mktime(0, 0, 0, $gm, $gd + $days, $gy);
    $new_gy = date('Y', $timestamp);
    $new_gm = date('m', $timestamp);
    $new_gd = date('d', $timestamp);
    
    return gregorian_to_jalali($new_gy, $new_gm, $new_gd);
}

function convert_to_persian_date($date) {
    if (empty($date)) return '-';
    if (!preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date)) return $date;
    
    list($year, $month, $day) = explode('/', $date);
    $months = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    return $year . ' / ' . $months[(int)$month] . ' / ' . $day;
}

function convert_datetime_to_jalali($datetime) {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    if ($timestamp === false) return '-';
    $gy = date('Y', $timestamp);
    $gm = date('m', $timestamp);
    $gd = date('d', $timestamp);
    return gregorian_to_jalali($gy, $gm, $gd);
}

function convert_numbers_to_persian($num) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($english, $persian, (string)$num);
}

function validate_national_code($code) {
    if (!preg_match('/^\d{10}$/', $code)) return false;
    
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int)$code[$i] * (10 - $i);
    }
    $remainder = $sum % 11;
    $control = (int)$code[9];
    
    if ($remainder < 2) {
        return $control === $remainder;
    } else {
        return $control === (11 - $remainder);
    }
}

function validate_phone($phone) {
    return preg_match('/^09\d{9}$/', $phone);
}

?>