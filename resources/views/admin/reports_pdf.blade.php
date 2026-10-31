<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #111; }
    h1 { font-size: 16px; text-align: center; margin-bottom: 2px; }
    .subtitle { text-align: center; color: #666; font-size: 10px; margin-bottom: 8px; }
    .filters { font-size: 9px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 9px; border-bottom: 1px solid #ccc; padding: 4px 6px; }
    td { font-size: 9px; padding: 4px 6px; border-bottom: 1px solid #eee; }
    .total { text-align: right; color: #666; font-size: 8px; margin-top: 10px; }
    .empty { color: #888; text-align: center; padding: 20px; }
  </style>
</head>
<body>
  <h1>QRode &mdash; Visitor Traffic Report</h1>
  <p class="subtitle">University of Cebu &mdash; Main Campus</p>
  <p class="filters">
    @if(isset($gateName))Gate: <strong>{{ $gateName }}</strong> &nbsp;|&nbsp; @endif Filters &mdash; From: {{ $from ?: 'any' }} &nbsp; To: {{ $to ?: 'any' }} &nbsp; Status: {{ $action ?: 'any' }}
    &nbsp;|&nbsp; Generated: {{ now()->format('n/j/Y, g:i A') }}
  </p>

  @if($rows->isEmpty())
    <p class="empty">No records match the selected filters.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Gate</th>
          <th>Direction</th>
          <th>Status</th>
          <th>Time</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $r)
          <tr>
            <td>{{ $r->visitor->name ?? ($r->walkIn->name ?? '—') }}</td>
            <td>{{ $r->gate->name }}</td>
            <td>{{ $r->direction === 'in' ? 'Entry' : 'Exit' }}</td>
            <td>{{ $r->scan_status }}</td>
            <td>{{ $r->scanned_at }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <p class="total">Total records: {{ $rows->count() }}</p>
</body>
</html>
