<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/cart_helpers.php';
$user = auth_require_api_role('user');
if ($_SERVER['REQUEST_METHOD']!=='POST') auth_json_error('Method not allowed.',405);
$name=trim($_POST['shipping_name']??'');
$phone=trim($_POST['shipping_phone']??'');
$address=trim($_POST['shipping_address']??'');
$method=trim($_POST['payment_method']??'cod');
$reference=trim($_POST['payment_reference']??'');
$bank=trim($_POST['bank_name']??'');
if($name===''||$phone===''||$address==='') auth_json_error('Please complete your shipping information.',422);
if(!in_array($method,['gcash','bank','cod'],true)) auth_json_error('Choose a valid payment method.',422);
if($method==='gcash'&&$reference==='') auth_json_error('Enter the GCash reference number.',422);
if($method==='bank'&&($bank===''||$reference==='')) auth_json_error('Enter the bank name and reference number.',422);
if($method==='cod'){ $reference=''; $bank=''; }
if($method==='gcash'){ $bank=''; }

$items=cart_items_for_user($pdo,(int)$user['id']);
if(!$items) auth_json_error('Your bag is empty.',422);
$t=cart_totals($items);$shippingFee=0.00;$total=$t['subtotal']+$shippingFee;
$paymentStatus=$method==='cod'?'unpaid':'for_verification';

try{
    $pdo->beginTransaction();
    // Lock every variant and verify stock again.
    $lock=$pdo->prepare('SELECT id,stock_qty,in_stock FROM product_variants WHERE id=? FOR UPDATE');
    foreach($items as $item){
        $lock->execute([(int)$item['variant_id']]);$v=$lock->fetch();
        if(!$v||!(int)$v['in_stock']||(int)$v['stock_qty']<(int)$item['quantity']) throw new RuntimeException($item['name'].' no longer has enough stock.');
    }
    $o=$pdo->prepare("INSERT INTO orders (user_id,order_status,payment_status,payment_method,payment_reference,bank_name,shipping_name,shipping_phone,shipping_address,subtotal,shipping_fee,total) VALUES (?,'pending',?,?,?,?,?,?,?,?,?,?)");
    $o->execute([(int)$user['id'],$paymentStatus,$method,$reference?:null,$bank?:null,$name,$phone,$address,$t['subtotal'],$shippingFee,$total]);
    $orderId=(int)$pdo->lastInsertId();
    $oi=$pdo->prepare('INSERT INTO order_items (order_id,product_id,variant_id,product_name,size_label,unit_price,quantity,line_total) VALUES (?,?,?,?,?,?,?,?)');
    $stock=$pdo->prepare('UPDATE product_variants SET in_stock=IF(stock_qty-? > 0,1,0), stock_qty=stock_qty-? WHERE id=?');
    foreach($items as $item){
        $oi->execute([$orderId,(int)$item['product_id'],(int)$item['variant_id'],$item['name'],$item['size_label'],$item['unit_price'],$item['quantity'],$item['line_total']]);
        $stock->execute([(int)$item['quantity'],(int)$item['quantity'],(int)$item['variant_id']]);
    }
    $cartId=cart_id_for_user($pdo,(int)$user['id'],false);
    if($cartId) $pdo->prepare('DELETE FROM cart_items WHERE cart_id=?')->execute([$cartId]);
    $pdo->commit();
    echo json_encode(['ok'=>true,'order_id'=>$orderId,'message'=>'Order placed successfully.']);
}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    auth_json_error($e instanceof RuntimeException?$e->getMessage():'Could not place your order. Please try again.',500);
}
