-- ============================================
-- دیتابیس: gurdfitnes_club
-- ============================================
CREATE DATABASE IF NOT EXISTS gurdfitnes_club;
USE gurdfitnes_club;

-- ============================================
-- جدول کاربران (برای ۳ نفر管理者)
-- ============================================
CREATE TABLE users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL UNIQUE,
  password varchar(255) NOT NULL,
  role enum('admin','coach_m','coach_f') NOT NULL,
  full_name varchar(100) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- جدول اعضا (اطلاعات اصلی)
-- ============================================
CREATE TABLE members (
  id int(11) NOT NULL AUTO_INCREMENT,
  first_name varchar(50) NOT NULL,
  last_name varchar(50) NOT NULL,
  father_name varchar(50) NOT NULL,
  birth_date varchar(10) NOT NULL COMMENT 'فرمت: YYYY/MM/DD',
  national_code varchar(10) NOT NULL UNIQUE,
  phone varchar(11) NOT NULL,
  gender enum('male','female') NOT NULL,
  sport_type varchar(50) NOT NULL,
  insurance tinyint(1) DEFAULT 0 COMMENT '0=ندارد, 1=دارد',
  payment_date varchar(10) NOT NULL COMMENT 'تاریخ شهریه شمسی',
  payment_duration int(1) NOT NULL COMMENT '1,2,3 ماه',
  expiry_date varchar(10) NOT NULL COMMENT 'تاریخ انقضا شمسی',
  remaining_days int(5) NOT NULL DEFAULT 0 COMMENT 'روز باقی‌مانده',
  status enum('active','inactive') DEFAULT 'active',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_gender (gender),
  KEY idx_status (status),
  KEY idx_national_code (national_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- جدول اطلاعات تکمیلی اعضا
-- ============================================
CREATE TABLE member_details (
  id int(11) NOT NULL AUTO_INCREMENT,
  member_id int(11) NOT NULL,
  height decimal(5,2) DEFAULT NULL COMMENT 'قد به سانتی‌متر',
  weight decimal(5,2) DEFAULT NULL COMMENT 'وزن به کیلوگرم',
  waist decimal(5,2) DEFAULT NULL COMMENT 'دور کمر',
  chest decimal(5,2) DEFAULT NULL COMMENT 'دور سینه',
  arm decimal(5,2) DEFAULT NULL COMMENT 'دور بازو',
  thigh decimal(5,2) DEFAULT NULL COMMENT 'دور ران',
  hip decimal(5,2) DEFAULT NULL COMMENT 'دور باسن',
  goal text DEFAULT NULL COMMENT 'هدف',
  health_conditions text DEFAULT NULL COMMENT 'بیماری‌های خاص',
  past_injuries text DEFAULT NULL COMMENT 'آسیب‌دیدگی‌های قبلی',
  medications text DEFAULT NULL COMMENT 'داروهای مصرفی',
  hormonal_status text DEFAULT NULL COMMENT 'وضعیت هورمونی (فقط زنان)',
  medical_notes text DEFAULT NULL COMMENT 'توضیحات پزشکی (فقط زنان)',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_member (member_id),
  CONSTRAINT fk_member_details FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- درج کاربران پیش‌فرض (رمز: 123456)
-- ============================================
INSERT INTO users (username, password, role, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'مدیر سیستم'),
('coach_m', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coach_m', 'مربی آقایان'),
('coach_f', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coach_f', 'مربی بانوان');

-- ============================================
-- نمایش جداول ساخته شده
-- ============================================
SHOW TABLES;