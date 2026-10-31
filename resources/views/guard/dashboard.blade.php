@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode">
  <div class="container">
    <a class="navbar-brand" href="{{ route('home') }}"><i class="bi bi-qr-code-scan"></i> QRode Guard</a>
    <div>
      <span class="text-white small me-2">{{ $gate->name }}</span>
      <a href="{{ route('guard.index') }}" class="btn btn-sm btn-outline-light">Switch Gate</a>
      <a href="{{ route('staff.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <h4 class="mb-4">Guard Terminal &middot; {{ $gate->name }}</h4>
  <div class="row g-4">
    <div class="col-md-6 col-lg-3">
      <div class="card card-portal h-100 text-center p-4 shadow-sm">
        <div class="fs-1 text-primary"><i class="bi bi-qr-code-scan"></i></div>
        <h6>Guard Terminal</h6>
        <p class="small text-muted">Scan QR, daily gate log, edit visitor info</p>
        <a href="{{ route('guard.scan') }}" class="btn btn-qrode mt-auto">Open Terminal</a>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-portal h-100 text-center p-4 shadow-sm">
        <div class="fs-1 text-primary"><i class="bi bi-person-plus"></i></div>
        <h6>Register Walk-in</h6>
        <p class="small text-muted">No pre-registered QR pass</p>
        <a href="{{ route('guard.walkin') }}" class="btn btn-qrode mt-auto">New Walk-in</a>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-portal h-100 text-center p-4 shadow-sm">
        <div class="fs-1 text-primary"><i class="bi bi-journal-check"></i></div>
        <h6>Walk-in Log</h6>
        <p class="small text-muted">Manual time-in/out for approved walk-ins</p>
        <a href="{{ route('guard.walkinsLog') }}" class="btn btn-qrode mt-auto">Open Log</a>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-portal h-100 text-center p-4 shadow-sm">
        <div class="fs-1 text-primary"><i class="bi bi-file-earmark-pdf"></i></div>
        <h6>Gate Report</h6>
        <p class="small text-muted">PDF of this gate's traffic by date range</p>
        <a href="{{ route('guard.reports') }}" class="btn btn-qrode mt-auto">Generate Report</a>
      </div>
    </div>
  </div>
</div>
@include('partials.foot')
