<?php

namespace App\Exports;

use DateTime;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class FrekuensiSheet implements WithTitle, WithEvents, WithColumnFormatting
{
    protected $sales;
    protected $fromDate;
    protected $toDate;

    public function __construct($sales, $fromDate, $toDate)
    {
        $this->sales = $sales;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $this->setHeader($sheet);
                $dates = $this->getDateRange();
                // $this->populateData($sheet, $dates);
                $this->autoSizeColumns($sheet);
            }
        ];
    }

    public function setHeader($sheet)
    {
        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'KODE TOKO');
        $sheet->setCellValue('C1', 'TOKO');
        $sheet->setCellValue('D1', 'KAB/KOTA');
        $sheet->setCellValue('E1', 'PROVINSI');
        $sheet->setCellValue('F1', 'SALES');
        $sheet->setCellValue('G1', 'MINIMAL KUNJUNGAN');
        $sheet->setCellValue('H1', 'REALISASI KUNJUNGAN');

        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => ['bold' => true],
        ];
        $sheet->getDelegate()->getStyle('A1:H1')->applyFromArray($styleArray);
    }

    private function getDateRange()
    {
        $dateBeginLoop = new DateTime($this->fromDate);
        $dateEndLoop = new DateTime($this->toDate);
        $dates = [];

        while ($dateBeginLoop <= $dateEndLoop) {
            $dates[] = $dateBeginLoop->format('Y-m-d');
            $dateBeginLoop->modify('+1 day');
        }

        return $dates;
    }

    private function autoSizeColumns($sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        foreach (range(1, $highestColumnIndex) as $columnIndex) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }

    public function title(): string
    {
        return 'Frekuensi Kunjungan';
    }

    public function columnFormats(): array
    {
        return [];
    }
}
