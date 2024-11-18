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

        return view('AOP.detail', compact('invoiceAop'));
    }

    public function final()
    {
        $this->guard();

        return view('AOP.final');
    }

    public function store(Request $request)
    {
        $request->validate([
            'surat_tagihan' => 'required|file|mimes:txt|max:2048',
            'rekap_tagihan' => 'required|file|mimes:txt|max:2048',
        ], $message = [
            'surat_tagihan.required' => 'Upload file surat tagihan.',
            'rekap_tagihan.required' => 'Upload file rekap tagihan.',
        ]);

        // VALIDASI NAMA FILE
        if (!str_contains($request->file('surat_tagihan')->getClientOriginalName(), 'surat_tagihan')) {
            return back()->withErrors(['surat_tagihan' => 'File tidak sesuai.']);
        }

        if (!str_contains($request->file('rekap_tagihan')->getClientOriginalName(), 'rekap_tagihan')) {
            return back()->withErrors(['rekap_tagihan' => 'File tidak sesuai.']);
        }

        // Proses file surat_tagihan
        $suratTagihan = $request->file('surat_tagihan');
        $suratContent = file_get_contents($suratTagihan->getRealPath());
        $suratLines = explode("\n", trim($suratContent));

        // Proses file rekap_tagihan
        $rekapTagihan = $request->file('rekap_tagihan');
        $rekapContent = file_get_contents($rekapTagihan->getRealPath());
        $rekapLines = explode("\n", trim($rekapContent));

        // SURAT TAGIHAN HEADER
        $suratTagihanHeader = str_getcsv(array_shift($suratLines), "\t");

        // REKAP TAGIHAN HEADER
        $rekapTagihanHeader = str_getcsv(array_shift($rekapLines), "\t");

        /**
         * HEADER SURAT TAGIHAN 
         * [0] => CUSTOMER NUMBER
         * [1] => CUSTOMER NAME
         * [2] => BILLING NUMBER
         * [3] => BILLING DOCUMENT DATE
         * [4] => MATERIAL NUMBER
         * [5] => BILLING QTY
         * [6] => BILLING AMOUNT
         * [7] => SPB NO
         * [8] => TANGGAL CETAK FAKTUR
         * [9] => TANGGAL JATUH TEMPO
         */

        /**
         * HEADER REKAP TAGIHAN 
         * [0] => CUSTOMER NUMBER
         * [1] => CUSTOMER NAME
         * [2] => BILLING NUMBER    
         * [3] => BILLING DOCUMENT DATE    
         * [4] => TANGGAL JATUH TEMPO    
         * [5] => BILLING AMOUNT + PPN    
         * [6] => ADD DISCOUNT    
         * [7] => CASH DISCOUNT    
         * [8] => EXTRA (PLAFON) DISCOUNT    
         */

        /**
         * MISALNYA:
         * AMOUNT 6.461.579
         * ADD DISCOUNT 261.032
         * TOTAL PRICE (SEBELUM ADD DISCOUNT) 261.032
         */

        /**
         * RUMUS MENDAPATKAN AMOUNT:
         * EXTRA (PLAFON) DISCOUNT => DIDAPATKAN DARI REKAP TAGIHAN
         * BILLING AMOUNT => DIDAPATKAN DARI SURAT TAGIHAN
         * EXTRA (PLAFON) DISCOUNT + (BILLING AMOUNT) = AMOUNT
         */

        /**
         * RUMUS NET SALES:
         * AMOUNT - TOTAL EXTRA (PLAFON) DISCOUNT (PROGRAM) = NET SALES
         */

        /**
         * RUMUS GRAND TOTAL:
         * NET SALES - TAX = GRAND TOTAL
         */

        // DATA MENTAH SURAT TAGIHAN
        $suratTagihanArray = [];
        foreach ($suratLines as $line) {
            $data = str_getcsv($line, "\t");
            $suratTagihanArray[] = $data;
        }

        // DATA MENTAH REKAP TAGIHAN
        $rekapTagihanArray = [];
        foreach ($rekapLines as $line) {
            $data = str_getcsv($line, "\t");
            $rekapTagihanArray[] = $data;
        }

        // PROSES PENGGABUNGAN DATA MENTAH SURAT TAGIHAN DAN REKAP TAGIHAN 
        $combinedArray = [];

        foreach ($suratTagihanArray as $index => $suratData) {
            if (isset($rekapTagihanArray[$index])) {
                $rekapData = $rekapTagihanArray[$index];

                $combinedArray[] = [
                    'CUSTOMER_NUMBER' => $suratData[0],
                    'CUSTOMER_NAME' => $suratData[1],
                    'BILLING_NUMBER' => $suratData[2],
                    'BILLING_DOCUMENT_DATE' => $suratData[3],
                    'MATERIAL_NUMBER' => $suratData[4],
                    'BILLING_QTY' => $suratData[5],
                    'BILLING_AMOUNT' => $suratData[6],
                    'SPB_NO' => $suratData[7],
                    'TANGGAL_CETAK_FAKTUR' => $suratData[8],
                    'TANGGAL_JATUH_TEMPO' => $rekapData[4],
                    'BILLING_AMOUNT_PPN' => $rekapData[5],
                    'ADD_DISCOUNT' => $rekapData[6],
                    'CASH_DISCOUNT' => $rekapData[7],
                    'EXTRA_DISCOUNT' => $rekapData[8],
                ];
            }
        }

        // Group by BILLING_NUMBER and MATERIAL_NUMBER
        $groupedArray = [];

        foreach ($combinedArray as $item) {
            $key = $item['BILLING_NUMBER'] . '|' . $item['MATERIAL_NUMBER'];

            if (!isset($groupedArray[$key])) {
                $groupedArray[$key] = [
                    'CUSTOMER_NUMBER' => $item['CUSTOMER_NUMBER'],
                    'CUSTOMER_NAME' => $item['CUSTOMER_NAME'],
                    'BILLING_NUMBER' => $item['BILLING_NUMBER'],
                    'BILLING_DOCUMENT_DATE' => $item['BILLING_DOCUMENT_DATE'],
                    'MATERIAL_NUMBER' => $item['MATERIAL_NUMBER'],
                    'BILLING_QTY' => 0,
                    'BILLING_AMOUNT' => 0,
                    'SPB_NO' => $item['SPB_NO'],
                    'TANGGAL_CETAK_FAKTUR' => $item['TANGGAL_CETAK_FAKTUR'],
                    'TANGGAL_JATUH_TEMPO' => $item['TANGGAL_JATUH_TEMPO'],
                    'BILLING_AMOUNT_PPN' => $item['BILLING_AMOUNT_PPN'],
                    'ADD_DISCOUNT' => $item['ADD_DISCOUNT'],
                    'CASH_DISCOUNT' => $item['CASH_DISCOUNT'],
                    'EXTRA_DISCOUNT' => $item['EXTRA_DISCOUNT'],
                ];
            }

            $groupedArray[$key]['BILLING_QTY'] += $item['BILLING_QTY'];
            $groupedArray[$key]['BILLING_AMOUNT'] += $item['BILLING_AMOUNT'];
        }

        $groupedArray = array_values($groupedArray);

        $filteredArray = array_filter($groupedArray, function ($item) {
            return $item['BILLING_NUMBER'] === '4009268869'; // Gunakan tanda kutip untuk mencocokkan string
        });

        // Jika Anda ingin mengubah hasil kembali ke indeks numerik
        $filteredArray = array_values($filteredArray);
        foreach ($filteredArray as $data) {
            $amount = $data['BILLING_AMOUNT'] + $data['EXTRA_DISCOUNT'];

            dd($amount);
        }
    }
}
