<?php

require_once 'helpers.php';

api_require_role(['owner']);


$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders"
);

$total_orders = mysqli_fetch_assoc($result)['total'];


$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'completed'"
);

$completed_orders = mysqli_fetch_assoc($result)['total'];


$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'pending'"
);

$pending_orders = mysqli_fetch_assoc($result)['total'];


$result = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM orders
     WHERE status = 'completed'"
);

$total_revenue = mysqli_fetch_assoc($result)['total'];


$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM product"
);

$total_products = mysqli_fetch_assoc($result)['total'];


$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users"
);

$total_users = mysqli_fetch_assoc($result)['total'];


$sales_query = mysqli_query(
    $conn,
    "SELECT DATE(order_date) AS tanggal,
            SUM(total) AS pendapatan
     FROM orders
     WHERE status = 'completed'
       AND order_date >= CURDATE() - INTERVAL 6 DAY
     GROUP BY DATE(order_date)
     ORDER BY tanggal ASC"
);

$sales_labels = [];
$sales_data = [];

while ($row = mysqli_fetch_assoc($sales_query)) {

    $sales_labels[] = date('d/m', strtotime($row['tanggal']));

    $sales_data[] = (float) $row['pendapatan'];
}


$recent_query = mysqli_query(
    $conn,
    "SELECT orders.*,
            COALESCE(users.username, 'Guest') AS customer_name
     FROM orders
     LEFT JOIN users ON orders.customer_id = users.id
     ORDER BY orders.order_date DESC
     LIMIT 5"
);

$recent_orders = [];

while ($row = mysqli_fetch_assoc($recent_query)) {
    $recent_orders[] = $row;
}


api_response([

    'message' => 'Dashboard data retrieved successfully',

    'dashboard' => [

        'total_orders' => (int) $total_orders,

        'completed_orders' => (int) $completed_orders,

        'pending_orders' => (int) $pending_orders,

        'total_revenue' => (float) $total_revenue,

        'total_products' => (int) $total_products,

        'total_users' => (int) $total_users,

        'sales_labels' => $sales_labels,

        'sales_data' => $sales_data,

        'recent_orders' => $recent_orders

    ]

]);

?>