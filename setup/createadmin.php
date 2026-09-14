<?php
require __DIR__ . '/../config.php';
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid name and email.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password_hash) VALUES (?, ?, 'admin', ?)");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $message = 'Admin account created. You can now use the main login page.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Create Admin | TIO Perfume Collection</title><link rel="stylesheet" href="../style.css?v=rebuilt2"></head>
<body class="auth-page auth-page-tio">
<div class="auth-page-wrap"><div class="auth-card auth-card-wide">
<img src="../img/logo-header.png" class="auth-logo-small" alt="TIO Perfume Collection">
<h1>Create Admin</h1><p class="auth-card-sub">Create an administrator account for the dashboard.</p>
<?php if ($error): ?><p class="modal-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<?php if ($message): ?><p class="modal-success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
<form method="post" autocomplete="off">
<label>Name<input type="text" name="name" required></label>
<label>Email<input type="email" name="email" required autocomplete="off"></label>
<label>Password<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
<label>Confirm Password<input type="password" name="confirm" minlength="8" required autocomplete="new-password"></label>
<button type="submit" class="btn btn-primary btn-block">Create Admin</button>
</form>
<p class="auth-bottom-text"><a href="../login.php">Go to main login</a></p>
</div></div></body></html>
