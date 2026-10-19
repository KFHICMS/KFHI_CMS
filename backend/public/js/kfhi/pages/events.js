/* KFHI CMS – Event Management: event list, event workspace (Attendance / Questionnaires / Details / Reports) */
(function (K) {
  const STATUSES = ['active', 'completed', 'closed', 'cancelled', 'archived'];
  const COLOR = { active: 'success', completed: 'primary', closed: 'secondary', cancelled: 'warning', archived: 'dark' };
  const ROLE_LABEL = { admin: 'Admin', child_officer: 'Child Officer', field_officer: 'Field Officer', survey_enumerator: 'Enumerator' };
  const ASSIGNABLE = ['child_officer', 'field_officer', 'survey_enumerator'];

  const cap = s => (s ? s.charAt(0).toUpperCase() + s.slice(1) : '');
  const when = iso => {
    if (!iso) return '—';
    const d = new Date(iso);
    return isNaN(d) ? '—' : d.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
  };
  const pad = n => String(n).padStart(2, '0');
  const toLocalInput = iso => {
    if (!iso) return '';
    const d = new Date(iso);
    return isNaN(d) ? '' : `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  };
  const fromLocalInput = v => (v ? new Date(v).toISOString() : null);   // the browser's local time -> UTC

  function timing(e) {
    if (e.status !== 'active') return cap(e.status);
    const now = Date.now();
    const s = e.start_at ? Date.parse(e.start_at) : null;
    const en = e.end_at ? Date.parse(e.end_at) : null;
    if (s && now < s) return 'Upcoming';
    if (en && now > en) return 'Ended';
    return s ? 'In progress' : 'Active';
  }
  const statusBadge = e => `<span class="badge bg-${COLOR[e.status] || 'secondary'}">${K.esc(timing(e))}</span>`;
  const onOff = (on, label) => `<span class="badge ${on ? 'bg-success' : 'bg-light text-secondary border'}">
    <i class="fa-solid ${on ? 'fa-check' : 'fa-xmark'}"></i> ${K.esc(label)} ${on ? 'on' : 'off'}</span>`;

  function saveBlob(blob, name) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
  }

  // ---------- QR scanner (camera) ----------
  /** Starts the camera and calls onText(text) for every QR code it reads. Returns { stop }. */
  async function startScanner(video, onText) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('The camera needs a secure connection (https). Use the link box below instead.');
    }
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
    video.srcObject = stream;
    video.setAttribute('playsinline', '');
    await video.play();

    let stopped = false;
    let detector = null;
    try { if ('BarcodeDetector' in window) detector = new BarcodeDetector({ formats: ['qr_code'] }); } catch (e) { detector = null; }
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    async function tick() {
      if (stopped) return;
      if (video.readyState >= 2 && video.videoWidth) {
        try {
          let text = null;
          if (detector) {
            const codes = await detector.detect(video);
            if (codes.length) text = codes[0].rawValue;
          } else if (window.jsQR) {
            canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const r = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
            if (r) text = r.data;
          }
          if (text) onText(text);
        } catch (err) { detector = null; }     // native detector not usable here: fall back to jsQR
      }
      setTimeout(tick, 130);
    }
    tick();

    return { stop() { stopped = true; stream.getTracks().forEach(t => t.stop()); video.srcObject = null; } };
  }

  /** The QR holds a link such as https://site/scan?t=TOKEN. Returns TOKEN, or null. */
  function tokenFrom(text) {
    const s = String(text || '').trim();
    try { const t = new URL(s).searchParams.get('t'); if (t) return t; } catch (e) { /* not a URL */ }
    return s.length > 40 && !/\s/.test(s) ? s : null;
  }

  // ==================================================================
  K.pages.events = {
    title: 'Event Management',
    icon: 'fa-calendar-check',
    order: 5,

    async render(el, params) {
      const id = params && /^\d+$/.test(params[0] || '') ? Number(params[0]) : null;
      if (id) return workspace(el, id);
      return list(el, params);
    },
  };

  // ---------------------------------------------------------------- list
  async function list(el, params) {
    const canManage = K.can('manage_programs');
    const state = { q: '', status: 'active' };
    let events = [];

    el.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Event Management</h1>
        ${canManage ? '<button class="btn btn-primary" id="addEvent"><i class="fa-solid fa-plus"></i> Add event</button>' : ''}
      </div>
      <div class="row g-2 mb-3">
        <div class="col-12 col-md-6"><input id="q" class="form-control" placeholder="Search events"></div>
        <div class="col-12 col-md-3"><select id="fStatus" class="form-select">
          <option value="active">Active</option><option value="completed">Completed</option><option value="closed">Closed</option>
          <option value="cancelled">Cancelled</option><option value="archived">Archived</option><option value="all">All</option></select></div>
      </div>
      <div id="cards"></div>`;

    const $ = s => el.querySelector(s);
    const cards = $('#cards');

    function draw() {
      const q = state.q.toLowerCase();
      const rows = events.filter(e => (state.status === 'all' || e.status === state.status) &&
        (!q || (e.name || '').toLowerCase().includes(q) || (e.location || '').toLowerCase().includes(q) ||
          ((e.program && e.program.name) || '').toLowerCase().includes(q)));
      if (!rows.length) {
        cards.innerHTML = `<div class="card"><div class="card-body text-muted">${K.can('manage_programs')
          ? 'No events found. Click "Add event" to create one.' : 'No events are assigned to you yet.'}</div></div>`;
        return;
      }
      cards.innerHTML = '<div class="row g-3">' + rows.map(e => `
        <div class="col-md-6 col-xl-4"><div class="card h-100 event-card" data-id="${K.esc(e.id)}" style="cursor:pointer">
          <div class="card-body">
            <div class="d-flex gap-3">
              <i class="fa-solid fa-folder-open fa-2x text-success"></i>
              <div class="flex-grow-1">
                <div class="fw-bold">${K.esc(e.name)}</div>
                <div class="text-muted small">${K.esc((e.program && e.program.name) || 'No program')}</div>
              </div>${statusBadge(e)}
            </div>
            <div class="small mt-3"><i class="fa-regular fa-clock text-muted"></i> ${K.esc(when(e.start_at))}</div>
            <div class="small"><i class="fa-solid fa-location-dot text-muted"></i> ${K.esc(e.location || '—')}</div>
            <div class="d-flex flex-wrap gap-1 mt-3">${onOff(e.qr_attendance_enabled, 'Attendance')} ${onOff(e.has_questionnaire, 'Questionnaires')}</div>
            ${e.qr_attendance_enabled ? `<div class="small text-muted mt-2">${K.esc(e.attendances_count ?? 0)} attendance record(s)</div>` : ''}
          </div></div></div>`).join('') + '</div>';
    }

    async function load() {
      cards.innerHTML = K.spinner();
      try { events = await K.api('GET', '/events'); draw(); }
      catch (e) { cards.innerHTML = K.alert(e.friendly); }
    }

    cards.addEventListener('click', e => {
      const c = e.target.closest('.event-card');
      if (c) location.hash = '#/events/' + c.dataset.id;
    });
    $('#q').addEventListener('input', K.debounce(e => { state.q = e.target.value.trim(); draw(); }, 200));
    $('#fStatus').addEventListener('change', e => { state.status = e.target.value; draw(); });
    const add = $('#addEvent');
    if (add) add.addEventListener('click', () => openEventForm(null, load));

    await load();
    if (params && params[0] === 'new' && canManage) openEventForm(null, load);
  }

  // ---------------------------------------------------------------- add / edit form
  async function openEventForm(e, onSaved) {
    const editing = !!e;
    const v = e || {};
    const canOptions = K.can('manage_event_options');
    const canAssign = K.can('manage_users');

    let modalEl = document.getElementById('eventFormModal');
    if (modalEl) { bootstrap.Modal.getOrCreateInstance(modalEl).dispose(); modalEl.remove(); }
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal fade" id="eventFormModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-scrollable modal-lg">
        <form class="modal-content" id="eventForm" novalidate>
          <div class="modal-header"><h5 class="modal-title">${editing ? 'Edit event' : 'Add event'}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body" id="eventFormBody">${K.spinner()}</div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" id="eventSave">Save</button></div>
        </form></div></div>`);
    modalEl = document.getElementById('eventFormModal');
    const modal = new bootstrap.Modal(modalEl);
    modalEl.addEventListener('hidden.bs.modal', () => { modal.dispose(); modalEl.remove(); });
    modal.show();

    let programs = [], users = [];
    try { programs = await K.api('GET', '/programs'); } catch (x) { programs = []; }
    if (canAssign) {
      try { users = (await K.api('GET', '/users')).filter(u => u.is_active && (u.roles || []).some(r => ASSIGNABLE.includes(r))); }
      catch (x) { users = []; }
    }
    const assigned = (v.user_ids || []).map(String);
    const progOptions = programs.filter(p => p.status !== 'archived' || p.id === v.program_id);

    const body = modalEl.querySelector('#eventFormBody');
    body.innerHTML = `
      <div id="eventErr"></div>
      <div class="mb-3"><label class="form-label" for="eName">Event name *</label>
        <input id="eName" class="form-control" maxlength="255" value="${K.esc(v.name || '')}"></div>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label" for="eProgram">Program / project</label>
          <select id="eProgram" class="form-select"><option value="">None</option>
            ${progOptions.map(p => `<option value="${K.esc(p.id)}" ${p.id === v.program_id ? 'selected' : ''}>${K.esc(p.name)}</option>`).join('')}</select></div>
        <div class="col-md-6"><label class="form-label" for="eLocation">Location</label>
          <input id="eLocation" class="form-control" maxlength="255" value="${K.esc(v.location || '')}"></div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label" for="eStart">Start date and time</label>
          <input id="eStart" type="datetime-local" class="form-control" value="${K.esc(toLocalInput(v.start_at))}"></div>
        <div class="col-md-6"><label class="form-label" for="eEnd">End date and time</label>
          <input id="eEnd" type="datetime-local" class="form-control" value="${K.esc(toLocalInput(v.end_at))}"></div>
      </div>
      <div class="mb-3"><label class="form-label" for="eDesc">Description</label>
        <textarea id="eDesc" class="form-control" rows="3">${K.esc(v.description || '')}</textarea></div>
      ${editing ? `<div class="mb-3"><label class="form-label" for="eStatus">Status</label>
        <select id="eStatus" class="form-select">${STATUSES.map(s => `<option value="${s}" ${s === v.status ? 'selected' : ''}>${cap(s)}</option>`).join('')}</select></div>` : ''}
      ${canAssign ? `<div class="mb-3"><label class="form-label">Assigned officers and enumerators</label>
        <div class="border rounded p-2" style="max-height:160px;overflow:auto">
          ${users.length ? users.map(u => `<div class="form-check">
            <input class="form-check-input eUser" type="checkbox" id="eu${K.esc(u.id)}" value="${K.esc(u.id)}" ${assigned.includes(String(u.id)) ? 'checked' : ''}>
            <label class="form-check-label" for="eu${K.esc(u.id)}">${K.esc(u.name)} <span class="text-muted small">(${K.esc((u.roles || []).map(r => ROLE_LABEL[r] || r).join(', '))})</span></label></div>`).join('')
            : '<span class="text-muted small">No active officers or enumerators yet.</span>'}</div>
        <div class="form-text">Enumerators only see events they are assigned to.</div></div>` : ''}
      ${canOptions ? `<div class="border rounded p-3 bg-light">
        <div class="fw-semibold mb-2">Event options <span class="text-muted small fw-normal">(administrator only)</span></div>
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="eAtt" ${v.qr_attendance_enabled ? 'checked' : ''}>
          <label class="form-check-label" for="eAtt">Enable QR attendance</label></div>
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="eQues" ${v.has_questionnaire ? 'checked' : ''}>
          <label class="form-check-label" for="eQues">Enable questionnaires</label></div></div>`
        : '<div class="text-muted small">Attendance and questionnaires can only be switched on by an administrator.</div>'}`;

    modalEl.querySelector('#eventForm').onsubmit = async ev => {
      ev.preventDefault();
      const btn = modalEl.querySelector('#eventSave');
      const err = modalEl.querySelector('#eventErr');
      err.innerHTML = '';
      const q = s => modalEl.querySelector(s);

      const payload = {
        name: q('#eName').value.trim(),
        program_id: q('#eProgram').value ? Number(q('#eProgram').value) : null,
        location: q('#eLocation').value.trim() || null,
        start_at: fromLocalInput(q('#eStart').value),
        end_at: fromLocalInput(q('#eEnd').value),
        description: q('#eDesc').value.trim() || null,
      };
      if (editing) payload.status = q('#eStatus').value;
      if (canAssign) payload.user_ids = [...modalEl.querySelectorAll('.eUser:checked')].map(c => Number(c.value));
      // only an administrator may send these two switches: the API refuses them from anyone else
      if (canOptions) { payload.qr_attendance_enabled = q('#eAtt').checked; payload.has_questionnaire = q('#eQues').checked; }

      btn.disabled = true;
      try {
        const saved = editing ? await K.api('PUT', `/events/${e.id}`, payload) : await K.api('POST', '/events', payload);
        modal.hide();
        K.toast(editing ? 'Event updated.' : 'Event created.', 'success');
        onSaved(saved);
      } catch (ex) { err.innerHTML = K.alert(ex.friendly); }
      finally { btn.disabled = false; }
    };
  }

  // ---------------------------------------------------------------- workspace
  async function workspace(el, id) {
    let ev, scanner = null;
    let tab = 'attendance';
    const stopScanner = () => { if (scanner) { scanner.stop(); scanner = null; } };
    window.addEventListener('hashchange', stopScanner, { once: true });   // never leave the camera running

    el.innerHTML = K.spinner();
    try { ev = await K.api('GET', `/events/${id}`); }
    catch (e) { el.innerHTML = K.alert(e.friendly); return; }

    const canOptions = K.can('manage_event_options');

    function shell() {
      el.innerHTML = `
        <div class="mb-2"><a href="#/events" class="text-decoration-none"><i class="fa-solid fa-arrow-left"></i> All events</a></div>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
          <div><h1 class="h4 mb-1"><i class="fa-solid fa-folder-open text-success"></i> ${K.esc(ev.name)}</h1>
            <div class="text-muted small">${K.esc((ev.program && ev.program.name) || 'No program')} · ${K.esc(when(ev.start_at))} · ${K.esc(ev.location || 'No location')}</div></div>
          <div class="d-flex gap-2 align-items-center">${statusBadge(ev)}
            ${K.can('manage_programs') ? '<button class="btn btn-sm btn-outline-secondary" id="editEvent"><i class="fa-solid fa-pen"></i> Edit</button>' : ''}</div>
        </div>
        <ul class="nav nav-tabs mb-3" id="tabs">
          ${[['attendance', 'fa-clipboard-check', 'Attendance'], ['questionnaires', 'fa-list-check', 'Questionnaires'],
             ['details', 'fa-circle-info', 'Event Details'], ['reports', 'fa-chart-column', 'Reports']].map(([k, ic, label]) => `
            <li class="nav-item"><button class="nav-link ${k === tab ? 'active' : ''}" data-tab="${k}"><i class="fa-solid ${ic}"></i> ${label}</button></li>`).join('')}
        </ul>
        <div id="tabBody"></div>`;

      const edit = el.querySelector('#editEvent');
      if (edit) edit.onclick = () => openEventForm(ev, async () => { ev = await K.api('GET', `/events/${id}`); stopScanner(); shell(); });
      el.querySelectorAll('[data-tab]').forEach(b => b.onclick = () => { stopScanner(); tab = b.dataset.tab; shell(); });

      const body = el.querySelector('#tabBody');
      if (tab === 'attendance') attendanceTab(body);
      if (tab === 'questionnaires') questionnairesTab(body);
      if (tab === 'details') detailsTab(body);
      if (tab === 'reports') reportsTab(body);
    }

    async function setOption(key, on, label) {
      try {
        await K.api('PUT', `/events/${id}`, { [key]: on });
        ev = await K.api('GET', `/events/${id}`);
        K.toast(`${label} ${on ? 'enabled' : 'disabled'}.`, 'success');
      } catch (e) { K.toast(e.friendly, 'danger'); }
      stopScanner(); shell();
    }

    // ---------------- Attendance
    function attendanceTab(body) {
      if (!ev.qr_attendance_enabled) {
        body.innerHTML = `<div class="alert alert-info mb-0"><i class="fa-solid fa-circle-info"></i>
          Attendance is not enabled for this event. Only an administrator can enable it.
          ${canOptions ? '<div class="mt-2"><button class="btn btn-sm btn-success" id="enableAtt">Enable attendance</button></div>' : ''}</div>`;
        const b = body.querySelector('#enableAtt');
        if (b) b.onclick = () => setOption('qr_attendance_enabled', true, 'Attendance');
        return;
      }

      const canScan = K.can('record_attendance') && ev.status === 'active';
      body.innerHTML = `
        <div class="row g-4">
          ${canScan ? `<div class="col-lg-5"><div class="card"><div class="card-body">
            <h2 class="h6 mb-3">Scan a child's QR code</h2>
            <div id="camBox" class="d-none mb-2"><video id="cam" muted playsinline style="width:100%;border-radius:8px;background:#000"></video></div>
            <div id="camMsg"></div>
            <div class="d-grid gap-2 mb-3"><button class="btn btn-success" id="camStart"><i class="fa-solid fa-camera"></i> Start camera</button>
              <button class="btn btn-outline-secondary d-none" id="camStop">Stop camera</button></div>
            <div id="scanResult" aria-live="polite"></div>
            <div class="small text-muted mb-1">No camera? Paste the scan link:</div>
            <div class="input-group"><input id="pasteLink" class="form-control" placeholder="https://…/scan?t=…"><button class="btn btn-outline-primary" id="pasteGo">Record</button></div>
          </div></div></div>` : `<div class="col-12"><div class="alert alert-secondary mb-0">${ev.status !== 'active'
            ? `This event is ${K.esc(ev.status)}, so attendance can no longer be recorded.` : 'Your role cannot record attendance.'}</div></div>`}
          <div class="${canScan ? 'col-lg-7' : 'col-12'}"><div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h2 class="h6 mb-0">Attendance records <span class="badge bg-success ms-1" id="presentCount">0</span></h2>
              ${K.can('view_reports') ? '<button class="btn btn-sm btn-outline-secondary" id="exportCsv"><i class="fa-solid fa-file-csv"></i> Export CSV</button>' : ''}
            </div><div id="records">${K.spinner()}</div></div></div></div>
        </div>`;

      const q = s => body.querySelector(s);

      async function loadRecords() {
        try {
          const r = await K.api('GET', `/events/${id}/attendance`);
          q('#presentCount').textContent = r.present;
          q('#records').innerHTML = r.records.length
            ? `<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Marked at</th></tr></thead><tbody>
              ${r.records.map(x => `<tr><td>${K.esc(x.child_code)}</td><td>${K.esc(x.child_name)}</td><td>${K.esc(cap(x.status))}</td><td>${K.esc(when(x.marked_at))}</td></tr>`).join('')}
              </tbody></table></div>`
            : '<div class="text-muted">No attendance recorded yet.</div>';
        } catch (e) { q('#records').innerHTML = K.alert(e.friendly); }
      }
      loadRecords();

      const exp = q('#exportCsv');
      if (exp) exp.onclick = async () => {
        exp.disabled = true;
        try {
          const blob = await K.apiBlob(`/events/${id}/attendance/export`);
          saveBlob(blob, `Attendance_${(ev.name || 'event').replace(/[^A-Za-z0-9]+/g, '_')}_${new Date().toISOString().slice(0, 10)}.csv`);
        } catch (e) { K.toast(e.friendly, 'danger'); }
        finally { exp.disabled = false; }
      };

      if (!canScan) return;

      // ---- recording
      let busy = false, lastToken = '', lastAt = 0;
      async function record(text) {
        const token = tokenFrom(text);
        const out = q('#scanResult');
        if (!token) { out.innerHTML = K.alert('That is not a KFHI child QR code.', 'warning'); return; }
        if (busy || (token === lastToken && Date.now() - lastAt < 4000)) return;   // ignore the same code held in front of the camera
        busy = true; lastToken = token; lastAt = Date.now();
        try {
          const r = await K.api('POST', `/events/${id}/attendance/scan`, { token });
          if (navigator.vibrate) navigator.vibrate(r.already_marked ? [80, 60, 80] : 120);
          out.innerHTML = `<div class="alert alert-${r.already_marked ? 'warning' : 'success'} mb-3">
            <div class="fw-bold">${K.esc(r.message)}</div>
            <div class="fs-5">${K.esc(r.child_code)}</div><div>${K.esc(r.child_name)}</div></div>`;
          loadRecords();
        } catch (e) {
          out.innerHTML = K.alert(e.friendly, 'danger');
        } finally { busy = false; }
      }

      q('#pasteGo').onclick = () => { record(q('#pasteLink').value); q('#pasteLink').value = ''; lastAt = 0; };

      q('#camStart').onclick = async () => {
        q('#camMsg').innerHTML = '';
        try {
          q('#camBox').classList.remove('d-none');
          scanner = await startScanner(q('#cam'), record);
          q('#camStart').classList.add('d-none');
          q('#camStop').classList.remove('d-none');
        } catch (e) {
          q('#camBox').classList.add('d-none');
          q('#camMsg').innerHTML = K.alert(e.name === 'NotAllowedError'
            ? 'Camera permission was denied. Allow camera access for this site in the browser, then try again.'
            : (e.message || 'Could not start the camera.'), 'warning');
        }
      };
      q('#camStop').onclick = () => {
        stopScanner();
        q('#camBox').classList.add('d-none');
        q('#camStop').classList.add('d-none');
        q('#camStart').classList.remove('d-none');
      };
    }

    // ---------------- Questionnaires
    function questionnairesTab(body) {
      if (!ev.has_questionnaire) {
        body.innerHTML = `<div class="alert alert-info mb-0"><i class="fa-solid fa-circle-info"></i>
          Questionnaires are not enabled for this event. Only an administrator can enable them.
          ${canOptions ? '<div class="mt-2"><button class="btn btn-sm btn-success" id="enableQ">Enable questionnaires</button></div>' : ''}</div>`;
        const b = body.querySelector('#enableQ');
        if (b) b.onclick = () => setOption('has_questionnaire', true, 'Questionnaires');
        return;
      }
      body.innerHTML = `<div class="card"><div class="card-body text-center py-5">
        <i class="fa-solid fa-list-check fa-3x text-muted mb-3"></i>
        <h2 class="h5">Questionnaires are enabled for this event</h2>
        <p class="text-muted mb-0">The questionnaire builder is the next module. Forms created there will appear here for respondents.</p>
      </div></div>`;
    }

    // ---------------- Event details
    function detailsTab(body) {
      const row = (k, v) => `<div class="row py-2 border-bottom"><div class="col-5 col-md-3 text-muted small">${K.esc(k)}</div><div class="col-7 col-md-9">${v}</div></div>`;
      body.innerHTML = `<div class="card"><div class="card-body">
        ${row('Description', K.esc(ev.description || '—'))}
        ${row('Program', K.esc((ev.program && ev.program.name) || '—'))}
        ${row('Starts', K.esc(when(ev.start_at)))}
        ${row('Ends', K.esc(when(ev.end_at)))}
        ${row('Location', K.esc(ev.location || '—'))}
        ${row('Status', statusBadge(ev))}
        ${canOptions ? `<div class="row py-2 border-bottom"><div class="col-5 col-md-3 text-muted small">Options</div><div class="col-7 col-md-9">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="swAtt" ${ev.qr_attendance_enabled ? 'checked' : ''}><label class="form-check-label" for="swAtt">QR attendance</label></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="swQ" ${ev.has_questionnaire ? 'checked' : ''}><label class="form-check-label" for="swQ">Questionnaires</label></div>
          </div></div>`
          : row('Options', `${onOff(ev.qr_attendance_enabled, 'Attendance')} ${onOff(ev.has_questionnaire, 'Questionnaires')}
              <div class="text-muted small mt-1">Only an administrator can change these options.</div>`)}
      </div></div>`;

      const swA = body.querySelector('#swAtt'), swQ = body.querySelector('#swQ');
      const confirmSwitch = (sw, key, label) => sw.addEventListener('change', () => {
        const on = sw.checked;
        if (confirm(`${on ? 'Enable' : 'Disable'} ${label} for "${ev.name}"?`)) setOption(key, on, label);
        else sw.checked = !on;
      });
      if (swA) confirmSwitch(swA, 'qr_attendance_enabled', 'QR attendance');
      if (swQ) confirmSwitch(swQ, 'has_questionnaire', 'Questionnaires');
    }

    // ---------------- Reports
    async function reportsTab(body) {
      body.innerHTML = K.spinner();
      let present = null;
      if (ev.qr_attendance_enabled) {
        try { present = (await K.api('GET', `/events/${id}/attendance`)).present; } catch (e) { /* shown as unavailable */ }
      }
      body.innerHTML = `<div class="row g-3">
        <div class="col-md-4"><div class="border rounded p-3 h-100">
          <div class="fs-3 fw-bold">${present === null ? '—' : K.esc(present)}</div>
          <div class="fw-semibold">Children present</div>
          <div class="text-muted small">${ev.qr_attendance_enabled ? 'Unique children recorded for this event' : 'Attendance is not enabled'}</div></div></div>
        <div class="col-md-4"><div class="border rounded p-3 h-100">
          <div class="fs-3 fw-bold">—</div><div class="fw-semibold">Questionnaire responses</div>
          <div class="text-muted small">Available once the questionnaire builder is added</div></div></div>
        <div class="col-12">${ev.qr_attendance_enabled && K.can('view_reports')
          ? '<button class="btn btn-outline-secondary" id="repCsv"><i class="fa-solid fa-file-csv"></i> Export attendance (CSV)</button>'
          : ''}</div></div>`;
      const b = body.querySelector('#repCsv');
      if (b) b.onclick = async () => {
        b.disabled = true;
        try {
          const blob = await K.apiBlob(`/events/${id}/attendance/export`);
          saveBlob(blob, `Attendance_${(ev.name || 'event').replace(/[^A-Za-z0-9]+/g, '_')}_${new Date().toISOString().slice(0, 10)}.csv`);
        } catch (e) { K.toast(e.friendly, 'danger'); }
        finally { b.disabled = false; }
      };
    }

    shell();
  }
})(window.KFHI);