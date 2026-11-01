<?php
include 'auth.php';
include 'config.php';

require_login();
require_owner();

$total_orders = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM orders"
))['total'];

$completed_orders = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM orders WHERE status = 'completed'"
))['total'];

$pending_orders = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'"
))['total'];

$total_revenue = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM orders
     WHERE status = 'completed'"
))['total'];

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
    $sales_data[] = (int) $row['pendapatan'];
}

$recent_orders = mysqli_query(
    $conn,
    "SELECT orders.*,
            COALESCE(users.username, 'Guest') AS customer_name
     FROM orders
     LEFT JOIN users ON orders.customer_id = users.id
     ORDER BY orders.order_date DESC
     LIMIT 5"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Owner Dashboard - Kopipiro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Owner Dashboard</h2>

        <a href="export_orders.php" class="btn btn-success">
            Download Excel
        </a>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Orders</h6>
                    <h3><?= $total_orders ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Completed Orders</h6>
                    <h3><?= $completed_orders ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Pending Orders</h6>
                    <h3><?= $pending_orders ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Revenue</h6>
                    <h4>
                        Rp <?= number_format($total_revenue, 0, ',', '.') ?>
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Revenue - Last 7 Days</h5>
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <h5 class="card-title mb-3">Recent Orders</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (mysqli_num_rows($recent_orders) > 0): ?>

                            <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>

                                    <td>
                                        <?= htmlspecialchars($order['customer_name']) ?>
                                    </td>

                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?>
                                    </td>

                                    <td>
                                        Rp <?= number_format($order['total'], 0, ',', '.') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($order['status']) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="text-center">
                                    No orders yet.
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<script>
const labels = <?= json_encode($sales_labels) ?>;
const revenue = <?= json_encode($sales_data) ?>;

new Chart(document.getElementById('salesChart'), {
    type: 'bar',

    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue (Rp)',
            data: revenue,
            borderWidth: 1
        }]
    },

    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>