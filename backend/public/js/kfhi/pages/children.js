(function (K) {
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

  K.pages.children = {
    title: 'Child Management',
    perm: 'view_children',
    order: 3,

    async render(el) {
      const state = { q: '', program: '', status: 'active', page: 1 };

      el.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Child Management</h1>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-12 col-md-5"><input id="q" class="form-control" placeholder="Search by name or child code"></div>
          <div class="col-6 col-md-4"><select id="program" class="form-select"><option value="">All programs</option></select></div>
          ${K.can('archive_child') ? `<div class="col-6 col-md-3"><select id="status" class="form-select">
            <option value="active">Active</option><option value="archived">Archived</option><option value="all">All</option></select></div>` : ''}
        </div>
        <div id="list"></div>
        <div id="pager" class="d-flex justify-content-between align-items-center mt-3"></div>
        <div class="modal fade" id="childModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable modal-lg">
          <div class="modal-content"><div class="modal-header"><h5 class="modal-title">Child details</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="childBody"></div></div></div></div>`;

      const list = el.querySelector('#list');
      const pager = el.querySelector('#pager');
      const modalEl = el.querySelector('#childModal');
      const modal = new bootstrap.Modal(modalEl);
      let photoUrl = null;
      modalEl.addEventListener('hidden.bs.modal', () => { if (photoUrl) { URL.revokeObjectURL(photoUrl); photoUrl = null; } });

      // program filter options
      try {
        const programs = await K.api('GET', '/programs');
        const sel = el.querySelector('#program');
        programs.forEach(p => sel.insertAdjacentHTML('beforeend',
          `<option value="${K.esc(p.name)}">${K.esc(p.name)}</option>`));
      } catch (e) { /* filter just stays empty */ }

      async function load() {
        list.innerHTML = K.spinner();
        const params = new URLSearchParams({ page: state.page, per_page: 15, status: state.status });
        if (state.q) params.set('q', state.q);
        if (state.program) params.set('program', state.program);

        try {
          const res = await K.api('GET', '/children?' + params);
          if (!res.data.length) {
            list.innerHTML = '<div class="card"><div class="card-body text-muted">No children found.</div></div>';
            pager.innerHTML = '';
            return;
          }
          list.innerHTML = `<div class="table-responsive card"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Code</th><th>Name</th><th>Program</th><th class="d-none d-md-table-cell">School</th><th></th></tr></thead>
            <tbody>${res.data.map(c => `<tr data-id="${K.esc(c.id)}">
              <td>${K.esc(c.child_code)}</td><td>${K.esc(c.full_name)}</td><td>${K.esc(c.program)}</td>
              <td class="d-none d-md-table-cell">${K.esc(c.school)}</td>
              <td class="text-end"><button class="btn btn-sm btn-outline-primary">View</button></td></tr>`).join('')}
            </tbody></table></div>`;

          list.querySelectorAll('tbody tr').forEach(tr =>
            tr.addEventListener('click', () => show(res.data.find(c => String(c.id) === tr.dataset.id))));

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

      async function show(c) {
        const row = (k, v) => v ? `<div class="row py-1 border-bottom"><div class="col-5 col-md-4 text-muted small">${K.esc(k)}</div><div class="col-7 col-md-8">${K.esc(v)}</div></div>` : '';
        const sens = (k, v) => v ? `<div class="py-2 field-sensitive"><div class="text-muted small">${K.esc(k)}</div><div>${K.esc(v)}</div></div>` : '';

        const guardians = (c.guardians || []).map(g =>
          `<li>${K.esc(g.name)} ${g.relationship ? '(' + K.esc(g.relationship) + ')' : ''} ${g.contact_number ? '· ' + K.esc(g.contact_number) : ''}</li>`).join('');

        modalEl.querySelector('#childBody').innerHTML = `
          <div class="d-flex gap-3 mb-3 align-items-center">
            <img id="photo" class="child-photo" alt="">
            <div><div class="h5 mb-0">${K.esc(c.full_name)}</div><div class="text-muted">${K.esc(c.child_code)}
              ${c.status === 'archived' ? '<span class="badge bg-secondary ms-1">archived</span>' : ''}</div></div>
          </div>
          ${FIELDS.map(([k, label]) => row(label, k === 'date_of_birth' || k === 'registration_date' ? K.date(c[k]) : c[k])).join('')}
          ${guardians ? `<div class="mt-3"><div class="text-muted small">Guardians</div><ul class="mb-0">${guardians}</ul></div>` : ''}
          <div class="mt-3">${SENSITIVE.map(([k, label]) => sens(label, c[k])).join('')}</div>`;
        modal.show();

        if (c.has_photo) {
          try {
            photoUrl = URL.createObjectURL(await K.apiBlob(`/children/${c.id}/photo`));
            modalEl.querySelector('#photo').src = photoUrl;
          } catch (e) { /* leave placeholder */ }
        }
      }

      el.querySelector('#q').addEventListener('input', K.debounce(e => { state.q = e.target.value.trim(); state.page = 1; load(); }));
      el.querySelector('#program').addEventListener('change', e => { state.program = e.target.value; state.page = 1; load(); });
      const st = el.querySelector('#status');
      if (st) st.addEventListener('change', e => { state.status = e.target.value; state.page = 1; load(); });

      load();
    },
  };
})(window.KFHI);