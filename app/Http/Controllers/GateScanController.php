<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Models\Gate;
use App\Models\GatePass;
use App\Models\LiveGateTraffic;
use App\Models\TrafficHistory;
use App\Models\Visitor;
use App\Models\WalkIn;
use App\Support\Qrode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Guard Terminal: gate selector, QR scanning, daily visitor log for the
 * guard's selected gate, and editing of visitor details.
 *
 * Everything here is scoped to the gate stored in the session (`gate_id`),
 * never to a gate supplied by the browser.
 */
class GateScanController extends Controller
{
    // A QR pass can't be scanned again at the same gate in the same
    // direction within this many seconds (entrance/exit tracked separately).
    private const COOLDOWN_SECONDS = 60;

    private function sessionGateId(Request $request): ?int
    {
        $id = $request->session()->get('gate_id');

        return $id ? (int) $id : null;
    }

    // ---------- Terminal page ----------

    public function terminal(Request $request)
    {
        $gateId = $this->sessionGateId($request);
        if (!$gateId) {
            return redirect()->route('guard.index');
        }

        $gates = Gate::where('is_active', true)->orderBy('name')->get();
        $gate = $gates->firstWhere('id', $gateId);

        if (!$gate) {
            $request->session()->forget('gate_id');

            return redirect()->route('guard.index');
        }

        return view('guard.terminal', [
            'title' => 'Guard Terminal',
            'gates' => $gates,
            'gate' => $gate,
        ]);
    }

    public function selectGate(Request $request)
    {
        $gate = Gate::where('is_active', true)->find((int) $request->input('gate_id'));
        if (!$gate) {
            return redirect()->route('guard.index');
        }

        $request->session()->put('gate_id', $gate->id);
        Qrode::log($request->session()->get('user_id'), 'gate_selected', ['gateId' => $gate->id]);

        return redirect()->route('guard.scan');
    }

    // ---------- QR scan + blacklist cross-check ----------

    private function cooldownRemaining(int $visitorId, int $gateId, string $direction): int
    {
        $last = TrafficHistory::where('visitor_id', $visitorId)
            ->where('gate_id', $gateId)
            ->where('direction', $direction)
            ->orderByDesc('scanned_at')
            ->first();

        if (!$last) {
            return 0;
        }

        $elapsed = (int) Carbon::parse($last->scanned_at)->diffInSeconds(now());
        $remaining = self::COOLDOWN_SECONDS - $elapsed;

        return $remaining > 0 ? $remaining : 0;
    }

    public function verify(Request $request)
    {
        $token = $request->input('token');
        $mode = $request->input('mode') === 'out' ? 'out' : 'in';
        $gateId = $this->sessionGateId($request);

        if (!$gateId) {
            return response()->json(['error' => 'No gate selected.'], 400);
        }

        $pass = GatePass::where('pass_token', $token)->where('is_active', true)->first();
        if (!$pass) {
            return response()->json([
                'status' => 'invalid',
                'alert' => 'red',
                'title' => 'INVALID QR',
                'message' => 'QR code not recognized.',
            ]);
        }

        $visitor = Visitor::find($pass->visitor_id);
        $photo = $visitor->selfie_path ? '/storage/' . $visitor->selfie_path : null;

        $remaining = $this->cooldownRemaining($visitor->id, $gateId, $mode);
        if ($remaining > 0) {
            return response()->json([
                'status' => 'cooldown',
                'alert' => 'grey',
                'title' => 'ALREADY SCANNED',
                'name' => $visitor->name,
                'email' => $visitor->email,
                'photo' => $photo,
                'remainingSeconds' => $remaining,
                'mode' => $mode,
                'message' => "Already scanned at this gate. Try again in {$remaining}s.",
            ]);
        }

        // Blacklist cross-check.
        $activeBlacklist = Blacklist::where('visitor_id', $visitor->id)->where('is_active', true)->first();

        if ($activeBlacklist || $visitor->status === 'blacklisted') {
            $scanStatus = 'blacklisted';
        } elseif ($visitor->status === 'approved') {
            $scanStatus = 'valid';
        } else {
            // Any non-approved visitor holding a pass is not granted entry.
            $scanStatus = 'flagged';
        }

        TrafficHistory::create([
            'visitor_id' => $visitor->id,
            'gate_id' => $gateId,
            'direction' => $mode,
            'scan_status' => $scanStatus,
            'scanned_by' => $request->session()->get('user_id'),
            'scanned_at' => now(),
        ]);

        if ($scanStatus === 'valid') {
            if ($mode === 'in') {
                LiveGateTraffic::create([
                    'visitor_id' => $visitor->id,
                    'gate_id' => $gateId,
                    'time_in' => now(),
                ]);
            } else {
                $live = LiveGateTraffic::where('visitor_id', $visitor->id)
                    ->whereNull('time_out')
                    ->orderByDesc('id')
                    ->first();
                $live?->update(['time_out' => now()]);
            }
        }

        Qrode::log($request->session()->get('user_id'), 'scan', [
            'visitorId' => $visitor->id,
            'mode' => $mode,
            'gateId' => $gateId,
            'scanStatus' => $scanStatus,
        ]);

        $granted = $scanStatus === 'valid';

        return response()->json([
            'status' => $scanStatus,
            'alert' => $granted ? 'green' : 'red',
            'title' => $granted
                ? 'ACCESS GRANTED'
                : ($scanStatus === 'blacklisted' ? 'DENIED — BLACKLISTED' : 'DENIED — NOT APPROVED'),
            'name' => $visitor->name,
            'email' => $visitor->email,
            'photo' => $photo,
            'reason' => $activeBlacklist->reason ?? $visitor->status_reason,
            'time' => now()->format('H:i:s'),
            'mode' => $mode,
        ]);
    }

