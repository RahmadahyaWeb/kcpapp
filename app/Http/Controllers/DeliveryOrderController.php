<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function index()
    {
        return view('do.index');
    }

    public function detail($lkh)
    {
        return view('do.detail', compact('lkh'));
    }
}