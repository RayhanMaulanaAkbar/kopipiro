<?php
include 'auth.php';
include 'config.php';

if (!is_owner() && !is_cashier()) {
    http_response_code(403);
    die("403 - Access denied.");
}

$message = "";

if (is_cashier() && isset($_POST['take_order'])) {

    $order_id = (int) $_POST['order_id'];
    $cashier_id = (int) $_SESSION['user_id'];

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
        $cashier_id,
        $order_id
    );

    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) === 1) {
        $message = "Order successfully taken!";
    } else {
        $message = "Order is no longer available.";
    }

    mysqli_stmt_close($stmt);
}

if (is_cashier() && isset($_POST['update_status'])) {

    $order_id = (int) $_POST['order_id'];
    $status = $_POST['status'];

    $allowedStatus = ['processing', 'completed', 'cancelled'];

    if (in_array($status, $allowedStatus, true)) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders
             SET status = ?
             WHERE id = ?
             AND cashier_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sii",
            $status,
            $order_id,
            $_SESSION['user_id']
        );

        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) >= 0) {
            $message = "Order status updated successfully.";
        }

        mysqli_stmt_close($stmt);
    }
}

if (is_owner()) {

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
        WHERE orders.cashier_id IS NULL
           OR orders.cashier_id = ?
        ORDER BY orders.order_date DESC
    ";

    $stmt = mysqli_prepare($conn, $query);

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $_SESSION['user_id']
    );
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$orders = [];

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - Kopipiro</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container mt-5">

        <h2 class="mb-4">
            <?= is_owner() ? 'All Orders' : 'Available & My Orders' ?>
        </h2>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>

            <div class="alert alert-info">
                No orders found.
            </div>

        <?php else: ?>

            <?php foreach ($orders as $order): ?>

                <?php
                    $isAvailable = $order['cashier_id'] === null;
                    $isMyOrder = (
                        is_cashier() &&
                        (int) $order['cashier_id'] === (int) $_SESSION['user_id']
                    );
                ?>

                <div class="card shadow-sm mb-4">

                    <div class="card-header d-flex justify-content-between">

                        <strong>
                            Order #<?= $order['id'] ?>
                        </strong>

                        <span class="badge bg-primary">
                            <?= htmlspecialchars($order['status']) ?>
                        </span>

                    </div>

                    <div class="card-body">

                        <p>
                            <strong>Customer:</strong>
                            <?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?>
                        </p>

                        <p>
                            <strong>Order Date:</strong>
                            <?= htmlspecialchars($order['order_date']) ?>
                        </p>

                        <p>
                            <strong>Subtotal:</strong>
                            Rp <?= number_format($order['subtotal'], 0, ',', '.') ?>
                        </p>

                        <p>
                            <strong>Discount:</strong>
                            Rp <?= number_format($order['discount'], 0, ',', '.') ?>
                        </p>

                        <h5 class="text-success">
                            Total:
                            Rp <?= number_format($order['total'], 0, ',', '.') ?>
                        </h5>

                        <hr>

                        <h6>Order Details</h6>

                        <?php
                        $detailStmt = mysqli_prepare(
                            $conn,
                            "SELECT product_name, price, quantity, subtotal
                             FROM order_details
                             WHERE order_id = ?"
                        );

                        mysqli_stmt_bind_param(
                            $detailStmt,
                            "i",
                            $order['id']
                        );

                        mysqli_stmt_execute($detailStmt);

                        $details = mysqli_stmt_get_result($detailStmt);
                        ?>

                        <ul>

                            <?php while ($detail = mysqli_fetch_assoc($details)): ?>

                                <li>
                                    <?= htmlspecialchars($detail['product_name']) ?>

                                    (<?= $detail['quantity'] ?> x
                                    Rp <?= number_format($detail['price'], 0, ',', '.') ?>)

                                    =
                                    Rp <?= number_format($detail['subtotal'], 0, ',', '.') ?>
                                </li>

                            <?php endwhile; ?>

                        </ul>

                        <?php mysqli_stmt_close($detailStmt); ?>

                        <hr>

                        <?php if (is_owner()): ?>

                            <p>
                                <strong>Assigned Cashier:</strong>

                                <?= htmlspecialchars(
                                    $order['cashier_name'] ?? 'Not assigned'
                                ) ?>
                            </p>

                        <?php elseif (is_cashier() && $isAvailable): ?>

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= $order['id'] ?>">

                                <button
                                    type="submit"
                                    name="take_order"
                                    class="btn btn-primary w-100">

                                    Take Order

                                </button>

                            </form>

                        <?php elseif (is_cashier() && $isMyOrder): ?>

                            <p class="text-success">
                                <strong>
                                    This order is assigned to you.
                                </strong>
                            </p>

                            <?php if (!in_array($order['status'], ['completed', 'cancelled'], true)): ?>

                                <form method="POST" class="d-flex gap-2">

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= $order['id'] ?>">

                                    <select
                                        name="status"
                                        class="form-select"
                                        required>

                                        <option value="processing"
                                            <?= $order['status'] == 'processing' ? 'selected' : '' ?>>
                                            Processing
                                        </option>

                                        <option value="completed">
                                            Completed
                                        </option>

                                        <option value="cancelled">
                                            Cancelled
                                        </option>

                                    </select>

                                    <button
                                        type="submit"
                                        name="update_status"
                                        class="btn btn-success">

                                        Update Status

                                    </button>

                                </form>

                            <?php endif; ?>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</body>
</html>