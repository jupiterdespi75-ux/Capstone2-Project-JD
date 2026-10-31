@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode">
  <div class="container">
    <a class="navbar-brand" href="{{ route('guard.dashboard') }}"><i class="bi bi-qr-code-scan"></i> QRode Guard</a>
    <a href="{{ route('staff.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container py-4 text-center" style="max-width:480px;">
  <h4 class="mb-1">Scan Visitor QR Code</h4>
  <p class="text-muted small mb-3">{{ $gate->name }}</p>

  <div class="btn-group mb-3" role="group">
    <button class="btn btn-outline-secondary active" id="modeIn" onclick="setMode('in')"><i class="bi bi-box-arrow-in-right"></i> Entrance</button>
    <button class="btn btn-outline-secondary" id="modeOut" onclick="setMode('out')"><i class="bi bi-box-arrow-right"></i> Exit</button>
  </div>

  <div id="reader"></div>

  <div class="mt-3">
    <button class="btn btn-sm btn-outline-secondary" onclick="startScanner()">Start Camera</button>
  </div>

  <div class="input-group mt-3">
    <input type="text" class="form-control" id="manualToken" placeholder="Or paste QR token manually">
    <button class="btn btn-qrode" onclick="verify(document.getElementById('manualToken').value)">Verify</button>
  </div>

  <div id="resultBox" class="mt-4"></div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
  const verifyUrl = @json(route('guard.scan.verify'));
  const csrfToken = @json(csrf_token());

  let mode = 'in';
  let html5QrCode;
  let scanning = false;
  let lastToken = null;
  let lastDecodeTime = 0;

  function setMode(m) {
    mode = m;
    document.getElementById('modeIn').classList.toggle('active', m === 'in');
    document.getElementById('modeOut').classList.toggle('active', m === 'out');
  }

  function startScanner() {
    if (scanning) return;
    html5QrCode = new Html5Qrcode('reader');
    html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      (decodedText) => {
        const now = Date.now();
        if (decodedText === lastToken && now - lastDecodeTime < 3000) return;
        lastToken = decodedText;
        lastDecodeTime = now;
        if (html5QrCode.getState() === Html5QrcodeScannerState.SCANNING) {
          html5QrCode.pause(true);
        }
        verify(decodedText);
      },
      () => {}
    ).then(() => { scanning = true; }).catch(() => {
      document.getElementById('resultBox').innerHTML =
        '<div class="alert alert-warning">Camera unavailable — use manual token entry below.</div>';
    });
  }

  function resumeScanner() {
    if (html5QrCode && scanning) html5QrCode.resume();
  }

  async function verify(token) {
    if (!token) return;
    const res = await fetch(verifyUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ token, mode }),
    });
    const data = await res.json();
    render(data);
  }

  function render(data) {
    const box = document.getElementById('resultBox');
    let cls = 'success', flash = 'scan-flash-valid', label = 'VALID';
    if (data.status === 'flagged') { cls = 'warning'; flash = 'scan-flash-alert'; label = 'FLAGGED'; }
    if (data.status === 'blacklisted') { cls = 'danger'; flash = 'scan-flash-alert'; label = 'BLACKLISTED'; }
    if (data.status === 'invalid') { cls = 'secondary'; flash = 'scan-flash-alert'; label = 'INVALID'; }
    if (data.status === 'cooldown') { cls = 'info'; flash = 'scan-flash-alert'; label = 'ALREADY SCANNED'; }

    const photoHtml = data.photo
      ? `<img src="${data.photo}" class="rounded border mb-2" style="width:90px;height:90px;object-fit:cover;">`
      : '';

    box.className = flash;
    box.innerHTML = `
      <div class="alert alert-${cls} text-start">
        <h5 class="mb-2">${label}</h5>
        ${data.name ? `<div class="d-flex gap-2 align-items-start mb-2">${photoHtml}
            <div><strong>${data.name}</strong><br><span class="small">${data.email || ''}</span></div>
          </div>` : ''}
        ${data.reason ? `<div class="small">Reason: ${data.reason}</div>` : ''}
        ${data.status === 'cooldown' ? `<div class="small">Try again in ${data.remainingSeconds}s.</div>` : ''}
        ${data.time ? `<div class="small text-muted">${data.mode === 'in' ? 'Time-in' : 'Time-out'}: ${data.time}</div>` : ''}
        ${!data.name ? `<div>${data.message || ''}</div>` : ''}
      </div>
      <button class="btn btn-sm btn-outline-secondary mt-1" onclick="resumeScanner()">
        <i class="bi bi-arrow-repeat"></i> Scan Next
      </button>`;
    document.getElementById('manualToken').value = '';
  }
</script>

@include('partials.foot')
