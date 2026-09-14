<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/cart_helpers.php';
$user = auth_require_api_role('user');
$itemId = (int)($_POST['item_id'] ?? 0);
$qty = (int)($_POST['quantity'] ?? 1);
$stmt = $pdo->prepare("SELECT ci.id, pv.stock_qty FROM cart_items ci JOIN carts c ON c.id=ci.cart_id JOIN product_variants pv ON pv.id=ci.variant_id WHERE ci.id=? AND c.user_id=? LIMIT 1");
$stmt->execute([$itemId,(int)$user['id']]);
$row = $stmt->fetch();
if (!$row) auth_json_error('Cart item not found.',404);
if ($qty <= 0) {
    $pdo->prepare('DELETE FROM cart_items WHERE id=?')->execute([$itemId]);
} else {
    $qty = min($qty,(int)$row['stock_qty']);
    $pdo->prepare('UPDATE cart_items SET quantity=? WHERE id=?')->execute([$qty,$itemId]);
}
$items=cart_items_for_user($pdo,(int)$user['id']);$t=cart_totals($items);
echo json_encode(['ok'=>true,'count'=>$t['count'],'subtotal'=>$t['subtotal']]);
