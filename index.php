<?php
include 'auth.php';
include 'config.php';

$query = "SELECT id, name, price, url_image, category, description, rating
          FROM product
          ORDER BY id DESC";

$result = mysqli_query($conn, $query);

$isCustomer = is_customer();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopipiro - Our Products</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container mt-5">

        <div class="text-center mb-5">
            <h1>Welcome to Kopipiro</h1>

            <p class="text-muted">
                Discover your favorite coffee and delicious treats.
            </p>

            <?php if (!$isCustomer): ?>
                <p class="text-success">
                    Login atau register untuk mendapatkan diskon 10%!
                </p>
            <?php else: ?>
                <p class="text-success">
                    Selamat! Kamu mendapatkan diskon 10%.
                </p>
            <?php endif; ?>
        </div>

        <h3 class="mb-4">Our Products</h3>

        <div class="row g-4">

            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <?php
                    $price = (float) $row['price'];
                    $discountPrice = $price * 0.9;
                ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card h-100 shadow-sm">

                        <img
                            src="<?= htmlspecialchars($row['url_image']) ?>"
                            class="card-img-top"
                            alt="<?= htmlspecialchars($row['name']) ?>"
                            style="height: 220px; object-fit: cover;">

                        <div class="card-body d-flex flex-column">

                            <span class="badge bg-success align-self-start mb-2">
                                <?= htmlspecialchars($row['category']) ?>
                            </span>

                            <h5 class="card-title">
                                <?= htmlspecialchars($row['name']) ?>
                            </h5>

                            <p class="text-warning mb-2">
                                ★ <?= number_format((float) $row['rating'], 1) ?>/5
                            </p>

                            <p class="card-text text-muted">
                                <?= htmlspecialchars(
                                    $row['description'] ?? 'No description available.'
                                ) ?>
                            </p>

                            <div class="mt-auto">

                                <?php if ($isCustomer): ?>

                                    <p class="mb-0 text-muted">
                                        <del>
                                            Rp <?= number_format($price, 0, ',', '.') ?>
                                        </del>
                                    </p>

                                    <h5 class="text-success">
                                        Rp <?= number_format($discountPrice, 0, ',', '.') ?>
                                    </h5>

                                    <small class="text-success">
                                        Diskon 10%
                                    </small>

                                <?php else: ?>

                                    <h5>
                                        Rp <?= number_format($price, 0, ',', '.') ?>
                                    </h5>

                                    <small class="text-muted">
                                        Harga normal
                                    </small>

                                <?php endif; ?>

                                <form action="add_to_cart.php" method="POST" class="mt-3">

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= $row['id'] ?>">

                                    <div class="input-group mb-2">
                                        <span class="input-group-text">Jumlah</span>

                                        <input
                                            type="number"
                                            name="quantity"
                                            class="form-control"
                                            value="1"
                                            min="1"
                                            required>
                                    </div>

                                    <button
                                        type="submit"
                                        class="btn btn-primary w-100">
                                        Add to Cart
                                    </button>

                                </form>

                            </div>

                        </div>
                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>