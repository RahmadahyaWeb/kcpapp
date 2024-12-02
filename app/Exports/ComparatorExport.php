<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;

class ComparatorExport implements FromCollection, WithHeadings, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DB::table('comparator')
            ->leftJoin('kcpinformation.mst_part', 'comparator.part_number', '=', 'mst_part.part_no')
            ->select(
                'comparator.part_number',
                DB::raw('IFNULL(mst_part.nm_part, "PART NUMBER TIDAK DIKENALI") as nm_part'),
                'comparator.qty'
            )
            ->get();
    }

    /**
     * Define the headings of the export.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'PART NUMBER',
            'NAMA PART',
            'QTY'
        ];
    }

    /**
     * Register events for after the sheet is loaded.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Auto size columns A, B, and C
                $event->sheet->getDelegate()->getColumnDimension('A')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('B')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('C')->setAutoSize(true);

                $event->sheet->getDelegate()->getStyle('A1')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('B1')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('C1')->getFont()->setBold(true);
            },
        ];
    }
}
