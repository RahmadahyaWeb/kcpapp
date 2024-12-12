<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('invoice.index');
    }

    public function detail($noso)
    {
        $data_so = DB::connection('kcpinformation')
            ->table('trns_so_header')
            ->select([
                'trns_so_header.*',
                'mst_outlet.*'
            ])
            ->join('mst_outlet', 'mst_outlet.kd_outlet', '=', 'trns_so_header.kd_outlet')
            ->where('noso', $noso)
            ->first();

        if ($data_so->flag_invoice == 'Y' || $data_so->flag_reject == 'Y') {
            return redirect()->route('inv.index')->with('error', 'SO sudah jadi invoice / dibatalkan');
        }

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
            'data_so',
            'nominal_gudang',
            'total',
            'nominal_total',
            'noso'
        ));
    }

    public function createInvoice(Request $request)
    {
        // Retrieve the sales order header and outlet details from the database
        $header = DB::connection('kcpinformation')
            ->table('trns_so_header')
            ->join('mst_outlet', 'mst_outlet.kd_outlet', '=', 'trns_so_header.kd_outlet')
            ->where('noso', $request->noso)
            ->first();

        // Check if the invoice for this sales order has already been created
        if ($header->flag_invoice == 'Y') {
            return redirect()->route('inv.index')->with('error', 'Invoice sudah pernah dibuat coba periksa list invoice.');
        }

        // Generate the invoice number and format it according to the current year and month
        $noinv = $this->getNoInv();
        $noinv_formatted = 'INV-' . date('Ym') . '-' . $noinv;

        // Calculate the due date by adding the "jth_tempo" (due period) to the current date
        $jatuh_tempo = date('Y-m-d', strtotime('+' . $header->jth_tempo . ' days'));

        try {
            // Start a transaction to ensure atomicity of the entire operation
            $connection = DB::connection('kcpinformation');
            $connection->beginTransaction();

            // Insert data into the 'trns_inv_header' table to create the invoice header
            DB::connection('kcpinformation')
                ->table('trns_inv_header')
                ->insert([
                    'noinv'         => $noinv_formatted,  // Invoice number
                    'area_inv'      => $header->area_so,  // Sales area
                    'noso'          => $header->noso,     // Sales order number
                    'kd_outlet'     => $header->kd_outlet, // Outlet code
                    'nm_outlet'     => $header->nm_outlet, // Outlet name
                    'status'        => 'O',               // Status of the invoice (Open)
                    'ket_status'    => 'OPEN',            // Status description
                    'user_sales'    => $header->user_sales, // Salesperson responsible
                    'tgl_jth_tempo' => $jatuh_tempo,     // Due date
                    'crea_date'     => now(),            // Creation date
                    'crea_by'       => Auth::user()->username, // Created by (user)
                ]);

            // Retrieve sales order details
            $details = DB::connection('kcpinformation')
                ->table('trns_so_details')
                ->where('noso', $request->noso)
                ->orderBy('part_no')
                ->get();

            // Initialize total nominal (amount) for the invoice
            $nominal_total = 0;

            // Loop through each sales order detail and insert corresponding data into the invoice details table
            foreach ($details as $value) {
                DB::connection('kcpinformation')
                    ->table('trns_inv_details')
                    ->insert([
                        'noinv'         => $noinv_formatted,      // Invoice number
                        'area_inv'      => $value->area_so,       // Sales area
                        'kd_outlet'     => $value->kd_outlet,     // Outlet code
                        'part_no'       => $value->part_no,       // Part number
                        'nm_part'       => $value->nm_part,       // Part name
                        'qty'           => $value->qty_gudang,    // Quantity in warehouse
                        'hrg_pcs'       => $value->hrg_pcs,       // Price per piece
                        'disc'          => $value->disc,          // Discount applied
                        'nominal'       => $value->nominal_gudang, // Nominal value (pre-discount)
                        'nominal_disc'  => $value->nominal_disc_gudang, // Discounted nominal value
                        'nominal_total' => $value->nominal_total_gudang, // Total nominal value (after discount)
                        'status'        => 'O',                    // Status of the invoice detail (Open)
                        'crea_date'     => now(),                 // Creation date
                        'crea_by'       => Auth::user()->username // Created by (user)
                    ]);

                // Accumulate total nominal for the invoice
                $nominal_total += $value->nominal_total_gudang;

                // Process stock reduction based on the quantity of goods in the warehouse
                $this->penguranganStock($value->qty_gudang, 'GD1', $value->kd_outlet, $value->part_no);

                // Insert a log for the stock movement
                $this->logStock('GD1', $value->part_no, $value, $noinv_formatted);
            }

            $invoice_details = DB::connection('kcpinformation')
                ->table('trns_inv_details')
                ->where('noinv', $noinv_formatted)
                ->get();

            $sumTotalNominal = 0;
            $sumTotalDPP = 0;
            $sumTotalDisc = 0;

            foreach ($invoice_details as $value) {
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
                ->where('noinv', $noinv_formatted)
                ->update([
                    "amount_dpp"        => ROUND($dpp),
                    "amount_ppn"        => ROUND($nominalPPn),
                    "amount"            => ROUND($sumTotalNominal),
                    "amount_disc"       => ROUND($sumTotalDisc),
                    "amount_dpp_disc"   => ROUND($dppDisc),
                    "amount_ppn_disc"   => ROUND($nominalPPnDisc),
                    "amount_total"      => ROUND($sumTotalDPP),
                ]);

            // Reduce the outlet's plafond (credit limit) based on the total nominal of the invoice
            $this->penguranganPlafond($header->kd_outlet, $nominal_total);

            // Update the sales order header to indicate that the invoice has been created
            DB::connection('kcpinformation')
                ->table('trns_so_header')
                ->where('noso', $request->noso)
                ->update([
                    'no_invoice'        => $noinv_formatted,  // Invoice number
                    'flag_invoice'      => 'Y',                // Flag to indicate that the invoice is created
                    'flag_invoice_date' => now()               // Invoice creation date
                ]);

            // Commit the transaction if all operations are successful
            $connection->commit();

            // Return a success response with a message
            return redirect()->route('inv.index')->with('success', 'Invoice berhasil dibuat. Silakan cetak nota pada list.');
        } catch (\Exception $e) {
            // Rollback the transaction if any error occurs during the process
            $connection->rollBack();

            // Return an error response with the exception message
            return redirect()->route('inv.index')->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function getNoInv()
    {
        $data = DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->whereRaw('SUBSTR(noinv, 5, 4) = ?', [date('Y')])
            ->whereRaw('SUBSTR(noinv, 1, 3) = ?', ['INV'])
            ->orderBy('noinv', 'desc')
            ->value('noinv');

        $currentInv = substr($data, 11, 5);
        $noinv = $currentInv + 1;

        if ($noinv >= 1 && $noinv <= 9) {
            $noinv = '0000' . $noinv;
        } elseif ($noinv >= 10 && $noinv <= 99) {
            $noinv = '000' . $noinv;
        } elseif ($noinv >= 100 && $noinv <= 999) {
            $noinv = '00' . $noinv;
        } elseif ($noinv >= 1000 && $noinv <= 9999) {
            $noinv = '0' . $noinv;
        } else {
            $noinv = $noinv;
        }

        return $noinv;
    }

    public function penguranganStock($qty, $kdGudang, $kdOutlet, $partNo)
    {
        // Retrieve the current stock information for the given part number and warehouse
        $dataStock = $this->cekStockPart($kdGudang, $partNo);

        // Calculate the new stock and booking stock values after subtracting the ordered quantity
        $vQty = $dataStock->stock - $qty;
        $vQty_booking = $dataStock->stock_booking - $qty;

        // Update the main stock and stock booking in the 'stock_part' table for the specific part and warehouse
        DB::connection('kcpinformation')
            ->table('stock_part')
            ->where('part_no', $partNo)   // Match part number
            ->where('kd_gudang', $kdGudang) // Match warehouse code
            ->update([
                'stock' => $vQty,           // Update stock value
                'stock_booking' => $vQty_booking // Update booking stock value
            ]);

        // Check the outlet code to determine the specific rack condition for stock processing
        if ($kdOutlet == 'V2') {
            // If outlet code is 'V2', use the 'Kon.Assa' rack condition
            $rakCondition = "kd_rak = 'Kon.Assa'";
        } elseif ($kdOutlet == 'NW') {
            // If outlet code is 'NW', use the 'Kanvasan' rack condition
            $rakCondition = "kd_rak = 'Kanvasan'";
        } else {
            // For all other outlet codes, exclude 'Kanvasan' and 'Kon.Assa' rack conditions
            $rakCondition = "kd_rak <> 'Kanvasan' AND kd_rak <> 'Kon.Assa'";
        }

        // Call the 'prosesRak' function to process stock based on the determined rack condition
        $this->prosesRak($qty, $kdGudang, $partNo, $rakCondition, $kdOutlet);
    }

    private function cekStockPart($kdGudang, $partNo)
    {
        return DB::connection('kcpinformation')
            ->table('stock_part')
            ->where('kd_gudang', $kdGudang)
            ->where('part_no', $partNo)
            ->first();
    }

    private function prosesRak($qty, $kdGudang, $partNo, $rakCondition, $kdOutlet)
    {
        // Retrieve stock part information for the given part number and warehouse code
        $stockPart = DB::connection('kcpinformation')
            ->table('stock_part')
            ->where('part_no', $partNo)  // Match part number
            ->where('kd_gudang', $kdGudang) // Match warehouse code
            ->get();

        // Get the ID of the stock part from the retrieved data
        $idStockPart = $stockPart[0]->id;

        // Retrieve the racks associated with the stock part where quantity is greater than zero, and filter based on rack condition
        $cekRak = DB::connection('kcpinformation')
            ->table('stock_part_rak')
            ->where('id_stock_part', $idStockPart) // Match the stock part ID
            ->where('qty', '>', 0)  // Only consider racks with positive stock quantities
            ->whereRaw($rakCondition)  // Apply the specific rack condition (e.g., based on outlet)
            ->get();

        // Initialize the temporary quantity to track the remaining quantity to be processed
        $tempQty = 0;
        $tempQty = $qty - $tempQty;  // Set the initial quantity to the total requested quantity

        // Loop through each rack entry and update the stock in the racks
        foreach ($cekRak as $value) {
            // Get the rack ID and the quantity available in the rack
            $idRak = $value->id;
            $rakQty = $value->qty;

            // Check if the quantity in the rack is greater than or equal to the remaining quantity to process
            if ($rakQty >= $tempQty) {
                // If the rack has sufficient quantity, update the rack's quantity and log the transaction
                $this->updateRak($idRak, $rakQty - $tempQty); // Update the rack with the remaining quantity
                $this->logRak($kdGudang, $value->kd_rak, $partNo, $tempQty, $rakQty - $tempQty, $kdOutlet); // Log the stock movement
                break;  // Exit the loop once the required quantity has been processed
            } else {
                // If the rack doesn't have enough quantity, set the rack's quantity to zero and process the next rack
                $this->updateRak($idRak, 0); // Set the rack quantity to zero
                $this->logRak($kdGudang, $value->kd_rak, $partNo, $rakQty, 0, $kdOutlet); // Log the stock movement for the full quantity in the rack
                $tempQty -= $rakQty;  // Subtract the processed quantity from the remaining quantity
            }
        }
    }

    private function updateRak($idRak, $newQty)
    {
        DB::connection('kcpinformation')
            ->table('stock_part_rak')
            ->where('id', $idRak)
            ->update([
                'qty' => $newQty
            ]);
    }

    private function logRak($kdGudang, $kdRak, $partNo, $qty, $stockRak, $kdOutlet)
    {
        DB::connection('kcpinformation')
            ->table('trns_log_stock_rak')
            ->insert([
                'status'        => 'PENJUALAN',
                'keterangan'    => 'PENJUALAN ' . $kdOutlet,
                'kd_gudang'     => $kdGudang,
                'kd_rak'        => $kdRak,
                'part_no'       => $partNo,
                'qty'           => $qty,
                'debet'         => 0,
                'kredit'        => $qty,
                'stock_rak'     => $stockRak,
                'crea_date'     => now(),
                'crea_by'       => Auth::user()->username,
            ]);
    }

    private function logStock($kdGudang, $partNo, $value, $noinv)
    {
        $cekStock = $this->cekStockPart($kdGudang, $partNo);

        DB::connection('kcpinformation')
            ->table('trns_log_stock')
            ->insert([
                'status'        => 'PENJUALAN',
                'keterangan'    => "PENJUALAN KCP/" . $value->area_so . "/" . $noinv,
                'kd_gudang'     => $cekStock->kd_gudang,
                'part_no'       => $value->part_no,
                'qty'           => $value->qty_gudang,
                'debet'         => 0,
                'kredit'        => $value->qty_gudang,
                'stock'         => $cekStock->stock,
                'crea_date'     => now(),
                'crea_by'       => Auth::user()->username
            ]);
    }

    private function penguranganPlafond($kdOutlet, $nominal_total)
    {
        $plafon_toko_saat_ini = DB::connection('kcpinformation')
            ->table('trns_plafond')
            ->where('kd_outlet', $kdOutlet)
            ->first();

        DB::connection('kcpinformation')
            ->table('trns_plafond')
            ->where('kd_outlet', $kdOutlet)
            ->decrement('nominal_plafond', $nominal_total);
    }

    public function detailPrint($invoice)
    {
        return view('invoice.detail-print', compact('invoice'));
    }

    public function batal($noso)
    {
        $kd_gudang = 'GD1';

        try {
            // Retrieve the SO and Invoice headers, and the SO details
            $so_header = DB::connection('kcpinformation')->table('trns_so_header')->where('noso', $noso)->first();
            $inv_header = DB::connection('kcpinformation')->table('trns_inv_header')->where('noso', $noso)->first();
            $so_details = DB::connection('kcpinformation')->table('trns_so_details')->where('noso', $noso)->get();

            DB::connection('kcpinformation')->beginTransaction();

            // If there's no invoice header, adjust stock by decreasing booking quantity
            if (!$inv_header) {
                foreach ($so_details as $value) {
                    $data_stock = $this->cekStockPart($kd_gudang, $value->part_no);

                    // Calculate and update stock booking
                    $v_stock_booking = $data_stock->stock_booking - $value->qty_gudang;

                    DB::connection('kcpinformation')
                        ->table('stock_part')
                        ->where('id', $data_stock->id)
                        ->update(['stock_booking' => $v_stock_booking]);
                }
            } else {
                // If invoice exists, log the cancellation and update stock
                $ket = 'PENJUALAN KCP/' . $inv_header->area_inv . '/' . $so_header->no_invoice;

                // Remove logs related to this invoice
                DB::connection('kcpinformation')->table('trns_log_stock')->where('keterangan', $ket)->delete();

                foreach ($so_details as $value) {
                    $data_stock = $this->cekStockPart($kd_gudang, $value->part_no);

                    // Revert stock based on the quantity in the SO details
                    $v_stock = $data_stock->stock + $value->qty_gudang;

                    DB::connection('kcpinformation')
                        ->table('stock_part')
                        ->where('id', $data_stock->id)
                        ->update(['stock' => $v_stock]);
                }

                // Update the invoice header to mark it as canceled
                DB::connection('kcpinformation')
                    ->table('trns_inv_header')
                    ->where('noinv', $inv_header->noinv)
                    ->update([
                        'flag_batal' => 'Y',
                        'flag_batal_date' => now(),
                    ]);
            }

            // Mark the SO header as rejected
            DB::connection('kcpinformation')
                ->table('trns_so_header')
                ->where('noso', $noso)
                ->update([
                    'flag_reject' => 'Y',
                    'flag_reject_date' => now(),
                    'flag_reject_keterangan' => 'Batal Invoice [SYSTEM]',
                ]);

            DB::connection('kcpinformation')->commit();

            // Return success message after successful cancellation
            return redirect()->route('inv.index')->with('success', 'Pembatalan invoice berhasil.');
        } catch (\Exception $e) {
            // Return error message to the user
            return redirect()->route('inv.index')->with('error', 'Terjadi kesalahan saat membatalkan invoice. Silakan coba lagi.');
        }
    }

    public function print($invoice)
    {
        $details = DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $invoice)
            ->get();

        $header = DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->where('noinv', $invoice)
            ->first();

        // CEK FLAG PRINT
        if ($header->cetak >= 1) {
            return back()->with('error', 'Invoice tidak dapat diprint lebih dari satu kali');
        }

        DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->where('noinv', $invoice)
            ->update([
                "status"            => "C",
                "ket_status"        => "CLOSE",
                "cetak"             => 1,
            ]);

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

        DB::table('invoice_bosnet')
            ->insert([
                'noso'          => $header->noso,
                'noinv'         => $header->noinv,
                'kd_outlet'     => $header->kd_outlet,
                'nm_outlet'     => $header->nm_outlet,
                'amount_total'  => $sumTotalDPP,
                'amount'        => $sumTotalNominal,
                'amount_disc'   => $sumTotalDisc,
                'crea_date'     => $header->crea_date,
                'tgl_jth_tempo' => $header->tgl_jth_tempo,
                'user_sales'    => $header->user_sales,
                'flag_print'    => 'Y'
            ]);

        $suppProgram = DB::table('history_bonus_invoice')
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

        $pdf = Pdf::loadView('invoice.print', $data);
        return $pdf->stream('invoice.pdf');
    }

    public static function convert($x)
    {
        $abil = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        if ($x < 12)
            return $abil[$x];
        elseif ($x < 20)
            return InvoiceController::convert($x - 10) . " belas ";
        elseif ($x < 100)
            return InvoiceController::convert($x / 10) . " puluh " . InvoiceController::convert($x % 10);
        elseif ($x < 200)
            return " seratus " . InvoiceController::convert($x - 100);
        elseif ($x < 1000)
            return InvoiceController::convert($x / 100) . " ratus " . InvoiceController::convert($x % 100);
        elseif ($x < 2000)
            return " seribu " . InvoiceController::convert($x - 1000);
        elseif ($x < 1000000)
            return InvoiceController::convert($x / 1000) . " ribu " . InvoiceController::convert($x % 1000);
        elseif ($x < 1000000000)
            return InvoiceController::convert($x / 1000000) . " juta " . InvoiceController::convert($x % 1000000);
    }

    public function invoiceBosnet()
    {
        return view('invoice.invoice-bosnet');
    }

    public function history()
    {
        return view('invoice.history');
    }

    public function historyDetail($noinv)
    {
        $header = DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->where('noinv', $noinv)
            ->first();

        $nominalSuppProgram = DB::table('history_bonus_invoice')
            ->where('noinv', $noinv)
            ->sum('nominal_program');

        $details = DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $noinv)
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

        return view('invoice.history-detail', compact(
            'header',
            'nominalSuppProgram',
            'sumTotalDPP',
            'details'
        ));
    }
}
