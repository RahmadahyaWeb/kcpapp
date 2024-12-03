<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('invoice.index');
    }

    public function detail($noso)
    {
        $item = DB::connection('kcpinformation')
            ->table('trns_so_details')
            ->select(['trns_so_details.*', 'mst_outlet.nm_outlet'])
            ->join('mst_outlet', 'mst_outlet.kd_outlet', '=', 'trns_so_details.kd_outlet')
            ->where('trns_so_details.status', 'C')
            ->where('trns_so_details.noso', $noso)
            ->orderBy('trns_so_details.part_no')
            ->get();

        return view('invoice.detail', compact('item'));
    }
}
