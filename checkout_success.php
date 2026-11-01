<?php
include 'auth.php';
include 'config.php';

$order_id = (int) ($_SESSION['last_order_id'] ?? 0);

if ($order_id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, total, order_date
     FROM orders
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$order) {
    http_response_code(404);
    die("Order not found.");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Kopipiro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container mt-5">

        <div class="card shadow-sm text-center mx-auto"
             style="max-width: 550px;">

            <div class="card-body p-5">

                <h1 class="text-success mb-3">
                    Order Successful!
                </h1>

                <p>Thank you for ordering at Kopipiro.</p>

                <hr>

                <p>
                    <strong>Order ID:</strong>
                    #<?= $order['id'] ?>
                </p>

                <p>
                    <strong>Order Date:</strong>
                    <?= htmlspecialchars($order['order_date']) ?>
                </p>

                <h4 class="text-success">
                    Rp <?= number_format($order['total'], 0, ',', '.') ?>
                </h4>

                <a href="index.php" class="btn btn-primary mt-3">
                    Back to Home
                </a>

            </div>

        </div>

    </div>

</body>
</html>