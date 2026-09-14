<?php
require __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Log In | TIO Perfume Collection</title>

    <link rel="stylesheet" href="style.css?v=login-final-2">
</head>

<body class="auth-page auth-page-tio">

<div class="auth-shell">

    <!-- ================= LEFT SIDE ================= -->
    <section class="auth-brand-panel">

        <div class="brand-content">

            <img
                src="./img/logo-header.png"
                alt="TIO Perfume Collection"
                class="auth-logo-large"
            >

            <div class="brand-text">

                <p class="auth-kicker">
                    TIO PERFUME COLLECTION
                </p>

                <h1>
                    A scent that<br>
                    defines you.
                </h1>

                <p class="auth-description">
                    One account gives customers and administrators
                    access to the correct dashboard.
                </p>

            </div>

        </div>

    </section>


    <!-- ================= RIGHT SIDE ================= -->
    <section class="auth-form-panel">

        <div class="auth-card">

            <img
                src="./img/logo-header.png"
                alt="TIO Perfume Collection"
                class="auth-logo-small"
            >

            <h2>
                Welcome Back
            </h2>

            <p class="auth-card-sub">
                Log in to continue.
            </p>


            <p
                class="modal-error"
                id="loginError"
                hidden
            ></p>


            <form
                id="pageLoginForm"
                autocomplete="off"
            >

                <div class="form-group">

                    <label for="identifier">
                        Email or Admin Username
                    </label>

                    <input
                        id="identifier"
                        type="text"
                        name="identifier"
                        placeholder="Enter email or username"
                        required
                        autocomplete="off"
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="new-password"
                    >

                </div>


                <button
                    type="submit"
                    class="login-button"
                    id="loginSubmit"
                >
                    Log In
                </button>

            </form>


            <p class="auth-bottom-text">

                Don't have an account?

                <a href="register.php">
                    Create account
                </a>

            </p>

        </div>

    </section>

</div>


<script>
(function () {

    const form = document.getElementById('pageLoginForm');
    const error = document.getElementById('loginError');
    const button = document.getElementById('loginSubmit');

    form.addEventListener('submit', async function (e) {

        e.preventDefault();

        error.hidden = true;

        button.disabled = true;
        button.textContent = 'Logging in...';

        try {

            const response = await fetch('login_action.php', {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (!data.ok) {
                throw new Error(
                    data.error || 'Could not log in.'
                );
            }

            sessionStorage.setItem(
                'tio_tab_ctx',
                data.ctx
            );

            window.location.replace(data.redirect);

        } catch (err) {

            error.textContent =
                err.message || 'Could not log in.';

            error.hidden = false;

            button.disabled = false;

            button.textContent = 'Log In';
        }

    });

})();
</script>

</body>
</html>