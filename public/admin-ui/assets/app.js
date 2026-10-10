// Shared tools for all admin pages.
const API = '/api';
const TOKEN_KEY = 'kfhi_token';
const IS_EMBEDDED = new URLSearchParams(location.search).get('embedded') === '1';

if (IS_EMBEDDED) document.body.classList.add('embedded');

function adminPageUrl(page, query = {}) {
  const url = new URL(page, location.href);
  for (const [key, value] of Object.entries(query)) url.searchParams.set(key, value);
  if (IS_EMBEDDED) url.searchParams.set('embedded', '1');
  return url.pathname + url.search + url.hash;
}

if (IS_EMBEDDED) {
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link) return;

    const url = new URL(link.href, location.href);
    if (url.origin === location.origin && url.pathname.startsWith('/admin-ui/')) {
      url.searchParams.set('embedded', '1');
      link.href = url.href;
    }
  }, true);
}

const getToken = () => sessionStorage.getItem(TOKEN_KEY);
const setToken = (t) => sessionStorage.setItem(TOKEN_KEY, t);
const clearToken = () => sessionStorage.removeItem(TOKEN_KEY);

class ApiError extends Error {
  constructor(message, status, errors) {
    super(message);
    this.status = status;
    this.errors = errors || null;   // field errors from validation (422)
  }
}

// One function for every API call. Adds the token, handles common errors.
async function api(path, { method = 'GET', body, form } = {}) {
  const headers = { Accept: 'application/json' };
  const token = getToken();
  if (token) headers.Authorization = 'Bearer ' + token;

  let payload;
  if (form) {
    payload = form;                                  // FormData (photo upload)
  } else if (body) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  let res;
  try {
    res = await fetch(API + path, { method, headers, body: payload });
  } catch {
    throw new ApiError('Cannot reach the server. Is it running?', 0);
  }

  let data = null;
  try { data = await res.json(); } catch { /* some answers have no body */ }

  // Token expired or invalid -> back to login.
  if (res.status === 401 && token) {
    clearToken();
    location.href = adminPageUrl('login.html', { expired: '1' });
    throw new ApiError('Session expired.', 401);
  }
  // Temporary password still active -> go to the change-password screen.
  if (res.status === 403 && data && data.code === 'password_change_required') {
    location.href = adminPageUrl('login.html', { change: '1' });
    throw new ApiError(data.message, 403);
  }
  if (!res.ok) {
    throw new ApiError((data && data.message) || 'Something went wrong.', res.status, data && data.errors);
  }
  return data;
}

// Turn an error into one readable sentence.
function errorText(err) {
  if (err && err.errors) return Object.values(err.errors).flat().join(' ');
  return (err && err.message) || 'Something went wrong.';
}

// Delete the token on the server and in the browser (no redirect).
async function endSession() {
  try { await api('/logout', { method: 'POST' }); } catch { /* ignore */ }
  clearToken();
}

async function logout() {
  await endSession();
  location.href = adminPageUrl('login.html');
}

// Every admin page calls this first. Returns the logged-in user, or sends to login.
async function requireAdmin() {
  if (!getToken()) { location.href = adminPageUrl('login.html'); return null; }
  const me = await api('/me');
  if (!me.permissions.includes('manage_users')) {
    await logout();
    return null;
  }
  return me;
}

// Small safe DOM builder: text is always added as text, never as HTML.
function el(tag, attrs = {}, ...children) {
  const node = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (k === 'class') node.className = v;
    else if (k.startsWith('on') && typeof v === 'function') node.addEventListener(k.slice(2), v);
    else if (v !== false && v != null) node.setAttribute(k, v);
  }
  for (const c of children.flat()) {
    if (c == null || c === false) continue;
    node.append(c instanceof Node ? c : document.createTextNode(String(c)));
  }
  return node;
}


// ---------- Helpers for staff pages ----------

const ROLES = {
  admin: 'Admin',
  child_officer: 'Child Officer',
  field_officer: 'Field Officer',
  survey_enumerator: 'Survey Enumerator',
};
const roleLabel = (r) => ROLES[r] || r || '—';

function formatDate(d) {            // "2022-01-15" -> "15/01/2022"
  if (!d) return '—';
  const [y, m, day] = d.split('-');
  return `${day}/${m}/${y}`;
}

function initials(name) {           // "Anna Perera" -> "AP"
  const parts = String(name || '?').trim().split(/\s+/);
  return ((parts[0][0] || '?') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
}

// Photos are private, so we fetch them WITH the token and show them as a blob.
const photoCache = new Map();
async function photoObjectUrl(photoUrl) {
  if (photoCache.has(photoUrl)) return photoCache.get(photoUrl);
  const u = new URL(photoUrl, location.origin);
  const res = await fetch(u.pathname + u.search, {
    headers: { Authorization: 'Bearer ' + getToken() },
  });
  if (!res.ok) throw new Error('photo');
  const objUrl = URL.createObjectURL(await res.blob());
  photoCache.set(photoUrl, objUrl);
  return objUrl;
}

// Circle with initials. If the user has a photo, it replaces the initials when loaded.
function avatar(user, size = 'md') {
  const box = el('div', { class: `avatar ${size}` }, initials(user.name));
  const url = user.staff_profile && user.staff_profile.photo_url;
  if (url) {
    photoObjectUrl(url)
      .then((src) => { box.textContent = ''; box.append(el('img', { src, alt: '' })); })
      .catch(() => {});
  }
  return box;
}