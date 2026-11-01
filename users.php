<?php
include 'auth.php';
include 'config.php';

require_login();
require_owner();

$error = "";
$success = "";

if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } elseif (!in_array($role, ['customer', 'kasir'])) {
        $error = "Role tidak valid.";
    } else {
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE username = ?"
        );

        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {
            $error = "Username sudah digunakan.";
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

            if (mysqli_stmt_execute($stmt)) {
                $success = "Pengguna berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan pengguna.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

if (isset($_POST['edit_user'])) {
    $id = (int) $_POST['id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if ($username === "") {
        $error = "Username wajib diisi.";
    } elseif (!in_array($role, ['customer', 'kasir'])) {
        $error = "Role tidak valid.";
    } else {
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users
             WHERE username = ? AND id != ?"
        );

        mysqli_stmt_bind_param($check, "si", $username, $id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {
            $error = "Username sudah digunakan.";
        } else {
            if ($password !== "") {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET username = ?, password = ?, role = ?
                     WHERE id = ? AND role != 'owner'"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssi",
                    $username,
                    $hash,
                    $role,
                    $id
                );
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET username = ?, role = ?
                     WHERE id = ? AND role != 'owner'"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssi",
                    $username,
                    $role,
                    $id
                );
            }

            if (mysqli_stmt_execute($stmt) &&
                mysqli_stmt_affected_rows($stmt) >= 0) {
                $success = "Data pengguna berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui pengguna.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM users
         WHERE id = ? AND role != 'owner'"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $success = "Pengguna berhasil dihapus.";
    } else {
        $error = "Gagal menghapus pengguna.";
    }

    mysqli_stmt_close($stmt);
}

$query = mysqli_query(
    $conn,
    "SELECT id, username, role FROM users ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Kopipiro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-4">

    <h2 class="mb-4">Manage Users</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h5>Add New User</h5>

            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Username</label>
                        <input type="text" name="username"
                               class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password"
                               class="form-control" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="customer">Customer</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="add_user"
                                class="btn btn-primary w-100">
                            Add User
                        </button>
                    </div>

                </div>

            </form>

        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <h5 class="mb-3">User List</h5>

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($user = mysqli_fetch_assoc($query)): ?>

                            <tr>
                                <td><?= $user['id'] ?></td>

                                <td>
                                    <?= htmlspecialchars($user['username']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['role']) ?>
                                </td>

                                <td>

                                    <?php if ($user['role'] !== 'owner'): ?>

                                        <button
                                            class="btn btn-warning btn-sm"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#edit<?= $user['id'] ?>">
                                            Edit
                                        </button>

                                        <a href="users.php?delete=<?= $user['id'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this user?')">
                                            Delete
                                        </a>

                                    <?php else: ?>

                                        <span class="text-muted">Protected</span>

                                    <?php endif; ?>

                                </td>
                            </tr>

                            <?php if ($user['role'] !== 'owner'): ?>

                                <tr class="collapse" id="edit<?= $user['id'] ?>">
                                    <td colspan="4">

                                        <form method="POST">

                                            <input type="hidden" name="id"
                                                   value="<?= $user['id'] ?>">

                                            <div class="row g-2">

                                                <div class="col-md-4">
                                                    <input type="text"
                                                           name="username"
                                                           class="form-control"
                                                           value="<?= htmlspecialchars($user['username']) ?>"
                                                           required>
                                                </div>

                                                <div class="col-md-3">
                                                    <input type="password"
                                                           name="password"
                                                           class="form-control"
                                                           placeholder="New password (optional)">
                                                </div>

                                                <div class="col-md-3">
                                                    <select name="role"
                                                            class="form-select">

                                                        <option value="customer"
                                                            <?= $user['role'] === 'customer' ? 'selected' : '' ?>>
                                                            Customer
                                                        </option>

                                                        <option value="kasir"
                                                            <?= $user['role'] === 'kasir' ? 'selected' : '' ?>>
                                                            Kasir
                                                        </option>

                                                    </select>
                                                </div>

                                                <div class="col-md-2">
                                                    <button type="submit"
                                                            name="edit_user"
                                                            class="btn btn-success w-100">
                                                        Save
                                                    </button>
                                                </div>

                                            </div>

                                        </form>

                                    </td>
                                </tr>

                            <?php endif; ?>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>