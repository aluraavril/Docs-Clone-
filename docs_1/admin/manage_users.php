<?php
require_once 'core/dbconfig.php';
require_once 'core/models.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$users = getAllNonAdminUsers($pdo);
?>

<?php include 'includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../styles.css" />
    <style>
        /* Container styles */
        .dashboard-container {
            width: 95vw;
            max-width: none;
            margin: 40px auto;
            padding: 20px 40px;
            box-sizing: border-box;
            min-width: 900px;
        }

        .documents-card {
            background-color: #f7f7f7;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgb(0 0 0 / 0.1);
        }

        h2.section-title {
            color: #111;
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: none;
        }

        .table-container {
            overflow-x: auto;
        }

        table.styled-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            background-color: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgb(0 0 0 / 0.1);
        }

        table.styled-table thead tr {
            background-color: #eee;
            color: #111;
            text-align: left;
            font-weight: bold;
        }

        table.styled-table th,
        table.styled-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            color: #111;
            vertical-align: middle;
        }

        table.styled-table tbody tr:hover {
            background-color: #f0f0f0;
            cursor: pointer;
        }

        /* Comment textarea styling */
        .comment-textarea {
            width: 100%;
            min-width: 200px;
            max-width: 400px;
            height: 3em;
            resize: vertical;
            font-family: inherit;
            font-size: 0.9rem;
            padding: 6px 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        /* Custom toggle switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 42px;
            height: 22px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 22px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #4caf50;
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        /* Center toggle in suspended column */
        .suspend-cell {
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <div class="documents-card">
            <h2 class="section-title">Manage Users</h2>

            <div class="table-container">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Date Created</th>
                            <th style="text-align:center;">Suspended</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['date_created']) ?></td>
                                <td class="suspend-cell">
                                    <label class="toggle-switch">
                                        <input
                                            type="checkbox"
                                            class="suspend-toggle"
                                            data-user-id="<?= $user['user_id'] ?>"
                                            <?= $user['is_suspended'] ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <textarea
                                        class="comment-textarea"
                                        data-user-id="<?= $user['user_id'] ?>"
                                        placeholder="Add suspension reason..."><?= htmlspecialchars($user['suspend_comment'] ?? '') ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        // Toggle suspension checkbox
        $('.suspend-toggle').on('change', function() {
            const userId = $(this).data('user-id');
            const isSuspended = $(this).is(':checked') ? 1 : 0;

            $.post('core/handleforms.php', {
                action: 'toggle_suspend_user',
                user_id: userId,
                is_suspended: isSuspended
            }, function(response) {
                console.log('Suspension updated:', response);
            }).fail(function() {
                alert('Failed to update suspension status.');
            });
        });

        // Save comment on textarea blur (losing focus)
        $('.comment-textarea').on('blur', function() {
            const userId = $(this).data('user-id');
            const comment = $(this).val();

            $.post('core/handleforms.php', {
                action: 'save_suspend_comment',
                user_id: userId,
                comment: comment
            }, function(response) {
                console.log('Comment saved:', response);
            }).fail(function() {
                alert('Failed to save comment.');
            });
        });
    </script>

</body>

</html>