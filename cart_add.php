<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/cart_helpers.php';
$user = auth_require_api_role('user');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') auth_json_error('Method not allowed.', 405);
$variantId = (int)($_POST['variant_id'] ?? 0);
$qty = max(1, (int)($_POST['quantity'] ?? 1));
$stmt = $pdo->prepare("SELECT id, stock_qty, in_stock FROM product_variants WHERE id = ? LIMIT 1");
$stmt->execute([$variantId]);
$variant = $stmt->fetch();
if (!$variant || !(int)$variant['in_stock'] || (int)$variant['stock_qty'] < 1) auth_json_error('This item is out of stock.', 422);
$cartId = cart_id_for_user($pdo, (int)$user['id'], true);
$check = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND variant_id = ? LIMIT 1');
$check->execute([$cartId,$variantId]);
$row = $check->fetch();
$newQty = min((int)$variant['stock_qty'], ($row ? (int)$row['quantity'] : 0) + $qty);
if ($row) {
    $u = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
    $u->execute([$newQty,(int)$row['id']]);
} else {
    $i = $pdo->prepare('INSERT INTO cart_items (cart_id, variant_id, quantity) VALUES (?, ?, ?)');
    $i->execute([$cartId,$variantId,$newQty]);
}
$items = cart_items_for_user($pdo, (int)$user['id']);
$t = cart_totals($items);
echo json_encode(['ok'=>true,'message'=>'Added to bag.','count'=>$t['count']]);
