<?php
require_once 'core/dbconfig.php';
require_once 'core/models.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header('Location: ../admin/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_documents = getUserDocuments($pdo, $user_id);
?>

<?php include 'includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Documents</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles.css">
</head>

<body>

    <div class="dashboard-container">
        <div class="documents-card">
            <!-- Recent Documents -->
            <div class="section">
                <h3 class="no-underline">Recent Documents</h3>

                <?php if (empty($user_documents)): ?>
                    <p class="empty-message">You haven’t created any documents yet.</p>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($user_documents as $doc): ?>
                            <div class="card">
                                <a href="edit_document.php?id=<?= $doc['document_id'] ?>">
                                    <?= htmlspecialchars($doc['title']) ?>
                                </a>
                                <p><small>Last Modified: <?= $doc['last_modified'] ?></small></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>

</html>