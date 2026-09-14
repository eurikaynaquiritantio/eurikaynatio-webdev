<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/cart_helpers.php';
$user = auth_require_api_role('user');
$itemId=(int)($_POST['item_id']??0);
$stmt=$pdo->prepare('DELETE ci FROM cart_items ci JOIN carts c ON c.id=ci.cart_id WHERE ci.id=? AND c.user_id=?');
$stmt->execute([$itemId,(int)$user['id']]);
echo json_encode(['ok'=>true]);
