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
        $dataSO = DB::connection('kcpinformation')
            ->table('trns_so_header')
            ->select([
                'trns_so_header.*',
                'mst_outlet.*'
            ])
            ->join('mst_outlet', 'mst_outlet.kd_outlet', '=', 'trns_so_header.kd_outlet')
            ->where('noso', $noso)
            ->first();

        $items = DB::connection('kcpinformation')
            ->table('trns_so_details')
            ->select([
                'trns_so_details.*',
                'mst_outlet.nm_outlet',
            ])
            ->join('mst_outlet', 'mst_outlet.kd_outlet', '=', 'trns_so_details.kd_outlet')
            ->where('trns_so_details.status', 'C')
            ->where('trns_so_details.noso', $noso)
            ->orderBy('trns_so_details.part_no')
            ->get();

        $nominal_gudang = 0;
        $total = 0;
        foreach ($items as $item) {
            $total += $item->qty_gudang * $item->hrg_pcs;
            $nominal_gudang += $item->nominal_gudang;
        }

        $nominal_total = DB::connection('kcpinformation')
            ->table('trns_so_details')
            ->where('noso', $noso)
            ->sum('nominal_total_gudang');

        return view('invoice.detail', compact(
            'items',
            'dataSO',
            'nominal_gudang',
            'total',
            'nominal_total'
        ));
    }
}
