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

    public function print($invoice)
    {
        $conn = $this->checkApiConn();

        if (!$conn) {
            abort(500);
        }

        $api = new KcpInformation;

        $invoices = $api->getInvoice($conn['token'], $invoice);

        if (isset($invoices['status']) && $invoices['status'] == 404) {
            $invoices = new Collection();
        } else if (isset($invoices['data']) && $invoices['data']) {
            $invoices = collect($invoices['data']);
        } else {
            $invoices = new Collection();
        }

        $header = DB::table('invoice_header')
            ->where('noinv', $invoice)
            ->first();

        // CEK FLAG PRINT
        if ($header->flag_print == 'Y') {
            return redirect("/sales-order/detail/$invoice")->with('error', 'Invoice tidak dapat diprint lebih dari satu kali');
        }

        // UPDATE FLAG PRINT
        DB::table('invoice_header')
            ->where('noinv', $invoice)
            ->update([
                'flag_print' => 'Y'
            ]);

        $suppProgram = DB::table('sales_order_program')
            ->where('noinv', $invoice)
            ->get();

        $data = [
            'invoices'       => $invoices,
            'header'         => $header,
            'suppProgram'    => $suppProgram,
        ];


        $alamat_toko = $api->getAddress($conn['token'], $header->kd_outlet);
        $alamat_toko = $alamat_toko['data']['almt_outlet'];

        $data['alamat_toko'] = $alamat_toko;

        $pdf = Pdf::loadView('so.print', $data);
        return $pdf->stream('invoice.pdf');
    }

    public function checkApiConn()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        return $login;
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
