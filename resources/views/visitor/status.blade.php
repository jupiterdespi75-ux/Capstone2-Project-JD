@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode">
  <div class="container">
    <a class="navbar-brand" href="{{ route('home') }}"><i class="bi bi-qr-code-scan"></i> QRode</a>
    <a href="{{ route('visitor.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container py-5" style="max-width:480px;">
  <div class="card shadow-sm">
    <div class="card-body text-center">
      <h5 class="card-title">{{ $visitor->name }}</h5>
      <p class="text-muted small mb-1">{{ $visitor->email }}</p>
      <p class="text-muted small mb-3">{{ $visitor->phone }}</p>

      <span class="badge badge-{{ $visitor->status }} mb-3 px-3 py-2 text-uppercase">{{ $visitor->status }}</span>

      @if($visitor->status === 'approved' && $qrSvg)
        <div>
          <div class="border rounded p-2 bg-white d-inline-block">{!! $qrSvg !!}</div>
          <p class="small text-muted mt-2">Present this QR code at the gate for entry and exit.</p>
        </div>
      @elseif($visitor->status === 'pending')
        <p class="text-muted">Your registration is awaiting Admin review.</p>
      @elseif($visitor->status === 'flagged')
        <p class="qr-text-flagged">Your registration was flagged during biometric verification and is awaiting manual Admin review.</p>
      @elseif($visitor->status === 'denied')
        <p class="text-secondary">Your registration was denied.@if($visitor->status_reason)<br><strong>Reason:</strong> {{ $visitor->status_reason }}@endif</p>
      @elseif($visitor->status === 'blacklisted')
        <p class="text-danger">Your account has been blacklisted.@if($visitor->status_reason)<br><strong>Reason:</strong> {{ $visitor->status_reason }}@endif</p>
      @endif
    </div>
  </div>
</div>
@include('partials.foot')
