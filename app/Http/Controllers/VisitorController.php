<?php

namespace App\Http\Controllers;

use App\Models\GatePass;
use App\Models\Visitor;
use App\Services\FacialVerificationService;
use App\Support\Qrode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VisitorController extends Controller
{
    public function __construct(private FacialVerificationService $faceService)
    {
    }

    // ---------- Registration wizard (Details, Biometric, ID Card, Review, Submitted) ----------

    public function showRegister()
    {
        return view('visitor.register', ['title' => 'Register', 'error' => null]);
    }

    public function register(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $password = $request->input('password');
        $purpose = $request->input('purpose');

        $selfieFile = $request->file('selfie');
        $idFile = $request->file('id_card');

        if (!$name || !$email || !$password || !$selfieFile || !$idFile) {
            return view('visitor.register', [
                'title' => 'Register',
                'error' => 'All fields, a selfie, and an ID capture are required.',
            ]);
        }

        if (Visitor::where('email', $email)->exists()) {
            return view('visitor.register', [
                'title' => 'Register',
                'error' => 'An account with that email already exists. Please log in instead.',
            ]);
        }

        // Store uploads on the "public" disk (php artisan storage:link
        // required) — paths are stored relative, e.g. "selfies/xxxx.jpg".
        $selfiePath = $selfieFile->store('selfies', 'public');
        $idPath = $idFile->store('ids', 'public');

        // Create the visitor and run the face check in one transaction, so a
        // photo with no face / several faces leaves nothing behind.
        DB::beginTransaction();

        try {
            $visitor = Visitor::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'selfie_path' => $selfiePath,
                'id_card_path' => $idPath,
                'purpose_of_visit' => $purpose,
                'status' => 'pending',
                'registration_type' => 'online',
                'created_at' => now(),
            ]);

            // ---- Facial duplicate detection (InsightFace via the Python service) ----
            $result = $this->faceService->processSelfieAndCheckDuplicate($visitor, $selfiePath);

            if ($result['user_fault']) {
                DB::rollBack();
                Storage::disk('public')->delete([$selfiePath, $idPath]);

                return view('visitor.register', [
                    'title' => 'Register',
                    'error' => ($result['message'] ?: 'We could not read a face in that photo.')
                        . ' Please retake your selfie.',
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Storage::disk('public')->delete([$selfiePath, $idPath]);
            throw $e;
        }

        $flagged = $result['flagged'];

        if ($flagged) {
            Qrode::log(null, 'registration_flagged', [
                'visitorId' => $visitor->id,
                'matches' => $result['matches'],
                'reason' => $result['error'],
            ]);
        } else {
            Qrode::log(null, 'registration_submitted', ['visitorId' => $visitor->id]);
        }

        $request->session()->put('visitor_id', $visitor->id);

        return view('visitor.submitted', ['title' => 'Submitted', 'flagged' => $flagged]);
    }

    // ---------- Login / Logout ----------

    public function showLogin()
    {
        return view('visitor.login', ['title' => 'Visitor Login', 'error' => null]);
    }

    public function login(Request $request)
    {
        $visitor = Visitor::where('email', $request->input('email'))->first();

        if (!$visitor || !Hash::check($request->input('password', ''), $visitor->password)) {
            return view('visitor.login', ['title' => 'Visitor Login', 'error' => 'Invalid email or password.']);
        }

        $request->session()->put('visitor_id', $visitor->id);

        return redirect()->route('visitor.status');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();

        return redirect('/');
    }

    // ---------- Status / QR pass ----------

    public function status(Request $request)
    {
        $visitor = Visitor::find($request->session()->get('visitor_id'));

        if (!$visitor) {
            return redirect()->route('visitor.login');
        }

        $qrSvg = null;
        $passToken = null;

        if ($visitor->status === 'approved') {
            $pass = GatePass::where('visitor_id', $visitor->id)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            if ($pass) {
                $passToken = $pass->pass_token;
                $qrSvg = QrCode::format('svg')->size(260)->generate($pass->pass_token);
            }
        }

        return view('visitor.status', [
            'title' => 'My Status',
            'visitor' => $visitor,
            'qrSvg' => $qrSvg,
            'passToken' => $passToken,
        ]);
    }
}
