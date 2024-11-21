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

        // DATA USER
        $latitude   = $request->latitude;
        $longitude  = $request->longitude;
        $keterangan = strtolower($request->keterangan);
        $user       = Auth::user()->username;
        $katalog    = $request->get('katalog');

        // JARAK ANTARA USER DENGAN TOKO DALAM METER
        $distance = $request->distance;

        // VALIDASI JARAK ANTARA USER DENGAN TOKO
        if ($distance > 50) {
            return redirect()->back()->with('error', 'Anda berada di luar radius toko!');
        }

        // VALIDASI CHECK IN / CHECK OUT
        $type = '';

        $check = DB::table('trns_dks')
            ->where('kd_toko', $kd_toko)
            ->where('user_sales', $user)
            ->where('type', '!=', 'katalog')
            ->whereDate('tgl_kunjungan', '=', now()->toDateString())
            ->count();

        // Jika belum ada kunjungan check-in
        if ($check == 0) {
            $type = 'in';

            if ($katalog[6] == 'Y') {
                return redirect()->back()->with('error', 'Tidak dapat scan katalog. Anda belum melakukan check in!');
            }
        } else if ($check == 2) {
            if ($katalog[6] == 'Y') {
                return redirect()->back()->with('error', 'Tidak dapat scan katalog. Anda sudah melakukan check out!');
            }

            return redirect()->back()->with('error', 'Anda sudah melakukan check out!');
        } else if ($keterangan == 'ist') {
            $type = 'out';

            if ($katalog[6] == 'Y') {
                return redirect()->back()->with('error', 'Anda sudah melakukan scan katalog!');
            }
        } else {
            $type = 'out';
        }

        // VALIDASI KATALOG
        if ($katalog[6] == 'Y') {
            $checkKatalog = DB::table('trns_dks')
                ->where('kd_toko', $kd_toko)
                ->where('user_sales', $user)
                ->where('type', '=', 'katalog')
                ->whereDate('tgl_kunjungan', '=', now()->toDateString())
                ->count();

            if ($checkKatalog > 0) {
                return redirect()->back()->with('error', 'Anda sudah melakukan scan katalog!');
            }
        }

        // VALIDASI TOKO AKTIF
        $provinsiToko = DB::table('master_toko')
            ->select(['*'])
            ->where('kd_toko', $kd_toko)
            ->where('status', 'active')
            ->first();

        if ($provinsiToko == null) {
            return back()->with('error', "Toko dengan kode $kd_toko tidak aktif!");
        }

        if ($provinsiToko->kd_provinsi == 2) {
            $waktu_kunjungan = now()->modify('-1 hour');
        } else {
            $waktu_kunjungan = now();
        }

        // Cek jika lokasi ada dan belum ada data untuk hari ini
        if ($latitude && $longitude) {
            // Cek jika katalog dipilih
            if ($katalog[6] == 'Y') {
                // Pastikan belum ada data katalog untuk user dan toko ini pada hari ini
                $existingKatalog = DB::table('trns_dks')
                    ->where('kd_toko', $kd_toko)
                    ->where('user_sales', $user)
                    ->where('type', 'katalog')
                    ->whereDate('tgl_kunjungan', '=', now()->toDateString())
                    ->first();

                if (!$existingKatalog) {
                    // Jika belum ada, insert data katalog
                    DB::table('trns_dks')
                        ->insert(
                            [
                                'tgl_kunjungan'     => now(),
                                'user_sales'        => $user,
                                'kd_toko'           => $kd_toko,
                                'waktu_kunjungan'   => $waktu_kunjungan,
                                'type'              => 'katalog',
                                'latitude'          => $latitude,
                                'longitude'         => $longitude,
                                'keterangan'        => $keterangan,
                                'created_by'        => $user,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                                'katalog'           => 'Y',
                                'katalog_at'        => $waktu_kunjungan
                            ]
                        );
                    return redirect()->route('dks.scan')->with('success', "Berhasil scan katalog");
                } else {
                    return redirect()->back()->with('error', 'Anda sudah melakukan scan katalog untuk toko ini!');
                }
            } else {
                // Jika tidak katalog, insert data check-in atau check-out
                $existingVisit = DB::table('trns_dks')
                    ->where('kd_toko', $kd_toko)
                    ->where('user_sales', $user)
                    ->whereDate('tgl_kunjungan', '=', now()->toDateString())
                    ->where('type', $type)
                    ->first();

                if (!$existingVisit) {
                    DB::table('trns_dks')
                        ->insert(
                            [
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
                            ]
                        );
                    return redirect()->route('dks.scan')->with('success', "Berhasil melakukan check $type");
                } else {
                    return redirect()->back()->with('error', 'Anda sudah melakukan check-in atau check-out untuk toko ini!');
                }
            }
        } else {
            return redirect()->back()->with('error', 'Lokasi tidak ditemukan!');
        }
    }
}
