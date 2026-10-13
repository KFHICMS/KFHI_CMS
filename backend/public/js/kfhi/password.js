/* KFHI CMS – forced password-change screen */
(function (K) {
  K.forcePasswordChange = function (root, onDone, onCancel) {
    root.innerHTML = `
      <div class="container"><div class="card login-card"><div class="card-body p-4">
        <h1 class="h4 fw-bold text-center mb-1">Set a new password</h1>
        <p class="text-muted text-center mb-4">Your account uses a temporary password. Choose your own to continue.</p>
        <div id="pwErr"></div>
        <form id="pwForm" novalidate>
          <div class="mb-3"><label class="form-label" for="curPw">Temporary / current password</label>
            <input id="curPw" type="password" class="form-control" autocomplete="current-password" required autofocus></div>
          <div class="mb-3"><label class="form-label" for="newPw">New password</label>
            <input id="newPw" type="password" class="form-control" autocomplete="new-password" required>
            <div class="form-text">At least 8 characters, with upper and lower case letters and a number.</div></div>
          <div class="mb-3"><label class="form-label" for="newPw2">Confirm new password</label>
            <input id="newPw2" type="password" class="form-control" autocomplete="new-password" required></div>
          <button class="btn btn-primary w-100 py-2" id="pwBtn">Change password</button>
          <button type="button" class="btn btn-link w-100 mt-2" id="pwLogout">Log out</button>
        </form>
      </div></div></div>`;

    const $ = sel => root.querySelector(sel);
    $('#pwLogout').addEventListener('click', onCancel);

    $('#pwForm').addEventListener('submit', async e => {
      e.preventDefault();
      const btn = $('#pwBtn');
      $('#pwErr').innerHTML = '';
      if ($('#newPw').value !== $('#newPw2').value) {
        $('#pwErr').innerHTML = K.alert('The new passwords do not match.');
        return;
      }
      btn.disabled = true; btn.textContent = 'Saving…';
      try {
        await K.api('POST', '/change-password', {
          current_password: $('#curPw').value,
          password: $('#newPw').value,
          password_confirmation: $('#newPw2').value,
        });
        K.toast('Password changed.', 'success');
        onDone();
      } catch (ex) {
        $('#pwErr').innerHTML = K.alert(ex.friendly);
        btn.disabled = false; btn.textContent = 'Change password';
      }
    });
  };
})(window.KFHI);