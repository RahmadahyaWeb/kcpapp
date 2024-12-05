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
    public $number_update;
    public $items = [];

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

    public function updateQty($qty, $part_number)
    {
        try {
            DB::beginTransaction();

            // Validate qty
            if ($qty < 1) {
                session()->flash('error', 'Qty tidak bisa kurang dari 1.');
                return;
            }

            $save_part_number = str_replace(' ', '', trim($part_number));

            // Update qty in the database
            DB::table('comparator')
                ->where('part_number', $save_part_number)
                ->update(['qty' => $qty]);

            DB::commit(); // Commit transaction

            $this->dispatch('qty-saved');

            $this->reset('number_update');

            session()->flash('success', "Qty berhasil diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction if error occurs
            session()->flash('error', "Terjadi kesalahan saat memperbarui qty.");
        }
    }

    public function increment($part_number)
    {
        DB::table('comparator')
            ->where('part_number', $part_number)
            ->increment('qty', 1);
    }

    public function decrement($part_number)
    {
        DB::table('comparator')
            ->where('part_number', $part_number)
            ->decrement('qty', 1);
    }

    public function destroy($part_number)
    {
        DB::table('comparator')
            ->where('part_number', $part_number)
            ->delete();

        $this->dispatch('qty-saved');

        session()->flash('success', "Data berhasil dihapus.");
    }

    public function render()
    {
        // Ambil data dari database 'mysql' (default)
        $comparatorItems = DB::connection('mysql')
            ->table('comparator')
            ->get();

        // Ambil daftar part_number dari comparatorItems
        $partNumbers = $comparatorItems->pluck('part_number')->unique();

        // Ambil data hanya untuk part_no yang ada di $partNumbers
        $mstParts = DB::connection('kcpinformation')
            ->table('mst_part')
            ->whereIn('part_no', $partNumbers)
            ->select('part_no', 'nm_part')
            ->get()
            ->mapWithKeys(function ($part) {
                return [(string) $part->part_no => $part];
            });

        // Gabungkan data secara manual dan tambahkan 'nm_part' ke setiap item
        $items = $comparatorItems->map(function ($item) use ($mstParts) {
            // Pastikan part_number di-cast ke string untuk konsistensi pencocokan
            $partNumber = (string) $item->part_number;

            // Cari part di mstParts berdasarkan part_number
            $nmPart = $mstParts->get($partNumber)->nm_part ?? 'PART NUMBER TIDAK DIKENALI';

            // Mengubah objek stdClass menjadi array dan menambahkan 'nm_part'
            return (object) array_merge((array) $item, ['nm_part' => $nmPart]);
        });

        $this->items = $items;

        return view('livewire.comparator-table', compact('items'));
    }
}
