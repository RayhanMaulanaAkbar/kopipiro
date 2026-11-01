<?php
require_once 'config.php';
require_once 'auth.php';

require_login();
require_customer();

$user_id = (int) $_SESSION['user_id'];

$query = "SELECT o.*,
          COUNT(od.id) AS total_items
          FROM orders o
          LEFT JOIN order_details od ON o.id = od.order_id
          WHERE o.customer_id = ?
          GROUP BY o.id
          ORDER BY o.order_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container py-4">
    <h2 class="mb-4">Order History</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total Items</th>
                        <th>Total Payment</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>#<?= $order['id']; ?></td>
                            <td><?= htmlspecialchars($order['order_date']); ?></td>
                            <td><?= $order['total_items']; ?></td>
                            <td>Rp <?= number_format($order['total'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars(ucfirst($order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_order.php?id=<?= $order['id']; ?>"
                                   class="btn btn-primary btn-sm">
                                    View Order
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            You haven't placed any orders yet.
        </div>
    <?php endif; ?>
</div>

</body>
</html>