<?php

namespace App\Http\Controllers;

use App\Livewire\SalesOrderDetail;
use App\Models\KcpInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class SalesOrderController extends Controller
{
    public function index()
    {
        return view('so.index');
    }
}
