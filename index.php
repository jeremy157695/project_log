<?php
// 資料庫連線設定
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'project_log';
$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("連線失敗：" . $conn->connect_error);
}

// 處理新增
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $date = $_POST['date'];
    $content = $_POST['content'];
    $note = $_POST['note'];
    $stmt = $conn->prepare("INSERT INTO records (date, content, note) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $date, $content, $note);
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// 處理更新
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $content = $_POST['content'];
    $note = $_POST['note'];
    $stmt = $conn->prepare("UPDATE records SET date=?, content=?, note=? WHERE id=?");
    $stmt->bind_param("sssi", $date, $content, $note, $id);
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// 處理刪除
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM records WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// 處理清除全部資料
if (isset($_POST['action']) && $_POST['action'] === 'clear_all') {
    $conn->query("DELETE FROM records");
    header("Location: index.php");
    exit;
}

// 編輯功能
$editRecord = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM records WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editResult = $stmt->get_result();
    $editRecord = $editResult->fetch_assoc();
}

// 分頁設定
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$search = '';
$searchSql = '';
$params = [];
$types = '';

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = trim($_GET['search']);
    $searchSql = "WHERE content LIKE ? OR note LIKE ?";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $types = "ss";
}

// 查詢總筆數
$countSql = "SELECT COUNT(*) as total FROM records $searchSql";
$countStmt = $conn->prepare($countSql);
if ($types) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// 查詢資料
$dataSql = "SELECT * FROM records $searchSql ORDER BY date DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$dataStmt = $conn->prepare($dataSql);
$dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$result = $dataStmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>專案紀錄</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h1 class="mb-4">專案事項紀錄</h1>

    <!-- 搜尋 -->
    <form method="get" class="mb-3 d-flex" role="search">
        <input type="text" name="search" class="form-control me-2" placeholder="搜尋內容或備註..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-outline-primary">搜尋</button>
        <?php if ($search): ?>
            <a href="index.php?page=1" class="btn btn-outline-secondary ms-2">清除</a>
        <?php endif; ?>
    </form>

    <!-- 表單：新增 / 編輯 -->
    <form method="post" class="mb-4">
        <input type="hidden" name="action" value="<?= $editRecord ? 'update' : 'add' ?>">
        <?php if ($editRecord): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($editRecord['id']) ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label">日期</label>
            <input type="date" name="date" class="form-control" required value="<?= $editRecord ? htmlspecialchars($editRecord['date']) : '' ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">內容</label>
            <textarea name="content" class="form-control" rows="8" required><?= $editRecord ? htmlspecialchars($editRecord['content']) : '' ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">備註</label>
            <textarea name="note" class="form-control" rows="2"><?= $editRecord ? htmlspecialchars($editRecord['note']) : '' ?></textarea>
        </div>

        <div class="d-flex">
            <button type="submit" class="btn btn-<?= $editRecord ? 'success' : 'primary' ?> me-2">
                <?= $editRecord ? '更新紀錄' : '新增紀錄' ?>
            </button>
            <?php if ($editRecord): ?>
                <a href="index.php" class="btn btn-secondary me-2">取消編輯</a>
            <?php endif; ?>
            <a href="export.php" class="btn btn-success me-2">匯出 CSV</a>
        </div>
    </form>

    <!-- 清除資料按鈕 -->
    <form method="post" action="index.php" onsubmit="return confirm('確定要清除所有資料嗎？')">
        <input type="hidden" name="action" value="clear_all">
        <button type="submit" class="btn btn-danger mb-3">清除所有資料</button>
    </form>

    <!-- 紀錄列表 -->
    <h3>紀錄列表</h3>
    <table class="table table-bordered table-striped">
        <thead class="table-light">
        <tr>
            <th class="text-center" style="width: 100px;">日期</th>
            <th class="text-center" style="width: 500px;">內容</th>
            <th class="text-center" style="width: 250px;">備註</th>
            <th class="text-center" style="width: 120px;">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['content'])) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['note'])) ?></td>
                    <td class="text-center">
                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">編輯</a>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('確定要刪除嗎？')">刪除</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" class="text-center">無符合的紀錄。</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- 分頁 -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
</body>
</html>
