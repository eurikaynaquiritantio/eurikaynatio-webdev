(() => {
    const requiredRole = document.documentElement.dataset.requiredRole || '';
    const loginPage = document.documentElement.dataset.loginPage || 'login.php';
    const ctx = sessionStorage.getItem('tio_tab_ctx') || '';

    const go = (url) => window.location.replace(url);
    if (!ctx) {
        go(loginPage);
        return;
    }

    fetch('auth_context.php', {
        headers: { 'X-Tab-Context': ctx },
        credentials: 'same-origin',
        cache: 'no-store'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.loggedIn) {
            sessionStorage.removeItem('tio_tab_ctx');
            go(loginPage);
            return;
        }
        if (requiredRole && data.role !== requiredRole) {
            go(data.role === 'admin' ? 'admin.php' : 'index.php');
            return;
        }
        window.TIO_AUTH = data;
        document.documentElement.classList.remove('auth-pending');
        document.dispatchEvent(new CustomEvent('tio-auth-ready', { detail: data }));
    })
    .catch(() => go(loginPage));
})();
