<?php?>

<nav class="navbar navbar-dark bg-dark">
    <div class="container">

        <a class="navbar-brand" href="index.php">Kopipiro</a>

        <div class="d-flex align-items-center gap-2 flex-wrap">

            <a href="index.php" class="btn btn-outline-info btn-sm">
                Menu Utama
            </a>

            <?php if (is_owner()): ?>

                <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                    Dashboard
                </a>

                <a href="users.php" class="btn btn-outline-light btn-sm">
                    Users
                </a>

                <a href="products.php" class="btn btn-outline-light btn-sm">
                    Manage Products
                </a>

                <a href="orders.php" class="btn btn-outline-light btn-sm">
                    All Orders
                </a>

            <?php elseif (is_cashier()): ?>

                <a href="orders.php" class="btn btn-outline-light btn-sm">
                    My Orders
                </a>

            <?php endif; ?>

            <?php if (!is_owner() && !is_cashier()): ?>

                <a href="cart.php" class="btn btn-outline-light btn-sm">
                    Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)
                </a>

                <?php if (is_customer()): ?>
                    <a href="order_history.php" class="btn btn-outline-light btn-sm">
                        Order History
                    </a>
                <?php endif; ?>

            <?php endif; ?>

            <?php if (is_logged_in()): ?>

                <span class="text-white small">
                    <?= htmlspecialchars($_SESSION['username']) ?>

                    <span class="badge bg-secondary">
                        <?= htmlspecialchars($_SESSION['role']) ?>
                    </span>
                </span>

                <a href="logout.php" class="btn btn-danger btn-sm">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php" class="btn btn-outline-light btn-sm">
                    Login
                </a>

                <a href="register.php" class="btn btn-outline-light btn-sm">
                    Register
                </a>

            <?php endif; ?>

        </div>
    </div>
</nav>