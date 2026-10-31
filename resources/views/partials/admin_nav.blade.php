<nav class="navbar navbar-qrode navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="{{ route('admin.dashboard') }}"><i class="bi bi-qr-code-scan"></i> QRode Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navAdmin">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Pending</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.walkins') }}">Walk-ins</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.visitors') }}">Visitors</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.blacklist') }}">Blacklist</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.liveTraffic') }}">Live Traffic</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.history') }}">History</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports') }}">Reports</a></li>
      </ul>
      <a href="{{ route('staff.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>
</nav>
