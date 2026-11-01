<?php
include 'auth.php';
include 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$isCustomer = is_customer();

if (isset($_POST['update_cart'])) {

    $product_id = (int) $_POST['product_id'];
    $quantity = (int) $_POST['quantity'];

    if (isset($_SESSION['cart'][$product_id])) {

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
    }

    header("Location: cart.php");
    exit;
}

if (isset($_POST['remove_item'])) {

    $product_id = (int) $_POST['product_id'];

    unset($_SESSION['cart'][$product_id]);

    header("Location: cart.php");
    exit;
}

$cartItems = [];
$subtotal = 0;

foreach ($_SESSION['cart'] as $product_id => $quantity) {

    $product_id = (int) $product_id;
    $quantity = (int) $quantity;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price, url_image
         FROM product
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($product && $quantity > 0) {

        $product['quantity'] = $quantity;
        $product['item_subtotal'] = $product['price'] * $quantity;

        $subtotal += $product['item_subtotal'];
        $cartItems[] = $product;

    } else {
        unset($_SESSION['cart'][$product_id]);
    }
}

$discount = $isCustomer ? $subtotal * 0.10 : 0;
$total = $subtotal - $discount;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Kopipiro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container mt-5">

        <h2 class="mb-4">Shopping Cart</h2>

        <?php if (empty($cartItems)): ?>

            <div class="alert alert-info">
                Your shopping cart is empty.
            </div>

            <a href="index.php" class="btn btn-primary">
                Browse Products
            </a>

        <?php else: ?>

            <div class="card shadow-sm">
                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($cartItems as $item): ?>

                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">

                                                <img
                                                    src="<?= htmlspecialchars($item['url_image']) ?>"
                                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                                    width="70"
                                                    height="70"
                                                    style="object-fit: cover;">

                                                <strong>
                                                    <?= htmlspecialchars($item['name']) ?>
                                                </strong>

                                            </div>
                                        </td>

                                        <td>
                                            Rp <?= number_format($item['price'], 0, ',', '.') ?>
                                        </td>

                                        <td>
                                            <form method="POST" class="d-flex gap-2">

                                                <input type="hidden" name="product_id"
                                                       value="<?= $item['id'] ?>">

                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    value="<?= $item['quantity'] ?>"
                                                    min="0"
                                                    class="form-control"
                                                    style="width: 80px;"
                                                    required>

                                                <button
                                                    type="submit"
                                                    name="update_cart"
                                                    class="btn btn-warning btn-sm">
                                                    Update
                                                </button>

                                            </form>
                                        </td>

                                        <td>
                                            Rp <?= number_format($item['item_subtotal'], 0, ',', '.') ?>
                                        </td>

                                        <td>
                                            <form method="POST">

                                                <input type="hidden" name="product_id"
                                                       value="<?= $item['id'] ?>">

                                                <button
                                                    type="submit"
                                                    name="remove_item"
                                                    class="btn btn-danger btn-sm">
                                                    Remove
                                                </button>

                                            </form>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>

                    </div>

                    <hr>

                    <div class="row justify-content-end">
                        <div class="col-md-5">

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
                                <small class="text-muted">
                                    Login untuk mendapatkan diskon 10%.
                                </small>
                            <?php endif; ?>

                            <hr>

                            <div class="d-flex justify-content-between mb-3">
                                <h5>Total</h5>
                                <h5 class="text-success">
                                    Rp <?= number_format($total, 0, ',', '.') ?>
                                </h5>
                            </div>

                            <a href="index.php"
                               class="btn btn-outline-secondary w-100 mb-2">
                                Continue Shopping
                            </a>

                            <a href="checkout.php"
                               class="btn btn-primary w-100">
                                Proceed to Checkout
                            </a>

                        </div>
                    </div>

                </div>
            </div>

        <?php endif; ?>

    </div>

</body>
</html>