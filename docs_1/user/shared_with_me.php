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
$shared_documents = getSharedDocuments($pdo, $user_id);
?>

<?php include 'includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Shared With Me</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles.css">
</head>

<body>

    <div class="dashboard-container">
        <div class="documents-card">
            <!-- Shared With Me -->
            <div class="section">
                <h3 class="no-underline">Shared With Me</h3>

                <?php if (empty($shared_documents)): ?>
                    <p class="empty-message">No shared documents found.</p>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($shared_documents as $doc): ?>
                            <div class="card" style="color: #000;">
                                <a href="shared_document.php?id=<?= $doc['document_id'] ?>" style="color: #000;">
                                    <?= htmlspecialchars($doc['title']) ?>
                                </a>
                                <p style="color: #000;">By <strong style="color: #000;"><?= htmlspecialchars($doc['owner_username']) ?></strong></p>
                                <p><small style="color: #000;">Last Modified: <?= $doc['last_modified'] ?></small></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>

</html>