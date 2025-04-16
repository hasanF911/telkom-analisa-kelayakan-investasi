<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Form AKI - Analisa Kelayakan Investasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h3 class="mb-4">Analisa Kelayakan Investasi (AKI)</h3>

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ url('/investasi') }}" method="POST" onsubmit="return stripCommas()">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Nama Proyek</label>
                                <input type="text" class="form-control" name="nama_project"
                                    value="{{ old('nama_project', $request->nama_project ?? '') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Biaya Investasi (Rp)</label>
                                <input type="text" class="form-control rupiah" name="biaya_investasi"
                                    value="{{ old('biaya_investasi', $request->biaya_investasi ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Periode (bulan)</label>
                                <input type="number" class="form-control" name="periode_bulan"
                                    value="{{ old('periode_bulan', $request->periode_bulan ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pendapatan OTC (Rp)</label>
                                <input type="text" class="form-control rupiah" name="revenue_otc"
                                    value="{{ old('revenue_otc', $request->revenue_otc ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pendapatan per Bulan (Rp)</label>
                                <input type="text" class="form-control rupiah" name="pendapatan_per_bulan"
                                    value="{{ old('pendapatan_per_bulan', $request->pendapatan_per_bulan ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cost OBL OTC (Rp)</label>
                                <input type="text" class="form-control rupiah" name="cost_obl_otc"
                                    value="{{ old('cost_obl_otc', $request->cost_obl_otc ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cost OBL Bulanan (Rp)</label>
                                <input type="text" class="form-control rupiah" name="cost_obl_bulanan"
                                    value="{{ old('cost_obl_bulanan', $request->cost_obl_bulanan ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Biaya Operasional (%) (Default 20%)</label>
                                <input type="number" step="0.01" class="form-control" name="biaya_operasional"
                                    value="{{ old('biaya_operasional', $request->biaya_operasional ?? 20) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Biaya Marketing (%) (Default 30%)</label>
                                <input type="number" step="0.01" class="form-control" name="biaya_marketing"
                                    value="{{ old('biaya_marketing', $request->biaya_marketing ?? 30) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bad Debt (%) (Default 5%)</label>
                                <input type="number" step="0.01" class="form-control" name="bad_debt"
                                    value="{{ old('bad_debt', $request->bad_debt ?? 5) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Taxes (%) (Default 30%)</label>
                                <input type="number" step="0.01" class="form-control" name="taxes"
                                    value="{{ old('taxes', $request->taxes ?? 30) }}">
                                <input type="hidden" name="tingkat_diskonto" value="0">
                            </div>

                            <button type="submit" class="btn btn-primary">Hitung Kelayakan</button>
                        </form>

                        <!-- Script format rupiah dan hapus koma sebelum submit -->
                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                const formatRupiah = (angka) => {
                                    return angka
                                        .replace(/[^\d]/g, '')
                                        .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                };

                                document.querySelectorAll(".rupiah").forEach(function (input) {
                                    input.addEventListener("input", function () {
                                        const caret = this.selectionStart;
                                        this.value = formatRupiah(this.value);
                                        this.setSelectionRange(caret, caret);
                                    });
                                });
                            });

                            function stripCommas() {
                                document.querySelectorAll(".rupiah").forEach(function (input) {
                                    input.value = input.value.replace(/,/g, '');
                                });
                                return true;
                            }
                        </script>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
