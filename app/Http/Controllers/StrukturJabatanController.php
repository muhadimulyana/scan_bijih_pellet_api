<?php

namespace App\Http\Controllers;

use App\StrukturJabatan;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StrukturJabatanController extends Controller
{

    public function show($dept)
    {
        // $result = StrukturJabatan::selectRaw('DARI_DEPT_AREA, KE_DEPT_AREA')
        //     ->where('DARI_DEPT_ID', $dept)
        //     ->where('AKTIF', '1')
        //     ->groupBy('DARI_DEPT_AREA')
        //     ->orderBy('DARI_DEPT_AREA', 'ASC')
        //     ->get();

        $result = StrukturJabatan::select('GRUP')->where('KODEP', $dept)->whereIn('grup', ['Pelletizing','Receiving','Plastics Pellet Warehouse','In Process','Mixing and Blowing'])->groupBy('GRUP')->get();

        $out = [
            'message' => 'success',
            'result' => $result,
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getArea($user)
    {
        $result = DB::table('erasystem_2012.whole_system')->select('val2')->where('wh_sys_id', 'BARCODE-PENGIRIM-AREA-DEPT')->where('val1', $user)->get();

        if($result->count()) {
            $out = [
                'message' => 'success',
                'result' => $result,
            ];
            $code = 200;
        } else{  
            $out = [
                'message' => 'empty',
                'result' => []
            ];
            $code = 404;
        }

        return response()->json($out, $code, [], JSON_NUMERIC_CHECK);
    }

}