<?php
include 'auth.php';
include 'config.php';

require_login();
require_owner();

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = mysqli_prepare($conn, "DELETE FROM product WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: products.php");
    exit;
}

$query = mysqli_query($conn, "SELECT * FROM product ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-4">

    <div class="d-flex justify-content-between mb-3">
        <h2>Manage Products</h2>
        <a href="product_form.php" class="btn btn-primary">Add Product</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Rating</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td>
                            <?php if (!empty($row['url_image'])): ?>
                                <img src="<?= htmlspecialchars($row['url_image']) ?>" width="60">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td>Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                        <td><?= $row['rating'] ?></td>
                        <td>
                            <a href="product_form.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="products.php?delete=<?= $row['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

</body>
</html>