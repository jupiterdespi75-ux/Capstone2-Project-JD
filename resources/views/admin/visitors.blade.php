@include('partials.head', ['title' => $title])
@include('partials.admin_nav')

<div class="container py-4">
  <h4 class="mb-3">Visitor List</h4>
  <table class="table table-sm bg-white shadow-sm align-middle">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Registered</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      @foreach($visitors as $v)
        <form method="post" action="{{ route('admin.visitors.update', $v->id) }}" id="form-{{ $v->id }}">@csrf</form>
        <tr>
          <td><input class="form-control form-control-sm" name="name" form="form-{{ $v->id }}" value="{{ $v->name }}"></td>
          <td class="small">{{ $v->email }}</td>
          <td><input class="form-control form-control-sm" name="phone" form="form-{{ $v->id }}" value="{{ $v->phone }}"></td>
          <td><span class="badge badge-{{ $v->status }} text-uppercase">{{ $v->status }}</span></td>
          <td class="small text-muted">{{ $v->created_at }}</td>
          <td class="text-end">
            <input type="hidden" name="purpose" form="form-{{ $v->id }}" value="{{ $v->purpose_of_visit }}">
            <button class="btn btn-sm btn-outline-primary" form="form-{{ $v->id }}">Save</button>
          </td>
        </tr>
        <tr>
          <td colspan="6" class="pt-0 border-0 text-end">
            <form method="post" action="{{ route('admin.visitors.delete', $v->id) }}" onsubmit="return confirm('Remove this visitor account?')" class="d-inline">
              @csrf
              <button class="btn btn-sm btn-outline-danger">Remove Account</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@include('partials.foot')
