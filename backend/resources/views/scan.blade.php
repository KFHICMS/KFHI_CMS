<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KFHI CMS — Scan</title>
    <style>
        * { box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { margin: 0; background: #f1f5f9; color: #1e293b; }
        .wrap { max-width: 460px; margin: 0 auto; padding: 20px; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.08); padding: 24px; margin-top: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 14px; }
        label { display: block; font-size: 13px; margin: 14px 0 4px; font-weight: 600; }
        input { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
        button { width: 100%; margin-top: 18px; padding: 12px; border: 0; border-radius: 8px;
                 background: #2563eb; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
        button.secondary { background: #e2e8f0; color: #334155; margin-top: 10px; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .row .k { color: #64748b; font-size: 13px; }
        .row .v { font-weight: 600; text-align: right; }
        .err { color: #dc2626; font-size: 14px; margin-top: 10px; }
        .badge { display:inline-block; background:#dbeafe; color:#1e40af; padding:3px 10px; border-radius:999px; font-size:12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>KFHI Child Management</h1>
        <div class="muted">Secure QR identification</div>

        <!-- LOGIN -->
        <div id="loginBox" style="display:none;">
            <label>Email</label>
            <input id="email" type="email" placeholder="you@kfhi.test">
            <label>Password</label>
            <input id="password" type="password" placeholder="••••••••">
            <button onclick="login()">Log in</button>
            <div id="loginErr" class="err"></div>
        </div>

        <!-- CHILD RESULT -->
        <div id="childBox" style="display:none;">
            <div id="who" class="muted" style="margin:10px 0;"></div>
            <div id="childData"></div>
            <button class="secondary" onclick="logout()">Log out</button>
        </div>

        <!-- MESSAGE -->
        <div id="msg" class="muted" style="margin-top:14px;"></div>
    </div>
</div>

<script>
const API   = window.location.origin;                       // same server
const token = new URLSearchParams(location.search).get('t'); // the QR token
const KEY   = 'kfhi_token';

function authToken() { return localStorage.getItem(KEY); }

async function init() {
    if (!token) { msg('No QR token in the link. Scan a valid child QR code.'); return; }
    if (authToken()) { resolve(); } else { showLogin(); }
}

function showLogin() {
    document.getElementById('loginBox').style.display = 'block';
    document.getElementById('childBox').style.display = 'none';
    msg('Please log in to view this child.');
}

async function login() {
    document.getElementById('loginErr').textContent = '';
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const res = await fetch(`${API}/api/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    const data = await res.json();
    if (!res.ok) { document.getElementById('loginErr').textContent = data.message || 'Login failed'; return; }
    localStorage.setItem(KEY, data.token);
    resolve();
}

async function resolve() {
    msg('Loading child…');
    const res = await fetch(`${API}/api/qr/resolve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer ' + authToken()
        },
        body: JSON.stringify({ token })
    });
    if (res.status === 401) { localStorage.removeItem(KEY); showLogin(); return; }
    const data = await res.json();
    if (!res.ok) { msg(data.message || 'Could not resolve QR.'); return; }
    renderChild(data);
}

function renderChild(c) {
    document.getElementById('loginBox').style.display = 'none';
    document.getElementById('childBox').style.display = 'block';
    msg('');
    let html = '';
    const show = (k, v) => v ? `<div class="row"><span class="k">${esc(k)}</span><span class="v">${esc(v)}</span></div>` : '';
    html += show('Child code', c.child_code);
    html += show('Name', c.full_name);
    html += show('Date of birth', c.date_of_birth ? c.date_of_birth.substring(0,10) : '');
    html += show('Gender', c.gender);
    html += show('Program', c.program);
    html += show('Medical info', c.medical_info);          // only shown if role permits
    html += show('Emergency contacts', c.emergency_contacts); // only shown if role permits
    if (c.guardians && c.guardians.length) {
               html += `<div class="row"><span class="k">Guardian</span><span class="v">${esc(c.guardians[0].name)} (${esc(c.guardians[0].relationship||'')})</span></div>`;
    }
    document.getElementById('childData').innerHTML = html;
}

const esc = s => String(s ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
function msg(t) { document.getElementById('msg').textContent = t; }
function logout() { localStorage.removeItem(KEY); location.reload(); }

init();
</script>
</body>
</html>
