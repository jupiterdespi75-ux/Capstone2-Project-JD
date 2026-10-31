@include('partials.head', ['title' => $title])
@include('partials.admin_nav')

<div class="container py-4">
  <h4 class="mb-3">Live Gate Traffic <span class="badge bg-success">{{ $live->count() }} on campus</span></h4>
  <table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Visitor</th><th>Gate</th><th>Time In</th></tr></thead>
    <tbody>
      @foreach($live as $l)
        <tr>
          <td>{{ $l->visitor->name ?? ($l->walkIn->name ?? '—') }}</td>
          <td>{{ $l->gate->name }}</td>
          <td class="small text-muted">{{ $l->time_in }}</td>
        </tr>
      @endforeach
      @if($live->isEmpty())
        <tr><td colspan="3" class="text-center text-muted py-3">No visitors currently on campus.</td></tr>
      @endif
    </tbody>
  </table>
  <p class="text-muted small">Refresh the page for the latest gate activity.</p>
</div>
@include('partials.foot')
