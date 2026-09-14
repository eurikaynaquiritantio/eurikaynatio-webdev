<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
$user = auth_require_api_role('user');
$stmt=$pdo->prepare("SELECT id, order_status, payment_status, payment_method, payment_reference, bank_name, shipping_name, shipping_phone, shipping_address, subtotal, shipping_fee, total, placed_at FROM orders WHERE user_id=? ORDER BY placed_at DESC");
$stmt->execute([(int)$user['id']]);
$orders=$stmt->fetchAll();
$itemsStmt=$pdo->prepare('SELECT product_name,size_label,unit_price,quantity,line_total FROM order_items WHERE order_id=? ORDER BY id');
foreach($orders as &$order){$itemsStmt->execute([(int)$order['id']]);$order['items']=$itemsStmt->fetchAll();}
unset($order);
echo json_encode(['ok'=>true,'orders'=>$orders]);
