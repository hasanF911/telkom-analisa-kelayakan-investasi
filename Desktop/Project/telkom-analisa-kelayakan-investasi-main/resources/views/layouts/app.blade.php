<!DOCTYPE html>
<html lang="en">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<head>
    <meta charset="UTF-8">
    <title>Analisa Kelayakan Investasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        @yield('content')
    </div>
    @stack('scripts')
    @yield('scripts')
</body>
</html>
