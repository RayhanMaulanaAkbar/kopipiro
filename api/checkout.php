<?php

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response([
        'message' => 'Method not allowed'
    ], 405);
}

$input = api_input();
$items = $input['items'] ?? [];

if (empty($items) || !is_array($items)) {
    api_response([
        'message' => 'Cart is empty'
    ], 400);
}

$isCustomer = is_customer();

$customer_id = $isCustomer
    ? (int) $_SESSION['user_id']
    : null;

$cartItems = [];
$subtotal = 0;

foreach ($items as $item) {

    $product_id = (int) ($item['product_id'] ?? 0);
    $quantity = (int) ($item['quantity'] ?? 0);

    if ($product_id <= 0 || $quantity < 1) {
        api_response([
            'message' => 'Invalid product or quantity'
        ], 400);
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price
         FROM product
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$product) {
        api_response([
            'message' => 'Product not found',
            'product_id' => $product_id
        ], 404);
    }

    $product['quantity'] = $quantity;
    $product['item_subtotal'] =
        $product['price'] * $quantity;

    $subtotal += $product['item_subtotal'];

    $cartItems[] = $product;
}

$discount = $isCustomer
    ? $subtotal * 0.10
    : 0;

$total = $subtotal - $discount;

mysqli_begin_transaction($conn);

try {

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO orders
         (customer_id, subtotal, discount, total)
         VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "iddd",
        $customer_id,
        $subtotal,
        $discount,
        $total
    );

    mysqli_stmt_execute($stmt);

    $order_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO order_details
         (order_id, product_id, product_name,
          price, quantity, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($cartItems as $item) {

        $product_id = $item['id'];
        $product_name = $item['name'];
        $price = $item['price'];
        $quantity = $item['quantity'];
        $item_subtotal = $item['item_subtotal'];

        mysqli_stmt_bind_param(
            $stmt,
            "iisdid",
            $order_id,
            $product_id,
            $product_name,
            $price,
            $quantity,
            $item_subtotal
        );

        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    api_response([
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'customer_id' => $customer_id,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => $total
    ], 201);

} catch (Throwable $e) {

    mysqli_rollback($conn);

    api_response([
        'message' => 'Checkout failed'
    ], 500);
}

?>