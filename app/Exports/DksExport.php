<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DksExport implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;

    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function sheets(): array
    {
        $sales = DB::table('trns_dks')->where('type', 'in')
            ->distinct()
            ->pluck('user_sales');

        $sheets = [];

        foreach ($sales as $user_sales) {
            $sheets[] = new SalesSheet($user_sales, $this->fromDate, $this->toDate);
        }

        $sheets[] = new KunjunganSheet($sales, $this->fromDate, $this->toDate);

        $sheets[] = new RekapSheet($sales, $this->fromDate, $this->toDate);

        return $sheets;
    }
}
