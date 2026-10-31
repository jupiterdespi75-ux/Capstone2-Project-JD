@include('partials.head', ['title' => $title])
@include('partials.admin_nav')

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">History</h4>
    <form method="get" class="d-flex gap-2">
      <select class="form-select form-select-sm" name="action" onchange="this.form.submit()">
        <option value="">All actions</option>
        @foreach($actions as $a)
          <option value="{{ $a }}" {{ $selected === $a ? 'selected' : '' }}>{{ $a }}</option>
        @endforeach
      </select>
    </form>
  </div>
  <table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
    <tbody>
      @foreach($rows as $r)
        <tr>
          <td class="small text-muted">{{ $r->created_at }}</td>
          <td class="small">{{ $r->user->name ?? 'system' }}</td>
          <td><span class="badge bg-secondary">{{ $r->action }}</span></td>
          <td class="small text-muted text-truncate" style="max-width:340px;">{{ $r->details }}</td>
        </tr>
      @endforeach
      @if($rows->isEmpty())
        <tr><td colspan="4" class="text-center text-muted py-3">No log entries.</td></tr>
      @endif
    </tbody>
  </table>
</div>
@include('partials.foot')
