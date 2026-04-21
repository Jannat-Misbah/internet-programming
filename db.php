<?php
// اتصال قاعدة البيانات باستخدام mysqli (عدل المستخدم/كلمة المرور إذا لزم)
$host = '127.0.0.1';
$db   = 'furniture_store';
$user = 'root';
$pass = '';
$port = 3306;

$mysqli = new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_errno) {
    exit('Database connection failed: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>