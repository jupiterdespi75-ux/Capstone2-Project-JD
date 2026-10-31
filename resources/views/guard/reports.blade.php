@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode">
  <div class="container">
    <a class="navbar-brand" href="{{ route('guard.dashboard') }}"><i class="bi bi-qr-code-scan"></i> QRode Guard</a>
    <a href="{{ route('staff.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container py-4" style="max-width:520px;">
  <h4 class="mb-1">Gate Traffic Report</h4>
  <p class="text-muted small mb-3">Restricted to <strong>{{ $gate->name }}</strong> &mdash; reports only include scans at your assigned gate.</p>

  @if($errors->any())
    <div class="alert alert-danger">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
  @endif

  <form method="get" action="{{ route('guard.reports.pdf') }}">
    <div class="mb-3">
      <label class="form-label">From</label>
      <input type="date" class="form-control" name="from" value="{{ old('from') }}">
    </div>
    <div class="mb-3">
      <label class="form-label">To</label>
      <input type="date" class="form-control" name="to" value="{{ old('to') }}">
    </div>
    <div class="mb-3">
      <label class="form-label">Result</label>
      <select class="form-select" name="action">
        <option value="">Any</option>
        <option value="valid">Granted</option>
        <option value="flagged">Denied / flagged</option>
        <option value="blacklisted">Blacklisted</option>
      </select>
    </div>
    <button class="btn btn-qrode"><i class="bi bi-file-earmark-pdf"></i> Download PDF</button>
    <a href="{{ route('guard.dashboard') }}" class="btn btn-outline-secondary">Back</a>
  </form>
</div>
@include('partials.foot')
