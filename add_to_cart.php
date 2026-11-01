<?php
include 'auth.php';
include 'config.php';

if (isset($_POST['product_id'])) {

    $product_id = (int) $_POST['product_id'];
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($product_id <= 0 || $quantity < 1) {
        header("Location: index.php");
        exit;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM product WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
    }

    mysqli_stmt_close($stmt);
}

header("Location: cart.php");
exit;