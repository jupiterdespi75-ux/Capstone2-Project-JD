@include('partials.head', ['title' => $title])
@include('partials.admin_nav')

<div class="container py-4">
  <h4 class="mb-3">Pending Walk-in Registrations</h4>
  @if($walkins->isEmpty())
    <p class="text-muted">No walk-ins awaiting review.</p>
  @endif
  <div class="row g-3">
    @foreach($walkins as $w)
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <h6>{{ $w->name }}</h6>
            <p class="small text-muted mb-1">Gate: {{ $w->gate->name ?? '—' }}</p>
            <p class="small text-muted mb-2">Purpose: {{ $w->purpose_of_visit ?? '—' }}</p>
            <div class="d-flex gap-2 mb-2">
              @if($w->photo_path)
                <img src="{{ asset('storage/' . $w->photo_path) }}" class="rounded border" style="width:70px;height:70px;object-fit:cover;">
              @endif
              @if($w->id_card_path)
                <img src="{{ asset('storage/' . $w->id_card_path) }}" class="rounded border" style="width:70px;height:70px;object-fit:cover;">
              @endif
            </div>
            <div class="d-flex gap-2">
              <form method="post" action="{{ route('admin.walkins.approve', $w->id) }}">
                @csrf
                <button class="btn btn-sm btn-approve">Approve</button>
              </form>
              <form method="post" action="{{ route('admin.walkins.deny', $w->id) }}" onsubmit="return setReason(this)">
                @csrf
                <input type="hidden" name="reason">
                <button class="btn btn-sm btn-deny">Deny</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
<script>
function setReason(form) {
  const reason = prompt('Reason:');
  if (!reason) return false;
  form.querySelector('[name=reason]').value = reason;
  return true;
}
</script>
@include('partials.foot')
