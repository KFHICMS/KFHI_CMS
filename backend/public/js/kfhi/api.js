/* KFHI CMS – API client + session helpers (no framework, plain JS) */
window.KFHI = window.KFHI || {};
(function (K) {
  K.TOKEN_KEY = 'kfhi_token';        // shared with /scan page so a login carries over
  K.IDLE_MINUTES = 15;               // auto-logout after this much inactivity
  K.session = { user: null };
  K.pages = {};                      // page registry – modules add themselves here
  K.base = (document.querySelector('meta[name="api-base"]') || {}).content || '/api';

  K.getToken   = () => localStorage.getItem(K.TOKEN_KEY);
  K.setToken   = t  => localStorage.setItem(K.TOKEN_KEY, t);
  K.clearToken = () => localStorage.removeItem(K.TOKEN_KEY);
  K.can  = perm => ((K.session.user && K.session.user.permissions) || []).includes(perm);
  K.hasRole = role => ((K.session.user && K.session.user.roles) || []).includes(role);

  class ApiError extends Error {
    constructor(status, message, errors) {
      super(message || 'Request failed');
      this.status = status;
      this.errors = errors || null;
    }
    /** First validation message if present, else the main message. */
    get friendly() {
      if (this.errors) {
        const first = Object.values(this.errors)[0];
        if (first && first[0]) return first[0];
      }
      return this.message;
    }
  }
  K.ApiError = ApiError;

  async function request(method, path, body, asBlob) {
    const headers = { Accept: 'application/json' };
    const token = K.getToken();
    if (token) headers.Authorization = 'Bearer ' + token;

    let payload;
    if (body instanceof FormData) {
      payload = body;
    } else if (body !== undefined) {
      headers['Content-Type'] = 'application/json';
      payload = JSON.stringify(body);
    }

    let res;
    try {
      res = await fetch(K.base + path, { method, headers, body: payload });
    } catch (e) {
      throw new ApiError(0, 'Cannot reach the server. Check your connection.');
    }

    if (res.status === 401 && path !== '/login') {
      K.clearToken();
      window.dispatchEvent(new Event('kfhi:unauthorized'));
    }

    if (res.ok && asBlob) return res.blob();

    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (e) { /* non-JSON body */ }

    if (!res.ok) {
      throw new ApiError(res.status, (data && data.message) || res.statusText, data && data.errors);
    }
    return data;
  }

  K.api     = (method, path, body) => request(method, path, body, false);
  K.apiBlob = path => request('GET', path, undefined, true);
})(window.KFHI);