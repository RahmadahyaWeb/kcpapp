<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginPage()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);


        $user = DB::table('users')->where('username', $request->username)->first();

        if ($user && $this->verifyPassword($request->password, $user->password_md5)) {

            return $this->authenticated($request->password, $request->username, $user->id);
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->withInput();
    }

    protected function authenticated($password, $username, $userId)
    {
        $user = User::where('username', $username)->update(['password' => Hash::make($password)]);

        if ($user) {
            Auth::logoutOtherDevices($password);    

            auth()->loginUsingId($userId);

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ]);
    }

    private function verifyPassword($inputPassword, $hashedPassword)
    {
        return md5($inputPassword) === $hashedPassword;
    }

    public function logout()
    {
        User::where('id', Auth::id())->update(['last_seen' => now(), 'isOnline' => 'T']);
        auth()->logout();
        return redirect('/login');
    }
}
