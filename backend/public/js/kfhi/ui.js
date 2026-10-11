/* KFHI CMS – small UI helpers. ALWAYS pass user/server data through K.esc() before using innerHTML. */
(function (K) {
  const MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  K.esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => MAP[c]);

  K.date = v => (v ? String(v).substring(0, 10) : '');

  K.debounce = (fn, ms = 300) => {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  };

  /** type: success | danger | warning | info */
  K.toast = (message, type = 'info') => {
    const box = document.getElementById('toasts');
    if (!box) return;
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type} border-0`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${K.esc(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    box.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 4000 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
  };

  K.spinner = () => '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

  K.alert = (message, type = 'danger') =>
    `<div class="alert alert-${type} mb-3">${K.esc(message)}</div>`;
})(window.KFHI);