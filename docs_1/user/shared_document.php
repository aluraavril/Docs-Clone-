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

$document_id = $_GET['id'] ?? null;
if (!$document_id) {
    die("No document ID provided.");
}

$document = getDocumentById($pdo, $document_id);
if (!$document) {
    die("Document not found.");
}

$access = getDocumentAccess($pdo, $document_id, $_SESSION['user_id']);
if (!$access) {
    die("Unauthorized access.");
}

$is_editable = $access['can_edit'] == 1;
?>

<?php include 'includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($document['title']) ?></title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
</head>

<body>
    <div class="toolbar" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 10px; border-bottom: 1px solid #ccc; background: #f8f9fa;">
        <!-- Undo/Redo -->
        <button type="button" data-command="undo">↩</button>
        <button type="button" data-command="redo">↪</button>

        <!-- Heading Dropdown -->
        <select id="headingDropdown" class="form-control form-control-sm" style="width: 120px;" data-command="formatBlock">
            <option value="">Heading</option>
            <option value="H1">H1</option>
            <option value="H2">H2</option>
            <option value="H3">H3</option>
            <option value="H4">H4</option>
        </select>

        <!-- List Dropdown -->
        <select id="listDropdown" class="form-control form-control-sm" style="width: 150px;">
            <option value="">List Type</option>
            <option value="insertOrderedList">Ordered List</option>
            <option value="insertUnorderedList">Bullet List</option>
            <option value="checklist">Checklist</option>
        </select>

        <!-- Font Dropdown -->
        <select id="fontDropdown" class="form-control form-control-sm" style="width: 170px;" data-command="fontName">
            <option value="">Font</option>
            <option value="Arial">Arial</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Courier New">Courier New</option>
            <option value="Comic Sans MS">Comic Sans</option>
            <option value="Pacifico">Pacifico</option>
        </select>

        <!-- Text Color -->
        <input type="color" id="textColorPicker" title="Text Color" style="height: 32px;" />

        <!-- Style Buttons -->
        <button type="button" data-command="bold"><b>B</b></button>
        <button type="button" data-command="italic"><i>I</i></button>
        <button type="button" data-command="underline"><u>U</u></button>
        <button type="button" data-command="strikeThrough"><s>S</s></button>

        <!-- Alignment -->
        <button type="button" data-command="justifyLeft">Left</button>
        <button type="button" data-command="justifyCenter">Center</button>
        <button type="button" data-command="justifyRight">Right</button>



        <!-- Image -->
        <button id="insertImageBtn">Insert Image</button>

        <!-- Logs Link -->
        <div style="margin-left: auto;">
            <a href="view_logs.php?id=<?= $document_id ?>" class="btn-logs">View Activity Logs</a>
        </div>
    </div>



    <!-- Modal -->
    <!-- Insert Image Modal -->
    <div id="imageModal"
        style="display: none; position: fixed; top: 20%; left: 50%; transform: translateX(-50%);
            background: #fff; padding: 20px 25px; border: 1px solid #ccc; border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 1000; width: 320px;">

        <h4 style="margin-bottom: 15px;">Insert Image</h4>

        <input type="file" id="imageUpload" accept="image/png, image/jpeg, image/gif"
            style="margin-bottom: 15px; display: block; width: 100%;">

        <!-- Buttons -->
        <div style="display: flex; justify-content: space-between;">
            <button type="button" id="uploadImageConfirm" class="btn btn-sm btn-primary" style="width: 48%;">Insert</button>
            <button type="button" onclick="$('#imageModal').hide()" class="btn btn-sm btn-outline-secondary" style="width: 48%;">Cancel</button>
        </div>
    </div>


    <!-- Messages -->
    <!-- Chat Toggle Button -->
    <button id="toggleChat" style="position: fixed; bottom: 20px; right: 20px; padding: 12px 18px; font-size: 18px; border-radius: 10px; border: none; background-color: #2d89ff; color: white; cursor: pointer; box-shadow: 0 3px 6px rgba(0,0,0,0.15);">
        Document Chat
    </button>

    <!-- Chat Panel -->
    <div id="chatPanel" style="
    display: none;
    position: fixed;
    bottom: 70px;
    right: 20px;
    width: 400px;
    height: 70vh;
    max-height: 600px;
    background: #f9f9f9;
    border: 1.5px solid #ccc;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 20px;
    flex-direction: column;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
