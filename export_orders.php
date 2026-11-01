<?php
include 'auth.php';
include 'config.php';

require_login();
require_owner();

$query = mysqli_query(
    $conn,
    "SELECT orders.*,
            COALESCE(users.username, 'Guest') AS customer_name,
            COALESCE(cashiers.username, '-') AS cashier_name
     FROM orders
     LEFT JOIN users ON orders.customer_id = users.id
     LEFT JOIN users AS cashiers ON orders.cashier_id = cashiers.id
     ORDER BY orders.order_date DESC"
);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Orders_Kopipiro.xls");
header("Pragma: no-cache");
header("Expires: 0");
?>

<table border="1">
    <tr>
        <th>No</th>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Cashier</th>
        <th>Order Date</th>
        <th>Subtotal</th>
        <th>Discount</th>
        <th>Total</th>
        <th>Status</th>
    </tr>

    <?php
    $no = 1;

    while ($row = mysqli_fetch_assoc($query)):
    ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= htmlspecialchars($row['cashier_name']) ?></td>
            <td><?= $row['order_date'] ?></td>
            <td><?= $row['subtotal'] ?></td>
            <td><?= $row['discount'] ?></td>
            <td><?= $row['total'] ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
        </tr>
    <?php endwhile; ?>
</table>