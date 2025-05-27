<div class="navbar">
    <div class="navbar-left">
        <?php
        $page = basename($_SERVER['PHP_SELF']);
        if ($page === 'edit_document.php' || $page === 'shared_document.php') {
            // For document editor page
            echo '<button onclick="window.location.href=\'index.php\'" class="back-btn">⬅</button>';

            $rawTitle = trim($document['title'] ?? 'Untitled Document');
            $titleDisplay = $rawTitle !== '' ? $rawTitle . '.docx' : 'Untitled Document.docx';
            echo '<span class="doc-title-in-navbar">' . htmlspecialchars($titleDisplay) . '</span>';
        } else {
            // For index and others
            echo '<a href="index.php" class="brand-logo">Docs Clone</a>';
            echo '<a href="my_documents.php">My Documents</a>';
            echo '<a href="shared_with_me.php">Shared With Me</a>';
        }
        ?>
    </div>
    <div class="navbar-right">
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>