<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SalesSheet implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents, WithMapping, WithColumnFormatting, WithTitle
{
    protected $user_sales;
    protected $fromDate;
    protected $toDate;

    public function __construct($user_sales, $fromDate, $toDate)
    {
        $this->user_sales = $user_sales;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // SET HEADER MERGE CELL
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:D2');
                $sheet->mergeCells('E1:E2');
                $sheet->mergeCells('F1:F2');
                $sheet->mergeCells('G1:G2');
                $sheet->mergeCells('H1:I1');
                $sheet->mergeCells('J1:K1');
                $sheet->mergeCells('L1:M1');
                $sheet->mergeCells('N1:O1');

                // SET HEADER TITLE
                $sheet->setCellValue('A1', "Sales");
                $sheet->setCellValue('B1', "Tgl.Kunjungan");
                $sheet->setCellValue('C1', "Kode Toko");
                $sheet->setCellValue('D1', "Toko");
                $sheet->setCellValue('E1', "Check In");
                $sheet->setCellValue('F1', "Check Out");
                $sheet->setCellValue('G1', "Keterangan");
                $sheet->setCellValue('H1', "Durasi Kunjungan");
                $sheet->setCellValue('H2', "Lama");
                $sheet->setCellValue('I2', "Punishment");
                $sheet->setCellValue('J1', "Durasi Perjalanan");
                $sheet->setCellValue('J2', "Lama");
                $sheet->setCellValue('K2', "Punishment");
                $sheet->setCellValue('L1', "Cek In / Cek Out");
                $sheet->setCellValue('L2', "Kunjungan");
                $sheet->setCellValue('M2', "Punishment");
                $sheet->setCellValue('N1', "Katalog");
                $sheet->setCellValue('N2', "Tgl.Katalog");
                $sheet->setCellValue('O2', "Jam Katalog");

                // HEADER STYLE
                $styleArray = [
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'font' => [
                        'bold' => true,
                    ],
                ];

                $cellRange = 'A1:O2';
                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray($styleArray);

                $sheet->getRowDimension(2)->setRowHeight(20);

                // Enable auto width for all columns
                foreach (range('A', 'O') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                // FREEZE PANE
                $event->sheet->getDelegate()->freezePane('H1');
            },
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
            "",
        ];
    }

    public function collection()
    {
        $items = DB::table('trns_dks')
            ->select(
                'trns_dks.id',
                'trns_dks.user_sales',
                'master_toko.nama_toko',
                'trns_dks.waktu_kunjungan AS waktu_cek_in',
                'out_data.waktu_kunjungan AS waktu_cek_out',
                'trns_dks.tgl_kunjungan',
                'out_data.keterangan',
                'trns_dks.kd_toko',
                'katalog_data.katalog_at',
                DB::raw('CASE 
                            WHEN out_data.waktu_kunjungan IS NOT NULL 
                            THEN TIMESTAMPDIFF(MINUTE, trns_dks.waktu_kunjungan, out_data.waktu_kunjungan) 
                            ELSE NULL 
                        END AS lama_kunjungan')
            )
            ->leftJoin('trns_dks AS out_data', function ($join) {
                $join->on('trns_dks.user_sales', '=', 'out_data.user_sales')
                    ->whereColumn('trns_dks.kd_toko', 'out_data.kd_toko')
                    ->whereColumn('trns_dks.tgl_kunjungan', 'out_data.tgl_kunjungan')
                    ->where('out_data.type', '=', 'out');
            })
            ->leftJoin('master_toko', 'trns_dks.kd_toko', '=', 'master_toko.kd_toko')
            ->leftJoin('trns_dks AS katalog_data', function ($join) {
                $join->on('trns_dks.user_sales', '=', 'katalog_data.user_sales')
                    ->whereColumn('trns_dks.kd_toko', 'katalog_data.kd_toko')
                    ->whereColumn('trns_dks.tgl_kunjungan', 'katalog_data.tgl_kunjungan')
                    ->where('katalog_data.type', '=', 'katalog');
            })
            ->where('trns_dks.type', 'in')
            ->where('trns_dks.user_sales', $this->user_sales)
            ->whereBetween('trns_dks.tgl_kunjungan', [$this->fromDate, $this->toDate])
            ->orderBy('trns_dks.created_at', 'asc')
            ->orderBy('trns_dks.user_sales', 'desc')
            ->get();

        return $items;
    }

    public function map($row): array
    {
        // WAKTU CEK IN
        $waktu_cek_in = \Carbon\Carbon::parse($row->waktu_cek_in)->format('H:i:s');

        // WAKTU CEK OUT
        if ($row->waktu_cek_out) {
            $waktu_cek_out = \Carbon\Carbon::parse($row->waktu_cek_out)->format('H:i:s');
        } else {
            $waktu_cek_out = $waktu_cek_in;
        }

        // TGL KUNJUNGAN
        $tgl_kunjungan = Carbon::parse($row->tgl_kunjungan);
        $excelDate = Date::dateTimeToExcel($tgl_kunjungan);

        // KETERANGAN
        $keterangan = strtolower($row->keterangan);

        // LAMA KUNJUNGAN
        $lama_kunjungan = null;
        $punishment_lama_kunjungan = 0;

        if ($row->lama_kunjungan !== null) {
            $hours = floor($row->lama_kunjungan / 60);
            $minutes = $row->lama_kunjungan % 60;
            $lama_kunjungan = sprintf('%02d:%02d:00', $hours, $minutes);

            /**
             * PUNISHMENT DURASI LAMA KUNJUNGAN
             * MINIMAL KUNJUNGAN ADALAH 30 MENIT
             */

            if ($row->lama_kunjungan < 30) {
                $punishment_lama_kunjungan = 1;
            } else {
                $punishment_lama_kunjungan = 0;
            }
        } else {
            $lama_kunjungan = '00:00:00';
            $punishment_lama_kunjungan = 1;
        }

        // LAMA DURASI PERJALANAN
        $punishment_durasi_lama_perjalanan = 0;
        $lama_perjalanan = '00:00:00';

        $cekInSelanjutnya = DB::table('trns_dks')
            ->select(['*'])
            ->where('user_sales', $row->user_sales)
            ->whereDate('tgl_kunjungan', $row->tgl_kunjungan)
            ->where('type', 'in')
            ->where('id', '>', $row->id)
            ->first();

        if ($cekInSelanjutnya) {
            $cek_out = Carbon::parse($row->waktu_cek_out);
            $cek_in  = Carbon::parse($cekInSelanjutnya->waktu_kunjungan);

            $selisih = $cek_out->diff($cek_in);
            $lama_perjalanan = sprintf('%02d:%02d:%02d', $selisih->h, $selisih->i, $selisih->s);
        }

        /**
         * PUNISHMENT DURASI LAMA PERJALANAN
         * MAKSIMAL DURASI LAMA PERJALANAN ADALAH 4O MENIT
         * ISTIRAHAT JUMAT 1 JAM 45 MENIT + 40 MENIT
         * ISTIRAHAT SELAIN JUMAT 1 JAM 15 MENIT + 40 MENIT
         */

        list($hours, $minutes, $seconds) = explode(':', $lama_perjalanan);
        $lama_perjalanan_dalam_menit = ($hours * 60) + $minutes;

        $max_durasi_lama_perjalanan = 40;
        $isFriday = Carbon::parse($row->tgl_kunjungan)->isFriday();
        $waktu_istirahat = $isFriday ? 105 : 75;
        $max_durasi_lama_perjalanan_plus_waktu_istirahat = $waktu_istirahat + $max_durasi_lama_perjalanan;

        if (strpos($keterangan, 'ist') !== false) {
            $punishment_durasi_lama_perjalanan = ($lama_perjalanan_dalam_menit > $max_durasi_lama_perjalanan_plus_waktu_istirahat) ? 1 : 0;
        } else {
            $punishment_durasi_lama_perjalanan = ($lama_perjalanan_dalam_menit > $max_durasi_lama_perjalanan) ? 1 : 0;
        }

        if ($lama_perjalanan_dalam_menit == 0) {
            $lama_perjalanan = '00:00:00';
        }

        // KUNJUNGAN
        if (($row->lama_kunjungan % 60) > 0) {
            $kunjungan = 1;
        } else {
            $kunjungan = 0;
        }

        // PUNISHMENT KUNJUNGAN
        if ($waktu_cek_in == $waktu_cek_out) {
            $punishment_cek_in_cek_out = 1;
        } else {
            $punishment_cek_in_cek_out = 0;
        }

        // UNTUK ABSEN SALESMAN
        $tokoAbsen = [
            '6B',
            '6C',
            '6D',
            '6F',
            '6H',
            'TX'
        ];

        if (in_array($row->kd_toko, $tokoAbsen)) {
            $punishment_lama_kunjungan = 0;
            $punishment_durasi_lama_perjalanan = 0;
            $punishment_cek_in_cek_out = 0;
            $kunjungan = 'A';
        }

        // KATALOG
        $katalog = $row->katalog_at;

        if ($katalog) {
            $tgl_katalog = Date::dateTimeToExcel(Carbon::parse($row->katalog_at));
            $jam_katalog = \Carbon\Carbon::parse($row->katalog_at)->format('H:i:s');
        } else {
            $tgl_katalog = '';
            $jam_katalog = '';
        }

        return [
            $row->user_sales,
            $excelDate,
            $row->kd_toko,
            $row->nama_toko,
            $waktu_cek_in,
            $waktu_cek_out,
            $row->keterangan,
            $lama_kunjungan,
            $punishment_lama_kunjungan,
            $lama_perjalanan,
            $punishment_durasi_lama_perjalanan,
            $kunjungan,
            $punishment_cek_in_cek_out,
            $tgl_katalog,
            $jam_katalog,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'N' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function title(): string
    {
        return $this->user_sales;
    }
}
