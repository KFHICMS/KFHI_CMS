/* KFHI CMS – application shell: login, role-aware menu, hash router, idle logout */
(function (K) {
  const root = document.getElementById('app');
  const IDLE_MS = K.IDLE_MINUTES * 60 * 1000;
  const ACTIVITY = ['mousemove', 'keydown', 'click', 'touchstart', 'scroll'];
  const HOME = K.base.replace(/\/api\/?$/, '/');          // public home page URL
  const LOGO = HOME + 'images/kfhi-logo.png';             // put your logo at public/images/kfhi-logo.png
  const ICONS = { dashboard: 'fa-house', children: 'fa-child-reaching' };   // fallback icons per page key
  let idleTimer = null;

  // ---------- logo (falls back to text if the file is missing) ----------
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

  // ---------- shell ----------
  function visiblePages() {
    return Object.entries(K.pages)
      .filter(([, p]) => !p.perm || K.can(p.perm))
      .sort((a, b) => (a[1].order || 99) - (b[1].order || 99));
  }

  function renderShell() {
    const u = K.session.user;
    root.innerHTML = `
      <nav class="navbar navbar-expand-md navbar-dark kfhi-nav">
        <a class="navbar-brand m-0" href="#/dashboard">${logoImg()}</a>
        <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
          <ul class="navbar-nav ms-auto me-md-3 mt-3 mt-md-0">
            ${visiblePages().map(([key, p]) => `
              <li class="nav-item"><a class="nav-link" data-page="${K.esc(key)}" href="#/${K.esc(key)}">
                <i class="fa-solid ${K.esc(p.icon || ICONS[key] || 'fa-circle')}"></i><span>${K.esc(p.title)}</span></a></li>`).join('')}
          </ul>
          <span class="user-chip"><i class="fa-solid fa-user"></i> ${K.esc(u.name)}</span>
          <button class="btn-logout" id="logoutBtn"><i class="fa-solid fa-right-from-bracket"></i> Log out</button>
        </div>
      </nav>
      <main id="view"></main>`;
    logoFallback(root);
    root.querySelector('#logoutBtn').addEventListener('click', logout);
  }

  // ---------- router ----------
  async function route() {
    const view = document.getElementById('view');
    if (!view || !K.session.user) return;

    const [name, ...params] = (location.hash.replace(/^#\/?/, '') || 'dashboard').split('/');
    const page = K.pages[name];

    root.querySelectorAll('.nav-link').forEach(a => a.classList.toggle('active', a.dataset.page === name));

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