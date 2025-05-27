<?php
require_once 'dbconfig.php';
require_once 'models.php';



if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';

    $user = getUserByEmail($pdo, $email, $is_admin);

    if ($user) {
        if ($user['is_suspended']) {
            header('Location: ../login.php?error=suspended');
            exit();
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            header('Location: ' . ($user['is_admin'] ? '../index.php' : '../index.php'));
            exit();
        } else {
            header('Location: ../login.php?error=invalid');
            exit();
        }
    } else {
        header('Location: ../login.php?error=invalid');
        exit();
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_document'])) {
        if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
            header('Location: ../login.php');
            exit();
        }

        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $author_id = $_SESSION['user_id'];

        if (createDocument($pdo, $title, $content, $author_id)) {
            header('Location: ../index.php?created=1');
        } else {
            header('Location: ../create_document.php?error=1');
        }
        exit();
    }
}

if (isset($_POST['start_document'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }

    $author_id = $_SESSION['user_id'];
    $title = "Untitled Document";
    $content = "";
    $document_id = createBlankDocument($pdo, $title, $content, $author_id);

    if ($document_id) {
        header("Location: ../edit_document.php?id=$document_id");
        exit();
    } else {
        header("Location: ../index.php?error=create_fail");
        exit();
    }
}

if (isset($_POST['autosave']) && $_POST['autosave'] === "true") {
    $doc_id = $_POST['document_id'];
    $newTitle = $_POST['title'];
    $newContent = sanitizeDocumentContent($_POST['content']);

    if (!isset($_SESSION['user_id'])) {
        exit("Unauthorized");
    }

    $user_id = $_SESSION['user_id'];
    $doc = getDocumentById($pdo, $doc_id);

    if (!$doc) {
        exit("Document not found");
    }

    $hasAccess = false;

    if ($doc['author_id'] == $user_id) {
        $hasAccess = true;
    } else {
        $access = getDocumentAccess($pdo, $doc_id, $user_id);
        if ($access && $access['can_edit']) {
            $hasAccess = true;
        }
    }

    if ($hasAccess) {
        $oldTitle = $doc['title'];
        $oldContent = $doc['content'];


        if ($oldTitle !== $newTitle || $oldContent !== $newContent) {
            updateDocument($pdo, $doc_id, $newTitle, $newContent);
            logDocumentChange($pdo, $doc_id, $user_id, $oldTitle, $newTitle, $oldContent, $newContent);
        }

        exit("OK");
    }

    exit("Access Denied");
}

if (isset($_POST['upload_image']) && $_POST['upload_image'] === 'true') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $user_id = $_SESSION['user_id'];


    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['image']['tmp_name'];
        $mime = mime_content_type($tmp_name);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime, $allowed_mimes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid image type']);
            exit();
        }

        // Read file binary
        $image_binary = file_get_contents($tmp_name);

        $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $ext = $ext_map[$mime];

        $saved_path = saveUserImage($user_id, $image_binary, $ext);

        if ($saved_path) {
            echo json_encode(['url' => $saved_path]);
            exit();
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save image']);
            exit();
        }
    }

    if (isset($_POST['image_base64'])) {
        $base64 = $_POST['image_base64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $ext = strtolower($type[1]);
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowed_ext)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid image extension']);
                exit();
            }
            $data = substr($base64, strpos($base64, ',') + 1);
            $data = base64_decode($data);
            if ($data === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Base64 decode failed']);
                exit();
            }

            $saved_path = saveUserImage($user_id, $data, $ext);
            if ($saved_path) {
                echo json_encode(['url' => $saved_path]);
                exit();
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save image']);
                exit();
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid base64 image format']);
            exit();
        }
    }

    http_response_code(400);
    echo json_encode(['error' => 'No image provided']);
    exit();
}

