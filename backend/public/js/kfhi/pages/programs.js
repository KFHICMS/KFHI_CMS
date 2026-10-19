/* KFHI CMS – Program Management page */
(function (K) {
  const STATUSES = ['active', 'completed', 'closed', 'cancelled', 'archived'];
  const COLOR = { active: 'success', completed: 'primary', closed: 'secondary', cancelled: 'warning', archived: 'dark' };
  const ROLE_LABEL = { admin: 'Admin', child_officer: 'Child Officer', field_officer: 'Field Officer', survey_enumerator: 'Enumerator' };
  const cap = s => (s ? s.charAt(0).toUpperCase() + s.slice(1) : '');
  const badge = s => `<span class="badge bg-${COLOR[s] || 'secondary'}">${K.esc(cap(s))}</span>`;
  const dates = p => (p.start_date || p.end_date)
    ? `${K.esc(K.date(p.start_date) || '…')} → ${K.esc(K.date(p.end_date) || '…')}` : '—';

  K.pages.programs = {
    title: 'Program Management',
    perm: 'manage_programs',
    icon: 'fa-diagram-project',
    order: 4,

    async render(el, params) {
      const state = { q: '', status: 'active' };
      let programs = [], users = [];

      el.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h1 class="h4 mb-0">Program Management</h1>
          <button class="btn btn-primary" id="addProgram"><i class="fa-solid fa-plus"></i> Add program</button>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-12 col-md-6"><input id="q" class="form-control" placeholder="Search programs"></div>
          <div class="col-12 col-md-3"><select id="fStatus" class="form-select">
            <option value="active">Active</option><option value="completed">Completed</option>
            <option value="closed">Closed</option><option value="cancelled">Cancelled</option>
            <option value="archived">Archived</option><option value="all">All</option></select></div>
        </div>
        <div id="list"></div>

        <div class="modal fade" id="progModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable modal-lg"><div class="modal-content">
          <div class="modal-header"><h5 class="modal-title" id="progTitle"></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body" id="progBody"></div>
          <div class="modal-footer justify-content-between" id="progFooter"></div>
        </div></div></div>

        <div class="modal fade" id="progFormModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-scrollable">
          <form class="modal-content" id="progForm" novalidate>
            <div class="modal-header"><h5 class="modal-title" id="progFormTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="progFormBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button class="btn btn-primary" id="progSave">Save</button></div>
          </form></div></div>

        <div class="modal fade" id="progConfirm" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
          <div class="modal-header"><h5 class="modal-title" id="pcTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body" id="pcMsg"></div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="pcOk">Confirm</button></div>
        </div></div></div>`;

      const $ = s => el.querySelector(s);
      const list = $('#list');
      const detail = new bootstrap.Modal($('#progModal'));
      const formModal = new bootstrap.Modal($('#progFormModal'));
      const confirmModal = new bootstrap.Modal($('#progConfirm'));

      function ask(title, message, label, fn) {
        $('#pcTitle').textContent = title;
        $('#pcMsg').textContent = message;
        const ok = $('#pcOk');
        ok.textContent = label;
        ok.onclick = async () => { ok.disabled = true; try { await fn(); confirmModal.hide(); } finally { ok.disabled = false; } };
        confirmModal.show();
      }

      async function load() {
        list.innerHTML = K.spinner();
        try {
          programs = await K.api('GET', '/programs');
          draw();
        } catch (e) { list.innerHTML = K.alert(e.friendly); }
      }

      function visible() {
        const q = state.q.toLowerCase();
        return programs.filter(p =>
          (state.status === 'all' || p.status === state.status) &&
          (!q || (p.name || '').toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q)));
      }

      function draw() {
        const rows = visible();
        if (!rows.length) {
          list.innerHTML = '<div class="card"><div class="card-body text-muted">No programs found. Try a different search or status.</div></div>';
          return;
        }
        list.innerHTML = `<div class="table-responsive card"><table class="table table-hover align-middle mb-0">
          <thead><tr><th>Program</th><th class="d-none d-md-table-cell">Dates</th><th>Status</th>
            <th class="d-none d-md-table-cell text-center">Children</th><th class="d-none d-md-table-cell text-center">Events</th><th class="text-end">Actions</th></tr></thead>
          <tbody>${rows.map(p => `<tr data-id="${K.esc(p.id)}" style="cursor:pointer">
            <td><div class="fw-semibold">${K.esc(p.name)}</div>
              <div class="text-muted small text-truncate" style="max-width:340px">${K.esc(p.description || '')}</div></td>
            <td class="d-none d-md-table-cell">${dates(p)}</td>
            <td>${badge(p.status)}</td>
            <td class="d-none d-md-table-cell text-center">${K.esc(p.children_count ?? '—')}</td>
            <td class="d-none d-md-table-cell text-center">${K.esc(p.events_count ?? '—')}</td>
            <td class="text-end text-nowrap">
              <button class="btn btn-sm btn-outline-primary" data-act="view">View</button>
              <button class="btn btn-sm btn-outline-secondary" data-act="edit">Edit</button></td></tr>`).join('')}
          </tbody></table></div>
          <div class="text-muted small mt-2">${rows.length} of ${programs.length} programs</div>`;
      }

      list.addEventListener('click', e => {
        const tr = e.target.closest('tr[data-id]');
        if (!tr) return;
        const p = programs.find(x => String(x.id) === tr.dataset.id);
        if (!p) return;
        if (e.target.closest('[data-act="edit"]')) openForm(p); else show(p);
      });

      // ---------- details ----------
      async function show(p) {
        $('#progTitle').textContent = p.name;
        const row = (k, v) => `<div class="row py-1 border-bottom"><div class="col-5 col-md-4 text-muted small">${K.esc(k)}</div><div class="col-7 col-md-8">${v}</div></div>`;

        let officers = '<span class="text-muted small">Loading…</span>';
        $('#progBody').innerHTML = `
          ${row('Status', badge(p.status))}
          ${row('Dates', dates(p))}
          ${row('Children in this program', K.esc(p.children_count ?? '—'))}
          ${row('Events', K.esc(p.events_count ?? '—'))}
          ${row('Description', K.esc(p.description || '—'))}
          <div class="mt-3"><div class="text-muted small mb-1">Assigned officers</div><div id="progOfficers">${officers}</div>
            <div class="form-text">Officers are assigned to programs in User Management.</div></div>`;

        $('#progFooter').innerHTML = `
          <div>${p.status !== 'archived' ? '<button class="btn btn-outline-danger" id="progArchive"><i class="fa-solid fa-box-archive"></i> Archive</button>' : ''}</div>
          <div class="d-flex gap-2"><button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-primary" id="progEdit"><i class="fa-solid fa-pen"></i> Edit</button></div>`;
        $('#progEdit').onclick = () => { detail.hide(); openForm(p); };
        const arch = $('#progArchive');
        if (arch) arch.onclick = () => ask(`Archive ${p.name}?`,
          'The program is hidden from active lists. Its children, events and history are kept.', 'Archive', async () => {
            try {
              await K.api('PATCH', `/programs/${p.id}/archive`);
              detail.hide(); K.toast('Program archived.', 'success'); load();
            } catch (e) { K.toast(e.friendly, 'danger'); }
          });
        detail.show();

        // officers come from the user list (administrators only)
        const box = $('#progOfficers');
        if (!K.can('manage_users')) { box.innerHTML = '<span class="text-muted small">Not available for your role.</span>'; return; }
        try {
          if (!users.length) users = await K.api('GET', '/users');
          const mine = users.filter(u => (u.program_ids || []).map(String).includes(String(p.id)) && u.is_active);
          box.innerHTML = mine.length
            ? '<ul class="mb-0">' + mine.map(u => `<li>${K.esc(u.name)} <span class="text-muted small">(${K.esc((u.roles || []).map(r => ROLE_LABEL[r] || r).join(', '))})</span></li>`).join('') + '</ul>'
            : '<span class="text-muted small">No officers assigned yet.</span>';
        } catch (e) { box.innerHTML = K.alert(e.friendly); }
      }

      // ---------- add / edit ----------
      function openForm(p) {
        const editing = !!p;
        const v = p || {};
        $('#progFormTitle').textContent = editing ? `Edit ${p.name}` : 'Add program';
        $('#progFormBody').innerHTML = `
          <div id="progErr"></div>
          <div class="mb-3"><label class="form-label" for="pName">Program name *</label>
            <input id="pName" class="form-control" value="${K.esc(v.name || '')}" maxlength="255">
            ${editing ? '<div class="form-text">Renaming also updates the program name on its children, so nobody loses access.</div>' : ''}</div>
          <div class="mb-3"><label class="form-label" for="pDesc">Description</label>
            <textarea id="pDesc" class="form-control" rows="3">${K.esc(v.description || '')}</textarea></div>
          <div class="row g-3 mb-3">
            <div class="col-6"><label class="form-label" for="pStart">Start date</label><input id="pStart" type="date" class="form-control" value="${K.esc(K.date(v.start_date))}"></div>
            <div class="col-6"><label class="form-label" for="pEnd">End date</label><input id="pEnd" type="date" class="form-control" value="${K.esc(K.date(v.end_date))}"></div>
          </div>
          ${editing ? `<div class="mb-1"><label class="form-label" for="pStatus">Status</label>
            <select id="pStatus" class="form-select">${STATUSES.map(s => `<option value="${s}" ${s === v.status ? 'selected' : ''}>${cap(s)}</option>`).join('')}</select></div>` : ''}`;

        $('#progForm').onsubmit = async ev => {
          ev.preventDefault();
          const btn = $('#progSave');
          $('#progErr').innerHTML = '';
          const body = {
            name: $('#pName').value.trim(),
            description: $('#pDesc').value.trim() || null,
            start_date: $('#pStart').value || null,
            end_date: $('#pEnd').value || null,
          };
          if (editing) body.status = $('#pStatus').value;
          btn.disabled = true;
          try {
            if (editing) await K.api('PUT', `/programs/${p.id}`, body);
            else await K.api('POST', '/programs', body);
            formModal.hide();
            K.toast(editing ? 'Program updated.' : 'Program created.', 'success');
            await load();
          } catch (ex) { $('#progErr').innerHTML = K.alert(ex.friendly); }
          finally { btn.disabled = false; }
        };
        formModal.show();
      }

      $('#addProgram').addEventListener('click', () => openForm(null));
      $('#q').addEventListener('input', K.debounce(e => { state.q = e.target.value.trim(); draw(); }, 200));
      $('#fStatus').addEventListener('change', e => { state.status = e.target.value; draw(); });

      await load();
      if (params && params[0] === 'new') openForm(null);
    },
  };
})(window.KFHI);