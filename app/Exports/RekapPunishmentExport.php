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

        $sales = array_keys($this->items);

        $sheets[] = new RekapSheet($sales, $this->fromDate, $this->toDate, $this->items);

        foreach ($this->items as $user_sales => $value) {
            $nama_sales = $value[0]->name;

            $sheets[] = new SalesSheet($nama_sales, $this->fromDate, $this->toDate, $value);
        }

        return $sheets;
    }
}
