<?php

namespace App\Http\Controllers;

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
            'nominal_total',
            'noso'
        ));
    }

    public function createInvoice(Request $request)
    {
        $header = DB::connection('kcpinformation')
            ->table('trns_so_header')
            ->join('mst_outlet', 'mst_outlet.kd_outlet', '=', 'trns_so_header.kd_outlet')
            ->where('noso', $request->noso)
            ->first();

        if ($header->flag_invoice == 'Y') {
            return redirect()->route('inv.index')->with('error', 'Invoice sudah pernahh dibuat coba periksa list invoice.');
        }

        $noinv = $this->getNoInv();
        $noinv_formatted = 'INV-' . date('Ym') . '-' . $noinv;
        $jatuh_tempo = date('Y-m-d', strtotime('+' . $header->jth_tempo . ' days'));

        try {
            // Mulai transaksi
            $connection = DB::connection('kcpinformation');

            $connection->beginTransaction();

            // Insert data ke dalam tabel trns_inv_header
            DB::connection('kcpinformation')
                ->table('trns_inv_header')
                ->insert([
                    'noinv'         => $noinv_formatted,
                    'area_inv'      => $header->area_so,
                    'noso'          => $header->noso,
                    'kd_outlet'     => $header->kd_outlet,
                    'nm_outlet'     => $header->nm_outlet,
                    'status'        => 'O',
                    'ket_status'    => 'OPEN',
                    'user_sales'    => $header->user_sales,
                    'tgl_jth_tempo' => $jatuh_tempo,
                    'crea_date'     => now(),
                    'crea_by'       => Auth::user()->username,
                ]);

            $details = DB::connection('kcpinformation')
                ->table('trns_so_details')
                ->where('noso', $request->noso)
                ->orderBy('part_no')
                ->get();

            $nominal_total = 0;
            foreach ($details as $value) {
                DB::connection('kcpinformation')
                    ->table('trns_inv_details')
                    ->insert([
                        'noinv'         => $noinv_formatted,
                        'area_inv'      => $value->area_so,
                        'kd_outlet'     => $value->kd_outlet,
                        'part_no'       => $value->part_no,
                        'nm_part'       => $value->nm_part,
                        'qty'           => $value->qty_gudang,
                        'hrg_pcs'       => $value->hrg_pcs,
                        'disc'          => $value->disc,
                        'nominal'       => $value->nominal_gudang,
                        'nominal_disc'  => $value->nominal_disc_gudang,
                        'nominal_total' => $value->nominal_total_gudang,
                        'status'        => 'O',
                        'crea_date'     => now(),
                        'crea_by'       => Auth::user()->username
                    ]);

                $nominal_total += $value->nominal_total_gudang;

                // PROSES PENGURANGAN STOCK
                $this->penguranganStock($value->qty_gudang, 'GD1', $value->kd_outlet, $value->part_no);

                // INSERT LOG STOCK
                $this->logStock('GD1', $value->part_no, $value, $noinv);
            }

            // PENGURANGAN PLAFOND
            $this->penguranganPlafond($header->kd_outlet, $nominal_total);

            // UPDATE SO
            DB::connection('kcpinformation')
                ->table('trns_so_header')
                ->where('noso', $request->noso)
                ->update([
                    'no_invoice'        => $noinv_formatted,
                    'flag_invoice'      => 'Y',
                    'flag_invoice_date' => now()
                ]);

            // Commit transaksi jika berhasil
            $connection->commit();

            return redirect()->route('inv.index')->with('success', 'Invoice berhasil dibuat silahkan cetak nota pada list.');
        } catch (\Exception $e) {
            // Rollback jika terjadi error
            $connection->rollBack();

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
        $dataStock = $this->cekStockPart($kdGudang, $partNo);
        $vQty = $dataStock->stock - $qty;
        $vQty_booking = $dataStock->stock_booking - $qty;

        // Update stok utama
        DB::connection('kcpinformation')
            ->table('stock_part')
            ->where('part_no', $partNo)
            ->where('kd_gudang', $kdGudang)
            ->update([
                'stock' => $vQty,
                'stock_booking' => $vQty_booking
            ]);

        if ($kdOutlet == 'V2') {
            $rakCondition = "kd_rak = 'Kon.Assa'";
        } elseif ($kdOutlet == 'NW') {
            $rakCondition = "kd_rak = 'Kanvasan'";
        } else {
            $rakCondition = "kd_rak <> 'Kanvasan' AND kd_rak <> 'Kon.Assa'";
        }

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
        $stockPart = DB::connection('kcpinformation')
            ->table('stock_part')
            ->where('part_no', $partNo)
            ->where('kd_gudang', $kdGudang)
            ->get();

        $idStockPart = $stockPart[0]->id;

        $cekRak = DB::connection('kcpinformation')
            ->table('stock_part_rak')
            ->where('id_stock_part', $idStockPart)
            ->where('qty', '>', 0)
            ->whereRaw($rakCondition)
            ->get();

        $tempQty = 0;
        $tempQty = $qty - $tempQty;

        foreach ($cekRak as $value) {
            $idRak = $value->id;
            $rakQty = $value->qty;

            if ($rakQty >= $tempQty) {
                $this->updateRak($idRak, $rakQty - $tempQty);
                $this->logRak($kdGudang, $value->kd_rak, $partNo, $tempQty, $rakQty - $tempQty, $kdOutlet);
                break;
            } else {
                $this->updateRak($idRak, 0);
                $this->logRak($kdGudang, $value->kd_rak, $partNo, $rakQty, 0, $kdOutlet);
                $tempQty -= $rakQty;
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
        return view('so.detail', compact('invoice'));
    }
}
