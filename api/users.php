<?php

require_once __DIR__ . '/helpers.php';

api_require_role(['owner']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    $query = mysqli_query(
        $conn,
        "SELECT id, username, role FROM users ORDER BY id DESC"
    );

    $users = [];

    while ($row = mysqli_fetch_assoc($query)) {
        $users[] = $row;
    }

    api_response([
        'message' => 'Users retrieved successfully',
        'users' => $users
    ]);

}

elseif ($method === 'POST') {

    $input = api_input();

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'customer';

    if ($username === '' || $password === '') {
        api_response([
            'message' => 'Username dan password wajib diisi'
        ], 400);
    }

    if (strlen($password) < 8) {
        api_response([
            'message' => 'Password minimal 8 karakter'
        ], 400);
    }

    if (!in_array($role, ['owner', 'kasir', 'customer'], true)) {
        api_response([
            'message' => 'Role tidak valid'
        ], 400);
    }

    $check = mysqli_prepare(
        $conn,
        "SELECT id FROM users WHERE username = ?"
    );

    mysqli_stmt_bind_param($check, "s", $username);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        api_response([
            'message' => 'Username sudah digunakan'
        ], 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (username, password, role)
         VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $username,
        $hashedPassword,
        $role
    );

    if (mysqli_stmt_execute($stmt)) {
        api_response([
            'message' => 'User created successfully',
            'user_id' => mysqli_insert_id($conn)
        ], 201);
    }

    api_response([
        'message' => 'Failed to create user'
    ], 500);

}

elseif ($method === 'PUT') {

    $input = api_input();

    $id = (int) ($input['id'] ?? 0);
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? '';

    if ($id <= 0 || $username === '' || $role === '') {
        api_response([
            'message' => 'Data pengguna tidak lengkap'
        ], 400);
    }

    if (!in_array($role, ['owner', 'kasir', 'customer'], true)) {
        api_response([
            'message' => 'Role tidak valid'
        ], 400);
    }

    $check = mysqli_prepare(
        $conn,
        "SELECT role FROM users WHERE id = ?"
    );

    mysqli_stmt_bind_param($check, "i", $id);
    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);
    $target = mysqli_fetch_assoc($result);

    if (!$target) {
        api_response([
            'message' => 'User not found'
        ], 404);
    }

    if ($target['role'] === 'owner') {
        api_response([
            'message' => 'Akun owner tidak dapat diubah'
        ], 403);
    }

    $checkUsername = mysqli_prepare(
        $conn,
        "SELECT id FROM users WHERE username = ? AND id != ?"
    );

    mysqli_stmt_bind_param(
        $checkUsername,
        "si",
        $username,
        $id
    );

    mysqli_stmt_execute($checkUsername);
    mysqli_stmt_store_result($checkUsername);

    if (mysqli_stmt_num_rows($checkUsername) > 0) {
        api_response([
            'message' => 'Username sudah digunakan'
        ], 409);
    }

    if ($password !== '') {

        if (strlen($password) < 8) {
            api_response([
                'message' => 'Password minimal 8 karakter'
            ], 400);
        }

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET username = ?, password = ?, role = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sssi",
            $username,
            $hashedPassword,
            $role,
            $id
        );

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET username = ?, role = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $username,
            $role,
            $id
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        api_response([
            'message' => 'User updated successfully'
        ]);
    }

    api_response([
        'message' => 'Failed to update user'
    ], 500);

}

elseif ($method === 'DELETE') {

    $input = api_input();

    $id = (int) ($input['id'] ?? 0);

    if ($id <= 0) {
        api_response([
            'message' => 'ID pengguna tidak valid'
        ], 400);
    }

    $check = mysqli_prepare(
        $conn,
        "SELECT role FROM users WHERE id = ?"
    );

    mysqli_stmt_bind_param($check, "i", $id);
    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);
    $target = mysqli_fetch_assoc($result);

    if (!$target) {
        api_response([
            'message' => 'User not found'
        ], 404);
    }

    if ($target['role'] === 'owner') {
        api_response([
            'message' => 'Akun owner tidak dapat dihapus'
        ], 403);
    }

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM users WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        api_response([
            'message' => 'User deleted successfully'
        ]);
    }

    api_response([
        'message' => 'Failed to delete user'
    ], 500);

}

else {
    api_response([
        'message' => 'Method not allowed'
    ], 405);
}

?>