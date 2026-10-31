@include('partials.head', ['title' => $title])
@include('partials.admin_nav')

<div class="container py-4">
  <h4 class="mb-3">Blacklisted Accounts</h4>

  <form method="post" action="{{ route('admin.blacklist.add') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-md-4"><input class="form-control" name="name" placeholder="Name" required></div>
    <div class="col-md-5"><input class="form-control" name="reason" placeholder="Reason" required></div>
    <div class="col-md-3"><button class="btn btn-qrode w-100">Add to Blacklist</button></div>
  </form>

  <table class="table table-sm bg-white shadow-sm">
    <thead><tr><th>Name</th><th>Reason</th><th>Status</th><th>Added</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      @foreach($entries as $e)
        <tr>
          <td>{{ $e->name }}</td>
          <td>{{ $e->reason }}</td>
          <td><span class="badge {{ $e->is_active ? 'badge-blacklisted' : 'bg-secondary' }}">{{ $e->is_active ? 'Active' : 'Reinstated' }}</span></td>
          <td class="small text-muted">{{ $e->created_at }}</td>
          <td class="text-end">
            @if($e->is_active)
              <form method="post" action="{{ route('admin.blacklist.reinstate', $e->id) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-success">Reinstate</button>
              </form>
            @endif
            <form method="post" action="{{ route('admin.blacklist.remove', $e->id) }}" class="d-inline" onsubmit="return confirm('Remove this entry?')">
              @csrf
              <button class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
          </td>
        </tr>
      @endforeach
      @if($entries->isEmpty())
        <tr><td colspan="5" class="text-center text-muted py-3">No blacklist entries.</td></tr>
      @endif
    </tbody>
  </table>
</div>
@include('partials.foot')
