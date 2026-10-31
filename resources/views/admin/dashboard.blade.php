@include('partials.head', ['title' => $title])
@include('partials.admin_nav')

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pending &amp; Flagged Registrations</h4>
    @if($pendingWalkins > 0)
      <a href="{{ route('admin.walkins') }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-exclamation-triangle"></i> {{ $pendingWalkins }} walk-in(s) awaiting review
      </a>
    @endif
  </div>

  @if($pending->isEmpty())
    <p class="text-muted">No pending or flagged registrations.</p>
  @endif

  <div class="row g-3">
    @foreach($pending as $v)
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <h6>{{ $v->name }}</h6>
              <span class="badge badge-{{ $v->status }} text-uppercase">{{ $v->status }}</span>
            </div>
            <p class="small text-muted mb-1">{{ $v->email }} &middot; {{ $v->phone }}</p>
            <p class="small text-muted mb-2">Purpose: {{ $v->purpose_of_visit ?? '—' }}</p>
            @if($v->status === 'flagged' && $v->status_reason)
              <p class="small qr-text-flagged mb-2">{{ $v->status_reason }}</p>
            @endif
            <div class="d-flex gap-2 mb-2">
              @if($v->selfie_path)
                <img src="{{ asset('storage/' . $v->selfie_path) }}" class="rounded border" style="width:70px;height:70px;object-fit:cover;">
              @endif
              @if($v->id_card_path)
                <img src="{{ asset('storage/' . $v->id_card_path) }}" class="rounded border" style="width:70px;height:70px;object-fit:cover;">
              @endif
            </div>
            @if(isset($matchesByVisitor[$v->id]))
              <div class="alert alert-flagged py-1 px-2 small mb-2">
                @foreach($matchesByVisitor[$v->id] as $m)
                  <div>
                    @if($m->matched_blacklist_id)
                      Selfie matches <strong>blacklist entry #{{ $m->matched_blacklist_id }}</strong>
                    @else
                      Possible duplicate of visitor ID {{ $m->matched_visitor_id }}
                    @endif
                    (similarity {{ number_format($m->similarity, 3) }})
                  </div>
                @endforeach
              </div>
            @endif
            <div class="d-flex gap-2 flex-wrap">
              <form method="post" action="{{ route('admin.registrations.approve', $v->id) }}">
                @csrf
                <button class="btn btn-sm btn-approve">Approve</button>
              </form>
              <form method="post" action="{{ route('admin.registrations.deny', $v->id) }}" onsubmit="return setReason(this)">
                @csrf
                <input type="hidden" name="reason">
                <button class="btn btn-sm btn-deny">Deny</button>
              </form>
              <form method="post" action="{{ route('admin.registrations.blacklist', $v->id) }}" onsubmit="return setReason(this)">
                @csrf
                <input type="hidden" name="reason">
                <button class="btn btn-sm btn-blacklist">Blacklist</button>
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
