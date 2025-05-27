<?php
session_start();


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
    <title>Messages - <?= htmlspecialchars($document['title']) ?></title>
    <style>
        #messages-container {
            border: 1px solid #ccc;
            height: 300px;
            overflow-y: auto;
            padding: 10px;
            background-color: #fafafa;
        }

        .message {
            margin-bottom: 10px;
        }

        .message .username {
            font-weight: bold;
        }

        .message .timestamp {
            font-size: 0.75em;
            color: #999;
            font-style: italic;
            display: block;
            margin-top: 4px;
        }


        #message-form {
            margin-top: 10px;
        }

        #message-input {
            width: 80%;
            padding: 5px;
        }

        #send-btn {
            padding: 5px 10px;
        }
    </style>
</head>

<body>
    <h2>Messages for: <?= htmlspecialchars($document['title']) ?></h2>
    <a href="edit_document.php?id=<?= $documentId ?>">Back to Edit</a>

    <div id="messages-container">Loading messages...</div>

    <form id="message-form">
        <input type="text" id="message-input" placeholder="Type a message" autocomplete="off" required />
        <button type="submit" id="send-btn">Send</button>
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function loadMessages() {
            $.getJSON('core/handleforms.php', {
                action: 'get_messages',
                document_id: <?= json_encode($documentId) ?>
            }, function(data) {
                if (data.error) {
                    $('#messages-container').html('<p>Error: ' + data.error + '</p>');
                    return;
                }

                let html = '';
                data.forEach(msg => {
                    const time = new Date(msg.created_at).toLocaleString();
                    html += `<div class="message">
                        <span class="username">${msg.username}</span>
                        <span class="text">${$('<div>').text(msg.message).html()}</span>
                        <span class="timestamp">${time}</span>
                    </div>`;

                });
                $('#messages-container').html(html);
                $('#messages-container').scrollTop($('#messages-container')[0].scrollHeight);
            });
        }

        $(document).ready(function() {
            loadMessages();

            setInterval(loadMessages, 10000);

            $('#message-form').submit(function(e) {
                e.preventDefault();
                const message = $('#message-input').val().trim();
                if (message.length === 0) return;

                $.post('core/handleforms.php?action=send_message', {
                    document_id: <?= json_encode($documentId) ?>,
                    message: message
                }, function(response) {
                    if (response.success) {
                        $('#message-input').val('');
                        loadMessages();
                    } else {
                        alert('Failed to send message: ' + (response.error || 'Unknown error'));
                    }
                }, 'json');
            });
        });
    </script>
</body>

</html>