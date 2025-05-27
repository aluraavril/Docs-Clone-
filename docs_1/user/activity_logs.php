<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

require_once 'core/models.php';

$documentId = $_GET['document_id'] ?? null;
if (!$documentId) {
    die("Document ID required");
}

$currentUserId = $_SESSION['user_id'];
$document = getDocumentById($documentId, $currentUserId);
if (!$document) {
    die("No access to this document");
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Activity Logs - <?= htmlspecialchars($document['title']) ?></title>
</head>

<body>
    <h2>Activity Logs for <?= htmlspecialchars($document['title']) ?></h2>
    <a href="edit_document.php?id=<?= $documentId ?>">Back to Edit</a>

    <div id="activity-log-container">
        Loading activity logs...
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchLogs() {
            $.getJSON('core/handleforms.php', {
                action: 'get_activity_logs',
                document_id: <?= json_encode($documentId) ?>
            }, function(data) {
                if (data.error) {
                    $('#activity-log-container').html('<p>Error: ' + data.error + '</p>');
                    return;
                }

                if (data.length === 0) {
                    $('#activity-log-container').html('<p>No activity logs yet.</p>');
                    return;
                }

                let html = '<ul>';
                data.forEach(log => {
                    const time = new Date(log.created_at).toLocaleString();
                    html += '<li><strong>' + log.username + '</strong> - ' + log.action + (log.detail ? ': ' + log.detail : '') + ' <em>(' + time + ')</em></li>';
                });
                html += '</ul>';

                $('#activity-log-container').html(html);
            });
        }

        fetchLogs();
    </script>
</body>

</html>