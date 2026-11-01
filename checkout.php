<?php
include 'auth.php';
include 'config.php';

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$isCustomer = is_customer();

$cartItems = [];
$subtotal = 0;

foreach ($_SESSION['cart'] as $product_id => $quantity) {

    $product_id = (int) $product_id;
    $quantity = (int) $quantity;

    if ($quantity < 1) {
        continue;
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

    if ($product) {

        $product['quantity'] = $quantity;
        $product['item_subtotal'] = $product['price'] * $quantity;

        $subtotal += $product['item_subtotal'];
        $cartItems[] = $product;
    }
}

if (empty($cartItems)) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}

$discount = $isCustomer ? $subtotal * 0.10 : 0;
$total = $subtotal - $discount;

$message = "";

if (isset($_POST['checkout'])) {

    mysqli_begin_transaction($conn);

    try {

        $customer_id = $isCustomer
            ? (int) $_SESSION['user_id']
            : null;

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

        $_SESSION['last_order_id'] = $order_id;

        $_SESSION['cart'] = [];

        header("Location: checkout_success.php");
        exit;

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        $message = "Checkout gagal. Silakan coba kembali.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kopipiro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container mt-5">

        <h2 class="mb-4">Checkout</h2>

        <?php if ($message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">

                <h5 class="mb-3">Order Summary</h5>

                <?php foreach ($cartItems as $item): ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span>
                            <?= htmlspecialchars($item['name']) ?>
                            (<?= $item['quantity'] ?>)
                        </span>

                        <strong>
                            Rp <?= number_format($item['item_subtotal'], 0, ',', '.') ?>
                        </strong>
                    </div>

                <?php endforeach; ?>

                <hr>

                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <strong>
                        Rp <?= number_format($subtotal, 0, ',', '.') ?>
                    </strong>
                </div>

                <div class="d-flex justify-content-between mb-2 text-success">
                    <span>Discount (10%)</span>
                    <strong>
                        - Rp <?= number_format($discount, 0, ',', '.') ?>
                    </strong>
                </div>

                <?php if (!$isCustomer): ?>
                    <p class="text-muted small">
                        Guest order: no discount applied.
                    </p>
                <?php endif; ?>

                <hr>

                <div class="d-flex justify-content-between mb-3">
                    <h5>Total</h5>
                    <h5 class="text-success">
                        Rp <?= number_format($total, 0, ',', '.') ?>
                    </h5>
                </div>

                <form method="POST">

                    <button
                        type="submit"
                        name="checkout"
                        class="btn btn-primary w-100">
                        Confirm Order
                    </button>

                </form>

                <a href="cart.php"
                   class="btn btn-outline-secondary w-100 mt-2">
                    Back to Cart
                </a>

            </div>
        </div>

    </div>

</body>
</html>