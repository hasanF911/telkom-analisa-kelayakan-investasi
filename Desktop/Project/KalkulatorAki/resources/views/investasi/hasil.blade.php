<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Analisa Kelayakan Investasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h3 class="mb-4">Estimasi Hasil Analisa Kelayakan Investasi</h3>

                        <table class="table table-bordered">
                            <tr><th>Nama Proyek</th><td>{{ $nama_project }}</td></tr>
                            <tr><th>Biaya Investasi</th><td>Rp {{ number_format($biaya_investasi, 0, ',', '.') }}</td></tr>
                            <tr><th>Pendapatan per Bulan</th><td>Rp {{ number_format($pendapatan_per_bulan, 0, ',', '.') }}</td></tr>
                            <tr><th>Periode</th><td>{{ $periode_bulan }} bulan</td></tr>

                            <tr class="table-light">
                                <th>Revenue</th>
                                <td>
                                    <strong>Rp {{ number_format($revenue_total, 0, ',', '.') }}</strong><br>
                                    OTC Akhir: Rp {{ number_format($revenue_otc_akhir, 0, ',', '.') }}<br>
                                    Bulanan Akhir: Rp {{ number_format($revenue_bulanan_akhir, 0, ',', '.') }} x {{ $periode_bulan }}
                                </td>
                            </tr>

                            <tr class="table-light">
                                <th>Cost OBL</th>
                                <td>
                                    <strong>Rp {{ number_format(($cost_obl_bulanan * $periode_bulan) + $cost_obl_otc, 0, ',', '.') }}</strong><br>
                                    OTC: Rp {{ number_format($cost_obl_otc, 0, ',', '.') }}<br>
                                    Bulanan: Rp {{ number_format($cost_obl_bulanan, 0, ',', '.') }} x {{ $periode_bulan }}
                                </td>
                            </tr>

                            <tr>
                                <th>Biaya Operasional + Marketing</th>
                                <td>
                                    {{ $biaya_operasional + $biaya_marketing }}% 
                                    (Rp {{ number_format($biaya_opex_total, 0, ',', '.') }})<br>
                                    <small class="text-muted">
                                        Marketing: {{ $biaya_marketing }}% (Rp {{ number_format($biaya_marketing_total, 0, ',', '.') }})<br>
                                        Operasional: {{ $biaya_operasional }}% (Rp {{ number_format($biaya_operasional_total, 0, ',', '.') }})
                                    </small>
                                </td>
                            </tr>

                            <tr><th>Bad Debt ({{ $bad_debt_input }}%)</th><td>Rp {{ number_format($bad_debt, 0, ',', '.') }}</td></tr>
                            <tr><th>EBITDA</th><td>Rp {{ number_format($ebitda, 0, ',', '.') }}</td></tr>
                            <tr><th>Depresiasi</th><td>Rp {{ number_format($depresiasi, 0, ',', '.') }}</td></tr>
                            <tr><th>EBIT</th><td>Rp {{ number_format($ebit, 0, ',', '.') }}</td></tr>
                            <tr><th>Taxes ({{ $taxes_input }}%)</th><td>Rp {{ number_format($pajak, 0, ',', '.') }}</td></tr>

                            <tr class="table-warning fw-bold">
                                <th>Net Income</th>
                                <td>Rp {{ number_format($net_income, 0, ',', '.') }}</td>
                            </tr>

                            <tr><th>BOP Lakwas (0.7%)</th><td>Rp {{ number_format($bop_lakwas, 0, ',', '.') }}</td></tr>
                            <tr><th>Biaya Capex</th><td>Rp {{ number_format($biaya_capex, 0, ',', '.') }}</td></tr>

                            <tr class="table-success fw-bold">
                                <th>NPV</th>
                                <td>
                                    Rp {{ number_format($npv, 0, ',', '.') }}
                                    <span class="badge bg-{{ $status_npv === 'Layak' ? 'success' : 'danger' }} ms-2">
                                        {{ $status_npv }}
                                    </span>
                                </td>
                            </tr>

                            <tr class="table-primary fw-bold">
                                <th>IRR</th>
                                <td>
                                    {{ is_numeric($irr) ? $irr . '%' : 'Tidak ditemukan' }}
                                    <span class="badge bg-{{ $status_irr === 'Layak' ? 'success' : 'danger' }} ms-2">
                                        {{ $status_irr }}
                                    </span>
                                </td>
                            </tr>

                            <tr class="table-info fw-bold">
                                <th>Payback Period</th>
                                <td>
                                @if ($payback_formatted === null || $npv <= 0)
                                     Tidak ditemukan
                                @else
                                    {{ $payback_formatted }}
                                @endif
                                </td>
                                </tr>

                        </table>

                        <a href="{{ url('/investasi/kembali') }}" class="btn btn-secondary mt-3">← Kembali ke Form</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
