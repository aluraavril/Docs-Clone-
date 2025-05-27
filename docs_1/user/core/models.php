
<?php
require_once 'dbconfig.php';


function getUserDocuments($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE author_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSharedDocuments($pdo, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT d.*, da.can_edit, u.username AS owner_username
        FROM document_access da
        JOIN documents d ON da.document_id = d.document_id
        JOIN users u ON d.author_id = u.user_id
        WHERE da.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function registerUser($pdo, $username, $email, $password, $is_admin = false)
{
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $hashed, $is_admin]);
}


function loginUser($pdo, $email, $password, $is_admin = false)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = ?");
    $stmt->execute([$email, $is_admin]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password']) && !$user['is_suspended']) {
        return $user;
    }
    return false;
}

function createDocument($pdo, $title, $content, $author_id)
{
    $stmt = $pdo->prepare("INSERT INTO documents (title, content, author_id) VALUES (?, ?, ?)");
    return $stmt->execute([$title, $content, $author_id]);
}

function createBlankDocument($pdo, $title, $content, $author_id)
{
    $stmt = $pdo->prepare("INSERT INTO documents (title, content, author_id) VALUES (?, ?, ?)");
    if ($stmt->execute([$title, $content, $author_id])) {
        return $pdo->lastInsertId();
    }
    return false;
}

