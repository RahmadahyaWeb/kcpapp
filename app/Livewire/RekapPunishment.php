<?php

namespace App\Livewire;

use App\Exports\RekapFrekuensiKunjungan;
use App\Exports\RekapPunishmentExport;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class RekapPunishment extends Component
{
    public $fromDate;
    public $toDate;
    public $user_sales = 'all';
    public $laporan;

    public function render()
    {
        $users = User::whereRaw('FIND_IN_SET("SALESMAN", role)')->get();

        return view('livewire.rekap-punishment', compact('users'));
    }

    public function export()
    {
        $this->validate([
            'fromDate'      => 'required',
            'toDate'        => 'required',
            'laporan'       => 'required'
        ]);

        $usersQuery = User::whereRaw('FIND_IN_SET("SALESMAN", role)');

        if ($this->user_sales != 'all') {
            $usersQuery->where('username', '=', $this->user_sales);
        }

        $users = $usersQuery->get();

        if ($this->laporan == 'rekap_punishment') {
            return $this->exportRekapPunishment($users);
        } else if ($this->laporan == 'frekuensi_kunjungan') {
            return $this->exportFrekuensiKunjungan($users);
        }
    }

    public function exportFrekuensiKunjungan($users)
    {
        $fromDateFormatted = \Carbon\Carbon::parse($this->fromDate)->format('Ymd');
        $toDateFormatted = \Carbon\Carbon::parse($this->toDate)->format('Ymd');

        $filename = "frekuensi-kunjungan_{$fromDateFormatted}_-_{$toDateFormatted}.xlsx";

        return Excel::download(new RekapFrekuensiKunjungan($this->fromDate, $this->toDate, $users), $filename);
    }

    public function exportRekapPunishment($users)
    {
        $dates = $this->getDateRange();

        $usersData = [];

        foreach ($users as $user) {
            $userData = collect();

            foreach ($dates as $date) {
                $dailyData = DB::table('trns_dks AS in_data')
                    ->select(
                        'in_data.user_sales',
                        'master_toko.nama_toko',
                        'in_data.waktu_kunjungan AS waktu_cek_in',
                        DB::raw('COALESCE(out_data.waktu_kunjungan, in_data.waktu_kunjungan) AS waktu_cek_out'),
                        'in_data.tgl_kunjungan',
                        'out_data.keterangan',
                        'in_data.kd_toko',
                        'katalog_data.katalog_at',
                        'users.name',
                        DB::raw('
                            CASE 
                                WHEN out_data.waktu_kunjungan IS NOT NULL 
                                THEN TIMESTAMPDIFF(MINUTE, in_data.waktu_kunjungan, out_data.waktu_kunjungan) 
                                ELSE NULL 
                            END AS lama_kunjungan'),
                        DB::raw('0 AS durasi_perjalanan'),
                        'in_data.id',
                    )
                    ->leftJoin('trns_dks AS out_data', function ($join) {
                        $join->on('in_data.user_sales', '=', 'out_data.user_sales')
                            ->whereColumn('in_data.kd_toko', 'out_data.kd_toko')
                            ->whereColumn('in_data.tgl_kunjungan', 'out_data.tgl_kunjungan')
                            ->where('out_data.type', '=', 'out');
                    })
                    ->leftJoin('master_toko', 'in_data.kd_toko', '=', 'master_toko.kd_toko')
                    ->leftJoin('users', 'users.username', '=', 'in_data.user_sales')
                    ->leftJoin('trns_dks AS katalog_data', function ($join) {
                        $join->on('in_data.user_sales', '=', 'katalog_data.user_sales')
                            ->whereColumn('in_data.kd_toko', 'katalog_data.kd_toko')
                            ->whereColumn('in_data.tgl_kunjungan', 'katalog_data.tgl_kunjungan')
                            ->where('katalog_data.type', '=', 'katalog');
                    })
                    ->whereDate('in_data.tgl_kunjungan', $date)
                    ->where('in_data.user_sales', $user->username)
                    ->where('in_data.type', 'in')
                    ->orderBy('in_data.created_at', 'asc')
                    ->get();

                if ($dailyData->isEmpty()) {
                    $userData->push((object)[
                        'user_sales'        => $user->username,
                        'nama_toko'         => null,
                        'waktu_cek_in'      => null,
                        'waktu_cek_out'     => null,
                        'tgl_kunjungan'     => $date,
                        'keterangan'        => null,
                        'kd_toko'           => null,
                        'katalog_at'        => null,
                        'lama_kunjungan'    => null,
                        'durasi_perjalanan' => 0,
                        'name'              => $user->name
                    ]);
                } else {
                    $dailyData->each(function ($item) use ($userData) {

                        $cekInSelanjutnya = DB::table('trns_dks')
                            ->select(['*'])
                            ->where('user_sales', $item->user_sales)
                            ->whereDate('tgl_kunjungan', $item->tgl_kunjungan)
                            ->where('type', 'in')
                            ->where('waktu_kunjungan', '>', $item->waktu_cek_in)  // Tambahkan kondisi waktu_kunjungan
                            ->first();

                        if ($cekInSelanjutnya) {
                            $cek_out = Carbon::parse($item->waktu_cek_out);
                            $cek_in  = Carbon::parse($cekInSelanjutnya->waktu_kunjungan);

                            $selisih = $cek_out->diff($cek_in);
                            $lama_perjalanan = sprintf('%02d:%02d:%02d', $selisih->h, $selisih->i, $selisih->s);

                            $item->durasi_perjalanan = $lama_perjalanan;
                        }

                        $tokoAbsen = [
                            '6B',
                            '6C',
                            '6D',
                            '6F',
                            '6H',
                            'TX'
                        ];

                        if (in_array($item->kd_toko, $tokoAbsen)) {
                            $item->durasi_perjalanan = 0;
                        }

                        $userData->push((object)$item);
                    });
                }
            }

            $usersData[$user->username] = $userData;
        }

        $fromDateFormatted = \Carbon\Carbon::parse($this->fromDate)->format('Ymd');
        $toDateFormatted = \Carbon\Carbon::parse($this->toDate)->format('Ymd');

        $filename = "rekap-punishment_{$fromDateFormatted}_-_{$toDateFormatted}.xlsx";

        return Excel::download(new RekapPunishmentExport($this->fromDate, $this->toDate, $usersData), $filename);
    }

    private function getDateRange()
    {
        $dateBeginLoop = new DateTime($this->fromDate);
        $dateEndLoop = new DateTime($this->toDate);
        $dates = [];

        while ($dateBeginLoop <= $dateEndLoop) {
            $dates[] = $dateBeginLoop->format('Y-m-d');
            $dateBeginLoop->modify('+1 day');
        }

        return $dates;
    }
}
