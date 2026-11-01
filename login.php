<?php
include 'auth.php';
include 'config.php';

$message = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, username, password, role FROM users WHERE username = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: index.php");
        exit;

    } else {
        $message = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kopipiro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">

                <div class="card shadow">
                    <div class="card-header bg-dark text-white text-center">
                        <h3>Kopipiro Login</h3>
                    </div>

                    <div class="card-body">

                        <?php if ($message != ""): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username"
                                    class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password"
                                    class="form-control" required>
                            </div>

                            <button type="submit" name="login"
                                class="btn btn-dark w-100">
                                Login
                            </button>

                        </form>

                        <div class="text-center mt-3">
                            Belum punya akun?
                            <a href="register.php">Register</a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>