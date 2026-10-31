<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\TrafficHistory;
use App\Support\Qrode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function guardIndex(Request $request)
    {
        $gate = Gate::find($request->session()->get('gate_id'));
        if (!$gate) {
            return redirect()->route('guard.index');
        }

        return view('guard.reports', ['title' => 'Gate Report', 'gate' => $gate]);
    }

    public function guardPdf(Request $request)
    {
        // The gate comes from the session ONLY — never from the query
        // string — so a guard can't request another gate's traffic.
        $gate = Gate::find($request->session()->get('gate_id'));
        if (!$gate) {
            return redirect()->route('guard.index');
        }

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'action' => ['nullable', 'in:valid,flagged,blacklisted'],
        ]);

        $query = TrafficHistory::with(['visitor', 'walkIn', 'gate'])
            ->where('gate_id', $gate->id)
            ->orderByDesc('scanned_at');

        if (!empty($data['from'])) {
            $query->whereDate('scanned_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query->whereDate('scanned_at', '<=', $data['to']);
        }
        if (!empty($data['action'])) {
            $query->where('scan_status', $data['action']);
        }

        $rows = $query->get();

        Qrode::log($request->session()->get('user_id'), 'generate_report', [
            'scope' => 'guard',
            'gateId' => $gate->id,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'action' => $data['action'] ?? null,
            'count' => $rows->count(),
        ]);

        $pdf = Pdf::loadView('admin.reports_pdf', [
            'rows' => $rows,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'action' => $data['action'] ?? null,
            'gateName' => $gate->name,
        ]);

        return $pdf->download('qrode-' . Str::slug($gate->name) . '-traffic-report.pdf');
    }
}
