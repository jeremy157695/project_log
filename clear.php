<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'project_log';

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("連線失敗：" . $conn->connect_error);
}

// 清空 records 資料表
$conn->query("TRUNCATE TABLE records");

// 導回首頁
header("Location: index.php");
exit;
?>
