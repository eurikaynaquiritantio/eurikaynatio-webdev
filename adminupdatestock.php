<?php
/**
 * adminupdatestock.php — processes admin.php's checkbox + quantity
 * form. Requires an admin session. For every variant that exists in
 * `product_variants`, sets in_stock = 1 if its checkbox was submitted
 * (checked), or 0 if it was omitted (unchecked — HTML forms don't
 * submit unchecked boxes), and updates stock_qty from the number input.
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: adminlogin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

require __DIR__ . '/config.php'; // provides $pdo

$submittedStock = $_POST['stock'] ?? []; // [variant_id] => "1" when checked
$submittedQty   = $_POST['qty']   ?? []; // [variant_id] => quantity string

// Every variant that currently exists — anything not in $submittedStock
// was left unchecked and should be marked out of stock.
$allVariantIds = $pdo->query("SELECT id FROM product_variants")->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare(
    "UPDATE product_variants SET in_stock = :in_stock, stock_qty = :qty WHERE id = :id"
);

foreach ($allVariantIds as $variantId) {
    $inStock = isset($submittedStock[$variantId]) ? 1 : 0;
    $qty = isset($submittedQty[$variantId]) ? max(0, (int)$submittedQty[$variantId]) : 0;
    $stmt->execute([
        ":in_stock" => $inStock,
        ":qty"      => $qty,
        ":id"       => $variantId,
    ]);
}

header('Location: admin.php?saved=1');
exit;