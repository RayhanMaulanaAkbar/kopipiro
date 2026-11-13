<?php

require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    $category = trim($_GET['category'] ?? '');

    if ($category !== '') {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, price, url_image, category,
                    description, rating
             FROM product
             WHERE category = ?
             ORDER BY id DESC"
        );

        mysqli_stmt_bind_param($stmt, "s", $category);

    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, price, url_image, category,
                    description, rating
             FROM product
             ORDER BY id DESC"
        );
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $products = [];

    while ($row = mysqli_fetch_assoc($result)) {

        $row['final_price'] = api_final_price($row['price']);

        $products[] = $row;
    }

    $category_result = mysqli_query(
        $conn,
        "SELECT DISTINCT category
         FROM product
         ORDER BY category ASC"
    );

    $categories = [];

    while ($row = mysqli_fetch_assoc($category_result)) {
        $categories[] = [
            'name' => $row['category']
        ];
    }

    api_response([
        'products' => $products,
        'categories' => $categories
    ]);
}


if ($method === 'POST') {

    api_require_role(['owner']);

    $data = api_input();

    $name = trim($data['name'] ?? '');
    $price = (int) ($data['price'] ?? 0);
    $url_image = trim($data['url_image'] ?? '');
    $category = trim($data['category'] ?? '');
    $description = trim($data['description'] ?? '');
    $rating = (float) ($data['rating'] ?? 0);

    if (
        $name === '' ||
        $price <= 0 ||
        $category === '' ||
        $rating < 0 ||
        $rating > 5
    ) {
        api_response([
            'message' => 'Invalid product data'
        ], 400);
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO product
         (name, price, url_image, category, description, rating)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sisssd",
        $name,
        $price,
        $url_image,
        $category,
        $description,
        $rating
    );

    if (!mysqli_stmt_execute($stmt)) {
        api_response([
            'message' => 'Failed to add product'
        ], 500);
    }

    api_response([
        'message' => 'Product added successfully',
        'product_id' => mysqli_insert_id($conn)
    ], 201);
}


if ($method === 'PUT') {

    api_require_role(['owner']);

    $data = api_input();

    $id = (int) ($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $price = (int) ($data['price'] ?? 0);
    $url_image = trim($data['url_image'] ?? '');
    $category = trim($data['category'] ?? '');
    $description = trim($data['description'] ?? '');
    $rating = (float) ($data['rating'] ?? 0);

    if (
        $id <= 0 ||
        $name === '' ||
        $price <= 0 ||
        $category === '' ||
        $rating < 0 ||
        $rating > 5
    ) {
        api_response([
            'message' => 'Invalid product data'
        ], 400);
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE product
         SET name = ?,
             price = ?,
             url_image = ?,
             category = ?,
             description = ?,
             rating = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sisssdi",
        $name,
        $price,
        $url_image,
        $category,
        $description,
        $rating,
        $id
    );

    if (!mysqli_stmt_execute($stmt)) {
        api_response([
            'message' => 'Failed to update product'
        ], 500);
    }

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM product WHERE id = ?"
        );

        mysqli_stmt_bind_param($check, "i", $id);
        mysqli_stmt_execute($check);

        if (mysqli_num_rows(mysqli_stmt_get_result($check)) === 0) {
            api_response([
                'message' => 'Product not found'
            ], 404);
        }
    }

    api_response([
        'message' => 'Product updated successfully'
    ]);
}


if ($method === 'DELETE') {

    api_require_role(['owner']);

    $data = api_input();
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        api_response([
            'message' => 'Invalid product ID'
        ], 400);
    }

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM product WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        api_response([
            'message' => 'Product not found'
        ], 404);
    }

    api_response([
        'message' => 'Product deleted successfully'
    ]);
}



header('Allow: GET, POST, PUT, DELETE');

api_response([
    'message' => 'Method not allowed'
], 405);

?>