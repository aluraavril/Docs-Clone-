<?php
require_once 'core/dbconfig.php';
require_once 'core/models.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$document_id = $_GET['id'] ?? null;
if (!$document_id) die("No document ID provided.");

$doc = getDocumentById($pdo, $document_id);
if (!$doc) die("Document not found.");

$is_admin = $_SESSION['is_admin'] ?? 0;
$has_access = getDocumentAccess($pdo, $document_id, $_SESSION['user_id']) || $doc['author_id'] == $_SESSION['user_id'];

if (!$is_admin && !$has_access) {
    die("Unauthorized.");
}

$stmt = $pdo->prepare("SELECT l.*, u.username FROM activity_logs l JOIN users u ON l.user_id = u.user_id WHERE l.document_id = ? ORDER BY l.created_at DESC");
$stmt->execute([$document_id]);
$logs = $stmt->fetchAll();
?>

<?php include 'includes/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Activity Logs - <?= htmlspecialchars($doc['title']) ?></title>
    <link rel="stylesheet" href="../styles.css" />
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #222;
        }

        .activity-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .section-title {
            font-size: 26px;
            margin-bottom: 25px;
            font-weight: bold;
            text-align: center;
            color: #111;
        }

        .activity-logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            color: #222;
        }

        .activity-logs-table th,
        .activity-logs-table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        .activity-logs-table th {
            background-color: #eee;
            font-weight: 600;
            color: #111;
        }

        .activity-logs-table tr:hover {
            background-color: #f9f9f9;
        }

        .view-btn {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 6px 14px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .view-btn:hover {
            background-color: #0056b3;
        }

        .rendered-html {
            background: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 15px;
            font-size: 14px;
            color: #222;
            overflow-wrap: break-word;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
        }


        .diff-wrapper {
            display: none;
            margin-top: 10px;
        }

        .no-diff {
            font-style: italic;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="activity-container">
        <h2 class="section-title">Activity Logs for "<em><?= htmlspecialchars($doc['title']) ?></em>"</h2>

        <?php if (empty($logs)): ?>
            <p style="text-align: center; color: #666;">No activity logs available for this document.</p>
        <?php else: ?>
            <table class="activity-logs-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Timestamp</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $index => $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['username']) ?></td>
                            <td><?= htmlspecialchars($log['action_summary']) ?></td>
                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                            <td class="diff-cell">
                                <?php if (!empty($log['content_diff'])): ?>
                                    <button class="view-btn" onclick="toggleDiff('diff<?= $index ?>')">View</button>
                                    <div id="diff<?= $index ?>" class="diff-wrapper">
                                        <div class="rendered-html">
                                            <?php echo $log['content_diff']; ?>

                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="no-diff">No diff available.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        function toggleDiff(id) {
            const el = document.getElementById(id);
            el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
        }
    </script>
</body>

</html>