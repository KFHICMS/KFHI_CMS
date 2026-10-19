/* KFHI CMS – monthly actions overview (dashboard) */
(function (K) {
  const tile = (label, value, hint) => `
    <div class="col-6 col-md-4">
      <div class="border rounded p-3 h-100">
        <div class="fs-3 fw-bold">${value === null ? '—' : K.esc(value)}</div>
        <div class="fw-semibold">${K.esc(label)}</div>
        <div class="text-muted small">${K.esc(hint)}</div>
      </div>
    </div>`;

  const shift = (ym, delta) => {
    const [y, m] = ym.split('-').map(Number);
    const d = new Date(y, m - 1 + delta, 1);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
  };

  K.mountMonthly = function (box) {
    let month = null;   // null = current month (the server decides)

    box.innerHTML = `
      <div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <h2 class="h5 mb-0">Monthly actions overview</h2>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" id="mPrev" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button>
            <button class="btn btn-outline-secondary disabled" id="mLabel" style="min-width:140px"></button>
            <button class="btn btn-outline-secondary" id="mNext" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button>
          </div>
        </div>
        <div id="mBody"></div>
      </div></div>`;

    const body = box.querySelector('#mBody');
    const label = box.querySelector('#mLabel');

    async function load() {
      body.innerHTML = K.spinner();
      try {
        const d = await K.api('GET', '/reports/monthly' + (month ? '?month=' + month : ''));
        month = d.month;
        label.textContent = d.label;

        const rate = d.completion_rate;
        body.innerHTML = `
          <div class="row g-3">
            ${tile('Children registered', d.children_registered, 'New child records this month')}
            ${tile('Programs created', d.programs_created, 'New programs this month')}
            ${tile('Programs updated', d.programs_updated, 'Existing programs changed')}
            ${tile('Events held', d.events_held, `of ${d.events_scheduled} scheduled this month`)}
            ${tile('Attendance records', d.attendance_records, 'Children marked present')}
            ${tile('Questionnaire submissions', d.questionnaire_submissions, 'Available once questionnaires are built')}
          </div>
          <div class="row g-3 mt-1">
            ${tile('Completed activities', d.activities.completed, 'Events that have finished')}
            ${tile('Outstanding activities', d.activities.outstanding, 'Upcoming or in progress')}
            <div class="col-12 col-md-4">
              <div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-1">Completion rate</div>
                ${rate === null
                  ? '<div class="text-muted small">No events scheduled this month.</div>'
                  : `<div class="progress" role="progressbar" aria-valuenow="${K.esc(rate)}" aria-valuemin="0" aria-valuemax="100">
                       <div class="progress-bar bg-success" style="width:${K.esc(rate)}%">${K.esc(rate)}%</div></div>`}
                <div class="text-muted small mt-1">Completed events ÷ events scheduled this month</div>
              </div>
            </div>
          </div>`;
      } catch (e) {
        body.innerHTML = K.alert(e.friendly);
      }
    }

    box.querySelector('#mPrev').addEventListener('click', () => { if (month) { month = shift(month, -1); load(); } });
    box.querySelector('#mNext').addEventListener('click', () => { if (month) { month = shift(month, 1); load(); } });

    load();
  };
})(window.KFHI);