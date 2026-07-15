-- تهيئة قاعدة البيانات
CREATE DATABASE IF NOT EXISTS labdb;
USE labdb;

-- إنشاء جدول logs
CREATE TABLE IF NOT EXISTS logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_agent VARCHAR(500) NOT NULL,
    request_time DATETIME NOT NULL,
    is_vulnerable TINYINT(1) DEFAULT 0
);

-- إنشاء جدول flag
CREATE TABLE IF NOT EXISTS flag (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flag_value VARCHAR(100) NOT NULL
);

-- إدخال الفلاج
INSERT INTO flag (flag_value) VALUES ('flag{Header_SQLi_Injection_Kali}');
