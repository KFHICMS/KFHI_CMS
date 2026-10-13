(function (K) {
  const cardHtml = (label, value, icon) => `
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card stat h-100">
        <div class="icon"><i class="fa-solid ${icon}"></i></div>
        <div class="num">${K.esc(value)}</div>
        <div class="label">${K.esc(label)}</div>
      </div>
    </div>`;

  // A shortcut only appears when its page exists and the user is allowed to use it
  const ACTIONS = [
    { label: 'Add Child',      icon: 'fa-child-reaching',  page: 'children', hash: '#/children/new', perm: 'create_child' },
    { label: 'Check Program',  icon: 'fa-diagram-project', page: 'programs', hash: '#/programs',     perm: 'manage_programs' },
    { label: 'Check Activity', icon: 'fa-calendar-check',  page: 'events',   hash: '#/events' },
    { label: 'Add Officers',   icon: 'fa-user-plus',       page: 'users',    hash: '#/users/new',    perm: 'manage_users' },
  ];

  const activityCol = (label, value, color) => `
    <div class="col-4">
      <div class="display-6 fw-bold text-${color}">${K.esc(value)}</div>
      <div class="text-muted small">${K.esc(label)}</div>
    </div>`;

  K.pages.dashboard = {
    title: 'Dashboard',
    icon: 'fa-table-columns',
    order: 1,

    async render(el) {
      const u = K.session.user;
      const orgWide = K.can('view_reports');

      el.innerHTML = `
        <div class="page-head mb-4">
          <h1 class="h3">Welcome back, ${K.esc(u.name)}</h1>
          <div class="text-muted">${orgWide
            ? "Here is what's happening across KFHI's child programs."
            : 'Here is your workspace. Use the menu on the left to get started.'}</div>
        </div>
        <div id="stats">${K.spinner()}</div>`;

      const stats = el.querySelector('#stats');
      try {
        if (orgWide) {
          const d = await K.api('GET', '/reports/dashboard');
          const c = d.cards, a = d.activities;

          const actions = ACTIONS
            .filter(x => K.pages[x.page] && (!x.perm || K.can(x.perm)))
            .map(x => `<a class="btn btn-outline-success text-start" href="${K.esc(x.hash)}">
              <i class="fa-solid ${x.icon} me-2"></i>${K.esc(x.label)}</a>`).join('');

          stats.innerHTML = `
            <div class="row g-4">
              ${cardHtml('Total children', c.children, 'fa-child-reaching')}
              ${cardHtml('Programs / projects', c.programs, 'fa-diagram-project')}
              ${cardHtml('Events / activities', c.events, 'fa-calendar-check')}
              ${cardHtml('Officers / users', c.users, 'fa-users')}
            </div>
            <div class="text-muted small mt-2">Children and users count active records only.</div>

            <div class="row g-4 mt-1">
              <div class="col-lg-7">
                <div class="card h-100"><div class="card-body">
                  <h2 class="h5 mb-3">Activity summary</h2>
                  <div class="row text-center">
                    ${activityCol('To do', a.to_do, 'secondary')}
                    ${activityCol('In progress', a.in_progress, 'primary')}
                    ${activityCol('Completed', a.completed, 'success')}
                  </div>
                  <hr>
                  <div class="small text-muted">Pending actions: <b>${K.esc(d.pending.follow_ups)}</b> follow-up(s) waiting</div>
                </div></div>
              </div>
              <div class="col-lg-5">
                <div class="card h-100"><div class="card-body">
                  <h2 class="h5 mb-3">Quick actions</h2>
                  <div class="d-grid gap-2">${actions || '<span class="text-muted small">No shortcuts available yet.</span>'}</div>
                </div></div>
              </div>
            </div>`;
        } else {
          // field officer / enumerator → only what their role can reach
          let cards = '';
          if (K.can('view_children')) {
            const r = await K.api('GET', '/children?per_page=1&status=active');
            cards += cardHtml('Children in my programs', r.total ?? 0, 'fa-child-reaching');
          }
          const events = await K.api('GET', '/events');
          cards += cardHtml('Active events', events.filter(e => e.status === 'active').length, 'fa-calendar-check');
          stats.innerHTML = `<div class="row g-4">${cards}</div>`;
        }
      } catch (e) {
        stats.innerHTML = K.alert(e.friendly);
      }
    },
  };
})(window.KFHI);