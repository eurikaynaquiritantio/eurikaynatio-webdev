<?php
header('Content-Type: application/json');
require __DIR__ . '/includes/auth.php';
$token = auth_request_context();
auth_destroy_context($token);
echo json_encode(['ok'=>true,'redirect'=>'login.php']);
