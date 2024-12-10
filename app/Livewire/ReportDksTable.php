<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReportDksTable extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $target = 'toDate, user_sales, kd_toko';

    public $fromDate;
    public $toDate;
    public $user_sales;
    public $kd_toko;

    public function render()
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $items = DB::table('trns_dks AS in_data')
            ->select(
                'in_data.user_sales',
                'master_toko.nama_toko',
                'in_data.waktu_kunjungan AS waktu_cek_in',
                'out_data.waktu_kunjungan AS waktu_cek_out',
                'in_data.tgl_kunjungan',
                'out_data.keterangan',
                'in_data.kd_toko',
                'katalog_data.katalog_at',
                DB::raw('
                    CASE 
                        WHEN out_data.waktu_kunjungan IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, in_data.waktu_kunjungan, out_data.waktu_kunjungan) 
                        ELSE NULL 
                    END AS lama_kunjungan')
            )
            ->leftJoin('trns_dks AS out_data', function ($join) {
                $join->on('in_data.user_sales', '=', 'out_data.user_sales')
                    ->whereColumn('in_data.kd_toko', 'out_data.kd_toko')
                    ->whereColumn('in_data.tgl_kunjungan', 'out_data.tgl_kunjungan')
                    ->where('out_data.type', '=', 'out');
            })
            ->leftJoin('master_toko', 'in_data.kd_toko', '=', 'master_toko.kd_toko')
            ->leftJoin('trns_dks AS katalog_data', function ($join) {
                $join->on('in_data.user_sales', '=', 'katalog_data.user_sales')
                    ->whereColumn('in_data.kd_toko', 'katalog_data.kd_toko')
                    ->whereColumn('in_data.tgl_kunjungan', 'katalog_data.tgl_kunjungan')
                    ->where('katalog_data.type', '=', 'katalog');
            })
            ->where('in_data.type', 'in')
            ->when($this->fromDate && $this->toDate, function ($query) {
                return $query->whereBetween('in_data.tgl_kunjungan', [$this->fromDate, $this->toDate]);
            })
            ->when($this->user_sales, function ($query) {
                return $query->where('in_data.user_sales', $this->user_sales);
            })
            ->when($this->kd_toko, function ($query) {
                return $query->where('master_toko.kd_toko', $this->kd_toko);
            })
            ->whereDate('in_data.tgl_kunjungan', '>=', $startOfMonth)
            ->orderBy('in_data.created_at', 'desc')
            ->paginate(20);

        $sales = DB::table('users')
            ->select(['*'])
            ->where('role', 'SALESMAN')
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get();

        $dataToko = DB::table('master_toko')
            ->select(['*'])->where('status', 'active')
            ->orderBy('nama_toko', 'asc')
            ->get();

        return view('livewire.report-dks-table', compact('items', 'sales', 'dataToko'));
    }
}
