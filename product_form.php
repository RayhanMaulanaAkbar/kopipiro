<?php
include 'auth.php';
include 'config.php';

require_login();
require_owner();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$name = "";
$price = "";
$category = "Coffee";
$description = "";
$rating = 0;
$url_image = "";

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM product WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        die("Product not found.");
    }

    $product = mysqli_fetch_assoc($result);

    $name = $product['name'];
    $price = $product['price'];
    $category = $product['category'];
    $description = $product['description'];
    $rating = $product['rating'];
    $url_image = $product['url_image'];
}

if (isset($_POST['save'])) {
    $name = trim($_POST['name']);
    $price = (int) $_POST['price'];
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $rating = (float) $_POST['rating'];
    $url_image = trim($_POST['url_image']);

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE product
             SET name=?, price=?, category=?, description=?, rating=?, url_image=?
             WHERE id=?"
        );

        mysqli_stmt_bind_param(
            $stmt, "sissdsi",
            $name, $price, $category, $description, $rating, $url_image, $id
        );
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO product (name, price, category, description, rating, url_image)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt, "sissds",
            $name, $price, $category, $description, $rating, $url_image
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        header("Location: products.php");
        exit;
    } else {
        $error = mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Form - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-4">

    <div class="card shadow-sm">
        <div class="card-body">

            <h3><?= $id > 0 ? "Edit Product" : "Add Product" ?></h3>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control"
                           value="<?= htmlspecialchars($name) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" name="price" class="form-control"
                           value="<?= htmlspecialchars((string)$price) ?>" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="Coffee" <?= $category === 'Coffee' ? 'selected' : '' ?>>Coffee</option>
                        <option value="Non-Coffee" <?= $category === 'Non-Coffee' ? 'selected' : '' ?>>Non-Coffee</option>
                        <option value="Food" <?= $category === 'Food' ? 'selected' : '' ?>>Food</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rating</label>
                    <input type="number" name="rating" class="form-control"
                           min="0" max="5" step="0.1"
                           value="<?= htmlspecialchars((string)$rating) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Image URL</label>
                    <input type="text" name="url_image" class="form-control"
                           value="<?= htmlspecialchars($url_image) ?>">
                    <small class="text-muted">Masukkan alamat gambar produk.</small>
                </div>

                <button type="submit" name="save" class="btn btn-primary">Save Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>

            </form>

        </div>
    </div>

</div>

</body>
</html>