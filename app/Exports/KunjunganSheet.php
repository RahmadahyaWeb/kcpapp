<?php

namespace App\Exports;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class KunjunganSheet implements WithTitle, WithEvents, WithColumnFormatting
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
                $daysMap = $this->getDaysMap();
                $this->populateData($sheet, $dates, $daysMap);
                $this->autoSizeColumns($sheet);
            }
        ];
    }

    private function setHeader($sheet)
    {
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->setCellValue('A1', 'Tgl. Kunjungan');
        $sheet->setCellValue('B1', 'Hari');

        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => ['bold' => true],
        ];

        $sheet->getDelegate()->getStyle('A1:B2')->applyFromArray($styleArray);
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

    private function getDaysMap()
    {
        return [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu',
        ];
    }

    private function populateData($sheet, $dates, $daysMap)
    {
        $startColumn = 3;
        foreach ($this->sales as $user_sales) {
            $this->setSalesHeaders($sheet, $startColumn, $user_sales);
            $this->fillData($sheet, $dates, $daysMap, $startColumn, $user_sales);
            $startColumn += 5;
        }
    }

    private function setSalesHeaders($sheet, $startColumn, $user_sales)
    {
        $endColumn = $startColumn + 4;
        $sheet->mergeCellsByColumnAndRow($startColumn, 1, $endColumn, 1);
        $sheet->setCellValueByColumnAndRow($startColumn, 1, $user_sales);
        $this->styleSalesHeader($sheet, $startColumn);

        $sheet->setCellValueByColumnAndRow($startColumn, 2, 'Kunjungan');

        $sheet->mergeCellsByColumnAndRow($startColumn + 1, 2, $startColumn + 2, 2);
        $sheet->setCellValueByColumnAndRow($startColumn + 1, 2, 'Cek In Pertama');

        $sheet->setCellValueByColumnAndRow($startColumn + 3, 2, 'Punishment');
        $sheet->mergeCellsByColumnAndRow($startColumn + 3, 2, $startColumn + 4, 2);
    }

    private function styleSalesHeader($sheet, $startColumn)
    {
        $sheet->getStyleByColumnAndRow($startColumn, 1)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyleByColumnAndRow($startColumn, 1)
            ->getFont()
            ->setBold(true);
    }

    private function fillData($sheet, $dates, $daysMap, $startColumn, $user_sales)
    {
        foreach ($dates as $index => $date) {
            $rowNumber = $index + 3; // Start from row 3
            $this->setVisitDate($sheet, $date, $rowNumber);
            $this->setDay($sheet, $date, $rowNumber, $daysMap);

            // CARI JUMLAH KUNJUNGAN 
            $totalKunjungan = DB::table('trns_dks')
                ->select(['count(*) as total_kunjungan'])
                ->where('user_sales', $user_sales)
                ->where('tgl_kunjungan', $date)
                ->where('type', 'in')
                ->count();

            // TOTAL KUNJUNGAN
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn);
            $sheet->setCellValue($columnLetter . $rowNumber, str_replace('{row}', $rowNumber, $totalKunjungan));

            // PENGECEKAN HARI MINGGU
            $isSunday = \Carbon\Carbon::parse($date)->isSunday();

            if ($isSunday) {
                $punishmentLupaCekInOut = 0;
            } else {
                $tokoAbsen = [
                    '6B',
                    '6C',
                    '6D',
                    '6F',
                    '6H',
                    'TX'
                ];

                // CEK IN PERTAMA
                $cekInPertama = DB::table('trns_dks')
                    ->select(['*'])
                    ->where('user_sales', $user_sales)
                    ->where('tgl_kunjungan', $date)
                    ->where('type', 'in')
                    ->whereNotIn('kd_toko', $tokoAbsen)
                    ->orderBy('waktu_kunjungan', 'asc')
                    ->first();

                if ($cekInPertama == null) {
                    $cekInPertama = '00:00:00';
                } else {
                    $cekInPertama = \Carbon\Carbon::parse($cekInPertama->waktu_kunjungan)->format('H:i:s');
                }

                $nextColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + 1);
                $sheet->setCellValue($nextColumnLetter . $rowNumber, str_replace('{row}', $rowNumber, $cekInPertama));

                // PUNISHMENT > 9.30
                if ($cekInPertama > '09:30:00') {
                    $punishmentCekInPertama = 1;
                } else {
                    $punishmentCekInPertama = 0;
                }

                $nextColumnLetter2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + 2);
                $sheet->setCellValue($nextColumnLetter2 . $rowNumber, str_replace('{row}', $rowNumber, $punishmentCekInPertama));

                // PUNISHMENT LUPA CEK IN / CEK OUT
                $punishmentLupaCekInOut = 0;
                $cekOut = DB::table('trns_dks')
                    ->select(['*'])
                    ->where('user_sales', $user_sales)
                    ->where('tgl_kunjungan', $date)
                    ->where('type', 'out')
                    ->orderBy('waktu_kunjungan', 'asc')
                    ->first();

                if ($cekInPertama == '00:00:00') {
                    $punishmentLupaCekInOut = 1;
                } else if ($cekOut == null) {
                    $punishmentLupaCekInOut = 1;
                }
            }

            $nextColumnLetter3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + 3);
            $sheet->setCellValue($nextColumnLetter3 . $rowNumber, str_replace('{row}', $rowNumber, $punishmentLupaCekInOut));
        }
    }

    private function setVisitDate($sheet, $date, $rowNumber)
    {
        $excelDate = Date::dateTimeToExcel(Carbon::parse($date));
        $sheet->setCellValue("A{$rowNumber}", $excelDate);
        $sheet->getStyle("A{$rowNumber}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    }

    private function setDay($sheet, $date, $rowNumber, $daysMap)
    {
        $dayInIndonesian = $daysMap[Carbon::parse($date)->format('D')] ?? '';
        $sheet->setCellValue("B{$rowNumber}", $dayInIndonesian);
    }

    private function autoSizeColumns($sheet)
    {
        $highestColumn = $sheet->getHighestColumn(); // Mendapatkan kolom tertinggi
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // Mengonversi huruf kolom menjadi indeks

        // Mengatur ukuran kolom secara otomatis dari kolom 1 hingga kolom tertinggi
        foreach (range(1, $highestColumnIndex) as $columnIndex) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex); // Mengonversi indeks kembali ke huruf kolom
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }

    public function title(): string
    {
        return 'Kunjungan';
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
