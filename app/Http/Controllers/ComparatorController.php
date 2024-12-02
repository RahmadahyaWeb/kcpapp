<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComparatorController extends Controller
{
    public function index()
    {
        return view('comparator.index');
    }

    public function destroy($part_number)
    {
        DB::table('comparator')
            ->where('part_number', $part_number)
            ->delete();

        return back()->with('success', 'Data berhasil dihapus');
    }
}
