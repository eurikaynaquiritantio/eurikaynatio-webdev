<!DOCTYPE html>
<html lang="en" data-required-role="user" class="auth-pending">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Shopping Bag | TIO Perfume Collection</title>
<link rel="stylesheet" href="style.css?v=rebuilt2"><script src="tab-guard.js?v=rebuilt2"></script>
</head><body>
<header class="site-header">
<a href="index.php" class="brand-logo"><span class="logo-mark"><img src="img/logo-header.png" alt="TIO Perfume Collection"></span></a>
<nav class="main-nav"><a href="index.php">HOME</a><a href="shop.php">SHOP</a><a href="best-sellers.php">BEST SELLERS</a><a href="orders.php">MY ORDERS</a><a href="contact.php">CONTACT</a></nav>
<div class="header-icons"><div class="auth-area" id="headerAuthArea"></div><a href="cart.php" class="icon-btn bag active" aria-label="Shopping bag"><svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0 0 20 4H5.21l-.94-2H1z"/></svg><b id="cartCount">0</b></a></div>
</header>
<main><section class="page-hero"><h1>Your Shopping Bag</h1><p class="breadcrumb">Review your items before checkout.</p></section>
<section class="section"><div class="cart-layout"><div id="cartItems" class="cart-items"><p class="loading-note">Loading your bag...</p></div><aside class="cart-summary"><h2>Order Summary</h2><div class="summary-line"><span>Subtotal</span><strong id="cartSubtotal">₱0.00</strong></div><div class="summary-line"><span>Shipping</span><span>Free</span></div><div class="summary-line total"><span>Total</span><strong id="cartTotal">₱0.00</strong></div><a href="checkout.php" class="btn btn-primary btn-block" id="checkoutLink">Proceed to Checkout</a><a href="shop.php" class="continue-link">Continue Shopping</a></aside></div></section></main>
<script src="script.js?v=rebuilt2"></script>
<script>
const peso=n=>'₱'+Number(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
async function loadCartPage(){
 const data=await apiFetch('cart_api.php',{cache:'no-store'}); if(!data.ok)return;
 const wrap=document.getElementById('cartItems'); document.getElementById('cartSubtotal').textContent=peso(data.subtotal);document.getElementById('cartTotal').textContent=peso(data.subtotal);
 const checkout=document.getElementById('checkoutLink'); checkout.classList.toggle('disabled',!data.items.length);
 if(!data.items.length){wrap.innerHTML='<div class="empty-state"><h2>Your bag is empty</h2><p>Add a fragrance from the shop to get started.</p><a class="btn btn-primary" href="shop.php">Shop Fragrances</a></div>';return;}
 wrap.innerHTML=data.items.map(i=>`<article class="cart-row"><img src="${escapeHtml(i.image)}" alt="${escapeHtml(i.name)}"><div class="cart-item-info"><h3>${escapeHtml(i.name)}</h3><p>${escapeHtml(i.size_label)} · ${peso(i.unit_price)}</p><div class="qty-control"><button type="button" data-dec="${i.cart_item_id}">−</button><input type="number" min="1" max="${i.stock_qty}" value="${i.quantity}" data-qty="${i.cart_item_id}"><button type="button" data-inc="${i.cart_item_id}">+</button></div></div><strong>${peso(i.line_total)}</strong><button type="button" class="remove-cart" data-remove="${i.cart_item_id}">Remove</button></article>`).join('');
}
async function updateItem(id,qty){const fd=new FormData();fd.append('item_id',id);fd.append('quantity',qty);await apiFetch('cart_update.php',{method:'POST',body:fd});await loadCartPage();await refreshCartCount();}
document.addEventListener('click',async e=>{let id;if(id=e.target.dataset.remove){const fd=new FormData();fd.append('item_id',id);await apiFetch('cart_remove.php',{method:'POST',body:fd});await loadCartPage();await refreshCartCount();} if(id=e.target.dataset.inc){const input=document.querySelector(`[data-qty="${id}"]`);updateItem(id,Math.min(Number(input.max),Number(input.value)+1));} if(id=e.target.dataset.dec){const input=document.querySelector(`[data-qty="${id}"]`);updateItem(id,Math.max(1,Number(input.value)-1));}});
document.addEventListener('change',e=>{if(e.target.dataset.qty)updateItem(e.target.dataset.qty,Math.max(1,Number(e.target.value)||1));});
document.addEventListener('tio-auth-ready',loadCartPage); setTimeout(()=>{if(window.TIO_AUTH)loadCartPage();},200);
</script></body></html>
