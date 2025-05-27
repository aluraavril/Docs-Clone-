<?php
require_once 'core/dbconfig.php';
require_once 'core/models.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$document_id = $_GET['id'] ?? null;
if (!$document_id) {
    die("No document ID provided.");
}

$document = getDocumentById($pdo, $document_id);
if (!$document) {
    die("Document not found.");
}
?>

<?php include 'includes/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($document['title']) ?> - View Only</title>
    <link rel="stylesheet" href="../styles.css" />
</head>

<body>

    <main class="document-container">
        <input type="text" value="<?= htmlspecialchars($document['title']) ?>" class="doc-title" readonly>
        <p><strong>Last Modified:</strong> <?= $document['last_modified'] ?></p>

        <!-- Moved button here and made it smaller -->
        <a href="view_logs.php?id=<?= $document_id ?>" class="btn-logs"
            style="display: inline-block; margin: 10px 0; padding: 4px 10px; font-size: 14px; background-color: #007bff; color: white; border-radius: 4px; text-decoration: none;">
            View Activity Logs
        </a>

        <div class="editor" style="background-color: #f8f8f8;">
            <?= $document['content'] ?>
        </div>
    </main>

</body>

</html>