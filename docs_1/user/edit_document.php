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

$document_id = $_GET['id'];
$document = getDocumentById($pdo, $document_id);

if (!$document || $document['author_id'] != $_SESSION['user_id']) {
    die("Unauthorized access.");
}
?>

<?php include 'includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document</title>
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
        <!-- add users -->
        <button id="openAddUserModal" class="add-user-btn">Share Document</button>
        <button id="openManageAccessModal">Manage Access</button>

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




    <!-- Add Users Button -->


    <!-- BINAGO KO -->
    <div id="addUserModal" style="display:none; position:fixed; top:15%; left:50%; transform:translateX(-50%); background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15); width:400px; z-index:1000;">
        <h4 style="margin-bottom: 20px; font-weight: 500;">Share Document</h4>

        <!-- Search -->
        <div class="form-group">
            <input type="text" id="userSearch" class="form-control" placeholder="Search username..." autocomplete="off" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
        </div>

        <!-- Results -->
        <div id="userResults" style="margin: 10px 0; max-height: 120px; overflow-y: auto; font-size: 14px;"></div>

        <!-- Form -->
        <form id="addUserAccessForm">
            <input type="hidden" name="document_id" value="<?= $document_id ?>">
            <input type="hidden" id="selected_user_id" name="user_id">

            <div class="form-group" style="margin-bottom: 10px;">
                <label style="display:block; margin-bottom: 5px; font-size: 14px; color: #555;">Access Type:</label>
                <label style="margin-right: 10px;"><input type="radio" name="access_type" value="view" checked> View only</label>
                <label><input type="radio" name="access_type" value="edit"> Can edit</label>
            </div>

            <input type="hidden" name="action" value="add_user_access">
            <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                <button type="submit" class="btn btn-outline-primary btn-sm" style="min-width: 120px;">Add Access</button>
                <button type="button" onclick="$('#addUserModal').hide()" class="btn btn-outline-secondary btn-sm" style="min-width: 120px;">Cancel</button>
            </div>
        </form>

        <!-- Feedback -->
        <div id="accessMessage" style="margin-top: 12px; font-size: 14px;"></div>
    </div>


    <!-- Modal -->
    <div id="manageAccessModal"
        style="display: none; position: fixed; top: 12%; left: 50%; transform: translateX(-50%);
            background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            width: 500px; max-height: 75vh; overflow-y: auto; z-index: 1000; font-family: 'Segoe UI', sans-serif;">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0; font-weight: 600;">Manage Access</h4>
            <button id="closeManageAccessModal" class="btn btn-sm btn-outline-secondary" style="padding: 5px 12px;">✕</button>
        </div>

        <div id="accessList" style="display: flex; flex-direction: column; gap: 12px;"></div>

        <div id="accessMessage" style="margin-top: 15px; font-size: 14px; color: #28a745;"></div>
    </div>


    <!-- Optional overlay -->
    <div id="modalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:#00000088; z-index:999;"></div>

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
        <div style="margin: 20px;">
            <input type="text" id="docTitle" value="<?= htmlspecialchars($document['title']) ?>" class="doc-title""><br><br>
            <div id=" status">Saved
        </div><br>
        <div id="editor" contenteditable="true" style="min-height: 300px; border: 1px solid #ccc; padding: 10px;">
            <?= $document['content'] ?>
        </div>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            let timeout;


            function autoSave() {
                const title = $('#docTitle').val();
                const content = $('#editor').html();
                $('#status').stop(true, true).text('Saving...').fadeIn(100);

                $.post('core/handleforms.php', {
                    autosave: true,
                    document_id: <?= json_encode($document_id) ?>,
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
        });

        document.execCommand("styleWithCSS", false, true);

        $('.toolbar button').click(function() {
            const command = $(this).data('command');
            const value = $(this).data('value') || null;
            document.execCommand(command, false, value);
            $('#editor').trigger('input');
        });



        $('#insertImageBtn').on('click', function() {
            $('#imageModal').show();
        });

        let selectedImageFile = null;

        $('#insertImageBtn').on('click', function() {
            $('#imageUpload').val('');
            selectedImageFile = null;
            $('#imageModal').show();
        });

        $('#imageUpload').on('change', function() {
            selectedImageFile = this.files[0];
        });

        $('#uploadImageConfirm').on('click', function() {
            if (!selectedImageFile) {
                alert("Please select an image file first.");
                return;
            }

            const formData = new FormData();
            formData.append('upload_image', "true");
            formData.append('image', selectedImageFile);

            $.ajax({
                url: 'core/handleForms.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    let imageUrl = response;

                    try {
                        const parsed = JSON.parse(response);
                        if (parsed.url) {
                            imageUrl = parsed.url;
                        }
                    } catch (e) {

                    }

                    const img = document.createElement('img');
                    img.src = imageUrl;
                    img.alt = 'Inserted Image';
                    img.style.maxWidth = '100%';
                    img.style.height = 'auto';

                    insertAtCursor(img);
                    $('#imageModal').hide();
                },
                error: function() {
                    alert('Error uploading image.');
                }
            });
        });

        function insertAtCursor(node) {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                range.deleteContents();
                range.insertNode(node);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }




        //search
        const documentId = <?php echo isset($_GET['id']) ? (int)$_GET['id'] : 'null'; ?>;
        $(document).ready(function() {
            $('#openAddUserModal').click(function() {
                $('#addUserModal').show();
                $('#accessMessage').html('');
                $('#userSearch').val('');
                $('#userResults').empty();
            });

            $('#userSearch').on('input', function() {
                let query = $(this).val();
                if (query.length < 2) return;

                $.ajax({
                    url: "core/handleforms.php",
                    method: "POST",
                    data: {
                        action: "search_users",
                        query: query,
                        document_id: documentId
                    },
                    success: function(data) {
                        let users = JSON.parse(data);
                        $('#userResults').empty();
                        if (users.length === 0) {
                            $('#userResults').append("<div>No users found</div>");
                        } else {
                            users.forEach(function(user) {
                                $('#userResults').append(`<div class="userResult" data-id="${user.user_id}">${user.username}</div>`);
                            });
                        }
                    }
                });
            });

            $(document).on('click', '.userResult', function() {
                let userId = $(this).data('id');
                let username = $(this).text();
                $('#selected_user_id').val(userId);
                $('#userSearch').val(username);
                $('#userResults').empty();
            });

            $('#addUserAccessForm').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: "core/handleforms.php",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#accessMessage').html(response);
                    }
                });
            });
        });

        // manage access
        $('#openManageAccessModal').click(function() {
            $('#manageAccessModal').show();
            $('#modalOverlay').show();
            $('#accessMessage').html('');
            loadAccessList();
        });

        // Close modal
        $('#closeManageAccessModal, #modalOverlay').click(function() {
            $('#manageAccessModal').hide();
            $('#modalOverlay').hide();
        });


        function loadAccessList() {
            $.ajax({
                url: "core/handleforms.php",
                method: "POST",
                data: {
                    action: "get_access_list",
                    document_id: documentId
                },
                success: function(data) {
                    const users = JSON.parse(data);
                    $('#accessList').empty();

                    if (users.length === 0) {
                        $('#accessList').append("<p>No users currently have access.</p>");
                    } else {
                        users.forEach(user => {
                            $('#accessList').append(`
                                <div class="access-user" data-id="${user.user_id}" style="margin-bottom:8px;">
                                    <strong>${user.username}</strong> (${user.can_edit ? 'Can Edit' : 'View Only'})
                                    <button class="removeAccessBtn" data-userid="${user.user_id}" style="margin-left:10px;">Remove</button>
                                </div>
                            `);
                        });
                    }
                }
            });
        }

        $(document).on('click', '.removeAccessBtn', function() {
            const userId = $(this).data('userid');

            $.ajax({
                url: "core/handleforms.php",
                method: "POST",
                data: {
                    action: "remove_user_access",
                    user_id: userId,
                    document_id: documentId
                },
                success: function(response) {
                    $('#accessMessage').html(response);
                    loadAccessList();
                }
            });
        });

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
                console.log("Raw message data:", data); // 👈 Add this line

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



        setInterval(loadMessages, 3000); // Refresh every 3 seconds
        loadMessages();
    </script>

</body>

</html>