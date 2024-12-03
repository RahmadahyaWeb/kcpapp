<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComparatorController extends Controller
{
    public function guard()
    {
        $userRoles = explode(',', Auth::user()->role);

        $allowedRoles = ['ADMIN', 'STORER', 'KEPALA-GUDANG', 'INVENTORY'];

        if (empty(array_intersect($allowedRoles, $userRoles))) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function index()
    {
        $this->guard();
        
        return view('comparator.index');
    }

    public function destroy($part_number)
    {
        $this->guard();

        DB::table('comparator')
            ->where('part_number', $part_number)
            ->delete();

        return back()->with('success', 'Data berhasil dihapus');
    }
}
