<?php
require_once 'core/dbconfig.php';
require_once 'core/models.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$all_documents = getAllDocuments($pdo);

// Get unique authors
$authors = array_unique(array_column($all_documents, 'author_name'));
sort($authors);

// Filter documents if filter applied
$selected_author = $_GET['author'] ?? '';
if (!empty($selected_author)) {
    $filtered_documents = array_filter($all_documents, function ($doc) use ($selected_author) {
        return $doc['author_name'] === $selected_author;
    });
} else {
    $filtered_documents = $all_documents;
}

?>

<?php include 'includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>All Documents - Admin</title>
    <link rel="stylesheet" href="../styles.css" />
</head>

<body>
    <div class="dashboard-container">
        <div class="documents-card">
            <div class="section">
                <h3 class="no-underline">All User Documents</h3>

                <!-- Author Filter -->
                <form method="get" class="filter-form" style="margin-bottom: 1em;">
                    <label for="author">Filter by Author:</label>
                    <select name="author" id="author" onchange="this.form.submit()">
                        <option value=""> All Authors </option>
                        <?php foreach ($authors as $author): ?>
                            <option value="<?= htmlspecialchars($author) ?>" <?= $selected_author === $author ? 'selected' : '' ?>>
                                <?= htmlspecialchars($author) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if (empty($filtered_documents)): ?>
                    <p class="empty-message">No documents found.</p>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($filtered_documents as $doc): ?>
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
        </div>
    </div>
</body>

</html>