function getDocumentById($pdo, $document_id)
{
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE document_id = ?");
    $stmt->execute([$document_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateDocument($pdo, $document_id, $title, $content)
{
    $stmt = $pdo->prepare("UPDATE documents SET title = ?, content = ?, last_modified = NOW() WHERE document_id = ?");
    return $stmt->execute([$title, $content, $document_id]);
}

function sanitizeDocumentContent($html)
{
    $allowed_tags = '<p><div><b><i><u><s><strike><del><strong><em><ol><ul><li><br><img><span><h1><h2><h3><h4><h5><h6>';

    $clean = strip_tags($html, $allowed_tags);

    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($clean, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $imgs = $dom->getElementsByTagName('img');
    foreach ($imgs as $img) {
        $attrs_to_remove = [];
        foreach ($img->attributes as $attr) {
            if (!in_array($attr->nodeName, ['src', 'alt'])) {
                $attrs_to_remove[] = $attr->nodeName;
            }
        }
        foreach ($attrs_to_remove as $attr_name) {
            $img->removeAttribute($attr_name);
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $clean_html = '';
    foreach ($body->childNodes as $child) {
        $clean_html .= $dom->saveHTML($child);
    }

    return $clean_html;
}

function saveUserImage($user_id, $image_binary, $extension)
{
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower($extension);

    if (!in_array($extension, $allowed_ext)) {
        return false;
    }

    $upload_dir = __DIR__ . "/../../admin/user/img/$user_id/";

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid('img_', true) . '.' . $extension;
    $filepath = $upload_dir . $filename;

    if (!file_put_contents($filepath, $image_binary)) {
        return false;
    }
    return "/doc_1/admin/user/img/$user_id/$filename";
}


function searchUsers($pdo, $query, $document_id, $current_user_id)
{
    $stmt = $pdo->prepare("
        SELECT user_id, username 
        FROM users 
        WHERE username LIKE ? 
          AND user_id != ? 
          AND user_id NOT IN (
              SELECT user_id FROM document_access WHERE document_id = ?
          )
          AND is_suspended = 0
        LIMIT 10
    ");
    $search = "%$query%";
    $stmt->execute([$search, $current_user_id, $document_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function hasAccess($pdo, $document_id, $user_id)
{
    $stmt = $pdo->prepare("SELECT * FROM document_access WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $user_id]);
    return $stmt->rowCount() > 0;
}


function addUserAccess($pdo, $document_id, $user_id, $can_edit)
{
    if (hasAccess($pdo, $document_id, $user_id)) {
        return "User already has access.";
    }

    $stmt = $pdo->prepare("INSERT INTO document_access (document_id, user_id, can_edit) VALUES (?, ?, ?)");
    $stmt->execute([$document_id, $user_id, $can_edit]);
    return "Access granted successfully.";
}



function getUsersWithAccess($pdo, $document_id)
{
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, da.can_edit 
        FROM document_access da
        JOIN users u ON da.user_id = u.user_id
        WHERE da.document_id = ?
    ");
    $stmt->execute([$document_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function removeUserAccess($pdo, $document_id, $user_id)
{
    $stmt = $pdo->prepare("DELETE FROM document_access WHERE document_id = ? AND user_id = ?");
    return $stmt->execute([$document_id, $user_id]);
}



function getDocumentAccess($pdo, $document_id, $user_id)
{
    $stmt = $pdo->prepare("SELECT * FROM document_access WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function logDocumentChange($pdo, $document_id, $user_id, $oldTitle, $newTitle, $oldContent, $newContent)
{
    if ($oldContent === $newContent && $oldTitle === $newTitle) return;

    $stmt = $pdo->prepare("SELECT created_at, previous_content, content_diff FROM activity_logs WHERE document_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$document_id, $user_id]);
    $lastLog = $stmt->fetch();

    if ($lastLog) {
        $lastTime = new DateTime($lastLog['created_at']);
        $now = new DateTime();
        $interval = $now->getTimestamp() - $lastTime->getTimestamp();

        $lastOld = $lastLog['previous_content'];
        $lastNew = $lastLog['content_diff'];

        if (
            $interval < 5 &&
            $lastOld === $oldContent &&
            $lastNew === $newContent
        ) {
            return;
        }
    }

    $summary = generateChangeSummary($oldTitle, $newTitle, $oldContent, $newContent);


    $stmt = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action_summary, previous_content, content_diff) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $document_id,
        $user_id,
        $summary,
        $oldContent,
        $newContent
    ]);
}


function generateChangeSummary($oldTitle, $newTitle, $oldContent, $newContent)
{
    $summaryParts = [];

    if ($oldTitle !== $newTitle) {
        $summaryParts[] = "Updated document title from \"" . htmlspecialchars($oldTitle) . "\" to \"" . htmlspecialchars($newTitle) . "\"";
    }

    $oldImages = extractImageSrcs($oldContent);
    $newImages = extractImageSrcs($newContent);

    $addedImages = array_diff($newImages, $oldImages);
    $removedImages = array_diff($oldImages, $newImages);

    if (!empty($addedImages)) {
        $summaryParts[] = "Added " . count($addedImages) . " image" . (count($addedImages) > 1 ? "s" : "");
    }
    if (!empty($removedImages)) {
        $summaryParts[] = "Removed " . count($removedImages) . " image" . (count($removedImages) > 1 ? "s" : "");
    }

    $oldText = trim(strip_tags($oldContent));
    $newText = trim(strip_tags($newContent));

    $diffs = countCharDiffs($oldText, $newText);

    if ($diffs['added'] > 0) {
        $summaryParts[] = "Added " . $diffs['added'] . " character" . ($diffs['added'] > 1 ? "s" : "");
    }
    if ($diffs['removed'] > 0) {
        $summaryParts[] = "Removed " . $diffs['removed'] . " character" . ($diffs['removed'] > 1 ? "s" : "");
    }

    if (empty($summaryParts)) {
        $summaryParts[] = "Made changes";
    }

    return implode("; ", $summaryParts);
}
function extractImageSrcs($html)
{
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $images = $doc->getElementsByTagName('img');
    $srcs = [];
    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        if ($src) {
            $srcs[] = $src;
        }
    }
    return $srcs;
}

function countCharDiffs($oldText, $newText)
{
    $added = 0;
    $removed = 0;

    $lenOld = mb_strlen($oldText);
    $lenNew = mb_strlen($newText);

    $prefixLen = 0;
    $maxPrefix = min($lenOld, $lenNew);
    for ($i = 0; $i < $maxPrefix; $i++) {
        if (mb_substr($oldText, $i, 1) === mb_substr($newText, $i, 1)) {
            $prefixLen++;
        } else {
            break;
        }
    }

    $suffixLen = 0;
    while ($suffixLen < ($lenOld - $prefixLen) && $suffixLen < ($lenNew - $prefixLen)) {
        if (mb_substr($oldText, $lenOld - $suffixLen - 1, 1) === mb_substr($newText, $lenNew - $suffixLen - 1, 1)) {
            $suffixLen++;
        } else {
            break;
        }
    }

    $oldMiddleLen = $lenOld - $prefixLen - $suffixLen;
    $newMiddleLen = $lenNew - $prefixLen - $suffixLen;

    if ($newMiddleLen > $oldMiddleLen) {
        $added = $newMiddleLen - $oldMiddleLen;
    } elseif ($oldMiddleLen > $newMiddleLen) {
        $removed = $oldMiddleLen - $newMiddleLen;
    }

    return ['added' => $added, 'removed' => $removed];
}

function addDocumentMessage($pdo, $document_id, $user_id, $message)
{
    $stmt = $pdo->prepare("INSERT INTO document_messages (document_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$document_id, $user_id, $message]);
}

function getDocumentMessages($pdo, $document_id)
{
    $stmt = $pdo->prepare("
        SELECT m.*, u.username 
        FROM document_messages m 
        JOIN users u ON m.user_id = u.user_id 
        WHERE m.document_id = ? 
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([$document_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function hasDocumentAccess($pdo, $document_id, $user_id)
{
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE document_id = ? AND author_id = ?");
    $stmt->execute([$document_id, $user_id]);
    if ($stmt->fetch()) return true;

    $stmt = $pdo->prepare("SELECT * FROM document_access WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $user_id]);
    return $stmt->fetch() ? true : false;
}


function getUserByEmail($pdo, $email, $is_admin)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = ?");
    $stmt->execute([$email, $is_admin]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