    // ---------- Daily visitor log (this gate, today only) ----------

    public function dailyLog(Request $request)
    {
        $gateId = $this->sessionGateId($request);
        if (!$gateId) {
            return response()->json(['error' => 'No gate selected.'], 400);
        }

        $rows = TrafficHistory::with(['visitor', 'walkIn'])
            ->where('gate_id', $gateId)
            ->whereDate('scanned_at', today())
            ->orderByDesc('scanned_at')
            ->limit(500)
            ->get()
            ->map(function ($t) {
                $isVisitor = $t->visitor_id !== null;
                $person = $isVisitor ? $t->visitor : $t->walkIn;
                $photoPath = $isVisitor ? ($person->selfie_path ?? null) : ($person->photo_path ?? null);

                return [
                    'type' => $isVisitor ? 'visitor' : 'walk_in',
                    'record_id' => $isVisitor ? $t->visitor_id : $t->walk_in_id,
                    'name' => $person->name ?? '—',
                    'email' => $isVisitor ? ($person->email ?? null) : null,
                    'phone' => $isVisitor ? ($person->phone ?? null) : null,
                    'purpose' => $person->purpose_of_visit ?? null,
                    'direction' => $t->direction,
                    'status' => $t->scan_status,
                    'time' => Carbon::parse($t->scanned_at)->format('H:i:s'),
                    'photo' => $photoPath ? '/storage/' . $photoPath : null,
                ];
            })
            ->values();

        return response()->json([
            'gate' => Gate::find($gateId)?->name,
            'date' => today()->toDateString(),
            'rows' => $rows,
        ]);
    }

    // ---------- Edit visitor / walk-in info from the terminal ----------

    public function updateRecord(Request $request, string $type, int $id)
    {
        $gateId = $this->sessionGateId($request);
        if (!$gateId) {
            return response()->json(['message' => 'No gate selected.'], 400);
        }

        // A guard may only edit people who actually passed through THEIR
        // gate today — not arbitrary records.
        $column = $type === 'visitor' ? 'visitor_id' : 'walk_in_id';
        $atThisGateToday = TrafficHistory::where('gate_id', $gateId)
            ->whereDate('scanned_at', today())
            ->where($column, $id)
            ->exists();

        if (!$atThisGateToday) {
            return response()->json(['message' => 'This person is not in today\'s log for your gate.'], 403);
        }

        if ($type === 'visitor') {
            $data = $request->validate([
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255', Rule::unique('visitors', 'email')->ignore($id)],
                'purpose' => ['nullable', 'string', 'max:255'],
            ]);

            Visitor::where('id', $id)->update([
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'],
                'purpose_of_visit' => $data['purpose'] ?? null,
            ]);
        } else {
            $data = $request->validate([
                'purpose' => ['nullable', 'string', 'max:255'],
            ]);

            WalkIn::where('id', $id)->update(['purpose_of_visit' => $data['purpose'] ?? null]);
        }

        Qrode::log($request->session()->get('user_id'), 'guard_edit_record', [
            'type' => $type,
            'id' => $id,
            'gateId' => $gateId,
        ]);

        return response()->json(['ok' => true]);
    }
}
