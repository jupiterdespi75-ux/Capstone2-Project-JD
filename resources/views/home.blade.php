@include('partials.head', ['title' => 'Home'])

<div class="hero py-5 text-center">
  <div class="container">
    <h1 class="fw-bold"><i class="bi bi-qr-code-scan"></i> QRode</h1>
    <p class="lead mb-0">School Visitor and Gate Pass Management System</p>
    <p class="small opacity-75">University of Cebu &ndash; Main Campus</p>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4 justify-content-center">
    <div class="col-md-4">
      <div class="card card-portal h-100 shadow-sm text-center p-4">
        <div class="fs-1 text-primary mb-2"><i class="bi bi-person-badge"></i></div>
        <h5>Visitor Portal</h5>
        <p class="text-muted small">Pre-register online, submit your selfie and ID, and get your QR gate pass.</p>
        <a href="{{ route('visitor.login') }}" class="btn btn-qrode mt-auto">Enter Visitor Portal</a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-portal h-100 shadow-sm text-center p-4">
        <div class="fs-1 text-primary mb-2"><i class="bi bi-shield-check"></i></div>
        <h5>Guard Terminal</h5>
        <p class="text-muted small">Scan QR passes, register walk-ins, and log entry/exit at the gate.</p>
        <a href="{{ route('staff.login') }}?next=guard" class="btn btn-qrode mt-auto">Enter Guard Terminal</a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-portal h-100 shadow-sm text-center p-4">
        <div class="fs-1 text-primary mb-2"><i class="bi bi-speedometer2"></i></div>
        <h5>Admin Console</h5>
        <p class="text-muted small">Review registrations, manage the blacklist, and generate reports.</p>
        <a href="{{ route('staff.login') }}?next=admin" class="btn btn-qrode mt-auto">Enter Admin Console</a>
      </div>
    </div>
  </div>

  <div class="text-center text-muted small mt-5">
    Demo accounts &mdash; Admin: admin@qrode.uc.edu.ph / admin123 &nbsp;|&nbsp; Guard: guard@qrode.uc.edu.ph / guard123
  </div>
</div>

@include('partials.foot')
