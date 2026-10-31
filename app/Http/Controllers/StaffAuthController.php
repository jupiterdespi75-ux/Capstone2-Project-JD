<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Qrode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('staff.login', [
            'title' => 'Staff Login',
            'error' => null,
            'next' => $request->query('next', ''),
        ]);
    }

    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $next = $request->input('next', '');

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password ?? '', $user->password)) {
            return view('staff.login', [
                'title' => 'Staff Login',
                'error' => 'Invalid email or password.',
                'next' => $next,
            ]);
        }

        $request->session()->put('user_id', $user->id);
        $request->session()->put('user_role', $user->role);
        $request->session()->put('user_name', $user->name);
        Qrode::log($user->id, 'login', ['role' => $user->role]);

        if ($next === 'admin' && $user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        if ($next === 'guard') {
            return redirect()->route('guard.index');
        }

        return redirect($user->role === 'admin' ? route('admin.dashboard') : route('guard.index'));
    }

    public function logout(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if ($userId) {
            Qrode::log($userId, 'logout', []);
        }
        $request->session()->flush();

        return redirect('/');
    }
}
