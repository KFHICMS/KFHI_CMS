/* KFHI CMS – application shell: login, permission-based sidebar, hash router, idle logout */
(function (K) {
  const root = document.getElementById('app');
  const IDLE_MS = K.IDLE_MINUTES * 60 * 1000;
  const ACTIVITY = ['mousemove', 'keydown', 'click', 'touchstart', 'scroll'];
  const HOME = K.base.replace(/\/api\/?$/, '/');          // public home page URL
  const LOGO = HOME + 'images/kfhi-logo.png';             // put your logo at public/images/kfhi-logo.png
  const ICONS = { dashboard: 'fa-table-columns', children: 'fa-child-reaching' };   // fallback icons per page key
  let idleTimer = null;

  // ---------- small helpers ----------
  const logoImg = () => `<img class="brand-logo" src="${K.esc(LOGO)}" alt="KFHI">`;
  function logoFallback(scope) {
    scope.querySelectorAll('img.brand-logo').forEach(img =>
      img.addEventListener('error', () => {
        const s = document.createElement('span');
        s.className = 'brand-text';
        s.textContent = 'KFHI';
        img.replaceWith(s);
      }, { once: true }));
  }
  const initials = name => String(name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
  const pretty = r => { const s = String(r).replace(/_/g, ' '); return s.charAt(0).toUpperCase() + s.slice(1); };

  // ---------- idle auto-logout ----------
  function resetIdle() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => { K.toast('Signed out after inactivity.', 'warning'); logout(); }, IDLE_MS);
  }
  function startIdleWatch() {
    ACTIVITY.forEach(ev => window.addEventListener(ev, resetIdle, { passive: true }));
    resetIdle();
  }
  function stopIdleWatch() {
    ACTIVITY.forEach(ev => window.removeEventListener(ev, resetIdle));
    clearTimeout(idleTimer);
  }

  // ---------- login ----------
  function renderLogin(notice) {
    root.innerHTML = `
      <div class="container"><div class="card login-card"><div class="card-body p-4">
        <div class="login-brand">${logoImg()}</div>
        <h1 class="h4 fw-bold text-center mb-1">Welcome back</h1>
        <p class="text-muted text-center mb-4">Sign in with your KFHI account</p>
        ${notice ? K.alert(notice, 'warning') : ''}
        <div id="loginErr"></div>
        <form id="loginForm" novalidate>
          <div class="mb-3"><label class="form-label">Email</label>
            <input id="email" type="email" class="form-control" autocomplete="username" required autofocus></div>
          <div class="mb-3"><label class="form-label">Password</label>
            <input id="password" type="password" class="form-control" autocomplete="current-password" required></div>
          <button class="btn btn-primary w-100 py-2" id="loginBtn">Sign in</button>
        </form>
        <a class="back-home" href="${K.esc(HOME)}"><i class="fa-solid fa-arrow-left"></i> Back to home</a>
      </div></div></div>`;
    logoFallback(root);

    root.querySelector('#loginForm').addEventListener('submit', async e => {
      e.preventDefault();
      const btn = root.querySelector('#loginBtn');
      const err = root.querySelector('#loginErr');
      err.innerHTML = '';
      btn.disabled = true; btn.textContent = 'Signing in…';
      try {
        const res = await K.api('POST', '/login', {
          email: root.querySelector('#email').value.trim(),
          password: root.querySelector('#password').value,
        });
        K.setToken(res.token);
        K.session.user = res.user;
        enterApp();
      } catch (ex) {
        err.innerHTML = K.alert(ex.friendly);
        btn.disabled = false; btn.textContent = 'Sign in';
      }
    });
  }

  // ---------- shell: sidebar + top bar ----------
  /** Pages the current user may open (permission-based) – this is what builds the sidebar. */
  function visiblePages() {
    return Object.entries(K.pages)
      .filter(([, p]) => !p.perm || K.can(p.perm))
      .sort((a, b) => (a[1].order || 99) - (b[1].order || 99));
  }

  function renderShell() {
    const u = K.session.user;
    const roleLabel = (u.roles || []).map(pretty).join(', ');

    let lastGroup = null;
    const links = visiblePages().map(([key, p]) => {
      let head = '';
      if (p.group && p.group !== lastGroup) { head = `<div class="sb-group">${K.esc(p.group)}</div>`; }
      if (p.group) lastGroup = p.group;
      return `${head}<a class="sb-link" data-page="${K.esc(key)}" href="#/${K.esc(key)}">
        <i class="fa-solid ${K.esc(p.icon || ICONS[key] || 'fa-circle')}"></i><span>${K.esc(p.title)}</span></a>`;
    }).join('');

    root.innerHTML = `
      <div class="app-shell">
        <aside class="sidebar" id="sidebar">
          <div class="sb-brand">
            <a href="#/dashboard">${logoImg()}</a>
            <div><div class="sb-brand-title">KFHI CMS</div><div class="sb-brand-sub">Child ID &amp; Records</div></div>
          </div>
          <nav class="sb-nav">${links}</nav>
          <div class="sb-foot">Access to every record is logged. Only authorized KFHI staff may view child information.</div>
        </aside>
        <div class="sb-backdrop" id="sbBackdrop"></div>

        <div class="app-main">
          <header class="topbar">
            <button class="sb-toggle" id="sbToggle" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">
              <strong>KFHI Child Digital ID &amp; Information Management System</strong>
              <small>Secure QR-based identification &amp; records platform</small>
            </div>
            <div class="topbar-user">
              <div class="u-meta"><div class="u-name">${K.esc(u.name)}</div><div class="u-role">${K.esc(roleLabel)}</div></div>
              <div class="avatar">${K.esc(initials(u.name))}</div>
              <button class="btn-icon" id="logoutBtn" title="Log out" aria-label="Log out"><i class="fa-solid fa-right-from-bracket"></i></button>
            </div>
          </header>
          <main id="view"></main>
        </div>
      </div>`;
    logoFallback(root);

    // mobile: open / close the sidebar
    const sidebar = root.querySelector('#sidebar');
    const backdrop = root.querySelector('#sbBackdrop');
    const closeSidebar = () => { sidebar.classList.remove('open'); backdrop.classList.remove('show'); };
    root.querySelector('#sbToggle').addEventListener('click', () => {
      sidebar.classList.toggle('open'); backdrop.classList.toggle('show');
    });
    backdrop.addEventListener('click', closeSidebar);
    sidebar.addEventListener('click', e => { if (e.target.closest('.sb-link')) closeSidebar(); });

    root.querySelector('#logoutBtn').addEventListener('click', logout);
  }

  // ---------- router ----------
  async function route() {
    const view = document.getElementById('view');
    if (!view || !K.session.user) return;

    const [name, ...params] = (location.hash.replace(/^#\/?/, '') || 'dashboard').split('/');
    const page = K.pages[name];

    root.querySelectorAll('.sb-link').forEach(a => a.classList.toggle('active', a.dataset.page === name));

    if (!page || (page.perm && !K.can(page.perm))) {
      view.innerHTML = K.alert('Page not found or you do not have access to it.', 'warning');
      return;
    }
    view.innerHTML = '';
    try {
      await page.render(view, params);
    } catch (e) {
      view.innerHTML = K.alert(e.friendly || e.message);
    }
  }

  // ---------- lifecycle ----------
  function enterApp() {
    if (K.session.user.must_change_password) {
      stopIdleWatch();
      K.forcePasswordChange(root, () => {
        K.session.user.must_change_password = false;
        enterApp();
      }, logout);
      return;
    }

    renderShell();
    startIdleWatch();
    if (!location.hash) { location.hash = '#/dashboard'; }   // triggers route() via hashchange
    else { route(); }
  }

  async function logout() {
    stopIdleWatch();
    try { await K.api('POST', '/logout'); } catch (e) { /* token may already be invalid */ }
    K.clearToken();
    K.session.user = null;
    renderLogin();
  }

  async function start() {
    if (!K.getToken()) { renderLogin(); return; }
    try {
      K.session.user = await K.api('GET', '/me');
      enterApp();
    } catch (e) {
      K.clearToken();
      renderLogin();
    }
  }

  window.addEventListener('hashchange', route);
  window.addEventListener('kfhi:unauthorized', () => {
    stopIdleWatch();
    K.session.user = null;
    renderLogin('Your session has expired. Please sign in again.');
  });

  start();
})(window.KFHI);