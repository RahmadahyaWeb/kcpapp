<?php

namespace App\Livewire;

use App\Exports\ComparatorExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;


class ComparatorTable extends Component
{
    public $barcode;

    public function store()
    {
        if (!$this->barcode) {
            session()->flash('error', 'Barcode error.');
            return;
        }

        try {
            DB::beginTransaction();

            // Periksa apakah part_number sudah ada
            $existingRecord = DB::table('comparator')->where('part_number', $this->barcode)->first();

            if ($existingRecord) {
                // Jika ada, increment qty
                DB::table('comparator')
                    ->where('part_number', $this->barcode)
                    ->increment('qty', 1);
            } else {
                // Jika tidak ada, masukkan data baru
                DB::table('comparator')->insert([
                    'part_number' => $this->barcode,
                    'qty'         => 1,
                    'scan_by'     => Auth::user()->username,
                    'created_at'  => now(),
                ]);
            }

            DB::commit(); // Commit transaksi

            // Reset field
            $this->barcode = '';

            session()->flash('success', "Berhasil scan barcode.");
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaksi jika terjadi kesalahan
        }
    }

    public function resetComparator()
    {
        DB::table('comparator')
            ->truncate();
    }

    public function export()
    {
        $filename = 'comparator_result_' . date('d-m-Y_H-i') . '.xlsx';

        return Excel::download(new ComparatorExport(), $filename);
    }

    public function render()
    {
        // Ambil data dari database 'mysql' (default)
        $comparatorItems = DB::connection('mysql')
            ->table('comparator')
            ->get();

        // Ambil data dari database 'kcpinformation'
        $mstParts = DB::connection('kcpinformation')
            ->table('mst_part')
            ->select('part_no', 'nm_part')
            ->get()
            ->keyBy('part_no'); // Indeks data berdasarkan 'part_no' untuk mempermudah pencarian

        // Gabungkan data secara manual
        $items = $comparatorItems->map(function ($item) use ($mstParts) {
            // Cari part di mstParts berdasarkan part_number
            $nmPart = $mstParts->get($item->part_number)->nm_part ?? 'PART NUMBER TIDAK DIKENALI';

            // Tambahkan nama part ke item
            $item->nm_part = $nmPart;

            return $item;
        });

        return view('livewire.comparator-table', compact('items'));
    }
}
