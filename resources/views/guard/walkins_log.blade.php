@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode">
  <div class="container">
    <a class="navbar-brand" href="{{ route('guard.dashboard') }}"><i class="bi bi-qr-code-scan"></i> QRode Guard</a>
    <a href="{{ route('staff.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h4 class="mb-3">Approved Walk-in Log</h4>
  <table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Name</th><th>Purpose</th><th>Registered</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      @foreach($walkins as $w)
        <tr>
          <td>{{ $w->name }}</td>
          <td>{{ $w->purpose_of_visit ?? '—' }}</td>
          <td class="small text-muted">{{ $w->created_at }}</td>
          <td class="text-end">
            <form method="post" action="{{ route('guard.walkinsLog.action', ['id' => $w->id, 'mode' => 'in']) }}" class="d-inline">
              @csrf
              <button class="btn btn-sm btn-outline-success">Time In</button>
            </form>
            <form method="post" action="{{ route('guard.walkinsLog.action', ['id' => $w->id, 'mode' => 'out']) }}" class="d-inline">
              @csrf
              <button class="btn btn-sm btn-outline-secondary">Time Out</button>
            </form>
          </td>
        </tr>
      @endforeach
      @if($walkins->isEmpty())
        <tr><td colspan="4" class="text-center text-muted py-3">No approved walk-ins at this gate yet.</td></tr>
      @endif
    </tbody>
  </table>
</div>
@include('partials.foot')
