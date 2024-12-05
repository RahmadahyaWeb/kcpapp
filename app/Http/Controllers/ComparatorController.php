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

    public function edit_qty(Request $request)
    {
        DB::table('comparator')
            ->where('part_number', $request->part_number)
            ->update([
                'qty' => $request->edited_qty
            ]);

        return redirect()->route('comparator.index')->with('success', 'Qty berhasil diperbarui');
    }
}
