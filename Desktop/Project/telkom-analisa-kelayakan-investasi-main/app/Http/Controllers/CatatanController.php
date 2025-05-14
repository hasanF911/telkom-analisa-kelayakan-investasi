<?php

namespace App\Http\Controllers;

use App\Models\Catatan;
use Illuminate\Http\Request;

class CatatanController extends Controller
{
    public function index()
    {
        $catatans = Catatan::latest()->get();
        return view('catatan.index',compact('catatans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required',
            'isi' => 'required',
        ]);

        Catatan::create($request->all());
        return redirect('/');
    }
}
