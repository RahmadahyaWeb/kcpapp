<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapFrekuensiKunjungan implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;
    protected $users;

    public function __construct($fromDate, $toDate, $users)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->users = $users;

    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->users as $user_sales) {
            $sheets[] = new FrekuensiSheet($user_sales, $this->fromDate, $this->toDate);
        }

        return $sheets;
    }
}
