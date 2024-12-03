<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
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
            return $this->redirectIntended('/');;
        }

        $this->addError('username', 'Username atau password salah.');
        $this->reset('password');
    }

    public function render()
    {
        return view('livewire.login');
    }
}
