/* KFHI CMS – child support categories (Education / Health / Nutrition / Shelter) */
(function (K) {
  K.CATEGORIES = [
    ['education', 'Education'], ['health', 'Health'],
    ['nutrition', 'Nutrition'], ['shelter', 'Shelter'],
  ];

  K.mountCategoryEditor = async function (box, child) {
    box.innerHTML = '<div class="text-muted small">Loading categories…</div>';
    let selected = [];
    try {
      selected = (await K.api('GET', `/children/${child.id}/categories`)).categories || [];
    } catch (e) {
      box.innerHTML = '';
      return;
    }

    const canEdit = K.can('edit_child') && child.status !== 'archived';
    box.innerHTML = `
      <div class="text-muted small mb-1">Support categories</div>
      <div class="d-flex flex-wrap gap-3">
        ${K.CATEGORIES.map(([key, label]) => `
          <div class="form-check">
            <input class="form-check-input cat-box" type="checkbox" id="cat_${K.esc(key)}" value="${K.esc(key)}"
              ${selected.includes(key) ? 'checked' : ''} ${canEdit ? '' : 'disabled'}>
            <label class="form-check-label" for="cat_${K.esc(key)}">${K.esc(label)}</label>
          </div>`).join('')}
      </div>`;

    if (!canEdit) return;
    box.querySelectorAll('.cat-box').forEach(cb => cb.addEventListener('change', async () => {
      const categories = [...box.querySelectorAll('.cat-box:checked')].map(x => x.value);
      try {
        await K.api('PUT', `/children/${child.id}/categories`, { categories });
        K.toast('Categories saved.', 'success');
      } catch (e) {
        cb.checked = !cb.checked;
        K.toast(e.friendly, 'danger');
      }
    }));
  };
})(window.KFHI);