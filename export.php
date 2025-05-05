<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'project_log';
$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=records_export.csv');

// 👇 這一行是關鍵：加入 BOM
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', '日期', '內容', '備註']);

$result = $conn->query("SELECT * FROM records ORDER BY date DESC");
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['id'], $row['date'], $row['content'], $row['note']]);
}
fclose($output);
exit;
?>
