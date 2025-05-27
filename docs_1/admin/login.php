<?php
//session_start();
require_once 'core/dbConfig.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <style>
        body {
            font-family: "Arial";
            background-image: url("https://img.freepik.com/free-vector/pink-gradient-background_78370-3286.jpg?semt=ais_hybrid&w=740");
            background-size: cover;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 p-5">
                <div class="card shadow">
                    <div class="card-header">
                        <h2>Welcome to Docs Admin Panel! Login Now!</h2>
                    </div>
                    <form action="core/handleForms.php" method="POST">
                        <input type="hidden" name="is_admin" value="1">
                        <div class="card-body">
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="alert alert-<?= $_SESSION['status'] == 200 ? 'success' : 'danger' ?>">
                                    <?= $_SESSION['message'] ?>
                                </div>
                                <?php unset($_SESSION['message'], $_SESSION['status']); ?>
                            <?php endif; ?>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary float-right mt-3" name="login">Login</button>
                            <p class="mt-4">Don’t have an account yet? <a href="register.php">Register here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>