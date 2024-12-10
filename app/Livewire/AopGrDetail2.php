<?php

namespace App\Livewire;

use App\Http\Controllers\API\GoodReceiptController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AopGrDetail extends Component
{
    public $spb;
    public $statusItem;
    public $selectedItems = [];
    public $details = [];
    public $selectAll = false;

    public function mount($spb)
    {
        $this->spb = $spb;
    }

    public function getIntransitBySpb($spb)
    {
        $intransitStock = DB::connection('kcpinformation')
            ->table('intransit_header as a')
            ->join('intransit_details as b', 'a.no_sp_aop', '=', 'b.no_sp_aop')
            ->where('a.no_sp_aop', '=', $spb)
            ->select('a.no_sp_aop', 'a.kd_gudang_aop', 'a.tgl_packingsheet', 'b.no_packingsheet', 'b.no_doos', 'b.part_no', 'b.qty', 'b.qty_terima')
            ->get();

        if ($intransitStock) {
            return $intransitStock;
        }
    }

    public function updatedSelectedItems($value)
    {
        $selectedItems[] = $value;
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedItems = collect($this->details)->pluck('materialNumber')->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function sendToBosnet()
    {
        try {
            $controller = new GoodReceiptController();
            $controller->sendToBosnet(new Request([
                'spb'    => $this->spb,
                'items'  => $this->selectedItems,
            ]));
            session()->flash('success', "Data GR berhasil dikirim!");
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Ambil semua detail invoice berdasarkan SPB yang diberikan
        $details = DB::table('invoice_aop_detail')
            ->where('SPB', $this->spb)
            ->get();

        $this->details = $details;

        // Mengelompokkan data berdasarkan materialNumber
        $grouped = $this->groupDetails($details);

        // Ambil data intransit yang terkait dengan SPB
        $dataIntransit = $this->getIntransitBySpb($this->spb)->toArray();

        // Hitung total qty_terima untuk setiap part_no
        $qtyTerimaByPartNo = $this->calculateQtyTerima($dataIntransit);

        // Update qty_terima pada masing-masing item yang sudah dikelompokkan
        $finalResult = $this->updateQtyTerima($grouped, $qtyTerimaByPartNo);

        // Render view dengan data yang sudah diproses
        return view('livewire.aop-gr-detail', compact('finalResult'));
    }

    /**
     * Mengelompokkan detail invoice berdasarkan materialNumber.
     *
     * @param \Illuminate\Support\Collection $details
     * @return array
     */
    private function groupDetails($details)
    {
        $grouped = [];

        foreach ($details as $detail) {
            // Jika statusItem ditentukan, filter berdasarkan status
            if (!empty($this->statusItem) && $detail->status !== $this->statusItem) {
                continue;
            }

            // Mengambil key sebagai materialNumber
            $key = $detail->materialNumber;

            // Ambil header yang terkait dengan materialNumber dan SPB yang sama
            $status = $this->getStatusForMaterialNumber($detail, $key);

            // Jika materialNumber belum ada dalam grouped, inisialisasi arraynya
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'materialNumber'    => $detail->materialNumber,
                    'total_qty'         => 0,
                    'statusHeader'      => $status,
                    'statusItem'        => $detail->status,
                    'invoices'          => []
                ];
            }

            // Tambahkan qty ke total_qty
            $grouped[$key]['total_qty'] += $detail->qty;

            // Kelompokkan qty berdasarkan invoiceAop
            $grouped[$key]['invoices'][$detail->invoiceAop] =
                isset($grouped[$key]['invoices'][$detail->invoiceAop])
                ? $grouped[$key]['invoices'][$detail->invoiceAop] + $detail->qty
                : $detail->qty;
        }

        return array_values($grouped);
    }

    /**
     * Mendapatkan status berdasarkan materialNumber dari header terkait.
     *
     * @param object $detail
     * @param string $materialNumber
     * @return string
     */
    private function getStatusForMaterialNumber($detail, $materialNumber)
    {
        // Ambil header yang terkait
        $header = DB::table('invoice_aop_header as h')
            ->join('invoice_aop_detail as d', 'h.invoiceAop', '=', 'd.invoiceAop')
            ->where('h.SPB', $detail->SPB)
            ->where('d.materialNumber', $materialNumber)
            ->select('h.*')
            ->get();

        // Tentukan status berdasarkan header
        $status = 'BOSNET';
        foreach ($header as $value) {
            if ($value->status == 'KCP') {
                $status = 'KCP';
            }
        }

        return $status;
    }

    /**
     * Menghitung total qty_terima berdasarkan part_no.
     *
     * @param array $dataIntransit
     * @return array
     */
    private function calculateQtyTerima($dataIntransit)
    {
        return array_reduce($dataIntransit, function ($carry, $item) {
            $partNo = $item->part_no;
            $qtyTerima = (int)$item->qty_terima;

            // Tambahkan qty_terima ke part_no yang sesuai
            if (!isset($carry[$partNo])) {
                $carry[$partNo] = 0;
            }

            $carry[$partNo] += $qtyTerima;

            return $carry;
        }, []);
    }

    /**
     * Memperbarui qty_terima untuk setiap item berdasarkan qty yang sudah dihitung.
     *
     * @param array $grouped
     * @param array $qtyTerimaByPartNo
     * @return array
     */
    private function updateQtyTerima($grouped, $qtyTerimaByPartNo)
    {
        foreach ($grouped as &$item) {
            $materialNumber = $item['materialNumber'];
            $item['qty_terima'] = 0;

            // Jika qty_terima ditemukan untuk materialNumber, perbarui nilai qty_terima
            if (isset($qtyTerimaByPartNo[$materialNumber])) {
                $item['qty_terima'] = $qtyTerimaByPartNo[$materialNumber];
            }
        }

        return $grouped;
    }
}
