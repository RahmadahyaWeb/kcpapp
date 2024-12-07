<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CustomerPaymentDetail extends Component
{
    public $no_piutang;
    public $model;
    public $target;
    public $customer_payment_header;
    public $customer_payment_details;

    public function mount($no_piutang)
    {
        $this->no_piutang = $no_piutang;
        $this->target = 'potong_piutang';
        $this->model = CustomerPaymentDetail::class;

        $kcpapplication = DB::connection('mysql');

        $this->customer_payment_header = $kcpapplication
            ->table('customer_payment_header')
            ->where('no_piutang', $this->no_piutang)
            ->first();

        $this->customer_payment_details = $kcpapplication
            ->table('customer_payment_details')
            ->where('no_piutang', $this->no_piutang)
            ->get();
    }

    public static function get_nominal_invoice($no_invoice)
    {
        $kcpinformation = DB::connection('kcpinformation');

        return $kcpinformation->table('trns_inv_header')
            ->where('noinv', $no_invoice)
            ->value('amount_total');
    }

    public function potong_piutang()
    {
        $jumlah_details = count($this->customer_payment_details);
        $pass = 0;

        foreach ($this->customer_payment_details as $value) {
            $nominal_invoice = CustomerPaymentDetail::get_nominal_invoice($value->noinv);
            $nominal_potong = $value->nominal;

            if ($nominal_invoice >= $nominal_potong) {
                $pass += 1;
            }
        }

        if (!$jumlah_details == $pass) {
            session()->flash('error', 'Nominal pembayaran tidak sesuai dengan nominal invoice.');
            return;
        }

        if ($jumlah_details == $pass) {
            try {
                $kcpapplication = DB::connection('mysql');
                $kcpinformation = DB::connection('kcpinformation');

                $kcpapplication->beginTransaction();
                $kcpinformation->beginTransaction();

                $kcpinformation->table('trns_pembayaran_piutang_header')
                    ->insert([
                        'nopiutang'         => $this->customer_payment_header->no_piutang,
                        'area_piutang'      => $this->customer_payment_header->area_piutang,
                        'kd_outlet'         => $this->customer_payment_header->kd_outlet,
                        'nm_outlet'         => $this->customer_payment_header->nm_outlet,
                        'nominal_potong'    => $this->customer_payment_header->nominal_potong,
                        'pembayaran_via'    => $this->customer_payment_header->pembayaran_via,
                        'no_bg'             => $this->customer_payment_header->no_bg,
                        'jth_tempo_bg'      => $this->customer_payment_header->tgl_jth_tempo_bg,
                        'bank'              => $this->customer_payment_details[0]->bank,
                        'status'            => 'C',
                        'no_bg'             => $this->customer_payment_header->no_bg,
                        'crea_date'         => $this->customer_payment_header->crea_date,
                        'crea_by'           => $this->customer_payment_header->crea_by,
                    ]);

                foreach ($this->customer_payment_details as $value) {
                    $kcpinformation->table('trns_pembayaran_piutang')
                        ->insert([
                            'noinv'             => $value->noinv,
                            'nopiutang'         => $value->no_piutang,
                            'kd_outlet'         => $value->kd_outlet,
                            'nm_outlet'         => $value->nm_outlet,
                            'nominal'           => $value->nominal,
                            'keterangan'        => $value->keterangan,
                            'pembayaran_via'    => $value->pembayaran_via,
                            'no_bg'             => $value->no_bg,
                            'jth_tempo_bg'      => $value->tgl_jth_tempo_bg,
                            'status'            => 'C',
                            'crea_date'         => $value->crea_date,
                            'crea_by'           => $value->crea_by,
                        ]);
                }

                $kcpapplication->table('customer_payment_header')
                    ->where('no_piutang', $this->no_piutang)
                    ->update([
                        'status' => 'C'
                    ]);

                $kcpapplication->table('customer_payment_details')
                    ->where('no_piutang', $this->no_piutang)
                    ->update([
                        'status' => 'C'
                    ]);

                switch ($this->customer_payment_header->pembayaran_via) {
                    case 'CASH':
                    case 'TRANSFER':
                        $kcpinformation->table('trns_plafond')
                            ->where('kd_outlet',  $this->customer_payment_header->kd_outlet)
                            ->increment('nominal_plafond',  $this->customer_payment_header->nominal_potong);
                        break;

                    case 'BG':
                        break;

                    default:
                        throw new \Exception("Jenis pembayaran '$this->customer_payment_header->pembayaran_via' tidak dikenali.");
                }

                $kcpapplication->commit();
                $kcpinformation->commit();
            } catch (\Exception $e) {
                $kcpapplication->rollBack();
                $kcpinformation->rollBack();

                session()->flash('error', $e->getMessage());
                return;
            }

            session()->flash('success', 'Penerimaan piutang toko berhasil.');
        }
    }

    public function render()
    {
        if (!$this->customer_payment_header) {
            abort(404);
        }

        return view('livewire.customer-payment-detail');
    }
}
