/**
 * script.js — shared front-end behavior for TIO Perfume Collection.
 * Talks to the PHP AJAX endpoints (session.php, login.php,
 * register.php, logout.php, subscribe.php) and keeps the header auth
 * area / modals in sync — same pattern as the Torres Rental site's
 * script.js, trimmed to what this site actually has (no reservations
 * or contact form here).
 *
 * Include right before </body>:
 *   <script src="script.js"></script>
 */

let currentUser = null; // { name, email } or null

// ===================== Element refs =====================
const headerAuthArea = document.getElementById("headerAuthArea");

const loginModal = document.getElementById("loginModal");
const registerModal = document.getElementById("registerModal");

const loginForm = document.getElementById("loginForm");
const registerForm = document.getElementById("registerForm");
const newsletterForm = document.getElementById("newsletterForm");
const contactForm = document.getElementById("contactForm");

// ===================== Utilities =====================
function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str ?? "";
    return div.innerHTML;
}

function setMessage(el, text, hidden) {
    if (!el) return;
    el.textContent = text || "";
    el.hidden = hidden === undefined ? !text : hidden;
}

async function postForm(url, formData) {
    try {
        const res = await fetch(url, {
            method: "POST",
            body: formData,
            credentials: "same-origin",
        });
        return await res.json();
    } catch (err) {
        return { ok: false, error: "Something went wrong. Please try again." };
    }
}

// ===================== Modals =====================
function openModal(modal) {
    if (!modal) return;
    closeAllModals();
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
}

function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
}

function closeAllModals() {
    [loginModal, registerModal].forEach(closeModal);
}

function openAuthModal(mode) {
    openModal(mode === "register" ? registerModal : loginModal);
}

// Close on backdrop click or any .modal-close button.
document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("click", (e) => {
        if (e.target === modal) closeModal(modal);
    });
});
document.querySelectorAll(".modal-close").forEach((btn) => {
    btn.addEventListener("click", () => closeModal(btn.closest(".modal")));
});
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeAllModals();
});

const switchToRegister = document.getElementById("switchToRegister");
if (switchToRegister) {
    switchToRegister.addEventListener("click", (e) => {
        e.preventDefault();
        openAuthModal("register");
    });
}

const switchToLogin = document.getElementById("switchToLogin");
if (switchToLogin) {
    switchToLogin.addEventListener("click", (e) => {
        e.preventDefault();
        openAuthModal("login");
    });
}

// ===================== Auth area (header) =====================
function renderAuthArea() {
    if (!headerAuthArea) return;

    if (currentUser) {
        headerAuthArea.innerHTML = `
      <span class="user-greeting">Hi, ${escapeHtml(currentUser.name.split(" ")[0])}</span>
      <button type="button" class="btn btn-outline-purple btn-sm" id="logoutBtn">Log Out</button>
    `;
        document.getElementById("logoutBtn").addEventListener("click", handleLogout);
    } else {
        headerAuthArea.innerHTML = `
      <button type="button" class="btn btn-outline-purple btn-sm" id="loginTrigger">Log In</button>
      <button type="button" class="btn btn-primary btn-sm" id="registerTrigger">Register</button>
    `;
        document.getElementById("loginTrigger").addEventListener("click", () => openAuthModal("login"));
        document.getElementById("registerTrigger").addEventListener("click", () => openAuthModal("register"));
    }
}

// Ask the server whether we're logged in (session cookie), then draw
// the header accordingly. Runs once on page load. Requires session.php
// to exist on the server — see the note at the bottom of this file.
async function checkSession() {
    try {
        const res = await fetch("session.php", { credentials: "same-origin" });
        const data = await res.json();
        currentUser = data.ok && data.loggedIn ? data.user : null;
    } catch (err) {
        currentUser = null;
    }
    renderAuthArea();
}

async function handleLogout() {
    const confirmed = window.confirm("Are you sure you want to log out?");
    if (!confirmed) return;

    await postForm("logout.php", new FormData());
    currentUser = null;
    renderAuthArea();
}

// ===================== Login / Register forms =====================
if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        setMessage(document.getElementById("loginError"), "");

        const data = await postForm("login.php", new FormData(loginForm));

        if (data.ok) {
            currentUser = data.user;
            renderAuthArea();
            loginForm.reset();
            closeModal(loginModal);
        } else {
            setMessage(document.getElementById("loginError"), data.error || "Could not log in.");
        }
    });
}

if (registerForm) {
    registerForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        setMessage(document.getElementById("registerError"), "");

        const data = await postForm("register.php", new FormData(registerForm));

        if (data.ok) {
            currentUser = data.user;
            renderAuthArea();
            registerForm.reset();
            closeModal(registerModal);
        } else {
            setMessage(document.getElementById("registerError"), data.error || "Could not create your account.");
        }
    });
}

// ===================== Contact form =====================
if (contactForm) {
    contactForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const errorEl = document.getElementById("contactError");
        const successEl = document.getElementById("contactSuccess");
        setMessage(errorEl, "");
        setMessage(successEl, "");

        const data = await postForm("contact_function.php", new FormData(contactForm));

        if (data.ok) {
            setMessage(successEl, data.message || "Thanks! We'll be in touch soon.", false);
            contactForm.reset();
        } else {
            setMessage(errorEl, data.error || "Could not send your message.", false);
        }
    });
}

// ===================== Newsletter form =====================
if (newsletterForm) {
    newsletterForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const msgEl = document.getElementById("newsletterMsg");
        setMessage(msgEl, "");

        const data = await postForm("subscribe.php", new FormData(newsletterForm));

        setMessage(msgEl, data.message || data.error || "", false);
        if (data.ok) newsletterForm.reset();
    });
}

// ===================== Header shadow on scroll =====================
const siteHeader = document.querySelector(".site-header");
if (siteHeader) {
    const applyHeaderShadow = () => {
        siteHeader.style.boxShadow = window.scrollY > 8
            ? "0 4px 14px rgba(16, 15, 15, 0.08)"
            : "none";
    };
    applyHeaderShadow();
    window.addEventListener("scroll", applyHeaderShadow, { passive: true });
}

// ===================== Placeholder links =====================
// Nav items and the PREMIUM button still point to "#" until those
// pages exist — stop them from jumping to the top when clicked.
document.querySelectorAll('a[href="#"]').forEach((link) => {
    link.addEventListener("click", (e) => e.preventDefault());
});

// ===================== Init =====================
checkSession();

/**
 * NOTE — endpoints this file expects to exist on the server, matching
 * the tables already in schema.sql:
 *   session.php   — reports { ok, loggedIn, user } for the current session
 *   login.php     — POST email + password  -> logs into `users`
 *   register.php  — POST name/email/password/confirm -> inserts into `users`
 *   logout.php    — POST, destroys the session
 *   subscribe.php — POST email -> inserts into `subscribers`
 * None of these are included yet — this script degrades gracefully
 * (checkSession() just shows "Log In / Register", forms show a
 * generic error) until they're added.
 */