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

    public function logout()
    {
        User::where('id', Auth::id())->update(['last_seen' => now(), 'isOnline' => 'T']);

        Auth::logout();
        
        return redirect('/');
    }
}
