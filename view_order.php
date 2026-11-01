<?php
require_once 'config.php';
require_once 'auth.php';

require_login();

$user_id = (int) $_SESSION['user_id'];
$role = current_role();

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id || $order_id <= 0) {
    die("Invalid order ID.");
}

if ($role === 'owner') {
    $query = "SELECT * FROM orders WHERE id = ?";
} elseif ($role === 'customer') {
    $query = "SELECT * FROM orders WHERE id = ? AND customer_id = ?";
} elseif ($role === 'kasir') {
    $query = "SELECT * FROM orders WHERE id = ? AND cashier_id = ?";
} else {
    http_response_code(403);
    die("Access denied.");
}

$stmt = mysqli_prepare($conn, $query);

if ($role === 'owner') {
    mysqli_stmt_bind_param($stmt, "i", $order_id);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
}

mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    http_response_code(404);
    die("Order not found or access denied.");
}

$detail_query = "SELECT * FROM order_details WHERE order_id = ?";
$detail_stmt = mysqli_prepare($conn, $detail_query);
mysqli_stmt_bind_param($detail_stmt, "i", $order_id);
mysqli_stmt_execute($detail_stmt);
$details = mysqli_stmt_get_result($detail_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Order - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container py-4">
    <h2 class="mb-4">Order Details #<?= $order['id']; ?></h2>

    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Order Date:</strong>
                <?= htmlspecialchars($order['order_date']); ?>
            </p>
            <p><strong>Status:</strong>
                <?= htmlspecialchars(ucfirst($order['status'])); ?>
            </p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Ordered Products
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($details)): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']); ?></td>
                            <td>Rp <?= number_format($item['price'], 0, ',', '.'); ?></td>
                            <td><?= $item['quantity']; ?></td>
                            <td>Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-end">
            <p>Subtotal:
                <strong>Rp <?= number_format($order['subtotal'], 0, ',', '.'); ?></strong>
            </p>
            <p>Discount:
                <strong>Rp <?= number_format($order['discount'], 0, ',', '.'); ?></strong>
            </p>
            <h4>
                Total Payment:
                Rp <?= number_format($order['total'], 0, ',', '.'); ?>
            </h4>
        </div>
    </div>

    <a href="<?= $role === 'customer' ? 'order_history.php' : 'orders.php'; ?>"
       class="btn btn-secondary mt-3">
        Back
    </a>
</div>

</body>
</html>