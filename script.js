/** Shared customer-side behavior for the rebuilt TIO Perfume Collection. */
let currentUser = null;

function escapeHtml(value) {
    const d = document.createElement('div');
    d.textContent = value ?? '';
    return d.innerHTML;
}
function tabContext() { return sessionStorage.getItem('tio_tab_ctx') || ''; }
async function apiFetch(url, options = {}) {
    const headers = new Headers(options.headers || {});
    headers.set('X-Tab-Context', tabContext());
    const response = await fetch(url, {...options, headers, credentials:'same-origin'});
    let data = {};
    try { data = await response.json(); } catch (_) {}
    if (response.status === 401 || response.status === 403) {
        if (data.redirect) location.replace(data.redirect);
    }
    return data;
}
function setMessage(el, text, hidden) {
    if (!el) return;
    el.textContent = text || '';
    el.hidden = hidden === undefined ? !text : hidden;
}

async function loadAuth() {
    const ctx = tabContext();
    if (!ctx) { location.replace('login.php'); return; }
    const data = await apiFetch('auth_context.php', {cache:'no-store'});
    if (!data.loggedIn) { sessionStorage.removeItem('tio_tab_ctx'); location.replace('login.php'); return; }
    if (data.role !== 'user') { location.replace('admin.php'); return; }
    currentUser = data.user;
    renderAuthArea();
    refreshCartCount();
}

function renderAuthArea() {
    const area = document.getElementById('headerAuthArea');
    if (!area || !currentUser) return;
    const first = (currentUser.name || 'User').split(/\s+/)[0];
    area.innerHTML = `<span class="user-greeting">Hi, ${escapeHtml(first)}</span><span class="auth-divider">|</span><button type="button" class="header-logout" id="logoutBtn">Log Out</button>`;
    document.getElementById('logoutBtn')?.addEventListener('click', handleLogout);
}

async function handleLogout() {
    if (!confirm('Are you sure you want to log out?')) return;
    await apiFetch('logout.php', {method:'POST'});
    sessionStorage.removeItem('tio_tab_ctx');
    location.replace('login.php');
}

async function refreshCartCount() {
    const badge = document.getElementById('cartCount');
    if (!badge) return;
    const data = await apiFetch('cart_api.php', {cache:'no-store'});
    if (data.ok) badge.textContent = data.count || 0;
}

// Add-to-bag controls on shop/bestseller cards.
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;
    e.preventDefault();
    const card = btn.closest('.product-card');
    const select = card?.querySelector('.variant-select');
    const variantId = select?.value || btn.dataset.variantId || '';
    if (!variantId) { alert('Choose a size first.'); return; }
    const fd = new FormData(); fd.append('variant_id', variantId); fd.append('quantity','1');
    btn.disabled = true;
    const old = btn.textContent;
    btn.textContent = 'ADDING...';
    const data = await apiFetch('cart_add.php', {method:'POST', body:fd});
    btn.disabled = false;
    btn.textContent = data.ok ? 'ADDED' : old;
    if (!data.ok) alert(data.error || 'Could not add this item.');
    if (data.ok) { const badge=document.getElementById('cartCount'); if (badge) badge.textContent=data.count || 0; setTimeout(()=>btn.textContent=old,900); }
});

// Contact form.
const contactForm = document.getElementById('contactForm');
if (contactForm) contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const err=document.getElementById('contactError'), ok=document.getElementById('contactSuccess');
    setMessage(err,''); setMessage(ok,'');
    const data=await apiFetch('contactfunction.php',{method:'POST',body:new FormData(contactForm)});
    if(data.ok){setMessage(ok,data.message||'Message sent.',false);contactForm.reset();}
    else setMessage(err,data.error||'Could not send your message.',false);
});

// Newsletter.
const newsletterForm=document.getElementById('newsletterForm');
if(newsletterForm) newsletterForm.addEventListener('submit',async(e)=>{
    e.preventDefault(); const msg=document.getElementById('newsletterMsg'); setMessage(msg,'');
    const data=await apiFetch('subscribe.php',{method:'POST',body:new FormData(newsletterForm)});
    setMessage(msg,data.message||data.error||'',false); if(data.ok)newsletterForm.reset();
});

// VIP signup.
const vipForm=document.getElementById('vipForm');
if(vipForm) vipForm.addEventListener('submit',async(e)=>{
    e.preventDefault(); const err=document.getElementById('vipError'),ok=document.getElementById('vipSuccess');setMessage(err,'');setMessage(ok,'');
    const data=await apiFetch('VIPsignup.php',{method:'POST',body:new FormData(vipForm)});
    if(data.ok){setMessage(ok,data.message||'Welcome to the club!',false);vipForm.reset();}else setMessage(err,data.error||'Could not complete signup.',false);
});

// Header shadow.
const siteHeader=document.querySelector('.site-header');
if(siteHeader){const fn=()=>siteHeader.style.boxShadow=scrollY>8?'0 4px 14px rgba(16,15,15,.08)':'none';fn();addEventListener('scroll',fn,{passive:true});}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', loadAuth); else loadAuth();
