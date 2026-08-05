<?php

namespace Tests\Feature;

use Tests\TestCase;

class PayrollSlipViewTest extends TestCase
{
    /** @test */
    public function payroll_slip_shows_all_entered_income_components()
    {
        $user = (object) [
            'name' => 'MGS Ilham Zuhdi',
            'ktp' => '1671092104010009',
            'employee_id' => 'EMP-001',
            'Jabatan' => (object) [
                'nama_jabatan' => 'Teknologi Informasi',
            ],
            'nama_bank' => 'BCA',
            'nama_rekening' => 'mgs.ilham.zuhdi',
            'rekening' => '0910204505',
            'izin_cuti' => 0,
        ];

        $payroll = (object) [
            'user' => $user,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_akhir' => '2026-07-25',
            'bulan' => '7',
            'tahun' => '2026',
            'gaji_pokok' => 5729876,
            'uang_transport' => 270124,
            'uang_makan' => 125000,
            'total_kehadiran' => 100000,
            'jumlah_lembur' => 2,
            'total_lembur' => 50000,
            'bonus_pribadi' => 300000,
            'bonus_team' => 400000,
            'bonus_jackpot' => 150000,
            'total_thr' => 200000,
            'total_reimbursement' => 75000,
            'jumlah_terlambat' => 2,
            'total_terlambat' => 100000,
            'jumlah_mangkir' => 0,
            'total_mangkir' => 0,
            'jumlah_izin' => 0,
            'total_izin' => 0,
            'bayar_kasbon' => 0,
            'loss' => 0,
            'bpjs_jht_persen' => '2.00',
            'bpjs_jht_amount' => 114598,
            'bpjs_kes_persen' => '1.00',
            'bpjs_kes_amount' => 57299,
            'pph21_persen' => '0.80',
            'pph21_amount' => 46091,
        ];

        $response = $this->view('payroll.download', ['data' => $payroll]);

        $response
            ->assertSee('Uang Transport')
            ->assertSee('Rp 270.124')
            ->assertSee('Tunjangan Makan')
            ->assertSee('Rp 300.000')
            ->assertSee('Tunjangan Transport')
            ->assertSee('Rp 400.000')
            ->assertSee('Tunjangan Komunikasi')
            ->assertSee('Rp 150.000')
            ->assertSee('Total Penghasilan')
            ->assertSee('Rp 7.400.000')
            ->assertSee('Gaji Dibayarkan')
            ->assertSee('Rp 7.082.012');
    }
}
