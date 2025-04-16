<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvestasiController extends Controller
{
    public function index(Request $request)
    {
        return view('investasi.form')->with([
            'request' => $request,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_project' => 'required',
            'biaya_investasi' => 'required|numeric',
            'periode_bulan' => 'required|numeric',
            'pendapatan_per_bulan' => 'required|numeric',
            'revenue_otc' => 'required|numeric',
            'cost_obl_bulanan' => 'required|numeric',
            'cost_obl_otc' => 'required|numeric',
            'biaya_operasional' => 'required|numeric',
            'biaya_marketing' => 'required|numeric',
            'tingkat_diskonto' => 'required|numeric',
            'bad_debt' => 'required|numeric',
            'taxes' => 'required|numeric',
        ]);

        // Variabel dasar
        $periode_bulan = $request->periode_bulan;
        $periode_tahun = $periode_bulan / 12;

        // Revenue dikurangi Cost OBL
        $revenue_bulanan_akhir = $request->pendapatan_per_bulan - $request->cost_obl_bulanan;
        $revenue_otc_akhir = $request->revenue_otc - $request->cost_obl_otc;

        // Total revenue akhir
        $revenue_total = ($revenue_bulanan_akhir * $periode_bulan) + $revenue_otc_akhir;

        // Biaya-biaya
        $biaya_operasional_total = ($request->biaya_operasional / 100) * $request->biaya_investasi * $periode_tahun;
        $biaya_marketing_total = ($request->biaya_marketing / 100) * $revenue_total;
        $biaya_opex_total = $biaya_operasional_total + $biaya_marketing_total;

        // EBITDA, Capex & Depresiasi
        $ebitda = $revenue_total - $biaya_opex_total;
        $bop_lakwas = 0.007 * $request->biaya_investasi;
        $biaya_capex = $request->biaya_investasi + $bop_lakwas;
        $depresiasi = $biaya_capex;

        // EBIT, Pajak, Net Income
        $ebit = $ebitda - $depresiasi;
        $pajak = ($request->taxes / 100) * $ebit;
        $net_income = $ebit - $pajak;

        // Tahunan
        $net_income_tahunan = $net_income / $periode_tahun;
        $depresiasi_tahunan = $depresiasi / $periode_tahun;

        // Cash Flow
        $cash_flows = [
            0 => -$biaya_capex,
            1 => $net_income_tahunan + $depresiasi_tahunan,
            2 => $net_income_tahunan + $depresiasi_tahunan,
        ];

        // NPV
        $r = $request->tingkat_diskonto / 100;
        $npv = $net_income + $depresiasi - $biaya_capex;

        // IRR
        // $irr = null;
        // $low = 0.00001;
        // $high = 10;
        // $tolerance = 0.00001;
        // $max_iterations = 1000;

        // for ($i = 0; $i < $max_iterations; $i++) {
        //     $mid = ($low + $high) / 2;
        //     $npv_guess = 0;
        //     foreach ($cash_flows as $t => $cf) {
        //         $npv_guess += $cf / pow(1 + $mid, $t);
        //     }

        //     if (abs($npv_guess) < $tolerance) {
        //         $irr = $mid * 100;
        //         break;
        //     }

        //     if ($npv_guess > 0) {
        //         $low = $mid;
        //     } else {
        //         $high = $mid;
        //     }
        // }

        function hitungIRR(array $cashFlows, $guess = 0.1)
        {
            $maxIterations = 1000;
            $precision = 0.00001;
            $rate = $guess;

            for ($i = 0; $i < $maxIterations; $i++) {
                $npv = 0.0;
                $dnpv = 0.0; // turunan NPV (untuk metode Newton-Raphson)

                foreach ($cashFlows as $t => $cf) {
                    $npv += $cf / pow(1 + $rate, $t);
                    if ($t != 0) {
                        $dnpv -= $t * $cf / pow(1 + $rate, $t + 1);
                    }
                }

                if (abs($npv) < $precision) {
                    return $rate * 100; // konversi ke persen
                }

                if ($dnpv == 0) {
                    return null; // menghindari pembagian nol
                }

                $rate = $rate - $npv / $dnpv;
            }

            return null; // tidak ditemukan dalam batas iterasi
        }

        $irr = hitungIRR($cash_flows);

        $status_npv = $npv > 0 ? 'Layak' : 'Tidak Layak';
        $status_irr = (is_numeric($irr) && $irr > 16) ? 'Layak' : 'Tidak Layak';

        // Simpan input untuk refill form
        session(['form_input' => $request->all()]);

        return view('investasi.hasil', [
            'nama_project' => $request->nama_project,
            'biaya_investasi' => $request->biaya_investasi,
            'pendapatan_per_bulan' => $request->pendapatan_per_bulan,
            'revenue_otc' => $request->revenue_otc,
            'periode_bulan' => $periode_bulan,
            'revenue_bulanan_akhir' => $revenue_bulanan_akhir,
            'revenue_otc_akhir' => $revenue_otc_akhir,
            'revenue_total' => $revenue_total,
            'cost_obl_bulanan' => $request->cost_obl_bulanan,
            'cost_obl_otc' => $request->cost_obl_otc,
            'biaya_operasional' => $request->biaya_operasional,
            'biaya_marketing' => $request->biaya_marketing,
            'biaya_operasional_total' => $biaya_operasional_total,
            'biaya_marketing_total' => $biaya_marketing_total,
            'biaya_opex_total' => $biaya_opex_total,
            'tingkat_diskonto' => $request->tingkat_diskonto,
            'bad_debt' => ($request->bad_debt / 100) * $revenue_total,
            'bad_debt_input' => $request->bad_debt,
            'ebitda' => $ebitda,
            'depresiasi' => $depresiasi,
            'ebit' => $ebit,
            'pajak' => $pajak,
            'taxes_input' => $request->taxes,
            'net_income' => $net_income,
            'bop_lakwas' => $bop_lakwas,
            'biaya_capex' => $biaya_capex,
            'npv' => round($npv),
            'irr' => $irr ? round($irr, 2) : null,
            'status_npv' => $status_npv,
            'status_irr' => $status_irr,
        ]);
    }
}
