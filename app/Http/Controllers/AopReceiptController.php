<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AopReceiptController extends Controller
{
    public function index()
    {
        return view('aop-gr.index');
    }

    public function detail($invoiceAop)
    {
        return view('aop-gr.detail', compact('invoiceAop'));
    }
}
