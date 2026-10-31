@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode">
  <div class="container">
    <a class="navbar-brand" href="{{ route('home') }}"><i class="bi bi-qr-code-scan"></i> QRode Guard</a>
    <a href="{{ route('staff.logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container py-5 text-center" style="max-width:420px;">
  <h4 class="mb-4">Select Your Gate</h4>
  <form method="post" action="{{ route('guard.selectGate') }}">
    @csrf
    <div class="d-grid gap-2">
      @foreach($gates as $g)
        <button type="submit" name="gate_id" value="{{ $g->id }}" class="btn btn-outline-secondary btn-lg {{ (string) $currentGate === (string) $g->id ? 'active' : '' }}">
          <i class="bi bi-signpost-split"></i> {{ $g->name }}
        </button>
      @endforeach
    </div>
  </form>
</div>
@include('partials.foot')
