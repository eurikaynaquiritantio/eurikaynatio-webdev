<?php require __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account | TIO Perfume Collection</title>
<link rel="stylesheet" href="style.css?v=rebuilt2">
</head>
<body class="auth-page auth-page-tio">
<div class="auth-shell">
    <div class="auth-brand-panel">
        <img src="img/logo-header.png" alt="TIO Perfume Collection" class="auth-logo-large">
        <p class="auth-kicker">TIO PERFUME COLLECTION</p>
        <h1>Create your account.</h1>
        <p>Register once, then shop, check out, and review your order history.</p>
    </div>
    <div class="auth-form-panel">
        <div class="auth-card auth-card-wide">
            <h2>Create Account</h2>
            <p class="auth-card-sub">Register as a customer.</p>
            <p class="modal-error" id="registerError" hidden></p>
            <form id="pageRegisterForm" autocomplete="off">
                <label>Full Name<input type="text" name="name" placeholder="Enter your full name" required></label>
                <label>Email<input type="email" name="email" placeholder="Enter your email" required autocomplete="off"></label>
                <label>Password<input type="password" name="password" placeholder="At least 8 characters" minlength="8" required autocomplete="new-password"></label>
                <label>Confirm Password<input type="password" name="confirm" placeholder="Re-enter your password" minlength="8" required autocomplete="new-password"></label>
                <button type="submit" class="btn btn-primary btn-block" id="registerSubmit">Register</button>
            </form>
            <p class="auth-bottom-text">Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>
</div>
<script>
document.getElementById('pageRegisterForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const error = document.getElementById('registerError');
    const button = document.getElementById('registerSubmit');
    error.hidden = true; button.disabled = true; button.textContent='Creating account...';
    try {
        const r = await fetch('register_action.php', {method:'POST', body:new FormData(this), credentials:'same-origin'});
        const data = await r.json();
        if (!data.ok) throw new Error(data.error || 'Could not create account.');
        sessionStorage.setItem('tio_tab_ctx', data.ctx);
        location.replace('index.php');
    } catch(err) {
        error.textContent = err.message; error.hidden = false; button.disabled=false; button.textContent='Register';
    }
});
</script>
</body>
</html>
