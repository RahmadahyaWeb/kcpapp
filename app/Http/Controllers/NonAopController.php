<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NonAopController extends Controller
{
    public function guard()
    {
        $userRoles = explode(',', Auth::user()->role);

        $allowedRoles = ['ADMIN', 'FINANCE'];

        if (empty(array_intersect($allowedRoles, $userRoles))) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function index()
    {
        $this->guard();

        return view('NON-AOP.index');
    }

    public function create()
    {
        $this->guard();

        return view('NON-AOP.create');
    }

    public function detail($invoiceNon)
    {
        $this->guard();

        return view('NON-AOP.detail', compact('invoiceNon'));
    }
}
