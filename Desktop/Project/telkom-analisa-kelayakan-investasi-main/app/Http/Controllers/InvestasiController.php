<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;



class InvestasiController extends Controller
{
    public function store(Request $request)
    {

        // Bersihkan angka dari titik dan koma
        $angkaFields = [
            'biaya_investasi',
            'pendapatan_per_bulan',
            'revenue_otc',
            'cost_obl_bulanan',
            'cost_obl_otc'
        ];
        foreach ($angkaFields as $field) {
            $request->merge([
                $field => str_replace(['.', ','], '', $request->$field),
            ]);
        }

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

        $request->merge([
            'biaya_investasi' => str_replace(['.', ','], '', $request->biaya_investasi),
            'pendapatan_per_bulan' => str_replace(['.', ','], '', $request->pendapatan_per_bulan),
            'revenue_otc' => str_replace(['.', ','], '', $request->revenue_otc),
            'cost_obl_bulanan' => str_replace(['.', ','], '', $request->cost_obl_bulanan),
            'cost_obl_otc' => str_replace(['.', ','], '', $request->cost_obl_otc),
        ]);

        $periode_bulan = $request->periode_bulan;
        $periode_tahun = $periode_bulan / 12;

        // Revenue dan cost
        $revenue_bulanan_total = $request->pendapatan_per_bulan * $periode_bulan;
        $revenue_total_bruto = $request->revenue_otc + $revenue_bulanan_total;

        $revenue_otc_akhir = $request->revenue_otc - $request->cost_obl_otc;
        $revenue_bulanan_akhir = $request->pendapatan_per_bulan - $request->cost_obl_bulanan;
        $revenue_total = ($revenue_bulanan_akhir * $periode_bulan) + $revenue_otc_akhir;

        $bad_debt = ($request->bad_debt / 100) * $revenue_total;

        $biaya_operasional_total = ($request->biaya_operasional / 100) * $request->biaya_investasi * $periode_tahun;
        $base_marketing = $revenue_bulanan_akhir * $periode_bulan;
        $biaya_marketing_total = ($request->biaya_marketing / 100) * $base_marketing;
        $biaya_opex_total = $biaya_operasional_total + $biaya_marketing_total;

        $ebitda = $revenue_total - $bad_debt - $biaya_opex_total;

        $bop_lakwas = 0.007 * $request->biaya_investasi;
        $biaya_capex = $request->biaya_investasi + $bop_lakwas;
        $depresiasi = $biaya_capex;
        $ebit = $ebitda - $depresiasi;
        $pajak = ($request->taxes / 100) * $ebit;
        $net_income = $ebit - $pajak;

        $bad_debt_otc = ($request->bad_debt / 100) * $revenue_otc_akhir;
        $opex_otc = ($request->biaya_marketing / 100) * $request->revenue_otc; // tidak ada biaya operasional di tahun ke-0

        $net_income_per_tahun = $net_income / ceil($periode_tahun);
        $depresiasi_per_tahun = $depresiasi / ceil($periode_tahun);


        // IRR & NPV
        $npv = $net_income + $depresiasi - $biaya_capex;


        // Buat list tahun dimulai dari tahun sekarang
        $tahun_awal = (int) now()->format('Y');
        $tahun_list = [];
        for ($i = 0; $i <= ceil($periode_tahun); $i++) {
            $tahun_list[] = "Tahun ke-{$i}";
        }

        // Inisialisasi array per tahun
        $revenue_list = [];
        $bad_debt_list = [];
        $opex_list = [];
        $ebitda_list = [];
        $depresiasi_list = [];
        $ebit_list = [];
        $pajak_list = [];
        $net_income_list = [];

        // Perhitungan tahun ke-0
        $revenue_list[0] = $revenue_otc_akhir;
        $bad_debt_list[0] = ($request->bad_debt / 100) * $revenue_list[0];
        $opex_list[0] = 0;
        $ebitda_list[0] = $revenue_list[0] - $bad_debt_list[0];
        $depresiasi_list[0] = 0;
        $ebit_list[0] = $ebitda_list[0];
        $pajak_list[0] = ($request->taxes / 100) * $ebit_list[0];
        $net_income_list[0] = $ebit_list[0] - $pajak_list[0];

        // EBITDA Tahun ke-0 = Revenue Tahun ke-0 - Bad Debt Tahun ke-0 - OPEX Tahun ke-0
        $ebitda_t0 = $revenue_list[0] - $bad_debt_list[0] - $opex_list[0];

        // EBIT Tahun ke-0 = EBITDA - 0 (belum ada depresiasi)
        $ebit_t0 = $ebitda_t0; // atau kalau kamu belum define $ebitda_t0, ganti dengan nilai yang sudah ada

        // Pajak Tahun ke-0 = % dari EBIT
        $pajak_t0 = ($request->taxes / 100) * $ebit_t0;

        // Net Income Tahun ke-0
        $net_income_t0 = $ebit_t0 - $pajak_t0;

        // Perhitungan tahun ke-1 sampai n
        for ($i = 1; $i <= ceil($periode_tahun); $i++) {
            $revenue_list[$i] = $revenue_bulanan_akhir * 12;
            $bad_debt_list[$i] = ($request->bad_debt / 100) * $revenue_list[$i];
            $opex_list[$i] = ($biaya_operasional_total + $biaya_marketing_total) / ceil($periode_tahun);
            $ebitda_list[$i] = $revenue_list[$i] - $bad_debt_list[$i] - $opex_list[$i];
            $depresiasi_list[$i] = $depresiasi / ceil($periode_tahun);
            $ebit_list[$i] = $ebitda_list[$i] - $depresiasi_list[$i];
            $pajak_list[$i] = ($request->taxes / 100) * $ebit_list[$i];
            $net_income_list[$i] = $ebit_list[$i] - $pajak_list[$i];
        }

        // Tambahkan bagian ini di bawahnya:
        $revenue_total_list = array_sum($revenue_list);
        $bad_debt_total = array_sum($bad_debt_list);
        $opex_total = array_sum($opex_list);
        $ebitda_total = array_sum($ebitda_list);
        $depresiasi_total = array_sum($depresiasi_list);
        $ebit_total = array_sum($ebit_list);
        $pajak_total = array_sum($pajak_list);
        $net_income_total = array_sum($net_income_list);

        // Proyeksi Profit Loss
        $proyeksi_profit_loss = [
            ['label' => 'Revenue', 'data' => $revenue_list],
            ['label' => 'Bad Debt', 'data' => $bad_debt_list],
            ['label' => 'OPEX', 'data' => $opex_list],
            ['label' => 'EBITDA', 'data' => $ebitda_list],
            ['label' => 'Depresiasi', 'data' => $depresiasi_list],
            ['label' => 'EBIT', 'data' => $ebit_list],
            ['label' => 'Pajak', 'data' => $pajak_list],
            ['label' => 'Net Income', 'data' => $net_income_list],
        ];

        // Proyeksi Cash Flow untuk tabel (per tahun)
        $total_cash_inflow_list = [$net_income_t0];
        $capex_list = [$biaya_capex];
        $net_cash_flow_list = [$net_income_t0 - $biaya_capex];
        $cum_cash_flow_list = [];
        $cum_cash_flow_list[0] = $net_income_list[0] - $biaya_capex;

        for ($i = 1; $i <= ceil($periode_tahun); $i++) {
            $total_cash_inflow_list[] = $net_income_list[$i] + $depresiasi_per_tahun;
            $capex_list[] = 0;
            $net_cash_flow_list[] = $net_income_list[$i] + $depresiasi_per_tahun;
            $cum_cash_flow_list[] = $cum_cash_flow_list[$i - 1] + $net_cash_flow_list[$i];
        }

        $net_income_bulanan = $net_cash_flow_list[$i-1]/12;

        // Kolom Jumlah (sum semua tahun)
        $cashflow_projection = [
            [
                'label' => 'Net Income',
                'data' => $net_income_list,
                'total' => array_sum($net_income_list)
            ],
            [
                'label' => 'Add Back Depresiasi',
                'data' => $depresiasi_list,
                'total' => array_sum($depresiasi_list)
            ],
            [
                'label' => 'TOTAL CASH INFLOW',
                'data' => $total_cash_inflow_list,
                'total' => array_sum($total_cash_inflow_list)
            ],
            [
                'label' => 'CAPEX',
                'data' => $capex_list,
                'total' => array_sum($capex_list)
            ],
            [
                'label' => 'Net Cash Flow',
                'data' => $net_cash_flow_list,
                'total' => array_sum($net_cash_flow_list)
            ],
            [
                'label' => 'Cum Cash Flow',
                'data' => $cum_cash_flow_list,
                'total' => array_sum($cum_cash_flow_list)
            ]
        ];

         // BET Period
        $cum_income = $net_income_t0-$biaya_capex;
        $bet_bulan=null;
        for ($bulan = 1; $bulan <= $periode_bulan; $bulan++) {
            $cum_income += $net_income_bulanan;
            if ($cum_income >= 0) {
                $bet_bulan = $bulan;
                break;
            }
        }
        if ($bulan>$periode_bulan){
            $bet_output = "Kontrak Kurang Panjang";
        }else{
            $bet_output = floor($bet_bulan / 12) . ' tahun ' . ($bet_bulan % 12) . ' bulan';
        }

        // Payback Period
        $cum_income = $net_income_t0-$biaya_capex;
        $pbb_bulan = null;

        for ($bulan = 1; $bulan <= $periode_bulan; $bulan++) {
            $cum_income += $net_income_bulanan;
            //echo $cum_income."<br>";
            if ($cum_income >= $biaya_capex) {
                $pbb_bulan = $bulan;
                break;
            }
        }

        $pbb_output2 = null;
        
        if ($bulan>$periode_bulan){
            $pbb_output = "Kontrak Kurang Panjang";
            $pbb_output2 = "Disarankan ".floor(($bet_bulan*2) / 12) . " tahun " . (($bet_bulan*2) % 12) . " bulan ( ".($bet_bulan*2)." bulan )";
        }else{
            $pbb_output = floor($pbb_bulan / 12) . ' tahun ' . ($pbb_bulan % 12) . ' bulan';
        }

        $payback_text = $pbb_output;
        $bet_text = $bet_output;
        $irr = $this->hitungIRR($net_cash_flow_list);

        session(['form_input' => $request->all()]);

        return view('investasi.hasil', compact(
            'periode_bulan',
            'periode_tahun',
            'revenue_bulanan_total',
            'revenue_total_bruto',
            'revenue_otc_akhir',
            'revenue_bulanan_akhir',
            'revenue_total',
            'bad_debt',
            'biaya_operasional_total',
            'biaya_marketing_total',
            'biaya_opex_total',
            'ebitda',
            'depresiasi',
            'ebit',
            'pajak',
            'net_income',
            'tahun_list',
            'biaya_capex',
            'bop_lakwas',
            'npv',
            'irr',
            'payback_text',
            'bet_text',
            'proyeksi_profit_loss',
            'revenue_list',
            'bad_debt_list',
            'opex_list',
            'ebitda_list',
            'depresiasi_list',
            'ebit_list',
            'pajak_list',
            'net_income_list',
            'revenue_total',
            'bad_debt_total',
            'opex_total',
            'ebitda_total',
            'depresiasi_total',
            'ebit_total',
            'pbb_output2',
            'pajak_total',
            'net_income_total',
            'cashflow_projection',
            'cum_cash_flow_list'

        ))->with([
            'nama_project' => $request->nama_project,
            'biaya_investasi' => $request->biaya_investasi,
            'pendapatan_per_bulan' => $request->pendapatan_per_bulan,
            'revenue_otc' => $request->revenue_otc,
            'cost_obl_bulanan' => $request->cost_obl_bulanan,
            'cost_obl_otc' => $request->cost_obl_otc,
            'biaya_operasional' => $request->biaya_operasional,
            'biaya_marketing' => $request->biaya_marketing,
            'bad_debt_input' => $request->bad_debt,
            'taxes' => $request->taxes,
            'tingkat_diskonto' => $request->tingkat_diskonto,
            'status_npv' => $npv > 0 ? 'Layak' : 'Tidak Layak',
            'status_irr' => $irr >= 16 ? 'Layak' : 'Tidak Layak',
        ]);
    }


