<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/validation.php';
auth_require_api_role('user');
if($_SERVER['REQUEST_METHOD']!=='POST')auth_json_error('Method not allowed.',405);
$name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$subject=trim($_POST['subject']??'');$message=trim($_POST['message']??'');$errors=[];
if(($e=validate_name($name))!=='')$errors[]=$e;if(($e=validate_email($email))!=='')$errors[]=$e;if($subject==='')$errors[]='Subject is required.';if($message==='')$errors[]='Message is required.';
if($errors)auth_json_error(implode(' ',$errors),422);
$stmt=$pdo->prepare('INSERT INTO messages (name,email,subject,message) VALUES (?,?,?,?)');$stmt->execute([$name,$email,$subject,$message]);echo json_encode(['ok'=>true,'message'=>'Thanks, '.$name.'! We received your message.']);
