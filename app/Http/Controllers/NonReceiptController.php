<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NonReceiptController extends Controller
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

        return view('non-gr.index');
    }

    public function detail($invoiceNon)
    {
        $this->guard();

        $invoice = DB::table('invoice_non_header')
            ->where('invoiceNon', $invoiceNon)
            ->where('status', 'BOSNET')
            ->first();

        if (!$invoice) {
            return back()->with('error', "Invoice tidak ditemukan.");
        }

        return view('non-gr.detail', compact('invoiceNon'));
    }
}
