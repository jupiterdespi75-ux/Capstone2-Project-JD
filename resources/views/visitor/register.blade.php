@include('partials.head', ['title' => $title])
<script src="{{ asset('js/camera-capture.js') }}"></script>
<nav class="navbar navbar-qrode"><div class="container"><a class="navbar-brand" href="{{ route('home') }}"><i class="bi bi-qr-code-scan"></i> QRode</a></div></nav>

<div class="container py-4" style="max-width:640px;">
  <h4 class="mb-1">Visitor Registration</h4>
  <p class="text-muted small">Complete all five steps to submit your registration for Admin review.</p>

  <div class="d-flex justify-content-between mb-4" id="stepDots">
    <span class="step-dot active" data-step="1">1</span>
    <span class="step-dot" data-step="2">2</span>
    <span class="step-dot" data-step="3">3</span>
    <span class="step-dot" data-step="4">4</span>
  </div>

  @if($error)
    <div class="alert alert-danger">{{ $error }}</div>
  @endif

  <form id="regForm" method="post" action="{{ route('visitor.register') }}" enctype="multipart/form-data">
    @csrf
    <!-- Step 1: Details -->
    <div class="wizard-step" data-step="1">
      <h6 class="text-muted mb-3">Step 1 &middot; Details</h6>
      <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" name="name" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
      <div class="mb-3"><label class="form-label">Phone Number</label><input class="form-control" name="phone"></div>
      <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required minlength="6"></div>
      @include('partials.purpose_field')
      <button type="button" class="btn btn-qrode" onclick="goStep(2)">Next</button>
    </div>

    <!-- Step 2: Biometric (selfie) -->
    <div class="wizard-step d-none" data-step="2">
      <h6 class="text-muted mb-3">Step 2 &middot; Biometric Verification</h6>
      <p class="small text-muted">Take a selfie. It will be checked automatically for possible duplicate registrations.</p>
      @include('partials.camera_capture', [
        'key' => 'selfie',
        'fieldName' => 'selfie',
        'label' => 'Selfie',
        'hint' => '',
        'facingMode' => 'user',
      ])
      <button type="button" class="btn btn-outline-secondary" onclick="goStep(1)">Back</button>
      <button type="button" class="btn btn-qrode" onclick="goStep(3)">Next</button>
    </div>

    <!-- Step 3: ID Card -->
    <div class="wizard-step d-none" data-step="3">
      <h6 class="text-muted mb-3">Step 3 &middot; ID Card</h6>
      @include('partials.camera_capture', [
        'key' => 'idcard',
        'fieldName' => 'id_card',
        'label' => 'ID Card',
        'hint' => 'Capture or upload a photo of a valid ID.',
        'facingMode' => 'environment',
        'required' => true,
      ])
      <button type="button" class="btn btn-outline-secondary" onclick="goStep(2)">Back</button>
      <button type="button" class="btn btn-qrode" onclick="reviewStep()">Next</button>
    </div>

    <!-- Step 4: Review -->
    <div class="wizard-step d-none" data-step="4">
      <h6 class="text-muted mb-3">Step 4 &middot; Review</h6>
      <div id="reviewBox" class="border rounded p-3 mb-3 small"></div>
      <button type="button" class="btn btn-outline-secondary" onclick="goStep(3)">Back</button>
      <button type="submit" class="btn btn-qrode">Submit Registration</button>
    </div>
  </form>
</div>

<script>
function goStep(n) {
  document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('d-none'));
  document.querySelector(`.wizard-step[data-step="${n}"]`).classList.remove('d-none');
  document.querySelectorAll('.step-dot').forEach(d => {
    const s = parseInt(d.dataset.step);
    d.classList.toggle('active', s === n);
    d.classList.toggle('done', s < n);
  });
}

function reviewStep() {
  const name = document.querySelector('[name=name]').value;
  const email = document.querySelector('[name=email]').value;
  const phone = document.querySelector('[name=phone]').value;
  const purpose = document.querySelector('[name=purpose]').value;
  const selfieOk = document.querySelector('[name=selfie]').files.length > 0;
  const idOk = document.querySelector('[name=id_card]').files.length > 0;
  document.getElementById('reviewBox').innerHTML = `
    <p><strong>Name:</strong> ${name}</p>
    <p><strong>Email:</strong> ${email}</p>
    <p><strong>Phone:</strong> ${phone || '&mdash;'}</p>
    <p><strong>Purpose:</strong> ${purpose || '&mdash;'}</p>
    <p><strong>Selfie captured:</strong> ${selfieOk ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'}</p>
    <p><strong>ID captured:</strong> ${idOk ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'}</p>
  `;
  goStep(4);
}
</script>

@include('partials.foot')
