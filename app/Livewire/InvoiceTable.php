<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $kcpInformation;
    public $token;

    public $noSo = '';
    public $noInv = '';
    public $status = '';

    public function print($noinv)
    {
        
        return redirect()->route('so.detail', $noinv);
    }

    public function render()
    {
        $so_belum_invoice = DB::connection('kcpinformation')
            ->table('trns_so_header as header')
            ->join('trns_so_details as details', 'header.noso', '=', 'details.noso')
            ->where('header.status', 'C')
            ->where('header.flag_selesai', 'Y')
            ->where('header.flag_cetak_gudang', 'Y')
            ->where('header.flag_vald_gudang', 'Y')
            ->where('header.flag_packingsheet', 'Y')
            ->where('header.flag_invoice', 'N')
            ->where('header.flag_reject', 'N')
            ->whereIn('header.no_packingsheet', function ($query) {
                $query->select('nops')
                    ->from('trns_packingsheet_header')
                    ->where('status', 'C');
            })
            ->groupBy('header.noso', 'header.area_so', 'header.kd_outlet', 'header.nm_outlet', 'header.user_sales')
            ->select(
                'header.noso',
                'header.area_so',
                'header.kd_outlet',
                'header.nm_outlet',
                DB::raw('SUM(details.nominal_total_gudang) as nominal_total'),
                'header.user_sales'
            )
            ->get();

        $ppn_factor = config('tax.ppn_factor');

        $invoices = DB::connection('kcpinformation')
            ->table('trns_inv_header as a')
            ->join('trns_inv_details as b', 'a.noinv', '=', 'b.noinv')
            ->select(
                'a.noinv',
                'a.area_inv',
                'a.noso',
                'a.kd_outlet',
                'a.nm_outlet',
                'a.tgl_jth_tempo',
                DB::raw('ROUND(SUM(b.nominal)) as nominal_ppn'),
                DB::raw('ROUND(SUM(b.nominal_disc)) as nominal_disc_ppn'),
                DB::raw('ROUND(SUM(b.nominal_total)) as nominal_total_ppn'),
                DB::raw('ROUND(SUM(b.nominal) / ' . $ppn_factor . ') as nominal_nonppn'),
                DB::raw('ROUND(SUM(b.nominal_disc) / ' . $ppn_factor . ') as nominal_disc_noppn'),
                DB::raw('ROUND(SUM(b.nominal_total) / ' . $ppn_factor . ') as nominal_total_noppn')
            )
            ->where('a.status', '=', 'O')
            ->where('a.flag_batal', '=', 'N')
            ->groupBy('a.noinv')
            ->get();

        return view('livewire.invoice-table', compact(
            'invoices',
            'so_belum_invoice'
        ));
    }
}
