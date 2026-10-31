@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode"><div class="container"><a class="navbar-brand" href="{{ route('home') }}"><i class="bi bi-qr-code-scan"></i> QRode</a></div></nav>

<div class="container py-5 text-center" style="max-width:480px;">
  <h6 class="text-muted mb-3">Step 5 &middot; Submitted</h6>
  @if($flagged)
    <i class="bi bi-flag-fill fs-1 qr-text-flagged"></i>
    <h4 class="mt-3">Registration Flagged for Review</h4>
    <p class="text-muted">Your registration needs a manual check by an Admin before a decision is made. You'll see the outcome on your status page.</p>
  @else
    <i class="bi bi-check-circle-fill fs-1 qr-text-success"></i>
    <h4 class="mt-3">Registration Submitted</h4>
    <p class="text-muted">Your registration is now <strong>Pending</strong> Admin approval. Once approved, your QR gate pass will appear on your status page.</p>
  @endif
  <a href="{{ route('visitor.status') }}" class="btn btn-qrode">View My Status</a>
</div>
@include('partials.foot')
