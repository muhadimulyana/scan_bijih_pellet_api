<?php

namespace App\Http\Controllers;

use App\Departemen;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DepartemenController extends Controller
{

    public function index()
    {
        $result = Departemen::orderBy('KoDep')->get();

        $out = [
            'message' => 'success',
            'result' => $result,
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getdeptScan()
    {
        $result = Departemen::whereIn('KoDep', ['PPC', 'PRO', 'QUA'])->get();

        $out = [
            'message' => 'success',
            'result' => $result,
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getdeptPengirim($user, $dept)
    {
        $check = DB::table('erasystem_2012.whole_system')->select('*')->whereRaw('wh_sys_id = ? AND val1 = ?', ['BARCODE-PENGIRIM-PILIH', $user])->first();

        if(!$check){
            $resdep = Departemen::whereIn('KoDep', [$dept])->get();
            $out = [
                'message' => 'success',
                'result' => $resdep,
            ];
        } else {
            $resdep = Departemen::whereIn('KoDep', ['PPC', 'PRO', 'QUA'])->get();
            $out = [
                'message' => 'success',
                'result' => $resdep,
            ];
        }

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }


}