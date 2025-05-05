<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'project_log';
$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8");

// 設定 HTTP 標頭為 Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=records_export.xls");
header("Pragma: no-cache");
header("Expires: 0");

// 產生 Excel 表格（實際是 HTML 表格）
echo "<table border='1'>";
echo "<tr>
        <th>ID</th>
        <th>日期</th>
        <th>內容</th>
        <th>備註</th>
      </tr>";

$result = $conn->query("SELECT * FROM records ORDER BY date DESC");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['date']) . "</td>";
        echo "<td>" . nl2br(htmlspecialchars($row['content'])) . "</td>";
        echo "<td>" . nl2br(htmlspecialchars($row['note'])) . "</td>";
    echo "</tr>";
}
echo "</table>";
exit;
?>
