<?php

namespace App\Http\Controllers;

use App\Penjualan;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller 
{
    public function getDrafPengeluaranBarang($kocab)
    {   
        $result = Penjualan::select('No_DO')->whereNull('OTORISASI_PPIC_USER')->get();

        if($result->count()){
            $out = [
                'message' => 'success',
                'result' => $result
            ];
        } else {
            $out = [
                'message' => 'success',
                'result' => []
            ];
        }

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);

    }

    public function getJadwal($kocab)
    {
        $result = DB::table('erasystem_2012.jp_pellet as A')
            ->selectRaw("A.ID_JADWAL, A.ID_SO, C.NOPOL, A.PT_ID, A.GUDANG, B.NoTrans, (SELECT SUM(Jml) FROM erasystem_2012.det_bpb_retur WHERE REPLACE(Notrans_BPB, '.B01', '') = B.NoTrans) AS TOTAL")
            ->join('erasystem_2012.jp_pellet_det as C', 'A.ID_JADWAL', '=', 'C.ID_JADWAL')
            ->join('erasystem_2012.daf_pengeluaran_barang as B', 'A.ID_SO', '=', 'B.NO_SO')
            ->whereRaw('A.TARIK_DO = ? AND A.GUDANG = ?', [0, $kocab])
            ->get();

        if($result->count()){
            $out = [
                'message' => 'success',
                'result' => $result
            ];
        } else {
            $out = [
                'message' => 'success',
                'result' => []
            ];
        }

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);

    }

    public function checkBarcodeKirim(Request $request)
    {
        $pt = $request->input('PT_ID'); // yg kirim
        $gudang = $request->input('GUDANG'); // yg kirim
        $barcode = $request->input('BARCODE');
        $id_so = $request->input('ID_SO');
        $no_trans = $request->input('NOTRANS');

        $kode = substr($barcode, 0, 13);
        $newpt = $pt == '1' ? 'ERA' : ($pt == '2' ? 'ERI' : 'EPI');

        $dept = 'PPC';
        $area = 'Plastics Pellet Warehouse';
        $newstatus = 'TERIMA';

        $check = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
            ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
            ->first();

        if($check) {

            $item = DB::table('erasystem_2012.daf_bpb_retur')->whereRaw('Notrans_BPB = ? AND KoHan = ?', [$no_trans, $kode])->first();

            if($item) {

                $result = DB::table('erasystem_2012.barcode_pellet')
                    ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                        $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                    })
                    ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
                    ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
                    ->first();

                if($result) {

                    $result->SISA = $item->Jml - $result->KG;
                    $code = 200;
                    $out = [
                        'message' => 'success',
                        'result' => $result,
                        'status' => true
                    ];

                } else {
                    $code = 200;
                    $out = [
                        'message' => 'Barcode tidak tersedia untuk area ' . $area . '. Detail barcode   saat ini: ',
                        'result' => $result,
                        'status' => false
                    ];
                }

            } else {
                $code = 200;
                $out = [
                    'message' => 'Item tidak sesuai dengan nomor SO!',
                    'result' => [],
                    'status' => false
                ];
            }

        } else {
            $code = 200;
            $out = [
                'message' => 'Barcode tidak terdaftar!',
                'result' => [],
                'status' => false
            ];
        }

        return response()->json($out, $code, [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request) 
    {
        $records = $request->all();

        $this->validate($request, [
            //'*' => 'required|array',
            '*.USERNAME' => 'required',
            '*.PT_ID' => 'required',
            '*.PT_NAMA' => 'required',
            '*.GUDANG' => 'required',
            '*.STATUS' => 'required',
            '*.ID_SO' => 'required',
            '*.ID_JADWAL' => 'required',
            '*.NOPOL' => 'required',
        ]);
    }

}