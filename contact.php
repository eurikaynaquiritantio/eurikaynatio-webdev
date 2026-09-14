<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | TIO Perfume Collection</title>
    <link rel="stylesheet" href="style.css?v=rebuilt2">
    <script>document.documentElement.classList.add('auth-pending');document.documentElement.dataset.requiredRole='user';</script>
    <script src="tab-guard.js?v=rebuilt2"></script>
</head>
<body>

<header class="site-header">
    <a href="index.php" class="brand-logo" aria-label="TIO Perfume Collection">
        <span class="logo-mark"><img src="img/logo-header.png" alt="TIO Perfume Collection"></span>
    </a>

    <nav class="main-nav">
        <a href="index.php">HOME</a>
        <a href="shop.php">SHOP</a>
        <a href="best-sellers.php">BEST SELLERS</a>
        <a href="about.php">ABOUT</a>
        <a href="orders.php">MY ORDERS</a>
        <a href="contact.php" class="active">CONTACT</a>    </nav>

    <div class="header-icons">
        <div class="auth-area" id="headerAuthArea"></div>
        <a href="cart.php" class="icon-btn bag" aria-label="Shopping bag">
            <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
            <b id="cartCount">0</b>
        </a>
    </div>
</header>

<main>

    <section class="page-hero">
        <h1>Get In Touch</h1>
        <p class="breadcrumb">Questions about an order, a fragrance, or anything else? We're here to help.</p>
    </section>

    <section class="section">
        <div class="contact-wrap">
            <div class="contact-form-card">
                <h2>Send Us a Message</h2>
                <p class="auth-card-sub">We typically respond within one business day.</p>

                <p class="modal-error" id="contactError" hidden></p>
                <p class="modal-success" id="contactSuccess" hidden></p>

                <form id="contactForm">
                    <label>Full Name<input type="text" name="name" required></label>
                    <label>Email<input type="email" name="email" required></label>
                    <label>Subject<input type="text" name="subject" required></label>
                    <label>Message<textarea name="message" rows="5" required></textarea></label>
                    <button type="submit" class="btn btn-primary btn-block">Send Message</button>
                </form>
            </div>

            <div class="contact-info-card">
                <h2>Reach Us Directly</h2>
                <p class="auth-card-sub">Prefer email or a call? Here's how to find us.</p>
                <ul class="contact-list">
                    <li><span class="label">Email Us:</span><span>hello@tioperfume.com</span></li>
                    <li><span class="label">Call Us:</span><span>+63 917 000 1234</span></li>
                    <li><span class="label">Store Hours:</span><span>Mon–Sat, 10am–7pm</span></li>
                </ul>
            </div>
        </div>
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
        <a href="VIPTIOperfumeclub.php">Perfumes Club</a>
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

<script src="script.js?v=rebuilt2"></script>
</body>
</html>