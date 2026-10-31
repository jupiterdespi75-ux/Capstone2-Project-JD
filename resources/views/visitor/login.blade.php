@include('partials.head', ['title' => $title])
<nav class="navbar navbar-qrode"><div class="container"><a class="navbar-brand" href="{{ route('home') }}"><i class="bi bi-qr-code-scan"></i> QRode</a></div></nav>

<div class="container py-5" style="max-width:420px;">
  <h4 class="mb-3 text-center">Visitor Login</h4>
  @if($error)
    <div class="alert alert-danger">{{ $error }}</div>
  @endif
  <form method="post" action="{{ route('visitor.login') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-qrode w-100">Sign In</button>
  </form>
  <p class="text-center mt-3">New visitor? <a href="{{ route('visitor.register') }}">Register here</a></p>
  <p class="text-center"><a href="{{ route('home') }}">&larr; Back home</a></p>
</div>
@include('partials.foot')
