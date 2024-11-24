<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapPunishmentExport implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;
    protected $items;

    public function __construct($fromDate, $toDate, $items)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->items = $items;

    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->items as $user_sales => $value) {
            $sheets[] = new SalesSheet($user_sales, $this->fromDate, $this->toDate, $value);
        }

        return $sheets;
    }
}
