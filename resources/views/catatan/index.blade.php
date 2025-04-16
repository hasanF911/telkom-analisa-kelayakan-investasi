<!DOCTYPE html>
<html>
<head>
    <title>Catatan Harian</title>
</head>
<body>
    <h1>Tambah Catatan Harian</h1>

    <form method="POST" action="{{ route('catatan.store') }}">
        @csrf
        <input type="text" name="judul" placeholder="Judul"><br><br>
        <textarea name="isi" placeholder="Isi catatan"></textarea><br><br>
        <button type="submit">Simpan</button>
    </form>

    <hr>

    <h2>Daftar Catatan:</h2>
    <ul>
        @foreach ($catatans as $catatan)
            <li>
                <strong>{{ $catatan->judul }}</strong><br>
                {{ $catatan->isi }}
            </li>
        @endforeach
    </ul>
</body>
</html>
