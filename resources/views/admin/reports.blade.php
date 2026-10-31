@include('partials.head', ['title' => $title])
@include('partials.admin_nav')

<div class="container py-4" style="max-width:520px;">
  <h4 class="mb-3">Generate PDF Report</h4>
  <form method="get" action="{{ route('admin.reports.pdf') }}">
    <div class="mb-3">
      <label class="form-label">From</label>
      <input type="date" class="form-control" name="from">
    </div>
    <div class="mb-3">
      <label class="form-label">To</label>
      <input type="date" class="form-control" name="to">
    </div>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select class="form-select" name="action">
        <option value="">Any</option>
        <option value="valid">Valid</option>
        <option value="flagged">Flagged</option>
        <option value="blacklisted">Blacklisted</option>
      </select>
    </div>
    <button class="btn btn-qrode"><i class="bi bi-file-earmark-pdf"></i> Download PDF</button>
  </form>
</div>
@include('partials.foot')
