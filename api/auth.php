<?php

require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    if (!is_logged_in()) {
        api_response([
            'logged_in' => false,
            'message' => 'User is not logged in'
        ]);
    }

    api_response([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ]
    ]);
}

if ($method === 'POST') {

    $data = api_input();
    $action = $data['action'] ?? '';

    if ($action === 'register') {

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            api_response([
                'message' => 'Username and password are required'
            ], 400);
        }

        if (strlen($password) < 8) {
            api_response([
                'message' => 'Password must be at least 8 characters'
            ], 400);
        }

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE username = ?"
        );

        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);

        if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) {
            api_response([
                'message' => 'Username already exists'
            ], 409);
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer';

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (username, password, role)
             VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $username,
            $hashed_password,
            $role
        );

        if (!mysqli_stmt_execute($stmt)) {
            api_response([
                'message' => 'Registration failed'
            ], 500);
        }

        api_response([
            'message' => 'Registration successful',
            'user_id' => mysqli_insert_id($conn)
        ], 201);
    }

    if ($action === 'login') {

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            api_response([
                'message' => 'Username and password are required'
            ], 400);
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, username, password, role
             FROM users
             WHERE username = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user || !password_verify($password, $user['password'])) {
            api_response([
                'message' => 'Invalid username or password'
            ], 401);
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        api_response([
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);
    }

    if ($action === 'logout') {

        if (!is_logged_in()) {
            api_response([
                'message' => 'User is not logged in'
            ], 401);
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        api_response([
            'message' => 'Logout successful'
        ]);
    }

    api_response([
        'message' => 'Invalid action'
    ], 400);
}


header('Allow: GET, POST');

api_response([
    'message' => 'Method not allowed'
], 405);

?>