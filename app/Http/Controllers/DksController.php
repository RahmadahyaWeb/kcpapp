<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DksController extends Controller
{
    public function guard()
    {
        $userRoles = explode(',', Auth::user()->role);

        $allowedRoles = ['ADMIN', 'SALESMAN'];

        if (empty(array_intersect($allowedRoles, $userRoles))) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function index(Request $request, $kd_toko = null)
    {
        $this->guard();

        if (!$kd_toko) {
            return view('dks.index');
        }

        $toko = $this->getTokoData($kd_toko);

        if (!$toko) {
            return redirect()->route('dks.scan')->with('error', "Kode toko tidak ditemukan.");
        }

        $check = DB::table('trns_dks')
            ->where('kd_toko', $kd_toko)
            ->where('user_sales', Auth::user()->username)
            ->where('type', 'in')
            ->whereDate('tgl_kunjungan', now()->toDateString())
            ->count();

        $katalog = $request->get('katalog');

        if ($katalog) {
            $check = 'katalog';
        }

        return view('dks.submit', compact('toko', 'katalog', 'check'));
    }

    public function scan()
    {
        $this->guard();

        return view('dks.scan');
    }

    public function store(Request $request, $kd_toko)
    {
        $this->guard();

        // Data Input User
        $latitude   = $request->latitude;
        $longitude  = $request->longitude;
        $keterangan = strtolower($request->keterangan);
        $user       = Auth::user()->username;
        $katalog    = $request->get('katalog');
        $distance   = $request->distance;

        // Validasi Lokasi
        if (!$latitude || !$longitude) {
            return $this->redirectBackWithError('Lokasi tidak ditemukan!');
        }

        if ($distance > 50) {
            return $this->redirectBackWithError('Anda berada di luar radius toko!');
        }

        // Validasi Check-In dan Check-Out atau Katalog
        try {
            $type = $this->determineCheckType($kd_toko, $user, $katalog);
        } catch (\Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        // Validasi Toko Aktif
        $provinsiToko = $this->validateActiveStore($kd_toko);
        if (!$provinsiToko) {
            return $this->redirectBackWithError("Toko dengan kode $kd_toko tidak aktif!");
        }

        // Penyesuaian Waktu
        $waktu_kunjungan = $this->adjustVisitTime($provinsiToko->kd_provinsi);

        // Proses Penyimpanan Data
        return $this->processStore($type, $kd_toko, $user, $latitude, $longitude, $keterangan, $waktu_kunjungan, $katalog);
    }

    private function redirectBackWithError($message)
    {
        return redirect()->route('dks.scan')->with('error', $message);
    }

    private function determineCheckType($kd_toko, $user, $katalog)
    {
        $check = DB::table('trns_dks')
            ->where('kd_toko', $kd_toko)
            ->where('user_sales', $user)
            ->where('type', '!=', 'katalog')
            ->whereDate('tgl_kunjungan', now()->toDateString())
            ->count();

        if ($check == 0) {
            if ($katalog == 'Y') {
                throw new \Exception('Tidak dapat scan katalog. Anda belum melakukan check in!');
            }
            return 'in';
        }

        if ($check == 2) {
            if ($katalog == 'Y') {
                throw new \Exception('Tidak dapat scan katalog. Anda sudah melakukan check out!');
            }
            throw new \Exception('Anda sudah melakukan check out!');
        }

        if ($check == 1) {
            if ($katalog == 'Y') {
                return 'katalog';
            }

            return 'out';
        }

    }

    private function validateActiveStore($kd_toko)
    {
        return DB::table('master_toko')
            ->where('kd_toko', $kd_toko)
            ->where('status', 'active')
            ->first();
    }

    private function adjustVisitTime($kd_provinsi)
    {
        return ($kd_provinsi == 2) ? now()->subHour() : now();
    }

    private function processStore($type, $kd_toko, $user, $latitude, $longitude, $keterangan, $waktu_kunjungan, $katalog)
    {
        DB::beginTransaction();
        try {
            // Validasi jeda waktu minimal 5 menit
            // $lastRecord = DB::table('trns_dks')
            //     ->where('kd_toko', $kd_toko)
            //     ->where('user_sales', $user)
            //     ->latest('waktu_kunjungan')
            //     ->first();

            // if ($lastRecord) {
            //     $lastVisitTime = \Carbon\Carbon::parse($lastRecord->waktu_kunjungan);

            //     // Selisih waktu dalam menit
            //     $timeDifference = $lastVisitTime->diff($waktu_kunjungan)->i;

            //     if ($timeDifference < 5) {
            //         throw new \Exception('Harus menunggu minimal 5 menit sebelum melakukan scan berikutnya.');
            //     }
            // }

            // Data yang akan disimpan
            $data = [
                'tgl_kunjungan'     => now(),
                'user_sales'        => $user,
                'kd_toko'           => $kd_toko,
                'waktu_kunjungan'   => $waktu_kunjungan,
                'type'              => $type,
                'latitude'          => $latitude,
                'longitude'         => $longitude,
                'keterangan'        => $keterangan,
                'created_by'        => $user,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];

            // Jika katalog, tambahkan validasi dan data tambahan
            if ($katalog == 'Y') {
                $data['type'] = 'katalog';
                $data['katalog'] = 'Y';
                $data['katalog_at'] = $waktu_kunjungan;

                $this->validateCatalogScan($kd_toko, $user);
            }

            // Simpan data
            DB::table('trns_dks')->insert($data);
            DB::commit();

            $action = $katalog == 'Y' ? 'scan katalog' : "check $type";
            return redirect()->route('dks.scan')->with('success', "Berhasil melakukan $action");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->redirectBackWithError($e->getMessage());
        }
    }

    private function validateCatalogScan($kd_toko, $user)
    {
        $checkKatalog = DB::table('trns_dks')
            ->where('kd_toko', $kd_toko)
            ->where('user_sales', $user)
            ->where('type', 'katalog')
            ->whereDate('tgl_kunjungan', now()->toDateString())
            ->count();

        if ($checkKatalog > 0) {
            throw new \Exception('Anda sudah melakukan scan katalog!');
        }
    }

    private function getTokoData(string $kd_toko)
    {
        return DB::table('master_toko')
            ->select(['kd_toko', 'nama_toko', 'latitude', 'longitude'])
            ->where('kd_toko', $kd_toko)
            ->first();
    }
}
