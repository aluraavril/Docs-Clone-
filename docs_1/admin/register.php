<?php
//session_start();
require_once 'core/dbConfig.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Register</title>
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
                        <h2>Welcome to Docs Clone Admin! Register Now!</h2>
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
                                <label>Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" name="register" class="btn btn-primary float-right mt-3">Register</button>
                            <p class="mt-4">Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>