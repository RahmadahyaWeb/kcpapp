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

    public function rekap()
    {
        $this->guard();

        return view('report-dks.rekap-punishment');
    }
}
