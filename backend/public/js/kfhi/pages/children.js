(function (K) {
  // ---------- details view ----------
  const FIELDS = [
    ['child_code', 'Child code'], ['full_name', 'Child name (English)'], ['name_korean', 'Child name (Korean)'],
    ['alias', 'Alias'], ['date_of_birth', 'Birthday'], ['age', 'Age'], ['gender', 'Gender'],
    ['religion', 'Religion'], ['address', 'Address'], ['area', 'Area'], ['gnd', 'GND / Grama Niladhari Division'],
    ['office_code', 'Office code'], ['office_name', 'Office name'], ['service_state', 'Service state'],
    ['sponsor_state', 'Sponsor state'], ['guardian_type', 'Guardian type'], ['caregiver', 'Caregiver'],
    ['program', 'Program'], ['registration_date', 'Registration date'], ['school', 'School'],
    ['curriculum', 'Curriculum'], ['grade', 'Grade'], ['education_status', 'Education status'],
    ['favorite_subject', 'Favorite subject'], ['pass_fail', 'Pass / Fail'], ['dream', 'Dream'],
    ['dream_description', 'Dream description'], ['favorite_activity', 'Favorite activity'],
    ['participation_details', 'Participation'],
  ];
  const SENSITIVE = [
    ['health', 'Health'], ['medical_info', 'Health description'],
    ['disability_type', 'Disability type'], ['disability_description', 'Disability description'],
    ['special_requirements', 'Special requirements'], ['emergency_contacts', 'Emergency contacts'],
  ];

  // ---------- add / edit form ----------
  // [key, label, type, options]
  const SECTIONS = [
    { title: 'Identity', fields: [
      ['child_code', 'Child code', 'text', { req: true, ph: 'e.g. LKA005001' }],
      ['full_name', 'Child name (English)', 'text', { req: true }],
      ['name_korean', 'Child name (Korean)', 'text'],
      ['alias', 'Alias', 'text'],
      ['date_of_birth', 'Birthday', 'date'],
      ['_age', 'Age (calculated from birthday)', 'age'],
      ['gender', 'Gender', 'select', { options: ['Male', 'Female', 'Other'] }],
      ['religion', 'Religion', 'text'],
      ['address', 'Address', 'textarea', { rows: 2 }],
      ['area', 'Area', 'text'],
      ['gnd', 'GND / Grama Niladhari Division', 'text'],
    ]},
    { title: 'Office and service', fields: [
      ['office_code', 'Office code', 'text'],
      ['office_name', 'Office name', 'text'],
      ['service_state', 'Service state', 'text', { suggest: ['Active', 'Inactive', 'Completed', 'Dropped'] }],
      ['sponsor_state', 'Sponsor state', 'text', { suggest: ['Sponsored', 'Waiting', 'Not sponsored'] }],
      ['guardian_type', 'Guardian type', 'text', { suggest: ['Parent', 'Grandparent', 'Relative', 'Guardian'] }],
      ['caregiver', 'Caregiver', 'text'],
      ['program', 'Program', 'program'],
      ['registration_date', 'Registration date', 'date'],
    ]},
    { title: 'Education', fields: [
      ['school', 'School', 'text'],
      ['curriculum', 'Curriculum', 'text'],
      ['grade', 'Grade', 'text'],
      ['education_status', 'Education status', 'text', { suggest: ['Studying', 'Completed', 'Dropped out', 'Not enrolled'] }],
      ['favorite_subject', 'Favorite subject', 'text'],
      ['pass_fail', 'Pass / Fail', 'select', { options: ['Pass', 'Fail'] }],
    ]},
    { title: 'Dream and interests', fields: [
      ['dream', 'Dream', 'text'],
      ['favorite_activity', 'Favorite activity', 'text'],
      ['dream_description', 'Dream description', 'textarea', { rows: 2 }],
      ['participation_details', 'Participation details', 'textarea', { rows: 2 }],
    ]},
    { title: 'Health and disability (confidential)', perm: 'view_child_health', fields: [
      ['health', 'Health', 'text'],
      ['medical_info', 'Health description', 'textarea', { rows: 2 }],
      ['disability_type', 'Disability type', 'text'],
      ['disability_description', 'Disability description', 'textarea', { rows: 2 }],
      ['special_requirements', 'Special requirements', 'textarea', { rows: 2 }],
    ]},
    { title: 'Emergency contacts (confidential)', perm: 'view_child_emergency', fields: [
      ['emergency_contacts', 'Emergency contacts', 'textarea', { rows: 2 }],
    ]},
  ];

  function ageOf(dob) {
    if (!dob) return '';
    const d = new Date(dob + 'T00:00:00');
    if (isNaN(d)) return '';
    const n = new Date();
    let a = n.getFullYear() - d.getFullYear();
    const m = n.getMonth() - d.getMonth();
    if (m < 0 || (m === 0 && n.getDate() < d.getDate())) a--;
    return a < 0 ? '' : a;
  }

  K.pages.children = {
    title: 'Child Management',
    perm: 'view_children',
    order: 3,

    async render(el, params) {
      const state = { q: '', program: '', status: 'active', page: 1, gender: '', area: '', office_code: '', service_state: '', sponsor_state: '' };
      let programs = [];

      el.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h1 class="h4 mb-0">Child Management</h1>
          ${K.can('create_child') ? '<button class="btn btn-primary" id="addChild"><i class="fa-solid fa-plus"></i> Add child</button>' : ''}
        </div>
        <div class="row g-2 mb-2">
          <div class="col-12 col-md-5"><input id="q" class="form-control" placeholder="Search by name or child code"></div>
          <div class="col-6 col-md-3"><select id="program" class="form-select"><option value="">All programs</option></select></div>
          ${K.can('archive_child') ? `<div class="col-6 col-md-2"><select id="status" class="form-select">
            <option value="active">Active</option><option value="archived">Archived</option><option value="all">All</option></select></div>` : ''}
          <div class="col-12 col-md-2"><button class="btn btn-outline-secondary w-100" data-bs-toggle="collapse" data-bs-target="#moreFilters">
            <i class="fa-solid fa-filter"></i> More filters</button></div>
        </div>
        <div class="collapse mb-3" id="moreFilters"><div class="card card-body"><div class="row g-2">
          <div class="col-6 col-md-2"><select id="fGender" class="form-select"><option value="">Any gender</option>
            <option>Male</option><option>Female</option><option>Other</option></select></div>
          <div class="col-6 col-md-2"><input id="fArea" class="form-control" placeholder="Area"></div>
          <div class="col-6 col-md-3"><input id="fOffice" class="form-control" placeholder="Office code"></div>
          <div class="col-6 col-md-2"><input id="fService" class="form-control" placeholder="Service state"></div>
          <div class="col-12 col-md-3"><input id="fSponsor" class="form-control" placeholder="Sponsor state"></div>
        </div></div></div>
        <div id="list"></div>
        <div id="pager" class="d-flex justify-content-between align-items-center mt-3"></div>

        <!-- details -->
        <div class="modal fade" id="childModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable modal-lg"><div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Child details</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body" id="childBody"></div>
          <div class="modal-footer justify-content-between" id="childFooter"></div>
        </div></div></div>

        <!-- add / edit -->
                <div class="modal fade" id="formModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-scrollable modal-lg">
          <form class="modal-content" id="childForm" novalidate>
            <div class="modal-header"><h5 class="modal-title" id="formTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="formBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <button class="btn btn-primary" id="formSave">Save</button></div>
          </form></div></div>

        <!-- confirm -->
        <div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
          <div class="modal-header"><h5 class="modal-title" id="cTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body" id="cMsg"></div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="cOk">Confirm</button></div>
        </div></div></div>`;

      const $ = sel => el.querySelector(sel);
      const list = $('#list');
      const pager = $('#pager');
      const modalEl = $('#childModal');
      const detailModal = new bootstrap.Modal(modalEl);
      const formModal = new bootstrap.Modal($('#formModal'));
      const confirmModal = new bootstrap.Modal($('#confirmModal'));
      let photoUrl = null;
      modalEl.addEventListener('hidden.bs.modal', () => { if (photoUrl) { URL.revokeObjectURL(photoUrl); photoUrl = null; } });

      try {
        programs = await K.api('GET', '/programs');
        programs.forEach(p => $('#program').insertAdjacentHTML('beforeend',
          `<option value="${K.esc(p.name)}">${K.esc(p.name)}</option>`));
      } catch (e) { programs = []; }

      // ---------- list ----------
      async function load() {
        list.innerHTML = K.spinner();
        const p = new URLSearchParams({ page: state.page, per_page: 15, status: state.status });
        ['q', 'program', 'gender', 'area', 'office_code', 'service_state', 'sponsor_state']
          .forEach(k => { if (state[k]) p.set(k, state[k]); });

        try {
          const res = await K.api('GET', '/children?' + p);
          if (!res.data.length) {
            list.innerHTML = '<div class="card"><div class="card-body text-muted">No children found. Try a different search or filter.</div></div>';
            pager.innerHTML = '';
            return;
          }
          list.innerHTML = `<div class="table-responsive card"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Code</th><th>Name</th><th class="d-none d-md-table-cell">Age</th><th class="d-none d-md-table-cell">Gender</th>
              <th class="d-none d-lg-table-cell">Area</th><th class="d-none d-lg-table-cell">Program</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>${res.data.map(c => `<tr data-id="${K.esc(c.id)}" style="cursor:pointer">
              <td class="fw-semibold">${K.esc(c.child_code)}</td>
              <td>${K.esc(c.full_name)}${c.name_korean ? `<div class="text-muted small">${K.esc(c.name_korean)}</div>` : ''}</td>
              <td class="d-none d-md-table-cell">${K.esc(c.age ?? '')}</td>
              <td class="d-none d-md-table-cell">${K.esc(c.gender || '')}</td>
              <td class="d-none d-lg-table-cell">${K.esc(c.area || '')}</td>
              <td class="d-none d-lg-table-cell">${K.esc(c.program || '')}</td>
              <td>${c.status === 'archived' ? '<span class="badge bg-secondary">Archived</span>' : '<span class="badge bg-success">Active</span>'}</td>
              <td class="text-end text-nowrap">
                <button class="btn btn-sm btn-outline-primary" data-act="view">View</button>
                ${K.can('edit_child') && c.status !== 'archived' ? '<button class="btn btn-sm btn-outline-secondary" data-act="edit">Edit</button>' : ''}
              </td></tr>`).join('')}</tbody></table></div>`;

          list.querySelectorAll('tbody tr').forEach(tr => tr.addEventListener('click', e => {
            const c = res.data.find(x => String(x.id) === tr.dataset.id);
            const act = e.target.closest('button[data-act]');
            if (act && act.dataset.act === 'edit') openForm(c); else show(c);
          }));

          pager.innerHTML = `<span class="text-muted small">Page ${res.current_page} of ${res.last_page} · ${res.total} children</span>
            <div class="btn-group"><button class="btn btn-outline-secondary btn-sm" id="prev" ${res.current_page <= 1 ? 'disabled' : ''}>Prev</button>
            <button class="btn btn-outline-secondary btn-sm" id="next" ${res.current_page >= res.last_page ? 'disabled' : ''}>Next</button></div>`;
          pager.querySelector('#prev').onclick = () => { state.page--; load(); };
          pager.querySelector('#next').onclick = () => { state.page++; load(); };
        } catch (e) {
          list.innerHTML = K.alert(e.friendly);
          pager.innerHTML = '';
        }
      }

      // ---------- confirm helper ----------
      function ask(title, message, label, fn, cls = 'btn-danger') {
        $('#cTitle').textContent = title;
        $('#cMsg').textContent = message;
        const ok = $('#cOk');
        ok.textContent = label;
        ok.className = 'btn ' + cls;
        ok.onclick = async () => { ok.disabled = true; try { await fn(); confirmModal.hide(); } finally { ok.disabled = false; } };
        confirmModal.show();
      }

      // ---------- details ----------
      async function show(listRow) {
        // load the full record from the server: it is fresh, and the view is recorded in the audit log
        let c;
        try { c = await K.api('GET', `/children/${listRow.id}`); }
        catch (e) { K.toast(e.friendly, 'danger'); return; }
        const row = (k, v) => (v !== null && v !== undefined && v !== '')
          ? `<div class="row py-1 border-bottom"><div class="col-5 col-md-4 text-muted small">${K.esc(k)}</div><div class="col-7 col-md-8">${K.esc(v)}</div></div>` : '';
        const sens = (k, v) => v ? `<div class="py-2 field-sensitive"><div class="text-muted small">${K.esc(k)}</div><div>${K.esc(v)}</div></div>` : '';
        const guardians = (c.guardians || []).map(g =>
          `<li>${K.esc(g.name)} ${g.relationship ? '(' + K.esc(g.relationship) + ')' : ''} ${g.contact_number ? '· ' + K.esc(g.contact_number) : ''}</li>`).join('');
        const archived = c.status === 'archived';

        $('#childBody').innerHTML = `
          <div class="d-flex gap-3 mb-3 align-items-center">
            <img id="photo" class="child-photo" alt="">
            <div>
              <div class="h5 mb-0">${K.esc(c.full_name)}</div>
              <div class="text-muted">${K.esc(c.child_code)} ${archived ? '<span class="badge bg-secondary ms-1">archived</span>' : ''}</div>
              ${K.can('edit_child') && !archived ? `<button class="btn btn-sm btn-outline-secondary mt-2" id="photoBtn"><i class="fa-solid fa-camera"></i> ${c.has_photo ? 'Change' : 'Add'} photo</button>
                <input type="file" id="photoFile" class="d-none" accept=".jpg,.jpeg,.png,image/jpeg,image/png">` : ''}
            </div>
          </div>
          ${FIELDS.map(([k, label]) => row(label, k === 'date_of_birth' || k === 'registration_date' ? K.date(c[k]) : c[k])).join('')}
          ${guardians ? `<div class="mt-3"><div class="text-muted small">Guardians</div><ul class="mb-0">${guardians}</ul></div>` : ''}
          <div class="mt-3">${SENSITIVE.map(([k, label]) => sens(label, c[k])).join('')}</div>`;

        const catBox = document.createElement('div');
        catBox.className = 'mt-3';
        $('#childBody').appendChild(catBox);
        K.mountCategoryEditor(catBox, c);

        $('#childFooter').innerHTML = `
          <div>${K.can('archive_child') ? (archived
            ? '<button class="btn btn-outline-success" id="restoreBtn"><i class="fa-solid fa-box-open"></i> Restore</button>'
            : '<button class="btn btn-outline-danger" id="archiveBtn"><i class="fa-solid fa-box-archive"></i> Archive</button>') : ''}</div>
          <div class="d-flex gap-2"><button class="btn btn-light" data-bs-dismiss="modal">Close</button>
          ${K.can('generate_qr') && !archived ? '<button class="btn btn-outline-primary" id="idBtn"><i class="fa-solid fa-id-card"></i> ID card</button>' : ''}
          ${K.can('edit_child') && !archived ? '<button class="btn btn-primary" id="editBtn"><i class="fa-solid fa-pen"></i> Edit</button>' : ''}
          </div>`;

        const editBtn = $('#editBtn');
        if (editBtn) editBtn.onclick = () => { detailModal.hide(); openForm(c); };
        const idBtn = $('#idBtn');
        if (idBtn) idBtn.onclick = () => { detailModal.hide(); K.openIdCard(c); };

        const arch = $('#archiveBtn');
        if (arch) arch.onclick = () => ask(`Archive ${c.full_name}?`,
          'The child is hidden from the active list and their QR codes stop working. The record is kept and is not deleted.',
          'Archive', async () => {
            try {
              await K.api('PATCH', `/children/${c.id}/archive`);

              detailModal.hide();
              K.toast('Child archived.', 'success');
              load();
            } catch (e) { K.toast(e.friendly, 'danger'); }
          });

        const rest = $('#restoreBtn');
        if (rest) rest.onclick = () => ask(`Restore ${c.full_name}?`,
          'The child returns to the active list and can be edited again. Their old QR code stays cancelled: generate a new one from the ID card screen.',
          'Restore', async () => {
            try {
              await K.api('PATCH', `/children/${c.id}/restore`);
              detailModal.hide();
              K.toast('Child restored.', 'success');
              load();
            } catch (e) { K.toast(e.friendly, 'danger'); }
          }, 'btn-success');

        detailModal.show();

        const loadPhoto = async () => {
          try {
            if (photoUrl) URL.revokeObjectURL(photoUrl);
            photoUrl = URL.createObjectURL(await K.apiBlob(`/children/${c.id}/photo`));
            $('#photo').src = photoUrl;
          } catch (e) { /* leave placeholder */ }
        };
        if (c.has_photo) loadPhoto();

        const pb = $('#photoBtn'), pf = $('#photoFile');
        if (pb) {
          pb.onclick = () => pf.click();
          pf.onchange = async () => {
            const file = pf.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) { K.toast('Photo must be 2 MB or smaller.', 'warning'); pf.value = ''; return; }
            const fd = new FormData();
            fd.append('photo', file);
            pb.disabled = true;
            try {
              await K.api('POST', `/children/${c.id}/photo`, fd);
              c.has_photo = true;
              K.toast('Photo saved.', 'success');
              await loadPhoto();
            } catch (e) { K.toast(e.friendly, 'danger'); }
            finally { pb.disabled = false; pf.value = ''; }
          };
        }
      }

      // ---------- add / edit form ----------
      function fieldHtml([key, label, type, o = {}], values, editing) {
        const id = 'f_' + key;
        const v = values[key] ?? '';
        const wide = type === 'textarea' ? 'col-12' : 'col-md-6';
        const lab = `<label class="form-label" for="${id}">${K.esc(label)}${o.req ? ' *' : ''}</label>`;
        let ctl;

        if (key === 'child_code' && editing) {
          ctl = `<input id="${id}" class="form-control" value="${K.esc(v)}" readonly>
                 <div class="form-text">The child code cannot be changed because QR codes and ID cards depend on it.</div>`;
        } else if (type === 'textarea') {
          ctl = `<textarea id="${id}" class="form-control" rows="${o.rows || 2}">${K.esc(v)}</textarea>`;
        } else if (type === 'age') {
          ctl = `<input id="${id}" class="form-control" value="${K.esc(ageOf(K.date(values.date_of_birth)))}" readonly>`;
        } else if (type === 'select' || type === 'program') {
          let opts = type === 'program' ? programs.map(p => p.name) : o.options;
          if (v && !opts.includes(v)) opts = [...opts, v];
          ctl = `<select id="${id}" class="form-select"><option value="">—</option>
            ${opts.map(x => 
              
              
              
              
              
              
              
              
              
              
              
              
              
              
              `<option value="${K.esc(x)}" ${x === v ? 'selected' : ''}>${K.esc(x)}</option>`).join('')}</select>`;
        } else {
          const t = type === 'date' ? 'date' : 'text';
          const val = type === 'date' ? K.date(v) : v;
          ctl = `<input id="${id}" type="${t}" class="form-control" value="${K.esc(val)}"
            ${o.ph && !editing ? `placeholder="${K.esc(o.ph)}"` : ''}
            ${o.suggest ? `list="dl_${id}"` : ''} ${type === 'date' && key === 'date_of_birth' ? `max="${new Date().toISOString().slice(0, 10)}"` : ''}>
            ${o.suggest ? `<datalist id="dl_${id}">${o.suggest.map(s => `<option value="${K.esc(s)}">`).join('')}</datalist>` : ''}`;
        }
        return `<div class="${wide}">${lab}${ctl}<div class="invalid-feedback"></div></div>`;
      }

      const guardianRow = (g = {}) => `
        <div class="row g-2 mb-2 guardian-row border rounded p-2 mx-0">
          <div class="col-md-4"><input class="form-control g-name" placeholder="Name *" value="${K.esc(g.name || '')}"></div>
          <div class="col-md-3"><input class="form-control g-rel" placeholder="Relationship" value="${K.esc(g.relationship || '')}"></div>
          <div class="col-md-3"><input class="form-control g-tel" placeholder="Contact number" value="${K.esc(g.contact_number || '')}"></div>
          <div class="col-md-2 text-end"><button type="button" class="btn btn-outline-danger btn-sm g-del" aria-label="Remove guardian"><i class="fa-solid fa-trash"></i></button></div>
          <div class="col-12"><input class="form-control g-addr" placeholder="Address" value="${K.esc(g.address || '')}"></div>
        </div>`;

      function openForm(c) {
        const editing = !!c;
        const values = c || {};
        $('#formTitle').textContent = editing ? `Edit ${c.full_name}` : 'Add child';

        $('#formBody').innerHTML = `<div id="formErr"></div>` + SECTIONS
          .filter(s => !s.perm || K.can(s.perm))
          .map(s => `<h6 class="text-uppercase text-muted small fw-bold mt-3 mb-2">${K.esc(s.title)}</h6>
            <div class="row g-3">${s.fields.map(f => fieldHtml(f, values, editing)).join('')}</div>`).join('') + `
          <h6 class="text-uppercase text-muted small fw-bold mt-4 mb-2">Guardians</h6>
          <div id="guardians">${(values.guardians || []).map(guardianRow).join('')}</div>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="addGuardian"><i class="fa-solid fa-plus"></i> Add guardian</button>
          <div class="text-muted small mt-3">* required</div>`;

        const form = $('#childForm');
        form.querySelector('#addGuardian').onclick = () => form.querySelector('#guardians').insertAdjacentHTML('beforeend', guardianRow());
        form.querySelector('#guardians').addEventListener('click', e => {
          const del = e.target.closest('.g-del');
          if (del) del.closest('.guardian-row').remove();
        });
        const dob = form.querySelector('#f_date_of_birth'), age = form.querySelector('#f__age');
        if (dob && age) dob.addEventListener('input', () => { age.value = ageOf(dob.value); });

        form.onsubmit = async ev => {
          ev.preventDefault();
          const btn = $('#formSave');
          $('#formErr').innerHTML = '';
          form.querySelectorAll('.is-invalid').forEach(x => x.classList.remove('is-invalid'));

          const data = {};
          SECTIONS.filter(s => !s.perm || K.can(s.perm)).forEach(s => s.fields.forEach(([key, , type]) => {
            if (type === 'age' || (key === 'child_code' && editing)) return;
            const input = form.querySelector('#f_' + key);
            if (!input) return;
            const val = input.value.trim();
            data[key] = val === '' ? null : val;
          }));

          data.guardians = [];
          let guardianProblem = false;
          form.querySelectorAll('.guardian-row').forEach(r => {
            const g = {
              name: r.querySelector('.g-name').value.trim(),
              relationship: r.querySelector('.g-rel').value.trim() || null,
              contact_number: r.querySelector('.g-tel').value.trim() || null,
              address: r.querySelector('.g-addr').value.trim() || null,
            };
            if (!g.name && (g.relationship || g.contact_number || g.address)) { guardianProblem = true; r.querySelector('.g-name').classList.add('is-invalid'); return; }
            if (g.name) data.guardians.push(g);
          });
          if (guardianProblem) { $('#formErr').innerHTML = K.alert('Each guardian needs a name.'); return; }

          btn.disabled = true;
          try {
            if (editing) await K.api('PUT', `/children/${c.id}`, data);
            else await K.api('POST', '/children', data);
            formModal.hide();
            K.toast(editing ? 'Child updated.' : 'Child registered.', 'success');
            load();
          } catch (ex) {
            $('#formErr').innerHTML = K.alert(ex.friendly);
            Object.keys(ex.errors || {}).forEach(k => {
              const input = form.querySelector('#f_' + k);
              if (input) {
                input.classList.add('is-invalid');
                const fb = input.parentElement.querySelector('.invalid-feedback');
                if (fb) fb.textContent = ex.errors[k][0];
              }
            });
            $('#formBody').scrollTop = 0;
          } finally { btn.disabled = false; }
        };
        formModal.show();
      }

      // ---------- filters ----------
      $('#q').addEventListener('input', K.debounce(e => { state.q = e.target.value.trim(); state.page = 1; load(); }));
      $('#program').addEventListener('change', e => { state.program = e.target.value; state.page = 1; load(); });
      const st = $('#status');
      if (st) st.addEventListener('change', e => { state.status = e.target.value; state.page = 1; load(); });
      $('#fGender').addEventListener('change', e => { state.gender = e.target.value; state.page = 1; load(); });
      [['#fArea', 'area'], ['#fOffice', 'office_code'], ['#fService', 'service_state'], ['#fSponsor', 'sponsor_state']]
        .forEach(([sel, key]) => $(sel).addEventListener('input', K.debounce(e => { state[key] = e.target.value.trim(); state.page = 1; load(); })));
      const add = $('#addChild');
      if (add) add.addEventListener('click', () => openForm(null));

      await load();
      if (params && params[0] === 'new' && K.can('create_child')) openForm(null);
    },
  };
})(window.KFHI);