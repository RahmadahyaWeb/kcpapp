<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AopController extends Controller
{

    public function guard()
    {
        $userRoles = explode(',', Auth::user()->role);

        $allowedRoles = ['ADMIN', 'FINANCE'];

        if (empty(array_intersect($allowedRoles, $userRoles))) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function indexUpload()
    {
        $this->guard();

        return view('AOP.index');
    }

    public function detail($invoiceAop)
    {
        $this->guard();

        $invoice = DB::table('invoice_aop_header')
            ->where('invoiceAop', $invoiceAop)->first();

        if (!$invoice) {
            return back()->with('error', "Invoice tidak ditemukan.");
        }

        $flag_final = $invoice->flag_final;

        if ($flag_final == 'Y') {
            return back()->with('error', "Invoice: $invoiceAop sudah berada di list Data AOP Final.");
        }

        return view('AOP.detail', compact('invoiceAop'));
    }

    public function final()
    {
        $this->guard();

        return view('AOP.final');
    }

    public function finalDetail($invoiceAop)
    {
        $this->guard();

        $invoice = DB::table('invoice_aop_header')
            ->where('invoiceAop', $invoiceAop)
            ->where('flag_final', 'Y')
            ->first();

        if (!$invoice) {
            return back()->with('error', "Invoice tidak ditemukan.");
        }

        return view('AOP.final-detail', compact('invoiceAop'));
    }
}
