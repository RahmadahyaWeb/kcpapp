<?php

namespace App\Http\Controllers;

use App\Livewire\SalesOrderDetail;
use App\Models\KcpInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class SalesOrderController extends Controller
{
    public function index()
    {
        return view('so.index');
    }

    public function detail($invoice)
    {
        return view('so.detail', compact('invoice'));
    }

    public function bosnet()
    {
        return view('so.so-bosnet');
    }

    public function print($invoice)
    {
        $details = DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $invoice)
            ->get();

        $sumTotalNominal = 0;
        $sumTotalDPP = 0;
        $sumTotalDisc = 0;

        foreach ($details as $value) {
            $sumTotalNominal = $sumTotalNominal + $value->nominal;
            $sumTotalDPP = $sumTotalDPP + $value->nominal_total;
            $sumTotalDisc = $sumTotalDisc + $value->nominal_disc;
            $nominalPPn = ($value->nominal_total / config('tax.ppn_factor')) * config('tax.ppn_percentage');
        }

        $dpp = round($sumTotalNominal) / config('tax.ppn_factor');
        $nominalPPn = round($dpp) * config('tax.ppn_percentage');
        $dppDisc = round($sumTotalDPP) / config('tax.ppn_factor');
        $nominalPPnDisc = round($dppDisc * config('tax.ppn_percentage'));

        DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->where('noinv', $invoice)
            ->update([
                "amount_dpp"        => ROUND($dpp),
                "amount_ppn"        => ROUND($nominalPPn),
                "amount"            => ROUND($sumTotalNominal),
                "amount_disc"       => ROUND($sumTotalDisc),
                "amount_dpp_disc"   => ROUND($dppDisc),
                "amount_ppn_disc"   => ROUND($nominalPPnDisc),
                "amount_total"      => ROUND($sumTotalDPP),
                "status"            => "C",
                "ket_status"        => "CLOSE",
            ]);

        $header = DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->where('noinv', $invoice)
            ->first();

        $invoice_bosnet = DB::table('invoice_bosnet')
            ->where('noinv', $invoice)
            ->first();

        // CEK FLAG PRINT
        if ($invoice_bosnet->flag_print == 'Y') {
            return redirect("/sales-order/detail/$invoice")->with('error', 'Invoice tidak dapat diprint lebih dari satu kali');
        }

        // UPDATE FLAG PRINT
        DB::table('invoice_bosnet')
            ->where('noinv', $invoice)
            ->update([
                'flag_print' => 'Y'
            ]);

        $suppProgram = DB::table('sales_order_program')
            ->where('noinv', $invoice)
            ->get();

        $master_toko = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->where('kd_outlet', $header->kd_outlet)
            ->first();

        $alamat_toko = $master_toko->almt_outlet;

        $data = [
            'invoices'       => $details,
            'header'         => $header,
            'suppProgram'    => $suppProgram,
            'alamat_toko'    => $alamat_toko
        ];

        $pdf = Pdf::loadView('so.print', $data);
        return $pdf->stream('invoice.pdf');
    }

    public static function convert($x)
    {
        $abil = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        if ($x < 12)
            return $abil[$x];
        elseif ($x < 20)
            return SalesOrderController::convert($x - 10) . " belas ";
        elseif ($x < 100)
            return SalesOrderController::convert($x / 10) . " puluh " . SalesOrderController::convert($x % 10);
        elseif ($x < 200)
            return " seratus " . SalesOrderController::convert($x - 100);
        elseif ($x < 1000)
            return SalesOrderController::convert($x / 100) . " ratus " . SalesOrderController::convert($x % 100);
        elseif ($x < 2000)
            return " seribu " . SalesOrderController::convert($x - 1000);
        elseif ($x < 1000000)
            return SalesOrderController::convert($x / 1000) . " ribu " . SalesOrderController::convert($x % 1000);
        elseif ($x < 1000000000)
            return SalesOrderController::convert($x / 1000000) . " juta " . SalesOrderController::convert($x % 1000000);
    }
}
