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

        if ($kd_toko) {
            $kd_toko = base64_decode($kd_toko);

            $toko = DB::table('master_toko')
                ->select([
                    'kd_toko',
                    'nama_toko',
                    'latitude',
                    'longitude',
                ])
                ->where('kd_toko', $kd_toko)
                ->first();

            $katalog = $request->get('katalog');

            if ($toko) {
                if ($katalog && $katalog[6] == 'Y') {
                    $check = 'katalog';
                } else {
                    // CHECK TYPE APAKAH CEK IN ATAU CEK OUT
                    $check = DB::table('trns_dks')
                        ->where('kd_toko', $kd_toko)
                        ->where('user_sales', Auth::user()->username)
                        ->where('type', 'in')
                        ->whereDate('tgl_kunjungan', '=', now()->toDateString())
                        ->count();
                }

                return view('dks.submit', compact('toko', 'katalog', 'check'));
            } else {
                return redirect()->route('dks.scan')->with('error', "Kode toko tidak ditemukan.");
            }
        } else {
            return view('dks.index');
        }
    }

    public function scan()
    {
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
        return redirect()->back()->with('error', $message);
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
            if ($katalog[6] == 'Y') {
                throw new \Exception('Tidak dapat scan katalog. Anda belum melakukan check in!');
            }
            return 'in';
        }

        if ($check == 2) {
            if ($katalog[6] == 'Y') {
                throw new \Exception('Tidak dapat scan katalog. Anda sudah melakukan check out!');
            }
            throw new \Exception('Anda sudah melakukan check out!');
        }

        return 'out';
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

            if ($katalog[6] == 'Y') {
                $data['type'] = 'katalog';
                $data['katalog'] = 'Y';
                $data['katalog_at'] = $waktu_kunjungan;

                $this->validateCatalogScan($kd_toko, $user);
            }

            DB::table('trns_dks')->insert($data);
            DB::commit();

            $action = $katalog[6] == 'Y' ? 'scan katalog' : "check $type";
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
}
