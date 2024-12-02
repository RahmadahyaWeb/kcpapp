<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SalesOrderTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $kcpInformation;
    public $token;

    public $noSo = '';
    public $noInv = '';
    public $status = '';

    public function render()
    {
        $nominalPlafondSementara = DB::connection('kcpinformation')
            ->table('trns_so_header as sub_header')
            ->select([
                'sub_header.kd_outlet',
                DB::raw('IFNULL(SUM(details.nominal_total), 0) as nominal_plafond_sementara'),
            ])
            ->join('trns_so_details as details', 'details.noso', '=', 'sub_header.noso')
            ->where('sub_header.flag_selesai', 'Y')
            ->where('sub_header.flag_invoice', 'N')
            ->where('sub_header.flag_reject', 'N')
            ->groupBy('sub_header.kd_outlet');

        $items = DB::connection('kcpinformation')
            ->table('trns_so_header as header')
            ->select([
                'header.noso',
                'header.area_so',
                'header.kd_outlet',
                'header.nm_outlet',
                DB::raw('SUM(details.nominal_total) as nominal_total'),
                'header.user_sales',
                'header.flag_approve',
                'header.flag_unlock_ar',
                'header.crea_date',
                'outlet.kode_kab',
                'plafond.nominal_plafond',
                'user.fullname',
                'mutasi.no_mutasi'
            ])
            ->join('trns_so_details as details', 'details.noso', '=', 'header.noso')
            ->join('mst_outlet as outlet', 'outlet.kd_outlet', '=', 'header.kd_outlet')
            ->join('trns_plafond as plafond', 'plafond.kd_outlet', '=', 'header.kd_outlet')
            ->leftJoinSub($nominalPlafondSementara, 'plafond_sementara', function ($join) {
                $join->on('plafond_sementara.kd_outlet', '=', 'header.kd_outlet');
            })
            ->leftJoin('trns_mutasi_header as mutasi', function ($join) {
                $join->on('mutasi.noso', '=', 'header.noso')
                    ->where('mutasi.flag_batal', '=', 'N');
            })
            ->join('user', 'user.username', '=', 'header.user_sales')
            ->where('header.status', 'O')
            ->where('header.flag_selesai', 'N')
            ->where('header.flag_cetak_gudang', 'N')
            ->where('header.flag_reject', '<>', 'Y')
            ->groupBy(
                'header.noso',
                'header.kd_outlet',
                'header.nm_outlet',
                'mutasi.no_mutasi'
            )
            ->paginate(10);

        foreach ($items as $item) {
            $item->nominal_plafond_sementara = $item->nominal_plafond_sementara ?? 0;
        }

        return view('livewire.sales-order-table', compact(
            'items'
        ));
    }
}
