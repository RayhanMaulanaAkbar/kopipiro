<?php

require_once 'helpers.php';

api_require_role(['owner', 'kasir', 'customer']);

$method = $_SERVER['REQUEST_METHOD'];
$role = current_role();
$user_id = (int) $_SESSION['user_id'];


if ($method === 'GET') {

    if ($role === 'owner') {

        $query = "
            SELECT orders.*,
                   users.username AS customer_name,
                   cashier.username AS cashier_name
            FROM orders
            LEFT JOIN users
                ON orders.customer_id = users.id
            LEFT JOIN users AS cashier
                ON orders.cashier_id = cashier.id
            ORDER BY orders.order_date DESC
        ";

        $stmt = mysqli_prepare($conn, $query);

    } elseif ($role === 'kasir') {

        $query = "
            SELECT orders.*,
                   users.username AS customer_name,
                   cashier.username AS cashier_name
            FROM orders
            LEFT JOIN users
                ON orders.customer_id = users.id
            LEFT JOIN users AS cashier
                ON orders.cashier_id = cashier.id
            WHERE orders.cashier_id IS NULL
               OR orders.cashier_id = ?
            ORDER BY orders.order_date DESC
        ";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);

    } else {

        $query = "
            SELECT orders.*,
                   users.username AS customer_name,
                   cashier.username AS cashier_name
            FROM orders
            LEFT JOIN users
                ON orders.customer_id = users.id
            LEFT JOIN users AS cashier
                ON orders.cashier_id = cashier.id
            WHERE orders.customer_id = ?
            ORDER BY orders.order_date DESC
        ";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $orders = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }

    mysqli_stmt_close($stmt);

    api_response([
        'message' => 'Orders retrieved successfully',
        'orders' => $orders
    ]);
}

if ($method === 'POST') {

    $input = api_input();

    $action = $input['action'] ?? 'detail';
    $order_id = (int) ($input['order_id'] ?? 0);

    if ($order_id <= 0) {
        api_response([
            'message' => 'Invalid order ID'
        ], 400);
    }


    if ($action === 'detail') {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM orders WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$order) {
            api_response([
                'message' => 'Order not found'
            ], 404);
        }

        if (
            $role === 'customer' &&
            (int) $order['customer_id'] !== $user_id
        ) {
         api_response([
                'message' => 'Access denied'
         ], 403);
        }

        if (
           $role === 'kasir' &&
           $order['cashier_id'] !== null &&
           (int) $order['cashier_id'] !== $user_id
        ) {
           api_response([
                'message' => 'Access denied'
            ], 403);
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT product_name, price, quantity, subtotal
             FROM order_details
             WHERE order_id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $details = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $details[] = $row;
        }

        mysqli_stmt_close($stmt);

        api_response([
            'message' => 'Order detail retrieved successfully',
            'order' => $order,
            'details' => $details
        ]);
    }


    if ($action === 'take_order') {

        if ($role !== 'kasir') {
            api_response([
                'message' => 'Only cashier can take orders'
            ], 403);
        }

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders
             SET cashier_id = ?
             WHERE id = ?
             AND cashier_id IS NULL
             AND status = 'pending'"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $user_id,
            $order_id
        );

        mysqli_stmt_execute($stmt);

        $success = mysqli_stmt_affected_rows($stmt) === 1;

        mysqli_stmt_close($stmt);

        if (!$success) {
            api_response([
                'message' => 'Order is no longer available'
            ], 409);
        }

        api_response([
            'message' => 'Order successfully taken'
        ]);
    }


    if ($action === 'update_status') {

        if ($role !== 'kasir') {
            api_response([
                'message' => 'Only cashier can update order status'
            ], 403);
        }

        $status = $input['status'] ?? '';

        $allowedStatus = [
            'processing',
            'completed',
            'cancelled'
        ];

        if (!in_array($status, $allowedStatus, true)) {
            api_response([
                'message' => 'Invalid order status'
            ], 400);
        }

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders
             SET status = ?
             WHERE id = ?
             AND cashier_id = ?
             AND status NOT IN ('completed', 'cancelled')"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sii",
            $status,
            $order_id,
            $user_id
        );

        mysqli_stmt_execute($stmt);

        $updated = mysqli_stmt_affected_rows($stmt) === 1;

        mysqli_stmt_close($stmt);

        if (!$updated) {
            api_response([
                'message' => 'Order could not be updated'
            ], 409);
        }

        api_response([
            'message' => 'Order status updated successfully'
        ]);
    }


    api_response([
        'message' => 'Invalid action'
    ], 400);
}


api_response([
    'message' => 'Method not allowed'
], 405);