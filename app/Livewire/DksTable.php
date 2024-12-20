<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DksTable extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $user = Auth::user()->username;

        $items = DB::table('trns_dks AS in_data')
            ->select(
                'in_data.user_sales',
                'master_toko.nama_toko',
                'in_data.waktu_kunjungan AS waktu_cek_in',
                'out_data.waktu_kunjungan AS waktu_cek_out',
                'in_data.tgl_kunjungan',
                'out_data.keterangan',
                'master_toko.kd_toko',
                'katalog_data.katalog_at',
                DB::raw('CASE 
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
            ->where('in_data.user_sales', $user)
            ->whereDate('in_data.tgl_kunjungan', '=', now()->toDateString())
            ->orderBy('in_data.created_at')
            ->get();

        return view('livewire.dks-table', compact('items'));
    }
}
