<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

require_once 'core/models.php';

$documentId = $_GET['document_id'] ?? null;
if (!$documentId) {
    die("Document ID required.");
}

$userId = $_SESSION['user_id'];
$document = getDocumentById($documentId, $userId);
if (!$document) {
    die("No access to this document.");
}

if ($document['owner_id'] != $userId) {
    die("Only the document owner can manage access.");
}

$usersWithAccess = getUsersWithAccess($pdo, $documentId);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Document Access - <?= htmlspecialchars($document['title']) ?></title>
    <style>
        #search-results {
            border: 1px solid #ccc;
            max-height: 150px;
            overflow-y: auto;
            margin-top: 5px;
        }

        .search-result {
            padding: 5px;
            cursor: pointer;
        }

        .search-result:hover {
            background-color: #eee;
        }

        #access-list {
            margin-top: 20px;
        }

        #access-list li {
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <h2>Manage Access for: <?= htmlspecialchars($document['title']) ?></h2>
    <a href="edit_document.php?id=<?= $documentId ?>">Back to Edit</a>

    <h3>Add Users</h3>
    <input type="text" id="user-search" placeholder="Search users by username or email" autocomplete="off" />
    <div id="search-results"></div>

    <h3>Users with Access</h3>
    <ul id="access-list">
        <?php foreach ($usersWithAccess as $user): ?>
            <li><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)</li>
        <?php endforeach; ?>
    </ul>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            let timer;
            $('#user-search').on('input', function() {
                clearTimeout(timer);
                const query = $(this).val().trim();
                if (query.length < 2) {
                    $('#search-results').empty();
                    return;
                }
                timer = setTimeout(() => {
                    $.getJSON('core/handleforms.php', {
                        action: 'search_users',
                        q: query,
                        document_id: <?= json_encode($documentId) ?>
                    }, function(data) {
                        let html = '';
                        if (data.length === 0) {
                            html = '<div>No users found</div>';
                        } else {
                            data.forEach(user => {
                                html += `<div class="search-result" data-id="${user.id}">${user.username} (${user.email})</div>`;
                            });
                        }
                        $('#search-results').html(html);
                    });
                }, 300);
            });

            $('#search-results').on('click', '.search-result', function() {
                const userId = $(this).data('id');
                const username = $(this).text();
                $.post('core/handleforms.php?action=add_user_to_document', {
                    document_id: <?= json_encode($documentId) ?>,
                    user_id: userId
                }, function(response) {
                    if (response.success) {
                        alert(`User ${username} added successfully.`);
                        $('#access-list').append(`<li>${username}</li>`);
                        $('#search-results').empty();
                        $('#user-search').val('');
                    } else {
                        alert('Failed to add user: ' + (response.error || 'Unknown error'));
                    }
                }, 'json');
            });
        });
    </script>
</body>

</html>