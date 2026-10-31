<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Models\FaceMatch;
use App\Models\GatePass;
use App\Models\LiveGateTraffic;
use App\Models\SystemLog;
use App\Models\TrafficHistory;
use App\Models\Visitor;
use App\Models\WalkIn;
use App\Support\Qrode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // ---------- Dashboard: pending + flagged registrations ----------

    public function dashboard(Request $request)
    {
        $pending = Visitor::whereIn('status', ['pending', 'flagged'])->orderByDesc('created_at')->get();
        $pendingWalkins = WalkIn::where('status', 'pending')->count();

        $matchesByVisitor = [];
        $matches = FaceMatch::with('facialAnalysis')->get();
        foreach ($matches as $match) {
            $sourceVisitorId = $match->facialAnalysis->visitor_id;
            $matchesByVisitor[$sourceVisitorId][] = $match;
        }

        return view('admin.dashboard', [
            'title' => 'Admin Console',
            'pending' => $pending,
            'pendingWalkins' => $pendingWalkins,
            'matchesByVisitor' => $matchesByVisitor,
        ]);
    }

    public function approveRegistration(Request $request, int $id)
    {
        $visitor = Visitor::find($id);
        if (!$visitor) {
            return redirect()->route('admin.dashboard');
        }

        $visitor->update([
            'status' => 'approved',
            'reviewed_by' => $request->session()->get('user_id'),
            'reviewed_at' => now(),
        ]);

        $token = Qrode::generatePassToken($id);
        GatePass::create(['visitor_id' => $id, 'pass_token' => $token, 'issued_at' => now()]);
        Qrode::log($request->session()->get('user_id'), 'approve_registration', ['visitorId' => $id]);

        return redirect()->route('admin.dashboard');
    }

    public function denyRegistration(Request $request, int $id)
    {
        $visitor = Visitor::find($id);
        $visitor?->update([
            'status' => 'denied',
            'status_reason' => $request->input('reason', 'Denied by Admin'),
            'reviewed_by' => $request->session()->get('user_id'),
            'reviewed_at' => now(),
        ]);

        Qrode::log($request->session()->get('user_id'), 'deny_registration', [
            'visitorId' => $id,
            'reason' => $request->input('reason'),
        ]);

        return redirect()->route('admin.dashboard');
    }

    public function blacklistRegistration(Request $request, int $id)
    {
        $visitor = Visitor::find($id);
        if (!$visitor) {
            return redirect()->route('admin.dashboard');
        }

        $reason = $request->input('reason', 'Blacklisted by Admin');

        $visitor->update([
            'status' => 'blacklisted',
            'status_reason' => $reason,
            'reviewed_by' => $request->session()->get('user_id'),
            'reviewed_at' => now(),
        ]);

        Blacklist::create([
            'visitor_id' => $id,
            'name' => $visitor->name,
            'reason' => $reason,
            'reference_photo' => $visitor->selfie_path,
            'created_by' => $request->session()->get('user_id'),
            'created_at' => now(),
        ]);

        Qrode::log($request->session()->get('user_id'), 'blacklist_registration', ['visitorId' => $id, 'reason' => $reason]);

        return redirect()->route('admin.dashboard');
    }

    // ---------- Walk-in review ----------

    public function walkins(Request $request)
    {
        $walkins = WalkIn::with('gate')->where('status', 'pending')->orderByDesc('created_at')->get();

        return view('admin.walkins', ['title' => 'Walk-in Review', 'walkins' => $walkins]);
    }

    public function approveWalkin(Request $request, int $id)
    {
        WalkIn::where('id', $id)->update([
            'status' => 'approved',
            'reviewed_by' => $request->session()->get('user_id'),
            'reviewed_at' => now(),
        ]);
        Qrode::log($request->session()->get('user_id'), 'approve_walkin', ['walkInId' => $id]);

        return redirect()->route('admin.walkins');
    }

    public function denyWalkin(Request $request, int $id)
    {
        WalkIn::where('id', $id)->update([
            'status' => 'denied',
            'status_reason' => $request->input('reason', 'Denied by Admin'),
            'reviewed_by' => $request->session()->get('user_id'),
            'reviewed_at' => now(),
        ]);
        Qrode::log($request->session()->get('user_id'), 'deny_walkin', ['walkInId' => $id, 'reason' => $request->input('reason')]);

        return redirect()->route('admin.walkins');
    }

    // ---------- Blacklist management ----------

    public function blacklist(Request $request)
    {
        $entries = Blacklist::orderByDesc('is_active')->orderByDesc('created_at')->get();

        return view('admin.blacklist', ['title' => 'Blacklist', 'entries' => $entries]);
    }

    public function blacklistAdd(Request $request)
    {
        $name = $request->input('name');
        $reason = $request->input('reason');
        if (!$name || !$reason) {
            return redirect()->route('admin.blacklist');
        }

        Blacklist::create([
            'name' => $name,
            'reason' => $reason,
            'created_by' => $request->session()->get('user_id'),
            'created_at' => now(),
        ]);
        Qrode::log($request->session()->get('user_id'), 'blacklist_add', ['name' => $name, 'reason' => $reason]);

        return redirect()->route('admin.blacklist');
    }

    public function blacklistReinstate(Request $request, int $id)
    {
        $entry = Blacklist::find($id);
        $entry?->update(['is_active' => false]);

        if ($entry?->visitor_id) {
            Visitor::where('id', $entry->visitor_id)->update(['status' => 'approved', 'status_reason' => null]);
        }

        Qrode::log($request->session()->get('user_id'), 'blacklist_reinstate', ['id' => $id]);

        return redirect()->route('admin.blacklist');
    }

    public function blacklistRemove(Request $request, int $id)
    {
        Blacklist::destroy($id);
        Qrode::log($request->session()->get('user_id'), 'blacklist_remove', ['id' => $id]);

        return redirect()->route('admin.blacklist');
    }

    // ---------- Visitor list ----------

    public function visitors(Request $request)
    {
        $visitors = Visitor::orderByDesc('created_at')->get();

        return view('admin.visitors', ['title' => 'Visitor List', 'visitors' => $visitors]);
    }

    public function updateVisitor(Request $request, int $id)
    {
        Visitor::where('id', $id)->update([
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'purpose_of_visit' => $request->input('purpose'),
        ]);
        Qrode::log($request->session()->get('user_id'), 'edit_visitor', ['visitorId' => $id]);

        return redirect()->route('admin.visitors');
    }

    public function deleteVisitor(Request $request, int $id)
    {
        Visitor::destroy($id);
        Qrode::log($request->session()->get('user_id'), 'delete_visitor', ['visitorId' => $id]);

        return redirect()->route('admin.visitors');
    }

    // ---------- Live gate traffic ----------

    public function liveTraffic(Request $request)
    {
        $live = LiveGateTraffic::with(['gate', 'visitor', 'walkIn'])
            ->whereNull('time_out')
            ->orderByDesc('time_in')
            ->get();

        return view('admin.live_traffic', ['title' => 'Live Gate Traffic', 'live' => $live]);
    }

    // ---------- History / system logs ----------

    public function history(Request $request)
    {
        $action = $request->query('action');

        $query = SystemLog::with('user')->orderByDesc('created_at')->limit(300);
        if ($action) {
            $query->where('action', $action);
        }
        $rows = $query->get();

        $actions = SystemLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.history', [
            'title' => 'History',
            'rows' => $rows,
            'actions' => $actions,
            'selected' => $action ?? '',
        ]);
    }

    // ---------- PDF report export ----------

    public function showReports(Request $request)
    {
        return view('admin.reports', ['title' => 'Reports']);
    }

    public function reportsPdf(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $action = $request->query('action');

        $query = TrafficHistory::with(['visitor', 'walkIn', 'gate'])->orderByDesc('scanned_at');
        if ($from) {
            $query->whereDate('scanned_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('scanned_at', '<=', $to);
        }
        if ($action) {
            $query->where('scan_status', $action);
        }
        $rows = $query->get();

        Qrode::log($request->session()->get('user_id'), 'generate_report', [
            'from' => $from,
            'to' => $to,
            'action' => $action,
            'count' => $rows->count(),
        ]);

        $pdf = Pdf::loadView('admin.reports_pdf', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'action' => $action,
        ]);

        return $pdf->download('qrode-traffic-report.pdf');
    }
}
