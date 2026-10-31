@php
  // Expected variables: $key (unique id, e.g. 'photo' / 'id'), $fieldName
  // (form field name), $label, $hint (optional), $facingMode
  // ('user' | 'environment'), $required (optional bool)
  $required = $required ?? false;
  $hint = $hint ?? null;
@endphp

<div class="capture-box mb-3">
  <label class="form-label fw-semibold">{{ $label }}</label>
  @if($hint)
    <p class="small text-muted mb-2">{{ $hint }}</p>
  @endif

  <div class="text-center mb-2">
    <video id="video-{{ $key }}" autoplay playsinline muted class="border rounded d-none" style="width:100%;max-width:300px;"></video>
    <canvas id="canvas-{{ $key }}" class="border rounded d-none" style="width:100%;max-width:300px;"></canvas>
  </div>

  <div class="d-flex gap-2 justify-content-center mb-2">
    <button type="button" class="btn btn-outline-secondary btn-sm" id="startCam-{{ $key }}">
      <i class="bi bi-camera"></i> Use Camera
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="snap-{{ $key }}">
      <i class="bi bi-circle"></i> Capture
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="retake-{{ $key }}">
      <i class="bi bi-arrow-repeat"></i> Retake
    </button>
  </div>
  <p class="small text-center mb-2 capture-status" id="status-{{ $key }}"></p>

  <label class="form-label small text-muted">...or upload a photo (backup if the camera isn't available)</label>
  <input
    type="file"
    accept="image/*"
    capture="{{ $facingMode === 'environment' ? 'environment' : 'user' }}"
    class="form-control"
    id="fileInput-{{ $key }}"
    name="{{ $fieldName }}"
    {{ $required ? 'required' : '' }}
  >
</div>

<script>
  initCameraCapture({ key: '{{ $key }}', facingMode: '{{ $facingMode }}' });
</script>
