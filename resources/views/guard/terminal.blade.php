@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="{{ route('guard.dashboard') }}"><i class="bi bi-qr-code-scan"></i> QRode Guard Terminal</a>
    <div>
      <a href="{{ route('guard.dashboard') }}" class="btn btn-sm btn-outline-light">Dashboard</a>
      <a href="{{ route('staff.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-3">

  {{-- Gate selector --}}
  <form method="post" action="{{ route('guard.terminal.selectGate') }}" class="d-flex flex-wrap align-items-center gap-2 mb-3">
    @csrf
    <span class="fw-semibold me-1">Gate:</span>
    @foreach($gates as $g)
      <button type="submit" name="gate_id" value="{{ $g->id }}"
        class="btn btn-sm {{ $g->id === $gate->id ? 'btn-qrode' : 'btn-outline-secondary' }}">
        <i class="bi bi-signpost-split"></i> {{ $g->name }}
      </button>
    @endforeach
    <span class="text-muted small ms-2">Showing <strong>{{ $gate->name }}</strong> only</span>
  </form>

  <div class="row g-3">
    {{-- Scanner --}}
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <h5 class="mb-3">Scan QR Pass</h5>

          <div class="btn-group mb-3" role="group">
            <button class="btn btn-outline-secondary active" id="modeIn" type="button"><i class="bi bi-box-arrow-in-right"></i> Entrance</button>
            <button class="btn btn-outline-secondary" id="modeOut" type="button"><i class="bi bi-box-arrow-right"></i> Exit</button>
          </div>

          <div id="reader" style="max-width:380px;margin:0 auto;"></div>
          <button class="btn btn-sm btn-outline-secondary mt-3" id="startCamBtn" type="button">Start Camera</button>

          <div class="input-group mt-3">
            <input type="text" class="form-control" id="manualToken" placeholder="Or paste QR token manually">
            <button class="btn btn-qrode" id="manualVerifyBtn" type="button">Verify</button>
          </div>

          <div id="resultBox" class="mt-3"></div>
        </div>
      </div>
    </div>

    {{-- Daily visitor log --}}
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Today's Visitors &mdash; {{ $gate->name }}</h5>
            <span class="small text-muted" id="logMeta">Loading&hellip;</span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr><th>Time</th><th>Visitor</th><th>Dir.</th><th>Result</th><th>Purpose</th><th>Phone</th><th class="text-end"></th></tr>
              </thead>
              <tbody id="logBody">
                <tr><td colspan="7" class="text-center text-muted py-3">Loading&hellip;</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Visitor Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="editError"></div>
        <p class="fw-semibold mb-3" id="editName"></p>
        <div class="mb-3" id="editEmailGroup">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="editEmail">
        </div>
        <div class="mb-3" id="editPhoneGroup">
          <label class="form-label">Phone</label>
          <input type="text" class="form-control" id="editPhone">
        </div>
        <div class="mb-3">
          <label class="form-label">Purpose of Visit</label>
          <input type="text" class="form-control" id="editPurpose" list="purposeOptions">
          <datalist id="purposeOptions">
            <option value="General inquiry">
            <option value="School information inquiry">
            <option value="Campus visit">
            <option value="Appointment with school personnel">
            <option value="Meeting with faculty/staff">
            <option value="Document processing">
            <option value="School-related transaction">
            <option value="Official business">
            <option value="School event attendance">
            <option value="Visitor / guest">
          </datalist>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-qrode" id="editSaveBtn">Save changes</button>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const VERIFY_URL = @json(route('guard.scan.verify'));
  const LOG_URL = @json(route('guard.terminal.log'));
  const UPDATE_BASE = @json(url('/guard/terminal/records'));
  const CSRF = @json(csrf_token());

  let mode = 'in';
  let html5QrCode = null;
  let scanning = false;
  let lastToken = null;
  let lastDecodeTime = 0;
  let rowsByKey = {};
  let editing = null;

  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));

  // ----- entrance / exit toggle -----
  function setMode(m) {
    mode = m;
    document.getElementById('modeIn').classList.toggle('active', m === 'in');
    document.getElementById('modeOut').classList.toggle('active', m === 'out');
  }
  document.getElementById('modeIn').addEventListener('click', () => setMode('in'));
  document.getElementById('modeOut').addEventListener('click', () => setMode('out'));

  // ----- scanner -----
  document.getElementById('startCamBtn').addEventListener('click', () => {
    if (scanning) return;
    html5QrCode = new Html5Qrcode('reader');
    html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      (decodedText) => {
        // The camera fires on every frame the QR is visible: ignore repeats
        // and pause until the guard taps "Scan Next".
        const now = Date.now();
        if (decodedText === lastToken && now - lastDecodeTime < 3000) return;
        lastToken = decodedText;
        lastDecodeTime = now;
        if (html5QrCode.getState() === Html5QrcodeScannerState.SCANNING) html5QrCode.pause(true);
        verify(decodedText);
      },
      () => {}
    ).then(() => { scanning = true; }).catch(() => {
      document.getElementById('resultBox').innerHTML =
        '<div class="alert alert-warning mb-0">Camera unavailable — use manual token entry.</div>';
    });
  });

  document.getElementById('manualVerifyBtn').addEventListener('click', () => {
    verify(document.getElementById('manualToken').value.trim());
  });

  async function verify(token) {
    if (!token) return;
    try {
      const res = await fetch(VERIFY_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ token, mode }),
      });
      renderResult(await res.json());
      document.getElementById('manualToken').value = '';
      loadLog();
    } catch (e) {
      renderResult({ alert: 'red', title: 'ERROR', message: 'Could not reach the server.' });
    }
  }

  function renderResult(d) {
    // Green = access granted, Red = blacklisted / denied / invalid.
    const palette = {
      green: { cls: 'alert-granted', icon: 'bi-check-circle-fill' },
      red:   { cls: 'alert-denied',  icon: 'bi-x-octagon-fill' },
      grey:  { cls: 'alert-info-qr', icon: 'bi-clock-history' },
    }[d.alert] || { cls: 'alert-denied', icon: 'bi-x-octagon-fill' };

    const photo = d.photo
      ? `<img src="${esc(d.photo)}" class="rounded border border-light" style="width:96px;height:96px;object-fit:cover;">`
      : '';

    document.getElementById('resultBox').innerHTML = `
      <div class="qr-alert ${palette.cls}">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="bi ${palette.icon} fs-2"></i>
          <span class="fs-5 fw-bold">${esc(d.title)}</span>
        </div>
        ${d.name ? `<div class="d-flex gap-3 align-items-start">${photo}
          <div><div class="fw-semibold">${esc(d.name)}</div><div class="small">${esc(d.email)}</div></div></div>` : ''}
        ${d.reason ? `<div class="small mt-2">Reason: ${esc(d.reason)}</div>` : ''}
        ${d.status === 'cooldown' ? `<div class="small mt-2">Try again in ${esc(d.remainingSeconds)}s.</div>` : ''}
        ${!d.name && d.message ? `<div class="small">${esc(d.message)}</div>` : ''}
        ${d.time ? `<div class="small mt-2 opacity-75">${d.mode === 'in' ? 'Time-in' : 'Time-out'}: ${esc(d.time)}</div>` : ''}
      </div>
      <button class="btn btn-sm btn-outline-secondary mt-2" id="scanNextBtn" type="button">
        <i class="bi bi-arrow-repeat"></i> Scan Next
      </button>`;

    document.getElementById('scanNextBtn').addEventListener('click', () => {
      if (html5QrCode && scanning) html5QrCode.resume();
      document.getElementById('resultBox').innerHTML = '';
    });
  }

  // ----- daily log (polls every 5s) -----
  async function loadLog() {
    try {
      const res = await fetch(LOG_URL, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('bad response');
      const data = await res.json();
      renderLog(data);
    } catch (e) {
      document.getElementById('logMeta').textContent = 'Could not refresh';
    }
  }

  function badge(status) {
    if (status === 'valid') return '<span class="badge badge-valid">Granted</span>';
    if (status === 'blacklisted') return '<span class="badge badge-blacklisted">Blacklisted</span>';
    return '<span class="badge badge-denied">Denied</span>';
  }

  function renderLog(data) {
    rowsByKey = {};
    const body = document.getElementById('logBody');

    document.getElementById('logMeta').textContent =
      `${data.rows.length} scan(s) · ${data.date} · updates every 5s`;

    if (data.rows.length === 0) {
      body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No entries at this gate yet today.</td></tr>';
      return;
    }

    body.innerHTML = data.rows.map((r, i) => {
      const key = `${r.type}-${r.record_id}`;
      rowsByKey[key] = r;
      const dir = r.direction === 'in'
        ? '<span class="badge badge-in">IN</span>'
        : '<span class="badge badge-out">OUT</span>';
      const photo = r.photo
        ? `<img src="${esc(r.photo)}" class="rounded me-2" style="width:28px;height:28px;object-fit:cover;">` : '';
      return `<tr>
        <td class="small text-muted">${esc(r.time)}</td>
        <td>${photo}${esc(r.name)}${r.type === 'walk_in' ? ' <span class="badge badge-neutral">walk-in</span>' : ''}</td>
        <td>${dir}</td>
        <td>${badge(r.status)}</td>
        <td class="small">${esc(r.purpose || '—')}</td>
        <td class="small">${esc(r.phone || '—')}</td>
        <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-edit="${esc(key)}" type="button">Edit</button></td>
      </tr>`;
    }).join('');
  }

  document.getElementById('logBody').addEventListener('click', (e) => {
    const btn = e.target.closest('[data-edit]');
    if (btn) openEdit(btn.dataset.edit);
  });

  // ----- edit modal -----
  const modal = new bootstrap.Modal(document.getElementById('editModal'));

  function openEdit(key) {
    const r = rowsByKey[key];
    if (!r) return;
    editing = r;
    document.getElementById('editError').classList.add('d-none');
    document.getElementById('editName').textContent = r.name + (r.type === 'walk_in' ? ' (walk-in)' : '');
    document.getElementById('editEmail').value = r.email || '';
    document.getElementById('editPhone').value = r.phone || '';
    document.getElementById('editPurpose').value = r.purpose || '';
    // Walk-ins only have a purpose to edit.
    document.getElementById('editEmailGroup').classList.toggle('d-none', r.type === 'walk_in');
    document.getElementById('editPhoneGroup').classList.toggle('d-none', r.type === 'walk_in');
    modal.show();
  }

  document.getElementById('editSaveBtn').addEventListener('click', async () => {
    if (!editing) return;
    const errBox = document.getElementById('editError');
    errBox.classList.add('d-none');

    const payload = { purpose: document.getElementById('editPurpose').value };
    if (editing.type === 'visitor') {
      payload.email = document.getElementById('editEmail').value;
      payload.phone = document.getElementById('editPhone').value;
    }

    const res = await fetch(`${UPDATE_BASE}/${editing.type}/${editing.record_id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify(payload),
    });

    if (res.ok) {
      modal.hide();
      loadLog();
      return;
    }

    const body = await res.json().catch(() => ({}));
    const messages = body.errors ? Object.values(body.errors).flat().join(' ') : (body.message || 'Could not save.');
    errBox.textContent = messages;
    errBox.classList.remove('d-none');
  });

  loadLog();
  setInterval(loadLog, 5000);
});
</script>

@include('partials.foot')
