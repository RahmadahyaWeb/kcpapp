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
            session()->flash('error', 'Barcode tidak boleh kosong.');
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
        $items = DB::table('comparator')
            ->leftJoin('kcpinformation.mst_part', 'comparator.part_number', '=', 'mst_part.part_no')
            ->select(
                'comparator.*',
                DB::raw('IFNULL(mst_part.nm_part, "PART NUMBER TIDAK DIKENALI") as nm_part')
            )
            ->get();

        return view('livewire.comparator-table', compact('items'));
    }
}
