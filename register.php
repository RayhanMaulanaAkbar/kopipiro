<?php
include 'auth.php';
include 'config.php';

$message = "";

if (isset($_POST['add_user'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $role = 'customer';

    if (strlen($password) < 6) {

        $message = "<div class='alert alert-danger'>
                    Password must be at least 6 characters.
                    </div>";

    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (username, password, role)
             VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $username,
            $hash,
            $role
        );

        try {

            mysqli_stmt_execute($stmt);

            $message = "<div class='alert alert-success'>
                        User added successfully.
                        </div>";

        } catch (mysqli_sql_exception $e) {

            $message = "<div class='alert alert-danger'>
                        Username is already taken.
                        </div>";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - Kopipiro</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container min-vh-100 d-flex align-items-center justify-content-center">

        <div class="row justify-content-center w-100">

            <div class="col-md-6 col-lg-4">

                <div class="text-center mb-4">
                    <h2>Kopipiro</h2>
                    <p class="text-muted">Create your account</p>
                </div>

                <?= $message ?>

                <div class="card shadow-sm">

                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Register</h5>
                    </div>

                    <div class="card-body">

                        <form method="POST" action="">

                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    Username
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="username"
                                    name="username"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password
                                </label>

                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    minlength="6"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    Role
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="Customer"
                                    readonly>
                            </div>

                            <div class="d-grid">
                                <button
                                    type="submit"
                                    name="add_user"
                                    class="btn btn-success">
                                    Register
                                </button>
                            </div>

                        </form>

                        <div class="text-center mt-3">
                            Already have an account?
                            <a href="login.php">Login</a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>