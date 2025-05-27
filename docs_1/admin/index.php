<?php
require_once 'core/dbconfig.php';
require_once 'core/models.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$shared_documents = getSharedDocuments($pdo, $user_id);
$all_documents = getAllDocuments($pdo);
?>

<?php include 'includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Docs Clone</title>
    <link rel="stylesheet" href="../styles.css" />
</head>

<body>
    <div class="dashboard-container">
        <h2>Welcome to Docs Clone, Admin <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
        <p>This is your admin dashboard. From here, you can access all user documents, view documents shared with you, and suspend users who violate terms.</p>

        <div class="documents-card">

            <!-- All User Documents Section -->
            <div class="section">
                <h3 class="no-underline">User Documents</h3>
                <h4>All User Documents</h4>
                <?php if (empty($all_documents)): ?>
                    <p class="empty-message">No documents found.</p>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($all_documents as $doc): ?>
                            <div class="card" style="color: #000;">
                                <a href="view_document.php?id=<?= $doc['document_id'] ?>" style="color: #000;">
                                    <?= htmlspecialchars($doc['title']) ?>
                                </a>
                                <p style="color: #000;">By <strong style="color: #000;"><?= htmlspecialchars($doc['author_name']) ?></strong></p>
                                <p><small style="color: #000;">Last Modified: <?= $doc['last_modified'] ?></small></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Shared With Me Section -->
            <div class="section">
                <h4>Shared With Me</h4>
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