">


        <h4 style="margin: 0 0 15px 0; font-weight: 700; color: #222; font-size: 22px;">Document Chat</h4>

        <div id="chatMessages" style="
    flex-grow: 1;
    overflow-y: auto;
    padding-right: 8px;
    margin-bottom: 15px;
  "></div>

        <textarea id="chatInput" rows="4" placeholder="Type your message..." style="
    width: 100%;
    resize: none;
    padding: 12px 15px;
    font-size: 16px;
    border-radius: 10px;
    border: 1.8px solid #bbb;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
  "></textarea>

        <button id="sendMessage" style="
    margin-top: 12px;
    padding: 14px 20px;
    background-color: #2d89ff;
    border: none;
    color: white;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    font-size: 16px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    transition: background-color 0.3s ease;
  ">Send</button>
    </div>

    <!-- Save Indicator -->
    <main class="document-container">
        <input type="text" id="docTitle" value="<?= htmlspecialchars($document['title']) ?>" class="doc-title" <?= $is_editable ? '' : 'readonly' ?>>
        <?php if ($is_editable): ?>
            <div id="status" class="status" style="color: #000;">Saved</div>
        <?php endif; ?>
        <div id="editor" contenteditable="<?= $is_editable ? 'true' : 'false' ?>" class="editor">
            <?= $document['content'] ?>
        </div>
    </main>


    <script>
        const isEditable = <?= json_encode($is_editable) ?>;
        const documentId = <?= json_encode($document_id) ?>;

        if (isEditable) {
            $(document).ready(function() {
                let timeout;

                function autoSave() {
                    const title = $('#docTitle').val();
                    const content = $('#editor').html();
                    $('#status').stop(true, true).text('Saving...').fadeIn(100);

                    $.post('core/handleforms.php', {
                        autosave: true,
                        document_id: documentId,
                        title: title,
                        content: content
                    }, function() {
                        setTimeout(() => {
                            $('#status').fadeOut(100, function() {
                                $(this).text('Saved').fadeIn(100);
                            });
                        }, 2500);
                    }).fail(function() {
                        $('#status').fadeOut(200, function() {
                            $(this).text('Error Saving').fadeIn(100);
                        });
                    });
                }

                $('#docTitle, #editor').on('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(autoSave, 1000);
                });

                $('.toolbar button').click(function() {
                    const command = $(this).data('command');
                    const value = $(this).data('value') || null;
                    document.execCommand(command, false, value);
                    $('#editor').trigger('input');
                });

                $('#insertImageBtn').on('click', function() {
                    $('#imageModal').show();
                });

                $('#imageUpload').on('change', function() {
                    const file = this.files[0];
                    const formData = new FormData();
                    formData.append('upload_image', true);
                    formData.append('image', file);

                    $('#status').text('Uploading image...').fadeIn(100);

                    $.ajax({
                        url: 'core/handleforms.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(path) {
                            const imgUrl = path.trim();

                            try {
                                const parsed = JSON.parse(imgUrl);
                                document.execCommand('insertImage', false, parsed.url ?? imgUrl);
                            } catch (e) {
                                document.execCommand('insertImage', false, imgUrl);
                            }

                            $('#status').text('Image inserted').fadeOut(3000);
                            $('#imageModal').hide();
                        },
                        error: function() {
                            $('#status').text('Error uploading image').fadeOut(3000);
                        }
                    });
                });
            });
        }

        // messages
        const docId = <?= json_encode($doc_id ?? $_GET['id']) ?>;

        $('#toggleChat').on('click', function() {
            $('#chatPanel').toggle();
        });

        function loadMessages() {
            $.get('core/handleforms.php', {
                action: 'fetch_messages',
                document_id: docId
            }, function(data) {
                console.log("Raw message data:", data);

                let messages;
                try {
                    messages = JSON.parse(data);
                } catch (e) {
                    console.error("Failed to parse JSON:", e);
                    console.error("Response was:", data);
                    return;
                }

                let html = '';
                for (let msg of messages) {
                    html += `<div><strong>${msg.username}</strong> <small>${msg.sent_at}</small><br>${msg.message}</div><hr>`;
                }
                $('#chatMessages').html(html).scrollTop($('#chatMessages')[0].scrollHeight);
            });
        }


        $('#sendMessage').on('click', function() {
            const message = $('#chatInput').val();
            if (!message.trim()) return;

            $.post('core/handleforms.php', {
                action: 'send_message',
                document_id: docId,
                message: message
            }, function(response) {
                $('#chatInput').val('');
                loadMessages();
            });
        });

        $('#headingDropdown, #fontDropdown').change(function() {
            const command = $(this).data('command');
            const value = $(this).val();
            if (value) {
                document.execCommand(command, false, value);
                $('#editor').trigger('input');
                $(this).val("");
            }
        });


        $('#listDropdown').change(function() {
            const value = $(this).val();
            if (value === 'checklist') {
                document.execCommand('insertUnorderedList');
                $('#editor ul li').each(function() {
                    if (!$(this).find('input[type="checkbox"]').length) {
                        $(this).prepend('<input type="checkbox" style="margin-right: 5px;" /> ');
                    }
                });
            } else {
                document.execCommand(value, false, null);
            }
            $('#editor').trigger('input');
            $(this).val('');
        });


        $('#textColorPicker').on('input', function() {
            document.execCommand('foreColor', false, this.value);
            $('#editor').trigger('input');
        });

        // sa image

        let selectedImageFile = null;

        $('#insertImageBtn').on('click', function() {
            $('#imageModal').show();
        });

        $('#imageUpload').on('change', function() {
            selectedImageFile = this.files[0];
        });

        $('#insertImageNow').on('click', function() {
            if (!selectedImageFile) {
                $('#uploadStatus').text('Please select an image first.').css('color', 'red');
                return;
            }

            const formData = new FormData();
            formData.append('upload_image', true);
            formData.append('image', selectedImageFile);

            $('#uploadStatus').text('Uploading...').css('color', '#666');

            $.ajax({
                url: 'core/handleforms.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const parsed = JSON.parse(response);
                        const imageUrl = parsed.url;

                        document.execCommand('insertImage', false, imageUrl);
                        $('#uploadStatus').text('Image inserted!').css('color', 'green');
                        $('#imageModal').hide();
                        selectedImageFile = null;
                        $('#imageUpload').val('');
                    } catch (e) {
                        console.error('Response parse error:', response);
                        $('#uploadStatus').text('Upload failed.').css('color', 'red');
                    }
                },
                error: function() {
                    $('#uploadStatus').text('Server error while uploading.').css('color', 'red');
                }
            });
        });

        setInterval(loadMessages, 3000); // Refresh every 3 seconds
        loadMessages();
    </script>

</body>

</html>