if (isset($_POST['upload_image']) && $_POST['upload_image'] === "true") {
    session_start();
    $user_id = $_SESSION['user_id'] ?? 'guest';

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file = $_FILES['image'];

    if ($file && in_array($file['type'], $allowed_types)) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        $upload_dir = __DIR__ . "/../img/$user_id/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = uniqid('img_') . '.' . $ext;
        $full_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $full_path)) {
            echo "/googledocs/user/img/$user_id/$filename";
        } else {
            http_response_code(500);
            echo "Failed to move file";
        }
    } else {
        http_response_code(400);
        echo "Invalid image type";
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'search_users') {
        $query = trim($_POST['query'] ?? '');
        $document_id = $_POST['document_id'] ?? null;
        $current_user_id = $_SESSION['user_id'] ?? null;

        if ($query && $document_id && $current_user_id) {
            $users = searchUsers($pdo, $query, $document_id, $current_user_id);
            echo json_encode($users);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_user_access') {
        $document_id = $_POST['document_id'] ?? null;
        $user_id = $_POST['user_id'] ?? null;
        $access_type = $_POST['access_type'] ?? 'view';

        if (!$document_id || !$user_id) {
            echo "<span style='color:red;'>Missing document or user ID.</span>";
            exit;
        }

        $can_edit = ($access_type === 'edit') ? 1 : 0;

        $result = addUserAccess($pdo, $document_id, $user_id, $can_edit);
        $color = strpos($result, 'success') !== false ? 'green' : 'red';
        echo "<span style='color:$color;'>$result</span>";
        exit;
    }
}


// manage access
if (isset($_POST['action']) && $_POST['action'] === 'get_access_list') {
    $document_id = $_POST['document_id'] ?? null;
    if ($document_id) {
        $accessUsers = getUsersWithAccess($pdo, $document_id);
        echo json_encode($accessUsers);
    }
    exit;
}


// Remove access
if (isset($_POST['action']) && $_POST['action'] === 'remove_user_access') {
    $document_id = $_POST['document_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    if ($document_id && $user_id) {
        $success = removeUserAccess($pdo, $document_id, $user_id);
        echo $success ? "Access removed." : "Failed to remove access.";
    }
    exit;
}


// messages
if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $document_id = $_POST['document_id'];
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];

    if (!hasDocumentAccess($pdo, $document_id, $user_id)) exit("No access");

    if (!empty($message)) {
        addDocumentMessage($pdo, $document_id, $user_id, $message);
    }
    exit("OK");
}

if (isset($_GET['action']) && $_GET['action'] === 'fetch_messages') {
    $document_id = $_GET['document_id'];
    $user_id = $_SESSION['user_id'];

    if (!hasDocumentAccess($pdo, $document_id, $user_id)) exit("No access");

    $messages = getDocumentMessages($pdo, $document_id);
    echo json_encode($messages);
    exit;
}

// get all non admin users

if (isset($_POST['action']) && $_POST['action'] === 'toggle_suspend_user') {
    $user_id = $_POST['user_id'] ?? null;
    $is_suspended = $_POST['is_suspended'] ?? 0;

    if ($user_id !== null) {
        toggleSuspendUser($pdo, $user_id, $is_suspended);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    }
    exit;
}

// validate confir password

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';

    if ($password !== $confirm) {
        $_SESSION['message'] = "Passwords do not match!";
        $_SESSION['status'] = 400;
        header('Location: ../register.php');
        exit();
    }

    if (registerUser($pdo, $username, $email, $password, $is_admin)) {
        $_SESSION['message'] = "Registration successful!";
        $_SESSION['status'] = 200;
        header('Location: ../login.php');
        exit();
    } else {
        $_SESSION['message'] = "Failed to register.";
        $_SESSION['status'] = 400;
        header('Location: ../register.php');
        exit();
    }
}

//suspend comment

if (isset($_POST['action']) && $_POST['action'] === 'save_suspend_comment') {
    $user_id = $_POST['user_id'] ?? null;
    $comment = $_POST['comment'] ?? '';

    if ($user_id !== null) {
        $success = updateSuspendComment($pdo, $user_id, $comment);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    }
    exit;
}
