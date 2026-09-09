<?php
/**
 * admin.php — protected dashboard for managing which perfume sizes
 * are in stock. Only accessible when $_SESSION['admin_id'] is set
 * (i.e. logged in via adminlogin.php). Submits to adminupdatestock.php.
 *
 * Unlike the rental site this was adapted from — where cars lived in a
 * PHP array and only color/stock lived in the database — here the
 * whole catalog (products + product_variants) already lives in
 * schema.sql, so this reads straight from the database.
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: adminlogin.php');
    exit;
}

require __DIR__ . '/config.php'; // provides $pdo

$siteName = "TIO Perfume Collection";

// Load every product together with its size variants in one query.
$rows = $pdo->query(
    "SELECT p.id AS product_id, p.name AS product_name,
            pv.id AS variant_id, pv.size_label, pv.in_stock, pv.stock_qty
     FROM products p
     LEFT JOIN product_variants pv ON pv.product_id = p.id
     ORDER BY p.bestseller_rank IS NULL, p.bestseller_rank, p.name, pv.size_label"
)->fetchAll();

// Group the flat rows into [product_id => ['name' => ..., 'variants' => [...]]]
$products = [];
foreach ($rows as $row) {
    $pid = $row['product_id'];
    if (!isset($products[$pid])) {
        $products[$pid] = ['name' => $row['product_name'], 'variants' => []];
    }
    if ($row['variant_id'] !== null) {
        $products[$pid]['variants'][] = [
            'id'         => (int)$row['variant_id'],
            'size_label' => $row['size_label'],
            'in_stock'   => (bool)$row['in_stock'],
            'stock_qty'  => (int)$row['stock_qty'],
        ];
    }
}

$saved = isset($_GET['saved']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Stock Management | <?php echo htmlspecialchars($siteName); ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
    <a href="index.php" class="brand-logo" aria-label="<?php echo htmlspecialchars($siteName); ?>">
        <span class="logo-mark"><img src="img/logo-header.png" alt="<?php echo htmlspecialchars($siteName); ?>"></span>
    </a>
    <div class="header-icons" style="margin-left:auto;">
        <span class="user-greeting">Admin: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
        <a href="adminlogout.php" class="btn btn-outline-purple btn-sm">Log Out</a>
    </div>
</header>

<section class="page-hero">
    <h1>Stock Management</h1>
    <p class="breadcrumb">Check a size to mark it in stock, and set how many bottles are left. A perfume with no sizes checked shows as "Out of Stock" on the Shop and Best Sellers pages.</p>
</section>

<section class="section">
    <?php if ($saved): ?>
        <p class="modal-success" style="text-align:center;margin-bottom:20px;">Stock updated successfully.</p>
    <?php endif; ?>

    <form method="POST" action="adminupdatestock.php">
        <div class="admin-stock-grid">
            <?php foreach ($products as $product): ?>
            <div class="admin-product-card">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <div class="admin-size-list">
                    <?php if (empty($product['variants'])): ?>
                        <p class="admin-empty-note">No sizes yet for this product — add rows to <code>product_variants</code>.</p>
                    <?php endif; ?>
                    <?php foreach ($product['variants'] as $variant): ?>
                    <div class="admin-size-row">
                        <label class="admin-size-toggle">
                            <input type="checkbox"
                                   name="stock[<?php echo $variant['id']; ?>]"
                                   value="1"
                                   <?php echo $variant['in_stock'] ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($variant['size_label']); ?>
                        </label>
                        <input type="number"
                               class="admin-qty-input"
                               name="qty[<?php echo $variant['id']; ?>]"
                               value="<?php echo $variant['stock_qty']; ?>"
                               min="0"
                               aria-label="Quantity for <?php echo htmlspecialchars($product['name'] . ' ' . $variant['size_label']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:28px;">
            <button type="submit" class="btn btn-primary">Save Stock Changes</button>
        </div>
    </form>
</section>

</body>
</html>