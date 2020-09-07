<?php

namespace App\Http\Controllers;

use App\BarcodePelletDet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarcodePelletDetController extends Controller
{
    public function index()
    {
        $result = BarcodePelletDet::orderBY('TANGGAL')->get();

        return response()->json($result);
    }
    
    public function getlistBarcode($status, $notrans)//Dep yg dimaksud dep penerima atau pengirim
    {

        $newstatus = $status == 'TERIMA' ? 'KIRIM' : 'KIRIM';
        //$newpt = $pt == '1' ? 'ERA' : 'ERI'; 

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', [$newstatus, $notrans, '1'])
            ->select('barcode_pellet_det.*')
            ->get();

        $out = [
            'message' => 'success',
            'result' => $result
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function checkBarcode(Request $request)
    {
        $barcode = $request->input('BARCODE');

        $check1 = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
        ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
        ->first();

        if($check1){
            $out = [
                'message' => 'Detail status barcode saat ini:',
                'result' => $check1,
                'isRegistered' => TRUE,
                'status' => FALSE,
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'Barcode tidak terdaftar!',
                'result' => [],
                'isRegistered' => FALSE,
                'status' => FALSE,
                'code' => 200
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function getkodePellet()
    {
        $result = DB::table('erasystem_2012.pellet')->select('KODE')->get();
        $out = [
            'message' => 'success',
            'result' => $result,
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    // public function getList()
    // {
    //     $result = BarcodePelletDet::where('NO_BST', )
    // }

    public function store(Request $request)
    {

        if ($request->method('post')) {

            $records = $request->all();

            foreach ($records as $record) {

                $data[] = [
                    'TANGGAL' => $record['tanggal'],
                    'BARCODE' => $record['barcode'],
                    'PT_ID' => $record['ptId'],
                    'PT_NAMA' => $record['ptNama'],
                    'GUDANG' => $record['gudang'],
                    'DEPT_ID' => $record['deptId'],
                    'DEPT_NAMA' => $record['deptNama'],
                    'NOTRANS' => $record['notrans'],
                    'USERNAME' => $record['uName'],
                    'STATUS' => $record['status'],
                ];
            }

            DB::beginTransaction();

            $insert = BarcodePelletDet::insert($data);

            if ($insert) {
                $out = [
                    'message' => 'success',
                    'result' => $data,
                    'code' => 200,
                ];

                DB::commit();
            } else {
                $out = [
                    'message' => 'failed',
                    'result' => $data,
                    'code' => 404,
                ];

                DB::rollback();
            }

            return response()->json($out, [], JSON_NUMERIC_CHECK);

        };
    }
    
    // public function checkBarcode($barcode, $notrans)
    // {
    //     $result = BarcodePelletDet::whereRaw('BARCODE = ? AND NOTRANS = ?', [$barcode, $notrans])->first();

    //     if ($result) {
    //         $out = [
    //             'message' => 'success',
    //             'result' => $result,
    //             'code' => 200
    //         ];
    //     } else {
    //         $out = [
    //             'message' => 'empty',
    //             'result' => $result,
    //             'code' => 404
    //         ];
    //     }

    //     return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    // }

}
