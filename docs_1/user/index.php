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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="../styles.css">
</head>

<body>
    <div class="dashboard-container">
        <h2>Welcome to Docs Clone, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
        <p>A student’s take on Google Docs built with PHP, HTML, CSS, JS, MYQSL and probably too much caffeine.</p>

        <div class="documents-card">
            <!-- Create Document Button -->
            <div class="section">
                <h3 class="no-underline">My Documents</h3>

                <div class="card-grid">
                    <div class="create-new-card" onclick="document.getElementById('newDocForm').submit()">
                        <div class="text"> + New Document</div>
                    </div>

                </div>

                <form id="newDocForm" action="core/handleforms.php" method="POST" style="display:none;">
                    <input type="hidden" name="start_document" value="1">
                </form>
            </div>


            <!-- Recent Documents -->
            <div class="section">
                <h4>Recent Documents</h4>
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

            <!-- Shared With Me -->
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