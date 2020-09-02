<?php

namespace App\Http\Controllers;

use App\Cabang;
use App\Http\Controllers\Controller;

class CabangController extends Controller
{
    public function index()
    {
        $result = Cabang::orderBy('No_Urut')->get();

        $out = [
            'message' => 'success',
            'result' => $result,
        ];

        return response()->json($out, 200);
    }

    public function getPt()
    {
        $result = Cabang::groupBy('KaCab')
            ->orderBy('No_Urut')
            ->get();

        $out = [
            'message' => 'success',
            'result' => $result,
        ];

        return response()->json($out, 200);
    }

    public function getGudang($kaCab)
    {
        $result = Cabang::where('KaCab', $kaCab)
            ->get();

        $out = [
            'message' => 'success',
            'result' => $result,
        ];

        return response()->json($out, 200);
    }
}
