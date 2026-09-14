<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
auth_require_api_role('user');
$email=trim($_POST['email']??'');
if(!filter_var($email,FILTER_VALIDATE_EMAIL)) auth_json_error('Enter a valid email address.',422);
try{$stmt=$pdo->prepare('INSERT INTO subscribers (email) VALUES (?)');$stmt->execute([$email]);echo json_encode(['ok'=>true,'message'=>'Subscribed successfully.']);}
catch(PDOException $e){if($e->getCode()==='23000') echo json_encode(['ok'=>true,'message'=>'This email is already subscribed.']); else auth_json_error('Could not subscribe right now.',500);}
