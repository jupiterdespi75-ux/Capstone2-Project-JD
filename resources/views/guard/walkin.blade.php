@include('partials.head', ['title' => $title])
<script src="{{ asset('js/camera-capture.js') }}"></script>
<nav class="navbar navbar-qrode">
  <div class="container">
    <a class="navbar-brand" href="{{ route('guard.dashboard') }}"><i class="bi bi-qr-code-scan"></i> QRode Guard</a>
    <a href="{{ route('staff.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container py-4" style="max-width:520px;">
  <h4 class="mb-3">Register Walk-in Visitor</h4>
  @if($error)
    <div class="alert alert-danger">{{ $error }}</div>
  @endif
  @if($success)
    <div class="alert alert-success">{{ $success }}</div>
  @endif
  <form method="post" action="{{ route('guard.walkin.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
      <label class="form-label">Visitor Name</label>
      <input class="form-control" name="name" required>
    </div>
    @include('partials.purpose_field')

    @include('partials.camera_capture', [
      'key' => 'photo',
      'fieldName' => 'photo',
      'label' => 'Face Photo',
      'hint' => "Use the camera for a clear face photo, or upload one if the camera isn't available.",
      'facingMode' => 'user',
    ])

    @include('partials.camera_capture', [
      'key' => 'id',
      'fieldName' => 'id_card',
      'label' => 'ID Capture',
      'hint' => 'Use the camera to capture a valid ID, or upload a photo of it instead.',
      'facingMode' => 'environment',
    ])

    <button class="btn btn-qrode">Submit for Admin Approval</button>
  </form>
  <p class="text-muted small mt-3">Walk-ins require Admin approval before entry/exit can be logged.</p>
</div>
@include('partials.foot')
