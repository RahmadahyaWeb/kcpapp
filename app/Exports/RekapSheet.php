<?php

namespace App\Exports;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class RekapSheet implements WithTitle, WithEvents, WithColumnFormatting
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
                $this->populateData($sheet, $dates);
                $this->autoSizeColumns($sheet);

                // FREEZE PANE
                $event->sheet->getDelegate()->freezePane('D1');
            }
        ];
    }

    private function setHeader($sheet)
    {
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:C2');
        $sheet->setCellValue('A1', 'Alur Kerja Penggunaan DKS');
        $sheet->setCellValue('B1', 'Pelanggaran');
        $sheet->setCellValue('C1', 'Punishment');

        // ALUR PENGGUNAAN DKS
        $sheet->mergeCells('A8:A9');

        $sheet->setCellValue('A3', 'Setiap masuk toko harus check in');
        $sheet->setCellValue('A4', 'Setiap keluar/pulang dari toko harus check in');
        $sheet->setCellValue('A5', 'Check in untuk toko yang pertama di kunjungi setiap hari maksimal jam 09.30');
        $sheet->setCellValue('A6', 'Durasi perjalanan dari toko ke toko berikutnya maksimal 40 menit');
        $sheet->setCellValue('A7', 'Durasi lama berkunjung di toko minimal 30 menit');
        $sheet->setCellValue('A8', 'Lama istirahat 1 jam 15 menit (selain hari jumat) harus memberikan "IST" di system');
        $sheet->setCellValue('A10', 'Lama istirahat 1 jam 45 menit (khusus hari jumat) harus memberikan "IST" di system');

        // PELANGGARAN
        $sheet->mergeCells('B3:B4');

        $sheet->setCellValue('B3', 'Lupa check in atau check out');
        $sheet->setCellValue('B5', 'Check in pertama melebihi 09.30');
        $sheet->setCellValue('B6', 'Durasi perjalanan dari toko ke toko berikutnya');
        $sheet->setCellValue('B7', 'Lama berkunjung di toko tidak sampai 30 menit');
        $sheet->setCellValue('B8', 'Istirahat melebihi 1 jam 15 menit (selain hari jumat)');
        $sheet->setCellValue('B9', 'Istirahat melebihi 1 jam 45 menit (khusus hari jumat)');
        $sheet->setCellValue('B10', 'Tidak memberikan keterangan saat mau istirahat');

        // PUNISHMENT
        $sheet->mergeCells('C3:C4');
        $sheet->mergeCells('C8:C9');

        $sheet->setCellValue('C3', 'Rp. 10.000 / kejadian');
        $sheet->setCellValue('C5', 'Rp. 25.000 / kejadian');
        $sheet->setCellValue('C6', 'Rp. 25.000 / kejadian');
        $sheet->setCellValue('C7', 'Rp. 15.000 / kejadian');
        $sheet->setCellValue('C8', 'Rp. 10.000 / kejadian');
        $sheet->setCellValue('C10', 'Rp. 5.000 / kejadian');

        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => ['bold' => true],
        ];

        $styleArrayAlurPenggunaanDks = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getDelegate()->getStyle('A1:C2')->applyFromArray($styleArray);
        $sheet->getDelegate()->getStyle('A3:C10')->applyFromArray($styleArrayAlurPenggunaanDks);
    }

    private function populateData($sheet, $dates)
    {
        $startColumn = 4;
        foreach ($this->sales as $user_sales) {
            $user_sales_upper = strtoupper($user_sales);

            $this->setSalesHeaders($sheet, $startColumn, $user_sales_upper);
            $this->fillData($sheet, $dates, $startColumn, $user_sales);
            $startColumn += 3;
        }
    }

    private function setSalesHeaders($sheet, $startColumn, $user_sales)
    {
        $endColumn = $startColumn + 2;
        $sheet->mergeCellsByColumnAndRow($startColumn, 1, $endColumn, 1);
        $sheet->setCellValueByColumnAndRow($startColumn, 1, $user_sales);
        $this->styleSalesHeader($sheet, $startColumn);

        $sheet->setCellValueByColumnAndRow($startColumn, 2, 'BANYAK');
        $sheet->setCellValueByColumnAndRow($startColumn + 1, 2, 'REV');
        $sheet->setCellValueByColumnAndRow($startColumn + 2, 2, 'BAYAR');
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

    private function fillData($sheet, $dates, $startColumn, $user_sales)
    {
        $punishmentLupaCekInOut = 0;
        $punishmentCekInPertama = 0;
        $punishment_istirahat   = 0;
        $punishment_istirahat_jumat = 0;

        foreach ($dates as $index => $date) {
            // PENGECEKAN HARI MINGGU
            $isSunday = \Carbon\Carbon::parse($date)->isSunday();

            if (!$isSunday) {
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
                    $cekInPertamaId = $cekInPertama->id;

                    $cekInPertama = \Carbon\Carbon::parse($cekInPertama->waktu_kunjungan)->format('H:i:s');

                    $cekInSelanjutnya = DB::table('trns_dks')
                        ->select(['*'])
                        ->where('user_sales', $user_sales)
                        ->whereDate('tgl_kunjungan', $date)
                        ->where('type', 'in')
                        ->where('id', '>', $cekInPertamaId)
                        ->whereNotIn('kd_toko', $tokoAbsen)
                        ->first();

                    $cekOutPertama = DB::table('trns_dks')
                        ->select(['*'])
                        ->where('user_sales', $user_sales)
                        ->where('tgl_kunjungan', $date)
                        ->where('type', 'out')
                        ->whereNotIn('kd_toko', $tokoAbsen)
                        ->orderBy('waktu_kunjungan', 'asc')
                        ->first();

                    $lama_perjalanan = '00:00:00';

                    if ($cekInSelanjutnya) {
                        $cek_out = Carbon::parse($cekOutPertama->waktu_kunjungan);
                        $cek_in  = Carbon::parse($cekInSelanjutnya->waktu_kunjungan);

                        $selisih = $cek_out->diff($cek_in);
                        $lama_perjalanan = sprintf('%02d:%02d:%02d', $selisih->h, $selisih->i, $selisih->s);
                    }

                    list($hours, $minutes, $seconds) = explode(':', $lama_perjalanan);
                    $lama_perjalanan_dalam_menit = ($hours * 60) + $minutes;

                    $max_durasi_lama_perjalanan = 40;
                    $isFriday = Carbon::parse($date)->isFriday();
                    $waktu_istirahat = $isFriday ? 105 : 75;

                    $max_durasi_lama_perjalanan_plus_waktu_istirahat = $waktu_istirahat + $max_durasi_lama_perjalanan;

                    if ($cekOutPertama !== null && strpos($cekOutPertama->keterangan, 'ist') !== false) {

                        if ($isFriday) {
                            if ($lama_perjalanan_dalam_menit > $max_durasi_lama_perjalanan_plus_waktu_istirahat) {
                                $punishment_istirahat_jumat += 1;
                            }
                        } else {
                            if ($lama_perjalanan_dalam_menit > $max_durasi_lama_perjalanan_plus_waktu_istirahat) {
                                $punishment_istirahat += 1;
                            }
                        }
                    }
                }

                // PUNISHMENT > 9.30
                if ($cekInPertama > '09:30:00') {
                    $punishmentCekInPertama += 1;
                }

                // PUNISHMENT LUPA CEK IN / CEK OUT
                $cekOut = DB::table('trns_dks')
                    ->select(['*'])
                    ->where('user_sales', $user_sales)
                    ->where('tgl_kunjungan', $date)
                    ->where('type', 'out')
                    ->orderBy('waktu_kunjungan', 'asc')
                    ->first();

                if ($cekInPertama == '00:00:00') {
                    $punishmentLupaCekInOut += 1;
                } else if ($cekOut == null) {
                    $punishmentLupaCekInOut += 1;
                }
            }
        }

        $rowNumber = 3;

        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn);

        $sheet->mergeCells($columnLetter . $rowNumber . ':' . $columnLetter . $rowNumber + 1);

        $nextColumnLetter2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + 1);

        $sheet->mergeCells($nextColumnLetter2 . $rowNumber . ':' . $nextColumnLetter2 . $rowNumber + 1);

        $nextColumnLetter3 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startColumn + 2);

        $sheet->mergeCells($nextColumnLetter3 . $rowNumber . ':' . $nextColumnLetter3 . $rowNumber + 1);

        // BANYAK PUNISHMENT CEK IN CEK OUT
        $sheet->setCellValue($columnLetter . $rowNumber, str_replace('{row}', $rowNumber, $punishmentLupaCekInOut));
        // BAYAR PUNISHMENT CEK IN CEK OUT
        $sheet->setCellValue($nextColumnLetter3 . $rowNumber, str_replace('{row}', $rowNumber, 10000 * $punishmentLupaCekInOut));

        // BANYAK PUNISHMENT CEK IN PERTAMA
        $sheet->setCellValue($columnLetter . ($rowNumber + 2), str_replace('{row}', ($rowNumber + 2), $punishmentCekInPertama));
        // BAYAR PUNISHMENT CEK IN PERTAMA
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 2), str_replace('{row}', ($rowNumber) + 2, 25000 * $punishmentCekInPertama));

        // BANYAK PUNISHMENT DURASI LAMA PERJALANAN TOKO
        $sheet->setCellValue($columnLetter . ($rowNumber + 3), str_replace('{row}', ($rowNumber + 2), "=SUM({$user_sales}!K3:K6898)"));
        // BAYAR PUNISHMENT DURASI LAMA PERJALANAN TOKO
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 3), str_replace('{row}', ($rowNumber + 2), "=SUM({$user_sales}!K3:K6898) * 25000"));

        // BANYAK PUNISHMENT DURASI KUNJUNGAN TOKO
        $sheet->setCellValue($columnLetter . ($rowNumber + 4), str_replace('{row}', ($rowNumber + 2), "=SUM({$user_sales}!I3:K6898)"));
        // BAYAR PUNISHMENT DURASI KUNJUNGAN TOKO
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 4), str_replace('{row}', ($rowNumber + 2), "=SUM({$user_sales}!I3:K6898) * 15000"));

        // BANYAK PUNISHMENT ISTIRAHAT SELAIN JUMAT
        $sheet->setCellValue($columnLetter . ($rowNumber + 5), str_replace('{row}', ($rowNumber + 2), $punishment_istirahat));
        // BAYAR PUNISHMENT ISTIRAHAT SELAIN JUMAT
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 5), str_replace('{row}', ($rowNumber) + 2, 10000 * $punishment_istirahat));
        // MERGE PUNISHMENT ISTIRAHAT SELAIN JUMAT
        $sheet->mergeCells($nextColumnLetter3 . ($rowNumber + 5) . ':' . $nextColumnLetter3 . $rowNumber + 6);
        $sheet->mergeCells($columnLetter . ($rowNumber + 5) . ':' . $columnLetter . $rowNumber + 6);
        $sheet->mergeCells($nextColumnLetter2 . ($rowNumber + 5) . ':' . $nextColumnLetter2 . $rowNumber + 6);

        // BANYAK PUNISHMENT ISTIRAHAT JUMAT
        $sheet->setCellValue($columnLetter . ($rowNumber + 7), str_replace('{row}', ($rowNumber + 2), $punishment_istirahat_jumat));
        // BAYAR PUNISHMENT ISTIRAHAT JUMAT
        $sheet->setCellValue($nextColumnLetter3 . ($rowNumber + 7), str_replace('{row}', ($rowNumber) + 2, 10000 * $punishment_istirahat_jumat));
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

    public function title(): string
    {
        return 'Rekap';
    }

    public function columnFormats(): array
    {
        return [];
    }
}
