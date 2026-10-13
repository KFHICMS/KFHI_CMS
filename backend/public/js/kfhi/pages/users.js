/* KFHI CMS – User Management page */
(function (K) {
  const ROLE_LABEL = {
    admin: 'Admin', child_officer: 'Child Officer',
    field_officer: 'Field Officer', survey_enumerator: 'Enumerator',
  };
  const roleLabel = r => ROLE_LABEL[r] || r;

  function rand(n) { return crypto.getRandomValues(new Uint32Array(1))[0] % n; }

  // 12-character temporary password: upper + lower + digit + symbol
  function genPassword() {
    const U = 'ABCDEFGHJKLMNPQRSTUVWXYZ', L = 'abcdefghijkmnopqrstuvwxyz', D = '23456789', S = '@#$%&*!?';
    const pick = set => set[rand(set.length)];
    const chars = [pick(U), pick(L), pick(D), pick(S)];
    while (chars.length < 12) chars.push(pick(U + L + D + S));
    for (let i = chars.length - 1; i > 0; i--) {
      const j = rand(i + 1);
      [chars[i], chars[j]] = [chars[j], chars[i]];
    }
    return chars.join('');
  }

  K.pages.users = {
    title: 'User Management',
    perm: 'manage_users',
    icon: 'fa-users',
    order: 3,

    async render(el, params) {
      const state = { q: '', role: '', status: '', sort: 'newest' };
      let users = [], roles = [], programs = [];

      el.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h1 class="h4 mb-0">User Management</h1>
          <button class="btn btn-primary" id="addUser"><i class="fa-solid fa-user-plus"></i> Add user</button>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-12 col-md-4"><input id="q" class="form-control" placeholder="Search name, username, email or phone"></div>
          <div class="col-6 col-md-2"><select id="fRole" class="form-select"><option value="">All roles</option></select></div>
          <div class="col-6 col-md-2"><select id="fStatus" class="form-select">
            <option value="">All statuses</option><option value="active">Active</option>
            <option value="inactive">Inactive</option><option value="must_change">Must change password</option></select></div>
          <div class="col-12 col-md-4"><select id="fSort" class="form-select">
            <option value="newest">Sort: Newest joined</option><option value="oldest">Sort: Oldest joined</option>
            <option value="name">Sort: Name A–Z</option></select></div>
        </div>
        <div id="list"></div>

        <!-- add / edit -->
        <div class="modal fade" id="userModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
          <form id="userForm" novalidate>
            <div class="modal-header"><h5 class="modal-title" id="userTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="userBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button class="btn btn-primary" id="userSave">Save</button></div>
          </form></div></div></div>

        <!-- reset password -->
        <div class="modal fade" id="pwModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
          <form id="pwForm" novalidate>
            <div class="modal-header"><h5 class="modal-title">Reset password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
              <p class="mb-2" id="pwWho"></p>
              <div id="pwErr"></div>
              <label class="form-label" for="pwNew">Temporary password</label>
              <div class="input-group mb-2"><input id="pwNew" class="form-control" autocomplete="off">
                <button type="button" class="btn btn-outline-secondary" id="pwGen">Generate</button>
                <button type="button" class="btn btn-outline-secondary" id="pwCopy">Copy</button></div>
              <div class="form-text">The user will be signed out everywhere and must choose a new password at next login. Share this one securely.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button class="btn btn-warning" id="pwSave">Reset password</button></div>
          </form></div></div></div>

        <!-- confirm -->
        <div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
          <div class="modal-header"><h5 class="modal-title" id="cTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body" id="cMsg"></div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="cOk">Confirm</button></div>
        </div></div></div>`;

      const $ = sel => el.querySelector(sel);
      const list = $('#list');
      const userModal = new bootstrap.Modal($('#userModal'));
      const pwModal = new bootstrap.Modal($('#pwModal'));
      const confirmModal = new bootstrap.Modal($('#confirmModal'));
      const me = K.session.user;

      // ---------- data ----------
      async function load() {
        list.innerHTML = K.spinner();
        try {
          users = await K.api('GET', '/users');
          draw();
        } catch (e) {
          list.innerHTML = K.alert(e.friendly);
        }
      }

      try {
        roles = await K.api('GET', '/roles');
        roles.forEach(r => $('#fRole').insertAdjacentHTML('beforeend',
          `<option value="${K.esc(r)}">${K.esc(roleLabel(r))}</option>`));
      } catch (e) { /* role filter stays empty */ }
      try { programs = await K.api('GET', '/programs'); } catch (e) { programs = []; }

      // ---------- list ----------
      function visible() {
        const q = state.q.toLowerCase();
        let rows = users.filter(u => {
          if (q && ![u.name, u.username, u.email, u.phone].some(v => (v || '').toLowerCase().includes(q))) return false;
          if (state.role && !(u.roles || []).includes(state.role)) return false;
          if (state.status === 'active' && !u.is_active) return false;
          if (state.status === 'inactive' && u.is_active) return false;
          if (state.status === 'must_change' && !u.must_change_password) return false;
          return true;
        });
        rows.sort((a, b) => {
          if (state.sort === 'name') return (a.name || '').localeCompare(b.name || '');
          const d = new Date(a.date_joined || 0) - new Date(b.date_joined || 0);
          return state.sort === 'oldest' ? d : -d;
        });
        return rows;
      }

      function avatar(u) {
        const initial = K.esc((u.name || '?').trim().charAt(0).toUpperCase());
        return `<div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
          style="width:40px;height:40px;background:#6c8f7a" aria-hidden="true">${initial}</div>`;
      }

      function draw() {
        const rows = visible();
        if (!rows.length) {
          list.innerHTML = '<div class="card"><div class="card-body text-muted">No users match your search.</div></div>';
          return;
        }
        list.innerHTML = `<div class="table-responsive card"><table class="table table-hover align-middle mb-0">
          <thead><tr><th>User</th><th class="d-none d-md-table-cell">Contact</th><th>Role</th><th>Status</th>
            <th class="d-none d-lg-table-cell">Joined</th><th class="text-end">Actions</th></tr></thead>
          <tbody>${rows.map(u => {
            const isMe = me && u.id === me.id;
            return `<tr>
              <td><div class="d-flex align-items-center gap-2">${avatar(u)}
                <div><div class="fw-semibold">${K.esc(u.name)}${isMe ? ' <span class="badge bg-light text-dark border">you</span>' : ''}</div>
                <div class="text-muted small">@${K.esc(u.username || '—')}</div></div></div></td>
              <td class="d-none d-md-table-cell"><div>${K.esc(u.email)}</div><div class="text-muted small">${K.esc(u.phone || '')}</div></td>
              <td>${(u.roles || []).map(r => `<span class="badge bg-primary-subtle text-primary-emphasis">${K.esc(roleLabel(r))}</span>`).join(' ')}</td>
              <td>${u.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}
                ${u.must_change_password ? '<div><span class="badge bg-warning text-dark mt-1">Must change password</span></div>' : ''}</td>
              <td class="d-none d-lg-table-cell">${K.esc(K.date(u.date_joined))}</td>
              <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-primary" data-act="edit" data-id="${K.esc(u.id)}">Edit</button>
                <button class="btn btn-sm btn-outline-warning" data-act="reset" data-id="${K.esc(u.id)}">Reset password</button>
                ${isMe ? '' : u.is_active
                  ? `<button class="btn btn-sm btn-outline-danger" data-act="deactivate" data-id="${K.esc(u.id)}">Deactivate</button>`
                  : `<button class="btn btn-sm btn-outline-success" data-act="activate" data-id="${K.esc(u.id)}">Activate</button>`}
              </td></tr>`;
          }).join('')}</tbody></table></div>
          <div class="text-muted small mt-2">${rows.length} of ${users.length} users</div>`;
      }

      list.addEventListener('click', e => {
        const btn = e.target.closest('button[data-act]');
        if (!btn) return;
        const u = users.find(x => String(x.id) === btn.dataset.id);
        if (!u) return;
        if (btn.dataset.act === 'edit') openForm(u);
        if (btn.dataset.act === 'reset') openReset(u);
        if (btn.dataset.act === 'deactivate') ask(`Deactivate ${u.name}?`,
          'They will be signed out immediately and cannot log in. Their past records are kept.', 'Deactivate',
          () => setActive(u, false));
        if (btn.dataset.act === 'activate') ask(`Activate ${u.name}?`, 'They will be able to log in again.', 'Activate',
          () => setActive(u, true));
      });

      // ---------- confirm helper ----------
      function ask(title, message, label, fn) {
        $('#cTitle').textContent = title;
        $('#cMsg').textContent = message;
        const ok = $('#cOk');
        ok.textContent = label;
        ok.onclick = async () => { ok.disabled = true; try { await fn(); confirmModal.hide(); } finally { ok.disabled = false; } };
        confirmModal.show();
      }

      async function setActive(u, active) {
        try {
          await K.api('PATCH', `/users/${u.id}/${active ? 'activate' : 'deactivate'}`);
          K.toast(active ? 'User activated.' : 'User deactivated.', 'success');
          await load();
        } catch (e) { K.toast(e.friendly, 'danger'); }
      }

      // ---------- add / edit ----------
      function openForm(u) {
        const editing = !!u;
        const isSelf = editing && me && u.id === me.id;
        $('#userTitle').textContent = editing ? 'Edit user' : 'Add user';
        const currentRole = editing ? (u.roles || [])[0] : (roles.includes('survey_enumerator') ? 'survey_enumerator' : roles[0]);
        const assigned = editing ? (u.program_ids || []).map(String) : [];

        $('#userBody').innerHTML = `
          <div id="userErr"></div>
          <div class="mb-3"><label class="form-label" for="uName">Full name *</label>
            <input id="uName" class="form-control" required value="${K.esc(editing ? u.name : '')}"></div>
          <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label" for="uUsername">Username</label>
              <input id="uUsername" class="form-control" autocomplete="off" value="${K.esc(editing ? (u.username || '') : '')}"
                placeholder="${editing ? '' : 'Leave empty to generate from email'}">
              <div class="form-text">Letters, numbers, dot, dash, underscore.</div></div>
            <div class="col-md-6"><label class="form-label" for="uPhone">Phone</label>
              <input id="uPhone" class="form-control" value="${K.esc(editing ? (u.phone || '') : '')}" placeholder="+94 77 123 4567"></div>
          </div>
          <div class="mb-3"><label class="form-label" for="uEmail">Email *</label>
            <input id="uEmail" type="email" class="form-control" required value="${K.esc(editing ? u.email : '')}"></div>
          <div class="mb-3"><label class="form-label" for="uRole">Role *</label>
            <select id="uRole" class="form-select" ${isSelf ? 'disabled' : ''}>
              ${roles.map(r => `<option value="${K.esc(r)}" ${r === currentRole ? 'selected' : ''}>${K.esc(roleLabel(r))}</option>`).join('')}</select>
            ${isSelf ? '<div class="form-text">You cannot change your own role.</div>' : ''}</div>
          <div class="mb-3"><label class="form-label">Assigned programs</label>
            <div class="border rounded p-2" style="max-height:140px;overflow:auto">
              ${programs.length ? programs.map(p => `<div class="form-check">
                <input class="form-check-input uProg" type="checkbox" id="prog${K.esc(p.id)}" value="${K.esc(p.id)}" ${assigned.includes(String(p.id)) ? 'checked' : ''}>
                <label class="form-check-label" for="prog${K.esc(p.id)}">${K.esc(p.name)}</label></div>`).join('')
                : '<span class="text-muted small">No programs yet.</span>'}</div>
            <div class="form-text">Field officers only see children in their assigned programs.</div></div>
          ${editing ? '' : `
          <div class="mb-3"><label class="form-label" for="uPw">Temporary password *</label>
            <div class="input-group"><input id="uPw" class="form-control" autocomplete="off">
              <button type="button" class="btn btn-outline-secondary" id="uGen">Generate</button>
              <button type="button" class="btn btn-outline-secondary" id="uCopy">Copy</button></div>
            <div class="form-text">8+ characters with upper and lower case letters and a number. Copy it before saving.</div></div>
          <div class="form-check mb-1"><input class="form-check-input" type="checkbox" id="uMust" checked>
            <label class="form-check-label" for="uMust">Require a password change at first login</label></div>`}`;

        if (!editing) {
          $('#uPw').value = genPassword();
          $('#uGen').onclick = () => { $('#uPw').value = genPassword(); };
          $('#uCopy').onclick = () => copy($('#uPw').value);
        }

        $('#userForm').onsubmit = async ev => {
          ev.preventDefault();
          const btn = $('#userSave');
          $('#userErr').innerHTML = '';

          const body = {
            name: $('#uName').value.trim(),
            email: $('#uEmail').value.trim(),
            phone: $('#uPhone').value.trim() || null,
            program_ids: [...el.querySelectorAll('.uProg:checked')].map(c => Number(c.value)),
          };
          const username = $('#uUsername').value.trim();
          if (username) body.username = username;
          if (!isSelf) body.role = $('#uRole').value;
          if (!editing) {
            body.password = $('#uPw').value;
            body.must_change_password = $('#uMust').checked;
            if (!body.phone) delete body.phone;
          }

          btn.disabled = true;
          try {
            if (editing) await K.api('PUT', `/users/${u.id}`, body);
            else await K.api('POST', '/users', body);
            userModal.hide();
            K.toast(editing ? 'User updated.' : 'User created.', 'success');
            await load();
          } catch (ex) {
            $('#userErr').innerHTML = K.alert(ex.friendly);
          } finally { btn.disabled = false; }
        };
        userModal.show();
      }

      // ---------- reset password ----------
      function openReset(u) {
        $('#pwWho').textContent = `Set a new temporary password for ${u.name}.`;
        $('#pwErr').innerHTML = '';
        $('#pwNew').value = genPassword();
        $('#pwGen').onclick = () => { $('#pwNew').value = genPassword(); };
        $('#pwCopy').onclick = () => copy($('#pwNew').value);
        $('#pwForm').onsubmit = async ev => {
          ev.preventDefault();
          const btn = $('#pwSave');
          btn.disabled = true;
          try {
            await K.api('PUT', `/users/${u.id}`, { password: $('#pwNew').value });
            pwModal.hide();
            K.toast('Password reset. The user must change it at next login.', 'success');
            await load();
          } catch (ex) {
            $('#pwErr').innerHTML = K.alert(ex.friendly);
          } finally { btn.disabled = false; }
        };
        pwModal.show();
      }

      function copy(text) {
        if (navigator.clipboard) {
          navigator.clipboard.writeText(text).then(() => K.toast('Copied.', 'success'), () => K.toast('Could not copy. Select and copy it manually.', 'warning'));
        } else {
          K.toast('Copy is not available here. Select and copy it manually.', 'warning');
        }
      }

      // ---------- filters ----------
      $('#addUser').addEventListener('click', () => openForm(null));
      $('#q').addEventListener('input', K.debounce(e => { state.q = e.target.value.trim(); draw(); }, 200));
      $('#fRole').addEventListener('change', e => { state.role = e.target.value; draw(); });
      $('#fStatus').addEventListener('change', e => { state.status = e.target.value; draw(); });
      $('#fSort').addEventListener('change', e => { state.sort = e.target.value; draw(); });

        load().then(() => { if (params && params[0] === 'new') openForm(null); });
    },
  };
})(window.KFHI);