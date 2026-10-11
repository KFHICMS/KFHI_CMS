(function (K) {
  const LABELS = [
    ['total_children',     'Registered children', 'fa-child-reaching'],
    ['active_programs',    'Active programs',     'fa-diagram-project'],
    ['active_events',      'Active events',       'fa-calendar-check'],
    ['total_users',        'System users',        'fa-users'],
    ['total_attendance',   'Attendance records',  'fa-clipboard-check'],
    ['total_benefits',     'Benefits given',      'fa-gift'],
    ['pending_follow_ups', 'Pending follow-ups',  'fa-list-check'],
  ];

  K.pages.dashboard = {
    title: 'Dashboard',
    icon: 'fa-house',
    order: 1,
    async render(el) {
      const u = K.session.user;
      el.innerHTML = `
        <div class="card welcome">
          <span class="badge-soft"><i class="fa-solid fa-user-shield"></i> ${u.roles.map(r => K.esc(r.replace(/_/g, ' '))).join(', ')}</span>
          <h1 class="h3">Welcome, ${K.esc(u.name)}</h1>
          <div class="text-muted">KFHI Child Digital Identification and Information Management System</div>
        </div>
        <div id="stats"></div>`;

      const stats = el.querySelector('#stats');
      if (!K.can('view_reports')) {
        stats.innerHTML = `<div class="card"><div class="card-body">
          Use the menu to open the sections available to your role.</div></div>`;
        return;
      }

      stats.innerHTML = K.spinner();
      try {
        const s = await K.api('GET', '/reports/summary');
        stats.innerHTML = '<div class="row g-4">' + LABELS.map(([key, label, icon]) => `
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat h-100">
              <div class="icon"><i class="fa-solid ${icon}"></i></div>
              <div class="num">${K.esc(s[key] ?? 0)}</div>
              <div class="label">${K.esc(label)}</div>
            </div>
          </div>`).join('') + '</div>';
      } catch (e) {
        stats.innerHTML = K.alert(e.friendly);
      }
    },
  };
})(window.KFHI);