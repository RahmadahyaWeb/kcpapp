<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Login extends Component
{
    public $username;
    public $password;

    public function login()
    {
        $this->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt($this->only('username', 'password'))) {

            session()->regenerate();

            return redirect()->intended('dashboard');
        }

        $this->addError('username', 'Username atau password salah.');
        $this->reset('password');
    }

    public function render()
    {
        return view('livewire.login');
    }
}
