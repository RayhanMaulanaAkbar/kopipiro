<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

function api_response($data, $status = 200) {
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );

    exit;
}

function api_input() {
    $input = file_get_contents('php://input');

    return json_decode($input, true) ?? [];
}

function api_require_role($roles) {
    if (!is_logged_in()) {
        api_response([
            'message' => 'Please log in first'
        ], 401);
    }

    if (!in_array(current_role(), $roles, true)) {
        api_response([
            'message' => 'Access denied'
        ], 403);
    }
}

function api_final_price($price) {
    if (is_customer()) {
        return (int) round($price * 0.9);
    }

    return (int) $price;
}

?>