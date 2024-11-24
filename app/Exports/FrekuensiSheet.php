<?php

namespace App\Exports;

use DateTime;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class FrekuensiSheet implements WithTitle, WithEvents, WithColumnFormatting, WithCustomStartCell, WithHeadings, FromCollection, WithMapping
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

    public function startCell(): string
    {
        return 'A2';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $this->setHeader($sheet);
                $this->autoSizeColumns($sheet);
            }
        ];
    }


    public function headings(): array
    {
        return [
            "",
            "",
            "",
            "",
            "",
            "",
            "",
        ];
    }

    public function setHeader($sheet)
    {
        $sheet->setCellValue('A1', 'KODE TOKO');
        $sheet->setCellValue('B1', 'TOKO');
        $sheet->setCellValue('C1', 'KAB/KOTA');
        $sheet->setCellValue('D1', 'PROVINSI');
        $sheet->setCellValue('E1', 'SALES');
        $sheet->setCellValue('F1', 'MINIMAL KUNJUNGAN');
        $sheet->setCellValue('G1', 'REALISASI KUNJUNGAN');

        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => ['bold' => true],
        ];

        $sheet->getDelegate()->getStyle('A1:G1')->applyFromArray($styleArray);

        // Mengatur alignment untuk kolom A
        $sheet->getDelegate()->getStyle('A:A')->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    public function collection()
    {
        return DB::table('master_toko')
            ->leftJoin('master_provinsi', 'master_provinsi.id', '=', 'master_toko.kd_provinsi')
            ->where('user_sales', $this->sales->username)
            ->where('status', 'active')
            ->get();
    }

    public function map($row): array
    {
        $kd_toko = $row->kd_toko;
        $nama_toko = $row->nama_toko;
        $alamat_toko = $row->alamat;
        $nama_provinsi = $row->nama_provinsi;
        $nama_sales = $this->sales->name;
        $minimal_kunjungan = $row->frekuensi;

        // REALISASI KUNJUNGAN
        $realisasi_kunjungan_data = DB::table('trns_dks')
            ->where('user_sales', $this->sales->username)
            ->whereBetween('tgl_kunjungan', [$this->fromDate, $this->toDate])
            ->where('type', 'in')
            ->where('kd_toko', $kd_toko)
            ->count();

        $realisasi_kunjungan = (string) $realisasi_kunjungan_data;

        return [
            $kd_toko,
            $nama_toko,
            $alamat_toko,
            $nama_provinsi,
            $nama_sales,
            $minimal_kunjungan,
            $realisasi_kunjungan
        ];
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
        return $this->sales->name;
    }

    public function columnFormats(): array
    {
        return [];
    }
}
