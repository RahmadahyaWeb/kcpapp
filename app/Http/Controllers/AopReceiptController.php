<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AopReceiptController extends Controller
{
    public function guard()
    {
        $userRoles = explode(',', Auth::user()->role);

        $allowedRoles = ['ADMIN', 'INVENTORY'];

        if (empty(array_intersect($allowedRoles, $userRoles))) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function index()
    {
        $this->guard();

        return view('aop-gr.index');
    }

    public function detail($invoiceAop)
    {
        $this->guard();

        $invoice = DB::table('invoice_aop_header')
            ->where('invoiceAop', $invoiceAop)
            ->where('flag_final', 'Y')
            ->where('flag_po', 'Y')
            ->first();

        if (!$invoice) {
            return back()->with('error', "Invoice tidak ditemukan.");
        }

        return view('aop-gr.detail', compact('invoiceAop'));
    }
}
