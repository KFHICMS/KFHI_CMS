/* KFHI CMS – child ID card: preview + NIC-size PDF (85.60 x 53.98 mm, landscape), built in the browser.
   Needs public/js/vendor/jspdf.umd.min.js and public/js/vendor/qrcode.js (loaded on first use). */
(function (K) {
  const W = 85.6, H = 53.98;                       // ISO/IEC 7810 ID-1 ("NIC size")
  const GREEN = [10, 90, 51], DARK = [15, 61, 38], MUTED = [79, 111, 95], WHITE = [255, 255, 255];

  // What is printed on the BACK of the card. Edit this list to change it.
  // (Religion, service state and sponsor state are NOT printed: private if a card is lost.)
  const BACK_ROWS = [
    ['Office',        c => [c.office_name, c.office_code && `(${c.office_code})`].filter(Boolean).join(' ')],
    ['Guardian type', c => c.guardian_type],
    ['Caregiver',     c => c.caregiver],
    ['Curriculum',    c => c.curriculum],
  ];

  // The built-in PDF font only holds Latin characters: anything else would print as garbage.
  const latin = s => String(s == null ? '' : s).replace(/[^\u0000-\u00FF]/g, '?');

  function drawQr(doc, qrcode, text, x, y, size) {
    const qr = qrcode(0, 'M');                     // auto size, medium error correction
    qr.addData(text);
    qr.make();
    const n = qr.getModuleCount(), quiet = 2, unit = size / (n + quiet * 2);
    doc.setFillColor(...WHITE);
    doc.rect(x, y, size, size, 'F');
    doc.setFillColor(0, 0, 0);
    for (let r = 0; r < n; r++) {
      for (let c = 0; c < n; c++) {
        if (qr.isDark(r, c)) doc.rect(x + (c + quiet) * unit, y + (r + quiet) * unit, unit + 0.015, unit + 0.015, 'F');
      }
    }
    return n;
  }

  function frame(doc, rightLabel) {
    doc.setFillColor(...WHITE); doc.setDrawColor(...GREEN); doc.setLineWidth(0.3);
    doc.roundedRect(0.2, 0.2, W - 0.4, H - 0.4, 3, 3, 'FD');
    doc.setFillColor(...GREEN);
    doc.roundedRect(0.2, 0.2, W - 0.4, 10, 3, 3, 'F');
    doc.rect(0.2, 5, W - 0.4, 5.2, 'F');           // square off the band's bottom corners
    doc.setTextColor(...WHITE); doc.setFont('helvetica', 'bold');
    doc.setFontSize(10); doc.text('KFHI', 4, 6.9);
    doc.setFontSize(5.5); doc.text(rightLabel, W - 4, 6.6, { align: 'right' });
  }

  const label = (doc, t, x, y) => { doc.setFont('helvetica', 'normal'); doc.setFontSize(4.5); doc.setTextColor(...MUTED); doc.text(t, x, y); };

  /** Builds the 2-page PDF (front, back). Libraries are passed in so this can run anywhere. */
  function buildIdCardPdf({ jsPDF, qrcode, card, scanUrl, photo }) {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: [W, H], compress: true });
    doc.setProperties({ title: `ID card ${card.child_code}`, subject: 'KFHI child identity card' });

    // ---------- front ----------
    frame(doc, 'CHILD IDENTITY CARD');

    if (photo) {
      doc.addImage(photo.dataUrl, photo.format, 4, 13, 22, 28);
    } else {
      doc.setFillColor(222, 233, 226); doc.rect(4, 13, 22, 28, 'F');
      doc.setTextColor(...MUTED); doc.setFont('helvetica', 'bold'); doc.setFontSize(14);
      doc.text(latin(card.full_name).trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase(), 15, 28, { align: 'center' });
    }

    drawQr(doc, qrcode, scanUrl, W - 4 - 24, 12.5, 24);

    label(doc, 'NAME', 29, 15.5);
    doc.setFont('helvetica', 'bold'); doc.setFontSize(8.5); doc.setTextColor(...DARK);
    doc.text(doc.splitTextToSize(latin(card.full_name), 26).slice(0, 2), 29, 19, { lineHeightFactor: 1.15 });

    label(doc, 'CHILD CODE', 29, 30.5);
    doc.setFont('helvetica', 'bold'); doc.setFontSize(8); doc.setTextColor(...DARK);
    doc.text(latin(card.child_code), 29, 34);

    doc.setDrawColor(207, 227, 214); doc.setLineWidth(0.2); doc.line(4, 43.5, W - 4, 43.5);
    doc.setFont('helvetica', 'normal'); doc.setFontSize(6); doc.setTextColor(...DARK);
    const l1 = [card.gnd && `GND: ${card.gnd}`, card.area && `Area: ${card.area}`].filter(Boolean).join('    ');
    const l2 = [card.date_of_birth && `DOB: ${card.date_of_birth}${card.age != null ? ` (age ${card.age})` : ''}`, card.gender].filter(Boolean).join('    ');
    if (l1) doc.text(doc.splitTextToSize(latin(l1), W - 8)[0], 4, 47);
    if (l2) doc.text(doc.splitTextToSize(latin(l2), W - 8)[0], 4, 50.6);

    // ---------- back ----------
    doc.addPage([W, H], 'landscape');
    frame(doc, card.child_code ? latin(card.child_code) : '');
    let y = 16;
    BACK_ROWS.forEach(([name, get]) => {
      const v = get(card);
      if (!v) return;
      label(doc, name.toUpperCase(), 4, y);
      doc.setFont('helvetica', 'bold'); doc.setFontSize(7); doc.setTextColor(...DARK);
      doc.text(doc.splitTextToSize(latin(v), W - 8)[0], 4, y + 3.4);
      y += 8;
    });
    doc.setFont('helvetica', 'normal'); doc.setFontSize(5); doc.setTextColor(...MUTED);
    doc.text(doc.splitTextToSize('Property of KFHI. Scan the QR code with an authorised KFHI account to verify this card. If found, please return it to the nearest KFHI office.', W - 8), 4, H - 6);

    return doc;
  }
  K.buildIdCardPdf = buildIdCardPdf;

  // =====================  browser part: modal, preview, download  =====================
  const VENDOR = K.base.replace(/\/api\/?$/, '/') + 'js/vendor/';
  const scripts = {};
  const loadScript = src => scripts[src] || (scripts[src] = new Promise((ok, fail) => {
    const s = document.createElement('script');
    s.src = src; s.onload = ok; s.onerror = () => { delete scripts[src]; fail(new Error('Could not load ' + src)); };
    document.head.appendChild(s);
  }));
  const loadLibs = () => Promise.all([loadScript(VENDOR + 'jspdf.umd.min.js'), loadScript(VENDOR + 'qrcode.js')])
    .then(() => ({ jsPDF: window.jspdf.jsPDF, qrcode: window.qrcode }));

  /** Centre-crops the photo to the 22 x 28 mm frame so it is never stretched. */
  async function photoData(blob) {
    const bmp = await createImageBitmap(blob);
    const target = 22 / 28;
    let sw = bmp.width, sh = bmp.height;
    if (sw / sh > target) sw = sh * target; else sh = sw / target;
    const c = document.createElement('canvas');
    c.width = 330; c.height = 420;
    c.getContext('2d').drawImage(bmp, (bmp.width - sw) / 2, (bmp.height - sh) / 2, sw, sh, 0, 0, c.width, c.height);
    return { dataUrl: c.toDataURL('image/jpeg', 0.92), format: 'JPEG' };
  }

  let ui = null, pdfUrl = null;
  function ensureUi() {
    if (ui) return ui;
    const el = document.createElement('div');
    el.className = 'modal fade'; el.tabIndex = -1;
    el.innerHTML = `<div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">ID card</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"></div>
      <div class="modal-footer justify-content-between"></div></div></div>`;
    document.body.appendChild(el);
    el.addEventListener('hidden.bs.modal', () => { if (pdfUrl) { URL.revokeObjectURL(pdfUrl); pdfUrl = null; } });
    ui = { el, modal: new bootstrap.Modal(el), body: el.querySelector('.modal-body'), foot: el.querySelector('.modal-footer') };
    return ui;
  }

  K.openIdCard = async function (child) {
    const u = ensureUi();
    u.body.innerHTML = K.spinner(); u.foot.innerHTML = '';
    u.el.querySelector('.modal-title').textContent = `ID card – ${child.full_name}`;
    u.modal.show();

    const generate = async regenerate => {
      if (regenerate && !window.confirm('Regenerate the QR code? Any card already printed with the old QR code will stop working.')) return;
      try {
        await K.api('POST', `/children/${child.id}/qr`);
        K.toast('QR code generated.', 'success');
        K.openIdCard(child);
      } catch (e) { K.toast(e.friendly, 'danger'); }
    };

    try {
      const data = await K.api('GET', `/children/${child.id}/id-card`);

      if (!data.qr) {
        u.body.innerHTML = '<p class="mb-0">This child has no QR code yet. Generate one to create the ID card.</p>';
        u.foot.innerHTML = '<span></span><div class="d-flex gap-2"><button class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary" id="idGen">Generate QR code</button></div>';
        u.foot.querySelector('#idGen').onclick = () => generate(false);
        return;
      }

      const { jsPDF, qrcode } = await loadLibs();
      let photo = null;
      if (data.card.has_photo) { try { photo = await photoData(await K.apiBlob(`/children/${child.id}/photo`)); } catch (e) { /* card is made without a photo */ } }

      const doc = buildIdCardPdf({ jsPDF, qrcode, card: data.card, scanUrl: data.qr.scan_url, photo });
      if (pdfUrl) URL.revokeObjectURL(pdfUrl);
      pdfUrl = URL.createObjectURL(doc.output('blob'));

      u.body.innerHTML = `<iframe title="ID card preview" src="${pdfUrl}#toolbar=0&navpanes=0&view=FitH" style="width:100%;height:360px;border:0;border-radius:12px;background:#fff"></iframe>
        <p class="text-muted small mt-2 mb-0">Page 1 is the front, page 2 the back. Print at 100% (actual size) on card stock: 85.60 × 53.98 mm.
        If the preview does not show on your device, use Download.</p>`;
      u.foot.innerHTML = `<button class="btn btn-outline-danger" id="idRegen"><i class="fa-solid fa-rotate"></i> Regenerate QR</button>
        <div class="d-flex gap-2"><button class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="idDownload"><i class="fa-solid fa-download"></i> Download PDF</button></div>`;
      u.foot.querySelector('#idRegen').onclick = () => generate(true);
      u.foot.querySelector('#idDownload').onclick = () => doc.save(`${data.card.child_code}.pdf`);
    } catch (e) {
      u.body.innerHTML = K.alert(e.friendly || e.message);
    }
  };
})(window.KFHI);