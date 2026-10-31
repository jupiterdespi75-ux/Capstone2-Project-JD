<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Models\Gate;
use App\Models\LiveGateTraffic;
use App\Models\TrafficHistory;
use App\Models\WalkIn;
use App\Support\Qrode;
use Illuminate\Http\Request;

class GuardController extends Controller
{
    public function index(Request $request)
    {
        $gates = Gate::where('is_active', true)->get();

        return view('guard.select_gate', [
            'title' => 'Guard Terminal',
            'gates' => $gates,
            'currentGate' => $request->session()->get('gate_id'),
        ]);
    }

    public function selectGate(Request $request)
    {
        $request->session()->put('gate_id', (int) $request->input('gate_id'));

        return redirect()->route('guard.dashboard');
    }

    public function dashboard(Request $request)
    {
        $gateId = $request->session()->get('gate_id');
        if (!$gateId) {
            return redirect()->route('guard.index');
        }

        $gate = Gate::find($gateId);

        return view('guard.dashboard', ['title' => 'Guard Terminal', 'gate' => $gate]);
    }

    // ---------- Walk-in registration ----------

    public function showWalkin(Request $request)
    {
        if (!$request->session()->get('gate_id')) {
            return redirect()->route('guard.index');
        }

        return view('guard.walkin', ['title' => 'Register Walk-in', 'error' => null, 'success' => null]);
    }

    public function storeWalkin(Request $request)
    {
        $name = $request->input('name');
        $purpose = $request->input('purpose');

        if (!$name) {
            return view('guard.walkin', [
                'title' => 'Register Walk-in',
                'error' => 'Name is required.',
                'success' => null,
            ]);
        }

        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('ids', 'public') : null;
        $idCardPath = $request->hasFile('id_card') ? $request->file('id_card')->store('ids', 'public') : null;

        WalkIn::create([
            'name' => $name,
            'id_card_path' => $idCardPath,
            'photo_path' => $photoPath,
            'purpose_of_visit' => $purpose,
            'gate_id' => $request->session()->get('gate_id'),
            'status' => 'pending',
            'encoded_by' => $request->session()->get('user_id'),
            'created_at' => now(),
        ]);

        Qrode::log($request->session()->get('user_id'), 'walkin_registered', [
            'name' => $name,
            'gateId' => $request->session()->get('gate_id'),
        ]);

        return view('guard.walkin', [
            'title' => 'Register Walk-in',
            'error' => null,
            'success' => 'Walk-in submitted for Admin approval.',
        ]);
    }

    // ---------- Manual log for approved walk-ins ----------

    public function walkinsLog(Request $request)
    {
        $gateId = $request->session()->get('gate_id');
        if (!$gateId) {
            return redirect()->route('guard.index');
        }

        $walkins = WalkIn::where('status', 'approved')->where('gate_id', $gateId)->orderByDesc('id')->get();

        return view('guard.walkins_log', ['title' => 'Walk-in Log', 'walkins' => $walkins, 'gateId' => $gateId]);
    }

    public function walkinsLogAction(Request $request, int $id, string $mode)
    {
        $walkin = WalkIn::find($id);
        if (!$walkin) {
            return redirect()->route('guard.walkinsLog');
        }

        $gateId = $request->session()->get('gate_id');
        $activeBlacklist = Blacklist::where('walk_in_id', $id)->where('is_active', true)->first();
        $scanStatus = $activeBlacklist ? 'blacklisted' : 'valid';

        TrafficHistory::create([
            'walk_in_id' => $id,
            'gate_id' => $gateId,
            'direction' => $mode,
            'scan_status' => $scanStatus,
            'scanned_by' => $request->session()->get('user_id'),
            'scanned_at' => now(),
        ]);

        if ($scanStatus === 'valid') {
            if ($mode === 'in') {
                LiveGateTraffic::create(['walk_in_id' => $id, 'gate_id' => $gateId, 'time_in' => now()]);
            } else {
                $live = LiveGateTraffic::where('walk_in_id', $id)
                    ->whereNull('time_out')
                    ->orderByDesc('id')
                    ->first();
                if ($live) {
                    $live->update(['time_out' => now()]);
                }
            }
        }

        Qrode::log($request->session()->get('user_id'), 'walkin_scan', [
            'walkInId' => $id,
            'mode' => $mode,
            'scanStatus' => $scanStatus,
        ]);

        return redirect()->route('guard.walkinsLog');
    }
}