    // Tambahkan semua method hitungIRR, hitungNPV, hitungPayback, hitungBET, formatTahunBulan di bawah ini

    private function hitungIRR(array $cashFlows, float $guess = 0.1): ?float
    {
        $maxIterations = 1000;
        $precision = 1e-6;

        for ($i = 0; $i < $maxIterations; $i++) {
            $npv = 0;
            $derivative = 0;

            foreach ($cashFlows as $t => $cf) {
                $npv += $cf / pow(1 + $guess, $t);
                if ($t > 0) {
                    $derivative -= $t * $cf / pow(1 + $guess, $t + 1);
                }
            }

            // Cek jika NPV sudah cukup dekat dengan nol
            if (abs($npv) < $precision) {
                return round($guess * 100, 2); // dalam persen
            }

            // Hindari pembagian dengan nol
            if ($derivative == 0) {
                return null;
            }

            // Newton-Raphson iteration
            $guess = $guess - ($npv / $derivative);
        }

        return null; // Jika tidak konvergen
    }

    public function form(Request $request)
    {
        $data = session('form_input', []);
        session()->forget('form_input');

        return view('investasi.form')->with([
            'request' => (object) $data
        ]);
    }

    public function downloadTemplate(Request $request)
{

    ini_set('memory_limit', '512M'); // ← tambahkan ini
    $spreadsheet = IOFactory::load(storage_path('app/template/AKI_template.xlsx'));
    

    // Ambil nilai inputan
    $namaProject = $request->nama_project;
    $biayaInvestasi = $request->biaya_investasi;
    $biaya_opex_total = $request->biaya_opex_total;
    $periode_bulan = (int) ($request->periode_bulan ?? 12);
    $periode_tahun = $periode_bulan / 12;   

    // Hitung bagian marketing & operasional
    $biayaMarketing = $biaya_opex_total * 0.3;
    $biayaOperasional = $biaya_opex_total * 0.2;

     // =================== COVER ====================
     $cover = $spreadsheet->getSheetByName('Cover');
     if ($cover) {
         $cover->setCellValue('E6', $namaProject);
         $cover->setCellValue('E14', $biayaInvestasi);
         $cover->setCellValue('G37', $request->npv ?? 0);
         $cover->setCellValue('G38', $request->irr ?? 0);
         $cover->setCellValue('G39', $request->payback_text ?? '-');
     }
 
     // =================== NJKI ====================
     $njki = $spreadsheet->getSheetByName('NJKI');
    if ($njki) {
         $njki->setCellValue('E10', null); // ← Kosongkan formula E10
         $njki->setCellValue('G10', $namaProject);
         $njki->setCellValue('G22', $biayaInvestasi);
         $njki->setCellValue('I34', $biayaInvestasi);
         $njki->setCellValue('G37', $request->npv ?? 0);
         $njki->setCellValue('G38', $request->irr ?? 0);
         $njki->setCellValue('G39', $request->payback_text ?? '-');
         $njki->setCellValue('H43', $request->status_npv ?? '-');
         $njki->setCellValue('H44', $request->status_irr ?? '-');
     }
 
     // =================== JUSTIFIKASI ====================
     $justifikasi = $spreadsheet->getSheetByName('JUSTIFIKASI');
     if ($justifikasi) {
         $justifikasi->setCellValue('G8', $namaProject);
     }
 
     // =================== REKAP HEM ====================
     $rekap = $spreadsheet->getSheetByName('REKAP HEM');
     if ($rekap) {
         $rekap->setCellValue('B2', null); // ← Kosongkan formula E10 
         $rekap->setCellValue('B3', null);
         $rekap->setCellValue('B4', $namaProject);
         $rekap->setCellValue('B5', null);
         $rekap->setCellValue('G8', $biayaInvestasi);
     }
 
     // =================== ASUMSI ====================
     $asumsi = $spreadsheet->getSheetByName('Asumsi');
     if ($asumsi) {
        $asumsi->setCellValue('D24', $biayaInvestasi);
        $asumsi->setCellValue('D29', $periode_tahun);
     }
 
     // =================== SALES & REVENUE ====================
    $salesRevenue = $spreadsheet->getSheetByName('Sales & Revenue');
    if ($salesRevenue) {
        // Asumsi baris SALES PLAN dimulai dari baris 16
        // Revenue OTC → Baris 15, kolom D (tahun ke-1 = kolom D = kolom index 4)
        //$salesRevenue->setCellValue('D15', $request->revenue_otc ?? 0); // Revenue OTC
        //$salesRevenue->setCellValue('C16', 1); // Subscriber Revenue Bulanan tahun ke-0
        //$salesRevenue->setCellValue('D16', $request->pendapatan_per_bulan ?? 0); // Revenue Bulanan
        //$salesRevenue->setCellValue('D17', $request->cost_obl_otc ?? 0); // Cost OBL OTC
        //$salesRevenue->setCellValue('D18', $request->cost_obl_bulanan ?? 0); // Cost OBL Bulanan

        // ================== REVENUE GROSS ==================
        $salesRevenue->setCellValue('D17', $request->revenue_otc ?? 0);
        $salesRevenue->setCellValue('D18', $request->pendapatan_per_bulan ?? 0);
        $salesRevenue->setCellValue('D19', $request->cost_obl_otc ?? 0);
        $salesRevenue->setCellValue('D20', $request->cost_obl_bulanan ?? 0);

        // ================== REVENUE NET ==================
        $salesRevenue->setCellValue('D24', $request->revenue_otc ?? 0);
        $salesRevenue->setCellValue('D25', $request->pendapatan_per_bulan ?? 0);
        $salesRevenue->setCellValue('D26', $request->cost_obl_otc ?? 0);
        $salesRevenue->setCellValue('D27', $request->cost_obl_bulanan ?? 0);

        // Total Revenue NET (baris 35)
        $salesRevenue->setCellValue('D35', "=SUM(D31:D34)");
        // Kolom B (label teks)
        $salesRevenue->setCellValue('B34', 'Revenue OTC');
        $salesRevenue->setCellValue('B35', 'Revenue Bulanan');
        $salesRevenue->setCellValue('B36', 'Cost OBL OTC');
        $salesRevenue->setCellValue('B37', 'Cost OBL Bulanan');

        // Kolom D (isi nilai input user)
        $salesRevenue->setCellValue('D34', $request->input('revenue_otc', 0));
        $salesRevenue->setCellValue('D35', $request->input('pendapatan_per_bulan', 0));
        $salesRevenue->setCellValue('D36', $request->input('cost_obl_otc', 0));
        $salesRevenue->setCellValue('D37', $request->input('cost_obl_bulanan', 0));
}


 

// =================== EXPENSE ====================
    $expense = $spreadsheet->getSheetByName('Expense');
    if ($expense) {
    // E6 -> =($C$6*'Sales & Revenue'!E$29)
    $expense->setCellValueExplicit('E6', '=$C$6*\'Sales & Revenue\'!E$29', DataType::TYPE_FORMULA);

    // E7 -> =($C$7*Asumsi!$D$24)
    $expense->setCellValueExplicit('E7', '=$C$7*Asumsi!$D$24', DataType::TYPE_FORMULA);

    // E10 -> =SUM(E6:E9)
    $expense->setCellValueExplicit('E10', '=SUM(E6:E9)', DataType::TYPE_FORMULA);

    // Untuk kolom tahun kedua dst (F, G, H, I) kamu ulangi
    $columns = ['E', 'F', 'G', 'H', 'I'];
    foreach ($columns as $col) {
        $expense->setCellValueExplicit($col.'6', '=$C$6*\'Sales & Revenue\'!'.$col.'$29', DataType::TYPE_FORMULA);
        $expense->setCellValueExplicit($col.'7', '=$C$7*Asumsi!$D$24', DataType::TYPE_FORMULA);
        $expense->setCellValueExplicit($col.'10', '=SUM('.$col.'6:'.$col.'9)', DataType::TYPE_FORMULA);
    }
}

 
     // =================== NEW VALUATION ====================
     $valuation = $spreadsheet->getSheetByName('NEW VALUATION');
     if ($valuation) {
         // Profit & Loss
         $valuation->setCellValue('C6', $request->revenue[0] ?? 0);
         $valuation->setCellValue('D6', $request->revenue[1] ?? 0);
         $valuation->setCellValue('E6', $request->revenue[2] ?? 0);
         $valuation->setCellValue('C7', $request->bad_debt[0] ?? 0);
         $valuation->setCellValue('D7', $request->bad_debt[1] ?? 0);
         $valuation->setCellValue('E7', $request->bad_debt[2] ?? 0);
         $valuation->setCellValue('C8', $request->opex[0] ?? 0);
         $valuation->setCellValue('D8', $request->opex[1] ?? 0);
         $valuation->setCellValue('E8', $request->opex[2] ?? 0);
         $valuation->setCellValue('C9', $request->ebitda[0] ?? 0);
         $valuation->setCellValue('D9', $request->ebitda[1] ?? 0);
         $valuation->setCellValue('E9', $request->ebitda[2] ?? 0);
         $valuation->setCellValue('C10', $request->depresiasi[0] ?? 0);
         $valuation->setCellValue('D10', $request->depresiasi[1] ?? 0);
         $valuation->setCellValue('E10', $request->depresiasi[2] ?? 0);
         $valuation->setCellValue('C11', $request->ebit[0] ?? 0);
         $valuation->setCellValue('D11', $request->ebit[1] ?? 0);
         $valuation->setCellValue('E11', $request->ebit[2] ?? 0);
         $valuation->setCellValue('C12', $request->pajak[0] ?? 0);
         $valuation->setCellValue('D12', $request->pajak[1] ?? 0);
         $valuation->setCellValue('E12', $request->pajak[2] ?? 0);
         $valuation->setCellValue('C13', $request->net_income[0] ?? 0);
         $valuation->setCellValue('D13', $request->net_income[1] ?? 0);
         $valuation->setCellValue('E13', $request->net_income[2] ?? 0);
 
         // Cash Flow Projection
         $valuation->setCellValue('C19', $request->cf_net_income[0] ?? 0);
         $valuation->setCellValue('D19', $request->cf_net_income[1] ?? 0);
         $valuation->setCellValue('E19', $request->cf_net_income[2] ?? 0);
         $valuation->setCellValue('C20', $request->cf_depresiasi[0] ?? 0);
         $valuation->setCellValue('D20', $request->cf_depresiasi[1] ?? 0);
         $valuation->setCellValue('E20', $request->cf_depresiasi[2] ?? 0);
         $valuation->setCellValue('C21', $request->cf_total_inflow[0] ?? 0);
         $valuation->setCellValue('D21', $request->cf_total_inflow[1] ?? 0);
         $valuation->setCellValue('E21', $request->cf_total_inflow[2] ?? 0);
         $valuation->setCellValue('C23', $request->cf_capex[0] ?? 0);
         $valuation->setCellValue('D23', $request->cf_capex[1] ?? 0);
         $valuation->setCellValue('E23', $request->cf_capex[2] ?? 0);
         $valuation->setCellValue('C25', $request->cf_net_cashflow[0] ?? 0);
         $valuation->setCellValue('D25', $request->cf_net_cashflow[1] ?? 0);
         $valuation->setCellValue('E25', $request->cf_net_cashflow[2] ?? 0);
         $valuation->setCellValue('C26', $request->cf_cum_cashflow[0] ?? 0);
         $valuation->setCellValue('D26', $request->cf_cum_cashflow[1] ?? 0);
         $valuation->setCellValue('E26', $request->cf_cum_cashflow[2] ?? 0);
 
         // Feasibility Analysis
        $valuation->setCellValue('C34', $request->npv ?? 0);
        $valuation->setCellValue('D34', $request->status_npv ?? '-');
        $valuation->setCellValue('C35', ($request->irr ?? 0) / 100);
        $valuation->setCellValue('D35', $request->status_irr ?? '-');
        $valuation->setCellValue('C36', $request->payback_text ?? '-');
        $valuation->setCellValue('C37', $request->bet_text ?? '-');

     }
 
     // =================== INVESTASI + EXPENSES ====================
     $investasiSheet = $spreadsheet->getSheetByName('Investasi & depresiasi + expens');
     if ($investasiSheet) {
        // Ambil data dari request
    $nilaiInvestasiList = [$request->nilai_investasi_1, $request->nilai_investasi_2]; // <-- Sesuaikan
    $umurInvestasiList = [$request->umur_investasi_1, $request->umur_investasi_2];

    $startColIndex = 6; // Kolom 'G' (0 tahun)
    $maxYears = 6; // 0-5 tahun

    foreach ($nilaiInvestasiList as $rowIndex => $nilaiInvestasi) {
        $umur = $umurInvestasiList[$rowIndex];
        // Cek validasi umur
        if (!$umur || $umur <= 0) {
            continue; // Lewati jika umur kosong atau 0
        }

        $depresiasiPerTahun = $nilaiInvestasi / $umur;

        // Isi sesuai umur
        for ($tahun = 1; $tahun <= $umur; $tahun++) {
            $colLetter = chr(71 + $tahun - 1); // 71 = 'G'
            $investasiSheet->setCellValue($colLetter . (7 + $rowIndex), $depresiasiPerTahun);
        }
    }

    // Hitung jumlah total per tahun
    for ($tahun = 0; $tahun <= $maxYears; $tahun++) {
        $colLetter = chr(71 + $tahun); // 71 = 'G'

        $total = 0;
        for ($rowIndex = 0; $rowIndex < count($nilaiInvestasiList); $rowIndex++) {
            $value = $investasiSheet->getCell($colLetter . (7 + $rowIndex))->getValue();
            if (is_numeric($value)) {
                $total += $value;
            }
        }

        $investasiSheet->setCellValue($colLetter . '10', $total);
    }
}
    // Simpan dan download
    $filename = 'AKI_Filled_' . time() . '.xlsx';
    $path = storage_path("app/public/{$filename}");

    (new Xlsx($spreadsheet))->save($path);

    return response()->download($path)->deleteFileAfterSend(true);
}
}
