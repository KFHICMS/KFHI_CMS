/* KFHI CMS – internal messages: inbox, read, reply, delete / restore, compose */
(function (K) {
  const ROLE_LABEL = { admin: 'Admin', child_officer: 'Child Officer', field_officer: 'Field Officer', survey_enumerator: 'Enumerator' };
  const roleLabel = r => ROLE_LABEL[r] || r || '';
  const when = d => (d ? new Date(d).toLocaleString() : '');
  let refreshList = () => {};

  function ensureModals() {
    if (document.getElementById('msgModal')) return;
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal fade" id="msgModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable modal-lg"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title" id="msgSubject"></h5><div class="text-muted small" id="msgWith"></div></div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="msgThread"></div>
        <div class="modal-footer flex-column align-items-stretch" id="msgFooter"></div>
      </div></div></div>

      <div class="modal fade" id="composeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form id="composeForm" novalidate>
          <div class="modal-header"><h5 class="modal-title">New message</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div id="mcErr"></div>
            <div class="mb-3"><label class="form-label" for="mcTo">To *</label><select id="mcTo" class="form-select"></select></div>
            <div class="mb-3"><label class="form-label" for="mcSubject">Subject *</label><input id="mcSubject" class="form-control" maxlength="150"></div>
            <div><label class="form-label" for="mcBody">Message *</label><textarea id="mcBody" class="form-control" rows="5" maxlength="5000"></textarea></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" id="mcSend">Send</button></div>
        </form></div></div></div>`);
  }

  async function openThread(id) {
    ensureModals();
    const modalEl = document.getElementById('msgModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const thread = modalEl.querySelector('#msgThread');
    const footer = modalEl.querySelector('#msgFooter');
    thread.innerHTML = K.spinner();
    footer.innerHTML = '';
    modalEl.querySelector('#msgSubject').textContent = 'Message';
    modalEl.querySelector('#msgWith').textContent = '';
    modal.show();

    let c;
    try { c = await K.api('GET', `/messages/${id}`); }
    catch (e) { thread.innerHTML = K.alert(e.friendly); return; }

    modalEl.querySelector('#msgSubject').textContent = c.subject;
    modalEl.querySelector('#msgWith').textContent = 'With: ' + c.participants.filter(p => !p.me).map(p => p.name).join(', ');
    thread.innerHTML = c.messages.map(m => `
      <div class="mb-3 d-flex ${m.mine ? 'justify-content-end' : ''}">
        <div class="p-2 rounded ${m.mine ? 'bg-success-subtle' : 'bg-light border'}" style="max-width:85%">
          <div class="small text-muted mb-1">${K.esc(m.mine ? 'You' : m.sender_name)} · ${K.esc(when(m.created_at))}</div>
          <div style="white-space:pre-wrap">${K.esc(m.body)}</div>
        </div></div>`).join('');
    thread.scrollTop = thread.scrollHeight;
    refreshList();   // opening marks it read

    if (c.deleted) {
      footer.innerHTML = `<div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small">This conversation is in Deleted.</span>
        <button class="btn btn-outline-success btn-sm" id="msgRestore">Restore</button></div>`;
      footer.querySelector('#msgRestore').onclick = async () => {
        try {
          await K.api('POST', `/messages/${id}/restore`);
          modal.hide(); K.toast('Conversation restored.', 'success'); refreshList();
        } catch (e) { K.toast(e.friendly, 'danger'); }
      };
      return;
    }

    footer.innerHTML = `
      <div id="msgErr"></div>
      <textarea id="msgReply" class="form-control mb-2" rows="3" maxlength="5000" placeholder="Write a reply…"></textarea>
      <div class="d-flex justify-content-between">
        <button class="btn btn-outline-danger btn-sm" id="msgDelete">Delete</button>
        <button class="btn btn-primary" id="msgSend">Reply</button></div>`;

    footer.querySelector('#msgSend').onclick = async ev => {
      const body = footer.querySelector('#msgReply').value.trim();
      if (!body) { footer.querySelector('#msgErr').innerHTML = K.alert('Write something first.'); return; }
      ev.target.disabled = true;
      try {
        await K.api('POST', `/messages/${id}/reply`, { body });
        K.toast('Reply sent.', 'success');
        await openThread(id);
      } catch (e) {
        footer.querySelector('#msgErr').innerHTML = K.alert(e.friendly);
        ev.target.disabled = false;
      }
    };

    footer.querySelector('#msgDelete').onclick = async () => {
      if (!confirm('Delete this conversation? It is only removed for you, and you can restore it from Deleted.')) return;
      try {
        await K.api('DELETE', `/messages/${id}`);
        modal.hide(); K.toast('Conversation deleted.', 'success'); refreshList();
      } catch (e) { K.toast(e.friendly, 'danger'); }
    };
  }

  /** Open the "New message" window. `to` = { id, name } to pre-select the person. */
  K.composeMessage = async function (to) {
    ensureModals();
    const el = document.getElementById('composeModal');
    const modal = bootstrap.Modal.getOrCreateInstance(el);
    const sel = el.querySelector('#mcTo');
    el.querySelector('#mcErr').innerHTML = '';
    el.querySelector('#mcSubject').value = '';
    el.querySelector('#mcBody').value = '';
    sel.innerHTML = '<option value="">Loading…</option>';
    modal.show();

    try {
      const people = await K.api('GET', '/messages/recipients');
      sel.innerHTML = '<option value="">Choose a person…</option>' + people.map(u =>
        `<option value="${K.esc(u.id)}">${K.esc(u.name)} (${K.esc(roleLabel(u.role))})</option>`).join('');
      if (to) sel.value = String(to.id);
    } catch (e) { el.querySelector('#mcErr').innerHTML = K.alert(e.friendly); }

    el.querySelector('#composeForm').onsubmit = async ev => {
      ev.preventDefault();
      const btn = el.querySelector('#mcSend');
      btn.disabled = true;
      el.querySelector('#mcErr').innerHTML = '';
      try {
        await K.api('POST', '/messages', {
          recipient_id: Number(sel.value) || null,
          subject: el.querySelector('#mcSubject').value.trim(),
          body: el.querySelector('#mcBody').value.trim(),
        });
        modal.hide();
        K.toast('Message sent.', 'success');
        refreshList();
      } catch (e) {
        el.querySelector('#mcErr').innerHTML = K.alert(e.friendly);
      } finally { btn.disabled = false; }
    };
  };

  /** Draw the messages card into `box` (used on the dashboard). */
  K.mountMessages = function (box) {
    let folder = 'inbox';

    box.innerHTML = `
      <div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <h2 class="h5 mb-0">Messages <span class="badge bg-danger ms-1 d-none" id="msgBadge"></span></h2>
          <div class="d-flex gap-2">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary active" data-f="inbox">Inbox</button>
              <button class="btn btn-outline-secondary" data-f="trash">Deleted</button></div>
            <button class="btn btn-sm btn-primary" id="msgNew"><i class="fa-solid fa-pen"></i> New message</button>
          </div>
        </div>
        <div id="msgList"></div>
      </div></div>`;

    const list = box.querySelector('#msgList');
    const badge = box.querySelector('#msgBadge');

    async function load() {
      list.innerHTML = K.spinner();
      try {
        const [rows, unread] = await Promise.all([
          K.api('GET', '/messages?folder=' + folder),
          K.api('GET', '/messages/unread-count'),
        ]);
        badge.textContent = unread.count + ' unread';
        badge.classList.toggle('d-none', !unread.count);

        if (!rows.length) {
          list.innerHTML = `<div class="text-muted">${folder === 'trash' ? 'No deleted conversations.' : 'No messages yet.'}</div>`;
          return;
        }
        list.innerHTML = '<div class="list-group">' + rows.map(r => `
          <button type="button" class="list-group-item list-group-item-action" data-id="${K.esc(r.id)}">
            <div class="d-flex justify-content-between gap-2">
              <span class="${r.unread ? 'fw-bold' : ''}">${r.unread ? '<i class="fa-solid fa-circle text-primary me-1" style="font-size:.5rem;vertical-align:middle"></i>' : ''}${K.esc(r.subject)}</span>
              <span class="text-muted small text-nowrap">${K.esc(when(r.last_at))}</span>
            </div>
            <div class="small text-muted">With ${K.esc((r.with || []).join(', '))}</div>
            <div class="small ${r.unread ? 'fw-semibold' : 'text-muted'}">${K.esc(r.last_sender || '')}: ${K.esc(r.preview)}</div>
          </button>`).join('') + '</div>';
      } catch (e) {
        list.innerHTML = K.alert(e.friendly);
      }
    }

    refreshList = load;
    list.addEventListener('click', e => {
      const item = e.target.closest('[data-id]');
      if (item) openThread(item.dataset.id);
    });
    box.querySelectorAll('[data-f]').forEach(b => b.addEventListener('click', () => {
      folder = b.dataset.f;
      box.querySelectorAll('[data-f]').forEach(x => x.classList.toggle('active', x === b));
      load();
    }));
    box.querySelector('#msgNew').addEventListener('click', () => K.composeMessage());

    load();
  };
})(window.KFHI);