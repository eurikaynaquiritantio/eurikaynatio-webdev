<?php
require __DIR__ . '/config.php';           // provides $pdo
require __DIR__ . '/product_functions.php';

$labels = category_labels();
$activeCategory = $_GET['category'] ?? 'all';
if ($activeCategory !== 'all' && !isset($labels[$activeCategory])) {
    $activeCategory = 'all';
}

$products = get_all_products($pdo, $activeCategory);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | TIO Perfume Collection</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
    <a href="index.php" class="brand-logo" aria-label="TIO Perfume Collection">
        <span class="logo-mark"><img src="img/logo-header.png" alt="TIO Perfume Collection"></span>
    </a>

    <nav class="main-nav">
        <a href="index.php">HOME</a>
        <a href="shop.php" class="active">SHOP</a>
        <a href="best-sellers.php">BEST SELLERS</a>
        <a href="about.php">ABOUT</a>
        <a href="contact.php">CONTACT</a>    </nav>

    <div class="header-icons">
        <div class="auth-area" id="headerAuthArea"></div>
        <a href="#" class="icon-btn bag" aria-label="Add to cart">
            <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
            <b>+</b>
        </a>
    </div>
</header>

<main>

    <section class="page-hero">
        <h1>Shop All Fragrances</h1>
        <p class="breadcrumb"><a href="index.php">Home</a><span> / </span>Shop</p>
    </section>

    <section class="section">
        <div class="shop-filters">
            <a href="shop.php" class="<?php echo $activeCategory === 'all' ? 'active' : ''; ?>">All</a>
            <?php foreach ($labels as $key => $label): ?>
                <a href="shop.php?category=<?php echo urlencode($key); ?>"
                   class="<?php echo $activeCategory === $key ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($label); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <p style="text-align:center;font-size:12px;">No fragrances found in this category yet.</p>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
            <article class="product-card<?php echo $product['is_out_of_stock'] ? ' out-of-stock' : ''; ?>">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?> perfume">
                </div>
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="product-price">&#8369;<?php echo number_format($product['price'], 0); ?></p>
                <?php if ($product['is_out_of_stock']): ?>
                    <span class="out-of-stock-badge">Out of Stock</span>
                <?php else: ?>
                    <p class="product-sizes"><?php echo htmlspecialchars(implode(', ', $product['available_sizes'])); ?></p>
                <?php endif; ?>
                <a href="#" class="product-btn<?php echo $product['is_out_of_stock'] ? ' disabled' : ''; ?>">
                    <?php echo $product['is_out_of_stock'] ? 'OUT OF STOCK' : 'VIEW PRODUCT'; ?>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

</main>

<footer class="footer">
    <div class="footer-brand">
        <div class="footer-mark"><img src="img/logo-footer.png" alt="TIO Perfume Collection"></div>
        <div class="socials" aria-label="Social media">
            <span>f</span>
            <span>✉</span>
            <span>◎</span>
        </div>
    </div>

    <div class="footer-column">
        <h3>QUICK LINKS</h3>
        <a href="index.php">Home</a>
        <a href="shop.php">Shop</a>
        <a href="best-sellers.php">Best Sellers</a>
        <a href="index.php#vip">Perfumes Club</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
    </div>

    <div class="footer-column">
        <h3>CUSTOMER CARE</h3>
        <a href="#">Privacy Policy</a>
        <a href="#">Terms &amp; Condition</a>
        <a href="#">Shipping &amp; Delivery</a>
        <a href="#">Returns &amp; Refunds</a>
        <a href="#">FAQ</a>
    </div>

    <div class="footer-column newsletter">
        <h3>NEWSLETTER</h3>
        <p>Subscribe to get special offers, fragrance, and exclusive updates.</p>
        <form class="newsletter-form" id="newsletterForm">
            <input type="email" name="email" class="newsletter-input" placeholder="Enter your email" required>
            <button type="submit" class="newsletter-btn">SUBSCRIBE</button>
        </form>
        <p class="newsletter-msg" id="newsletterMsg" role="status"></p>
    </div>

    <div class="copyright">
        © 2026 TIO PERFUME COLLECTION. All Rights Reserved.
    </div>
</footer>

<!-- Login modal -->
<div class="modal" id="loginModal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
        <button class="modal-close" id="loginModalClose" aria-label="Close" type="button">&times;</button>
        <h3 id="loginModalTitle">Log In</h3>
        <p id="loginModalMsg">Log in to your TIO Perfume account.</p>
        <form id="loginForm">
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required></label>
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
        <p class="modal-error" id="loginError" hidden></p>
        <p class="modal-switch">Don't have an account? <a href="#" id="switchToRegister">Register</a></p>
    </div>
</div>

<!-- Register modal -->
<div class="modal" id="registerModal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="registerModalTitle">
        <button class="modal-close" id="registerModalClose" aria-label="Close" type="button">&times;</button>
        <h3 id="registerModalTitle">Create an Account</h3>
        <p>Register to start shopping with TIO Perfume Collection.</p>
        <form id="registerForm">
            <label>Full Name<input type="text" name="name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required minlength="8"></label>
            <label>Confirm Password<input type="password" name="confirm" required minlength="8"></label>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p class="modal-error" id="registerError" hidden></p>
        <p class="modal-switch">Already have an account? <a href="#" id="switchToLogin">Log In</a></p>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>