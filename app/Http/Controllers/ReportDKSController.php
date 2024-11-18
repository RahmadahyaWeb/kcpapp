<?php

namespace App\Http\Controllers;

use App\Exports\DksExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportDKSController extends Controller
{
    public function guard()
    {
        $userRoles = explode(',', Auth::user()->role);

        $allowedRoles = ['ADMIN', 'SUPERVISOR-AREA', 'HEAD-MARKETING'];

        if (empty(array_intersect($allowedRoles, $userRoles))) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function index()
    {
        $this->guard();

        return view('report-dks.index');
    }

    public function export(Request $request)
    {
        $this->guard();

        $request->validate([
            'fromDate'  => 'required',
            'toDate'    => 'required',
        ]);

        $fromDateFormatted = \Carbon\Carbon::parse($request->fromDate)->format('Ymd');
        $toDateFormatted = \Carbon\Carbon::parse($request->toDate)->format('Ymd');

        $filename = "dks_{$fromDateFormatted}_-_{$toDateFormatted}.xlsx";

        return Excel::download(new DksExport($request->fromDate, $request->toDate), $filename);
    }
}
