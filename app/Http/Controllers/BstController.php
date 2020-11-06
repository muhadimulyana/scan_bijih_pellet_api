<?php

namespace App\Http\Controllers;

use App\Bst;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class BstController extends Controller
{

    public function bstKirim($pt, $gudang, $dept, $area)
    {
        //$data = Bst::where('STATUS', '1')->where('(SELECT(LEFT(NO_BST, 1))', '=', '1')->get();
         $gudang = urldecode($gudang);
         $area = urldecode($area);

        $data = Bst::whereRaw('STATUS = ? AND LEFT(NO_BST, 1) = ? AND GUDANG = ? AND KE_DEPT_ID = ? AND KE_DEPT_AREA = ?', ['1', $pt, $gudang, $dept, $area])->get();

        $out = [
            'message' => 'success',
            'result' => $data,
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getbstUser($pt, $dept) // Untuk user = 0 (Menampilkan BST untuk di terima)
    {
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

        $data = Bst::whereRaw('STATUS = ? AND PT_ID = ? AND KE_DEPT_ID = ?', ['1', $newpt, $dept])->get();

        if($data->count()){
            $out = [
                'message' => 'success',
                'result' => $data,
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'empty',
                'result' => [],
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function gettotalBarcode($notrans) // Untuk terima
    {
        // $barcode = DB::table('tes.barcode_pellet')
        // ->join('tes.barcode_pellet_det', function ($join) {
        //     $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        // })
        // ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ?', ['KIRIM', $notrans])
        // ->selectRaw('barcode_pellet_det.NOTRANS, count(*) as TOTAL')
        // ->first();

        $bst = Bst::selectRaw('TOTAL')->where('NO_BST', $notrans)->first();
        

        $out = [
            'message' => 'success',
            'total' => $bst->TOTAL,
            'notrans' => $notrans
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function ceklistArea(Request $request)
    {
        $this->validate($request, [
            'DARI_DEPT_AREA' => 'required',
            'KE_DEPT_AREA' => 'required'
        ]);

        $dariDeptArea = $request->input('DARI_DEPT_AREA');
        $kedeptArea = $request->input('KE_DEPT_AREA');

        $result = DB::table('erasystem_2012.list_bst_kirim')->select('*')->whereRaw('list_bst_kirim.DARI_DEPT_AREA = ? AND list_bst_kirim.KE_DEPT_AREA = ?', [$dariDeptArea, $kedeptArea])->first();
        

        if($result){
            $out = [
                'message' => 'success',
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'empty',
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checklistBst2($notrans)
    {
        $result = DB::table('tes.bst_pellet')
            ->join('tes.list_bst_kirim', function ($join) {
                $join->on('bst_pellet.DARI_DEPT_AREA', '=', 'list_bst_kirim.DARI_DEPT_AREA')->on('bst_pellet.KE_DEPT_AREA', '=', 'list_bst_kirim.KE_DEPT_AREA');
            })
            ->whereRaw('bst_pellet.NO_BST = ? AND list_bst_kirim.CEK_KODE_PELLET = ?', [$notrans, '1'])
            ->selectRaw('*')
            ->first();

        $res = $result ? 1 : 0;;

        $out = [
            'message' => 'success',
            'result' => $res
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }
    

    public function _setVariable($jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $deptArea, $notrans, $username, $status, $ket)
    {
        DB::statement(DB::raw("SET @JENIS_DOK='" . $jenisDok . "', @PT_ID='" . $ptId . "', @PT_NAMA='" . $ptNama . "', @GUDANG='" . $gudang . "', @DEPT_ID='" . $deptId . "', @DEPT_NAMA='" . $deptNama . "', @AREA='" . $deptArea ."', @NOTRANS='" . $notrans . "', @UserLoginAktif='" . $username . "', @STATUS_='" . $status . "', @KETERANGAN='" . $ket . "'"));
        //$this->var = [$jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $deptArea, $notrans, $username, $status, $ket];
    }

    public function bstGenerate($pt, $dept, $tgl) // Client request terlebih dahulu nomor bst
    {
        $bst = Bst::selectRaw('CAST(MAX(RIGHT(tes.bst_pellet.NO_BST,2)) AS SIGNED) AS LAST_NO')->whereRaw('tes.bst_pellet.PT_ID = ? AND tes.bst_pellet.DARI_DEPT_ID = ? AND tes.bst_pellet.TANGGAL = ?', [$pt, $dept, $tgl])->first();

        $NoPt = $pt == 'ERA' ? '1' : '2';
        $tahun = date('Y', strtotime($tgl));
        $bulan = date('m', strtotime($tgl));
        $tanggal = date('d', strtotime($tgl));
        
        if($bst){
            $no = $bst->LAST_NO;
            $no++;
            $NoTrans = $NoPt . '/' . $dept . '/BST/' . $tahun . '/' . $bulan . '/' .  $tanggal . '/' .  sprintf("%02s", $no);
        } else {
            $NoTrans = $NoPt . '/' . $dept . '/BST/' . $tahun . '/' . $bulan . '/' .  $tanggal . '/' .  '01';
        }

        $data = [
            'NO_BST' => $NoTrans
        ];

        $out = [
            'message' => 'success',
            'result' => $data,
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function terimaBst(Request $request) // Pastikan yg data PT, Gudang, dan dari dept itu adalah yg terima/user login
    {
        if($request->method('post')) {

            //Status terima minimal
            $this->validate($request, [
                'USERNAME' => 'required',
                'PT_ID' => 'required',
                'PT_NAMA' => 'required',
                'GUDANG' => 'required',
                'STATUS' => 'required',
                'NOTRANS' => 'required',
                'DEVICE_LOGIN' => 'required'
            ]);

            $status = $request->input('STATUS');
            $username = $request->input('USERNAME');
            $noBst = $request->input('NOTRANS');
            $ptId = $request->input('PT_ID');
            $ptNama = $request->input('PT_NAMA');
            $device = $request->input('DEVICE_LOGIN');
            //$gudang = $request->input('GUDANG');
            //$area = $request->input('AREA');


            $bst = Bst::select('GUDANG', 'KE_DEPT_ID', 'KE_DEPT_NAMA', 'KE_DEPT_AREA')->where('NO_BST', $noBst)->first();
            $gudang = $bst->GUDANG;
            $dariDeptId = $bst->KE_DEPT_ID;
            $dariDeptNama = $bst->KE_DEPT_NAMA;
            $dariDeptArea = $bst->KE_DEPT_AREA;

            $ket = $request->input('KETERANGAN');
            $datetime = date('Y-m-d H:i:s');
            $date = date('Y-m-d'); 

            $newpt = $ptId == '1' ? 'ERA' : ( $ptId == '2' ? 'ERI' : 'EPI'); 

            //Set variabel tambahkan area
            $this->_setVariable('BST', $newpt, $ptNama, $gudang, $dariDeptId, $dariDeptNama, $dariDeptArea, $noBst, $username, $status, $ket);

            $data = [
                'STATUS' => 2,
                'TERIMA_FINAL' => $username,
                'TERIMA_FINAL_TANGGAL' => $datetime,
                'LAST_UPDATE' => $datetime
            ];

            $newstatus = 'KIRIM';

            //Perlukah cari barcode berdasarkan join dan where dari dept/ke dept || Perbaiki seleksi barcode
            $resbarcode = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', [$newstatus, $noBst, '1'])
            ->selectRaw('barcode_pellet_det.BARCODE')
            ->get();
            $barcode = $resbarcode->toArray();
            $listbarcode = array_column($barcode, 'BARCODE');

            DB::beginTransaction();

            try {

                DB::statement(DB::raw("SET @USER_LOGIN='" . $username . "', @DEVICE_LOGIN='" . $device . "'"));
                $update = Bst::where('NO_BST', $noBst)->update($data);
                DB::statement(DB::raw("SET @AKSI = 'TAMBAH'"));
                DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $listbarcode)->update(['LAST_UPDATE' => $datetime]);

                $out = [
                    'message' => 'Submit sukses',
                    'result' => $data
                ];
                $code = 201;
                DB::commit();

            } catch( QueryException $e) {

                $out = [
                    'message' => 'Submit gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                    'result' => $data
                ];
                $code = 500;
                DB::rollBack();

            }

            return response()->json($out, $code, [], JSON_NUMERIC_CHECK);

        }
    }


    public function checkBarcode(Request $request) // cek barcode ketika akan kirim || cari barcode berdasarkan data pengirim yg login PT, Gudang, Dept dan Area
    {

        $pt = $request->input('PT_ID');
        $gudang = $request->input('GUDANG');
        $dept = $request->input('DEPT_ID');
        $area = $request->input('DEPT_AREA');
        $area2 = $request->input('KE_DEPT_AREA');
        $barcode = $request->input('BARCODE');
        $newstatus = 'TERIMA';
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
            ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
            ->first();
            //Belum diberikan return data ketika scan

        if ($result) {
            $out = [
                'message' => 'success',
                'result' => $result,
                'code' => 200
            ];
        } else {
            
            //$area2 = 'Mixing and Blowing';
            $cek_pellet = DB::table('erasystem_2012.list_bst_kirim')->select('*')->whereRaw('DARI_DEPT_AREA = ? AND KE_DEPT_AREA = ? AND CEK_KODE_PELLET = ?', [$area, $area2, '1'])->first();

            if($cek_pellet){

                $kode = substr($barcode, 0, 13);
                $pellet = DB::table('erasystem_2012.pellet')->select('NAMA', 'NAMA_LABEL')->where('KODE', $kode)->first();

                $result = [
                    'NAMA_LABEL' => $pellet->NAMA_LABEL,
                    'KODE_PELLET' => $kode,
                    'NAMA_PELLET' => $pellet->NAMA,
                    'KG' => 25
                ];

                $out = [
                    'message' => 'success',
                    'result' => $result,
                    'code' => 200
                ];
                
            } else {
                
                $out = [
                    'message' => 'empty',
                    'result' => [],
                    'code' => 404
                ];

            }

        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checkBarcodeKirim(Request $request) // cek barcode ketika akan kirim || cari barcode berdasarkan data pengirim yg login PT, Gudang, Dept dan Area
    {

        $pt = $request->input('PT_ID');
        $gudang = $request->input('GUDANG');
        $dept = $request->input('DEPT_ID');
        $area = $request->input('DEPT_AREA');
        $area2 = $request->input('KE_DEPT_AREA');
        $barcode = $request->input('BARCODE');
        $newstatus = 'TERIMA';
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $kode = substr($barcode, 0, 13);

        if(strlen($barcode) == 13){

            //$area2 = 'Mixing and Blowing';
            $cek_pellet = DB::table('erasystem_2012.list_bst_kirim')->select('*')->whereRaw('DARI_DEPT_AREA = ? AND KE_DEPT_AREA = ? AND CEK_KODE_PELLET = ?', [$area, $area2, '1'])->first();

            if($cek_pellet){

                
                $pellet = DB::table('erasystem_2012.barcode_pellet')
                ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                    $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                })
                ->whereRaw('barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.KODE_PELLET = ? AND barcode_pellet.AKTIF = ?', [$newpt, $gudang, $dept, $area, $newstatus, $kode, '1'])
                ->selectRaw("COUNT(*) AS SISA, barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET")
                ->first();

                $sisa = DB::table('erasystem_2012.viewstokpellet')->selectRaw('SUM(IF(JENIS=1,QTY,-1*QTY)) AS SISA')->whereRaw('PT_ID = ? AND GUDANG = ? AND KODE_PELLET = ? AND DATE(TANGGAL) <= ?', [$newpt, $gudang, $kode, date('Y-m-d')])->first();

                
                if($pellet->SISA > 0){
                    
                    $result = [
                        'NAMA_LABEL' => $pellet->NAMA_LABEL,
                        'KODE_PELLET' => $kode,
                        'NAMA_PELLET' => $pellet->NAMA_PELLET,
                        'KG' => 25,
                        'SISA' => $sisa->SISA
                    ];

                    $out = [
                        'message' => 'success',
                        'result' => $result,
                        'status' => TRUE,
                        'code' => 200,
                        'isRegistered' => TRUE
                    ];

                } else {

                    $out = [
                        'message' => 'Barcode tidak terdaftar!',
                        'result' => [],
                        'status' => FALSE,
                        'code' => 200,
                        'isRegistered' => FALSE
                    ];

                }
                
            } else {
                
                $out = [
                    'message' => 'Barcode tidak terdaftar!',
                    'result' => [],
                    'status' => FALSE,
                    'code' => 200,
                    'isRegistered' => FALSE
                ];
                
            }

        } else {

            $check1 = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
            ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
            ->first();
    
            if($check1){
                
                $result = DB::table('erasystem_2012.barcode_pellet')
                ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                    $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                })
                ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
                ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
                ->first();

                //Belum diberikan return data ketika scan
    
                if ($result) {

                    //$sisa = DB::table('erasystem_2012.viewstokpellet')->selectRaw('SUM(IF(JENIS=1,QTY,-1*QTY)) AS SISA')->whereRaw('PT_ID = ? AND GUDANG = ? AND KODE_PELLET = ? AND DATE(TANGGAL) <= ?', [$newpt, $gudang, $kode, date('Y-m-d')])->first();

                    $result_data = [
                        'NAMA_LABEL' => $result->NAMA_LABEL,
                        'KODE_PELLET' => $result->KODE_PELLET,
                        'NAMA_PELLET' => $result->NAMA_PELLET,
                        'KG' => $result->KG,
                        'SISA' => 1
                    ];

                    $out = [
                        'message' => 'success',
                        'result' => $result_data,
                        'status' => TRUE,
                        'code' => 200,
                        'isRegistered' => TRUE
                    ];
                } else {
                    $out = [
                        'message' => 'Barcode tidak tersedia untuk area ' . $area . '. Detail status barcode saat ini: ' ,
                        'result' => $check1,
                        'status' => FALSE,
                        'code' => 200,
                        'isRegistered' => TRUE
                    ];
                }
    
            } else {
    
                $out = [
                    'message' => 'Barcode tidak terdaftar!',
                    'result' => [],
                    'status' => FALSE,
                    'code' => 200,
                    'isRegistered' => FALSE
                ];
                
            }
        }


        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checkBarcodeKirimUpdate(Request $request)
    {
        $pt = $request->input('PT_ID');
        $gudang = $request->input('GUDANG');
        $dept = $request->input('DEPT_ID');
        $area = $request->input('DEPT_AREA');
        $area2 = $request->input('KE_DEPT_AREA');
        $barcode = $request->input('BARCODE');
        $total = $request->input('TOTAL_SCAN');

        $newstatus = 'TERIMA';
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $kode = substr($barcode, 0, 13);

        if(strlen($barcode) == 13){

            //$area2 = 'Mixing and Blowing';
            $cek_pellet = DB::table('erasystem_2012.list_bst_kirim')->select('*')->whereRaw('DARI_DEPT_AREA = ? AND KE_DEPT_AREA = ? AND CEK_KODE_PELLET = ?', [$area, $area2, '1'])->first();

            if($cek_pellet){

                
                $pellet = DB::table('erasystem_2012.barcode_pellet')
                ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                    $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                })
                ->whereRaw('barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet.KODE_PELLET = ? AND barcode_pellet.AKTIF = ?', [$newpt, $gudang, $dept, $area, $kode, '1'])
                ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET")
                ->first();
                
                if($pellet){

                    $sisa = DB::table('erasystem_2012.viewstokpellet')->selectRaw('SUM(IF(JENIS=1,QTY,-1*QTY)) AS SISA')->whereRaw('PT_ID = ? AND GUDANG = ? AND KODE_PELLET = ? AND DATE(TANGGAL) <= ?', [$newpt, $gudang, $kode, date('Y-m-d')])->first();

                    $sisa = $sisa->SISA - $total;
                    
                    $result = [
                        'NAMA_LABEL' => $pellet->NAMA_LABEL,
                        'KODE_PELLET' => $kode,
                        'NAMA_PELLET' => $pellet->NAMA_PELLET,
                        'KG' => 25,
                        'SISA' => $sisa
                    ];

                    $out = [
                        'message' => 'success',
                        'result' => $result,
                        'status' => TRUE,
                        'code' => 200,
                        'isRegistered' => TRUE
                    ];

                } else {

                    $out = [
                        'message' => 'Barcode tidak terdaftar!',
                        'result' => [],
                        'status' => FALSE,
                        'code' => 200,
                        'isRegistered' => FALSE
                    ];

                }
                
            } else {
                
                $out = [
                    'message' => 'Barcode tidak terdaftar!',
                    'result' => [],
                    'status' => FALSE,
                    'code' => 200,
                    'isRegistered' => FALSE
                ];
                
            }

        } else {

            $check1 = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
            ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
            ->first();
    
            if($check1){
                
                $result = DB::table('erasystem_2012.barcode_pellet')
                ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                    $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                })
                ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
                ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
                ->first();

                //Belum diberikan return data ketika scan
    
                if ($result) {

                    //$sisa = DB::table('erasystem_2012.viewstokpellet')->selectRaw('SUM(IF(JENIS=1,QTY,-1*QTY)) AS SISA')->whereRaw('PT_ID = ? AND GUDANG = ? AND KODE_PELLET = ? AND DATE(TANGGAL) <= ?', [$newpt, $gudang, $kode, date('Y-m-d')])->first();

                    //$sisa = $result->SISA - $total;

                    $result_data = [
                        'NAMA_LABEL' => $result->NAMA_LABEL,
                        'KODE_PELLET' => $result->KODE_PELLET,
                        'NAMA_PELLET' => $result->NAMA_PELLET,
                        'KG' => $result->KG,
                        'SISA' => 1
                    ];

                    $out = [
                        'message' => 'success',
                        'result' => $result_data,
                        'status' => TRUE,
                        'code' => 200,
                        'isRegistered' => TRUE
                    ];
                } else {
                    $out = [
                        'message' => 'Barcode tidak tersedia untuk area ' . $area . '. Detail status barcode saat ini: ' ,
                        'result' => $check1,
                        'status' => FALSE,
                        'code' => 200,
                        'isRegistered' => TRUE
                    ];
                }
    
            } else {
    
                $out = [
                    'message' => 'Barcode tidak terdaftar!',
                    'result' => [],
                    'status' => FALSE,
                    'code' => 200,
                    'isRegistered' => FALSE
                ];
                
            }
        }


        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checkBarcodeTerima(Request $request)
    {
        $barcode = $request->input('BARCODE');
        $notrans = $request->input('NOTRANS'); //Tambah satu parameter
        $kode = substr($barcode, 0, 13);

        $check1 = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
        ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
        ->first();

        if(strlen($barcode) == 13){

            $bst = DB::table('erasystem_2012.bst_pellet')->whereRaw('NO_BST = ?', [$notrans])->selectRaw('DARI_DEPT_AREA, KE_DEPT_AREA')->first();

            $cek_pellet = DB::table('erasystem_2012.list_bst_kirim')->select('*')->whereRaw('DARI_DEPT_AREA = ? AND KE_DEPT_AREA = ? AND CEK_KODE_PELLET = ?', [$bst->DARI_DEPT_AREA, $bst->KE_DEPT_AREA, '1'])->first();

            if($cek_pellet){
                
                $list = DB::table('erasystem_2012.bst_pellet_item')->whereRaw('NO_BST = ? AND KODE_PELLET = ?', [$notrans, $kode])->selectRaw('QTY AS JUMLAH_ITEM')->first();
                
                if($list){
                    
                    $pellet = DB::table('erasystem_2012.barcode_pellet')
                    ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                        $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                    })
                    ->whereRaw('barcode_pellet_det.NOTRANS = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.KODE_PELLET = ? AND barcode_pellet.AKTIF = ?', [$notrans, 'KIRIM', $kode, '1'])
                    ->selectRaw("COUNT(*) AS JUMLAH_BARCODE")
                    ->first();
    
                    $sisa = $list->JUMLAH_ITEM - $pellet->JUMLAH_BARCODE;
    
                    $result = [
                        'SISA' => $sisa
                    ];
    
                    $out = [
                        'message' => 'success',
                        'result' => $result,
                        'status' => TRUE,
                        'isRegistered' => TRUE,
                        'isScan' => TRUE,
                        'code' => 200
                    ];

                } else {

                    $out = [
                        'message' => 'Barcode tidak terdaftar!',
                        'result' => [],
                        'status' => FALSE,
                        'isRegistered' => FALSE,
                        'isScan' => FALSE,
                        'code' => 200
                    ];

                }

                
            } else {
                
                $out = [
                    'message' => 'Barcode tidak terdaftar!',
                    'result' => [],
                    'status' => FALSE,
                    'isRegistered' => FALSE,
                    'isScan' => FALSE,
                    'code' => 200
                ];
                
            }

        } else {

            if($check1){
                $out = [
                    'message' => 'Barcode tidak tersedia untuk BST nomor ' . $notrans . '. Detail status barcode saat ini: ',
                    'result' => $check1,
                    'status' => FALSE,
                    'isRegistered' => TRUE,
                    'isScan' => FALSE,
                    'code' => 200
                ];
            } else {
                $out = [
                    'message' => 'Barcode tidak terdaftar!',
                    'result' => [],
                    'status' => FALSE,
                    'isRegistered' => FALSE,
                    'isScan' => FALSE,
                    'code' => 200
                ];
            }
            
        }


        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);

    }


    public function checkbarcodeDraf(Request $request) // Kirim Update
    {
        $bst = $request->input('NO_BST');
        $barcode = $request->input('BARCODE');
        $newstatus = 'KIRIM';

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $bst, $newstatus, '1'])
            ->selectRaw("barcode_pellet_det.BARCODE, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
            ->first();

        if ($result) { 
            $out = [
                'message' => 'success',
                'result' => $result,
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'empty',
                'result' => $result,
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request) // Simpan / Tambah BST
    { // Ada 2 tambahkan PT dan Gudang yg kirim untuk set variabel
        $records = $request->all();

        $this->validate($request, [
            //'*' => 'required|array',
            '*.USERNAME' => 'required',
            '*.PT_ID' => 'required',
            '*.PT_NAMA' => 'required',
            '*.GUDANG' => 'required',
            '*.DARI_DEPT_ID' => 'required',
            '*.DARI_DEPT_NAMA' => 'required',
            '*.KE_DEPT_ID' => 'required',
            '*.KE_DEPT_NAMA' => 'required',
            '*.DARI_DEPT_AREA' => 'required',
            '*.KE_DEPT_AREA' => 'required',
            '*.STATUS' => 'required',
            '*.DEVICE_LOGIN' => 'required'
        ]);

        // handle data bst
        $username = $records[0]['USERNAME'];
        $pt = $records[0]['PT_ID'];
        $pt_nama = $records[0]['PT_NAMA'];
        $gudang = $records[0]['GUDANG'];
        $dari_dept_id =$records[0]['DARI_DEPT_ID'];
        $ke_dept_id = $records[0]['KE_DEPT_ID'];
        $dari_dept_nama = $records[0]['DARI_DEPT_NAMA'];
        $ke_dept_nama = $records[0]['KE_DEPT_NAMA'];
        $dari_dept_area = $records[0]['DARI_DEPT_AREA'];
        $ke_dept_area = $records[0]['KE_DEPT_AREA'];
        $status = $records[0]['STATUS'];
        $no_wo = $records[0]['NO_WO'];

        $newstatus = 'TERIMA';
        //$barcode = $records[0]['USERNAME'];
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $ket = null;

        $datetime = date('Y-m-d H:i:s');
        $date = date('Y-m-d');

        //cari nilai max bst_pellet
        $bst = Bst::selectRaw('CAST(MAX(RIGHT(erasystem_2012.bst_pellet.NO_BST,2)) AS SIGNED) AS LAST_NO')->whereRaw('bst_pellet.PT_ID = ? AND bst_pellet.DARI_DEPT_ID = ? AND bst_pellet.TANGGAL = ?', [$newpt, $dari_dept_id, $date])->first();

        //inisialisasi tgl , bulan, tahun
        $n_tahun = date('y', strtotime($date));
        $n_bulan = date('m', strtotime($date));
        $n_tanggal = date('d', strtotime($date));
        
        if($bst){ // Jika bst ada
            $no = $bst->LAST_NO;
            $no++;
            $NoTrans = $pt . '/' . $dari_dept_id . '/BST/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  sprintf("%02s", $no);
        } else { // Jika null
            $NoTrans = $pt . '/' . $dari_dept_id . '/BST/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  '01';
        }


        //handle request barcode
        $barcodes = array_column($records, 'BARCODE');
        $kode_pellet = array_column($records, 'KODE_PELLET');
        $total = array_count_values($kode_pellet);
        $list_bst = [];

        $total_item = [];
        foreach($records as $a){ // Digunakan untuk mengakumulasi KG

            $kg = $a['KG'];
            $kd_pellet = $a['KODE_PELLET'];

            if(array_key_exists($kd_pellet, $total_item)){
                $total_item[$kd_pellet] = $total_item[$kd_pellet] + $kg;
            } else {
                $total_item[$kd_pellet] = $kg;
            }

        }
        
        foreach ($records as $rec) {
            //Jika barcode kosong apakah perlu diupdate
            if(!in_array($rec['KODE_PELLET'], array_column($list_bst, 'KODE_PELLET'))){
                $list_bst[] = [
                    'NO_BST' => $NoTrans, // No BST
                    'KODE_PELLET' => $rec['KODE_PELLET'],
                    'NAMA_PELLET' => $rec['NAMA_PELLET'],
                    'NAMA_LABEL' => $rec['NAMA_LABEL'],
                    'KG' => $total_item[$rec['KODE_PELLET']], //Tambahkan KG jika field sudah diupdate
                    'QTY' => $total[$rec['KODE_PELLET']],
                    'SATUAN' => 'SAK',
                    'KETERANGAN' => null
                ];
            } 
        }

        $this->_setVariable('BST', $newpt, $pt_nama, $gudang, $dari_dept_id, $dari_dept_nama, $dari_dept_area, $NoTrans, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        $data_bst = [
            'NO_BST' => $NoTrans,
            'PT_ID' => $newpt,
            'PT_NAMA' => $pt_nama,
            'GUDANG' => $gudang,
            'DARI_DEPT_ID' => $dari_dept_id,
            'DARI_DEPT_NAMA' => $dari_dept_nama,
            'KE_DEPT_ID' => $ke_dept_id,
            'KE_DEPT_NAMA' => $ke_dept_nama,
            'DARI_DEPT_AREA' => $dari_dept_area,
            'KE_DEPT_AREA' => $ke_dept_area,
            'KETERANGAN' => $ket,
            'STATUS' => 0,
            'USERNAME' => $username,
            'LAST_UPDATE' => date('Y-m-d H:i:s'),
            'TANGGAL' => date('Y-m-d'),
            'TANGGAL_BUAT' => date('Y-m-d H:i:s'),
            'TOTAL' => count($barcodes),
            'NO_WO' => $no_wo
        ];

        DB::beginTransaction();

        try {

            DB::statement(DB::raw("SET @USER_LOGIN='" . $username . "', @DEVICE_LOGIN='" . $records[0]['DEVICE_LOGIN'] . "'"));
            $insert = Bst::create($data_bst);
            DB::table('erasystem_2012.bst_pellet_item')->insert($list_bst);
            DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $barcodes)->update(['LAST_UPDATE' => $datetime]);

            $out = [
                'message' => 'Submit sukses',
                'code' => 201
            ];
            DB::commit();

        } catch (QueryException $e) {

            $out = [
                'message' => 'Submit gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'code' => 500
            ];
            DB::rollBack();

        }

        return response()->json($out, $out['code']);

    }

    public function insertBst(Request $request)
    {
        $records = $request->all();

        $this->validate($request, [
            //'*' => 'required|array',
            '*.USERNAME' => 'required',
            '*.PT_ID' => 'required',
            '*.PT_NAMA' => 'required',
            '*.GUDANG' => 'required',
            '*.DARI_DEPT_ID' => 'required',
            '*.DARI_DEPT_NAMA' => 'required',
            '*.KE_DEPT_ID' => 'required',
            '*.KE_DEPT_NAMA' => 'required',
            '*.DARI_DEPT_AREA' => 'required',
            '*.KE_DEPT_AREA' => 'required',
            '*.STATUS' => 'required',
            '*.DEVICE_LOGIN' => 'required'
        ]);

        // handle data bst
        $username = $records[0]['USERNAME'];
        $pt = $records[0]['PT_ID'];
        $pt_nama = $records[0]['PT_NAMA'];
        $gudang = $records[0]['GUDANG'];
        $dari_dept_id =$records[0]['DARI_DEPT_ID'];
        $ke_dept_id = $records[0]['KE_DEPT_ID'];
        $dari_dept_nama = $records[0]['DARI_DEPT_NAMA'];
        $ke_dept_nama = $records[0]['KE_DEPT_NAMA'];
        $dari_dept_area = $records[0]['DARI_DEPT_AREA'];
        $ke_dept_area = $records[0]['KE_DEPT_AREA'];
        $status = $records[0]['STATUS'];
        $no_wo = $records[0]['NO_WO'];

        $newstatus = 'TERIMA';
        //$offline = $records[0]['NAMA_PELLET'] == null || $records[0]['NAMA_PELLET'] == '' ? true : false;
        //$barcode = $records[0]['USERNAME'];
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $ket = null;

        $datetime = date('Y-m-d H:i:s');
        $date = date('Y-m-d');

        //cari nilai max bst_pellet
        $bst = Bst::selectRaw('CAST(MAX(RIGHT(erasystem_2012.bst_pellet.NO_BST,2)) AS SIGNED) AS LAST_NO')->whereRaw('bst_pellet.PT_ID = ? AND bst_pellet.DARI_DEPT_ID = ? AND bst_pellet.TANGGAL = ?', [$newpt, $dari_dept_id, $date])->first();

        //inisialisasi tgl , bulan, tahun
        $n_tahun = date('y', strtotime($date));
        $n_bulan = date('m', strtotime($date));
        $n_tanggal = date('d', strtotime($date));
        
        if($bst){ // Jika bst ada
            $no = $bst->LAST_NO;
            $no++;
            $NoTrans = $pt . '/' . $dari_dept_id . '/BST/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  sprintf("%02s", $no);
        } else { // Jika null
            $NoTrans = $pt . '/' . $dari_dept_id . '/BST/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  '01';
        }


        //handle request barcode
        $barcodes = array_column($records, 'BARCODE');
        $kode_pellet = array_column($records, 'KODE_PELLET');
        
        $total = array_count_values($kode_pellet);
        $list_bst = [];

        $total_item = [];
        foreach($records as $a){ // Digunakan untuk mengakumulasi KG

            $pellet = DB::table('erasystem_2012.barcode_pellet')
                ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                    $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                })
                ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$a['BARCODE'], $newpt, $gudang, $dari_dept_id, $dari_dept_area, $newstatus, '1'])
                ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
                ->first();

            $kg = $pellet->KG;
            $kd_pellet = $a['KODE_PELLET'];

            if(array_key_exists($kd_pellet, $total_item)){
                $total_item[$kd_pellet] = $total_item[$kd_pellet] + $kg;
            } else {
                $total_item[$kd_pellet] = $kg;
            }

            if(!in_array($kd_pellet, array_column($list_bst, 'KODE_PELLET'))){
                $list_bst[] = [
                    'NO_BST' => $NoTrans, // No BST
                    'KODE_PELLET' => $kd_pellet,
                    'NAMA_PELLET' => $pellet->NAMA_PELLET,
                    'NAMA_LABEL' => $pellet->NAMA_LABEL,
                    'QTY' => $total[$kd_pellet],
                    'SATUAN' => 'SAK',
                    'KETERANGAN' => null
                ];
            } 

        }

        //Insert total ke list bst pellet
        $i = 0;
        foreach($list_bst as $rec){
            $list_bst[$i]['KG'] = $total_item[$rec['KODE_PELLET']];
            $i++;
        }
        
        $this->_setVariable('BST', $newpt, $pt_nama, $gudang, $dari_dept_id, $dari_dept_nama, $dari_dept_area, $NoTrans, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        $data_bst = [
            'NO_BST' => $NoTrans,
            'PT_ID' => $newpt,
            'PT_NAMA' => $pt_nama,
            'GUDANG' => $gudang,
            'DARI_DEPT_ID' => $dari_dept_id,
            'DARI_DEPT_NAMA' => $dari_dept_nama,
            'KE_DEPT_ID' => $ke_dept_id,
            'KE_DEPT_NAMA' => $ke_dept_nama,
            'DARI_DEPT_AREA' => $dari_dept_area,
            'KE_DEPT_AREA' => $ke_dept_area,
            'KETERANGAN' => $ket,
            'STATUS' => 0,
            'USERNAME' => $username,
            'LAST_UPDATE' => date('Y-m-d H:i:s'),
            'TANGGAL' => date('Y-m-d'),
            'TANGGAL_BUAT' => date('Y-m-d H:i:s'),
            'TOTAL' => count($barcodes),
            'NO_WO' => $no_wo
        ];

        DB::beginTransaction();

        try {

            DB::statement(DB::raw("SET @USER_LOGIN='" . $username . "', @DEVICE_LOGIN='" . $records[0]['DEVICE_LOGIN'] . "'"));
            $insert = Bst::create($data_bst);
            DB::table('erasystem_2012.bst_pellet_item')->insert($list_bst);
            DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $barcodes)->update(['LAST_UPDATE' => $datetime]);

            $out = [
                'message' => 'Submit sukses',
                'code' => 201
            ];
            DB::commit();

        } catch (QueryException $e) {

            $out = [
                'message' => 'Submit gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'code' => 500
            ];
            DB::rollBack();

        }

        return response()->json($out, $out['code']);
    }

    public function getdrafBst($pt, $gudang, $dept, $area) // ambil pt dan departemen si user login 
    {
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $gudang = urldecode($gudang);
        $area = urldecode($area);

        $data = Bst::whereRaw('STATUS = ? AND PT_ID = ? AND GUDANG = ? AND DARI_DEPT_ID = ? AND DARI_DEPT_AREA = ?', ['0', $newpt, $gudang, $dept, $area])->get();

        if($data->count()){
            $out = [
                'message' => 'success',
                'result' => $data,
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'empty',
                'result' => [],
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function update(Request $request)
    {
        $records = $request->all();

        $this->validate($request, [
            //'*' => 'required|array',
            '*.USERNAME' => 'required',
            '*.PT_ID' => 'required',
            '*.PT_NAMA' => 'required',
            '*.GUDANG' => 'required',
            '*.STATUS' => 'required',
            '*.NO_BST' => 'required',
            '*.DEVICE_LOGIN' => 'required'
            //'*.STATUS_BARCODE' => 'required'
            //Butuh satu field status_barcode = "hapus" / "tambah"
        ]);

        // handle data bst
        $NoTrans = $records[0]['NO_BST'];
        $username = $records[0]['USERNAME'];
        $pt = $records[0]['PT_ID'];
        $pt_nama = $records[0]['PT_NAMA'];
        $gudang = $records[0]['GUDANG'];
        
        $bst = Bst::select('DARI_DEPT_ID', 'DARI_DEPT_NAMA', 'DARI_DEPT_AREA')->where('NO_BST', $NoTrans)->first();
        $dari_dept_id = $bst->DARI_DEPT_ID;
        $dari_dept_nama = $bst->DARI_DEPT_NAMA;
        $dari_dept_area = $bst->DARI_DEPT_AREA;
        $status = 'KIRIM';
        //$newstatus = 'TERIMA';
        //$barcode = $records[0]['USERNAME'];
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $ket = null;

        $datetime = date('Y-m-d H:i:s');
        $date = date('Y-m-d');

        //Munculkan list barcode dalam bentuk array
        $q_old = DB::table('erasystem_2012.barcode_pellet_det')
        ->join('erasystem_2012.barcode_pellet', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.NOTRANS = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$NoTrans, 'KIRIM', '1'])
        ->select('barcode_pellet_det.BARCODE')->get();
        $arr_q_old = $q_old->toArray();
        $old_barcode = array_column($arr_q_old, 'BARCODE');

        //handle request barcode
        $barcodes = array_column($records, 'BARCODE'); // Barcode yg ditambahkan
        $kode_pellet = array_column($records, 'KODE_PELLET'); // Kode pellet 13 digit
        $add_barcode = [];
        $del_barcodes = []; // Barcode yg dihapus
        $count_barcode =  [];
        $list_bst = []; //List barcode yg akan dimasukkan kedalam bst_item
        
        $a_total = [];
        $total_item = [];
    
        foreach($records as $rec){

            $kg = $rec['KG'];
            $kd_pellet = $rec['KODE_PELLET'];

            if($rec['STATUS_BARCODE'] == 1){

                array_push($a_total, $kd_pellet);

                if(array_key_exists($kd_pellet, $total_item)){
                    $total_item[$kd_pellet] = $total_item[$kd_pellet] + $kg;
                } else {
                    $total_item[$kd_pellet] = $kg;
                }

            }
        }

        $total = array_count_values($a_total);
        //Untuk membedakan mana yg dihapus dan ditambah bisa disini
        foreach ($records as $rec) {

            if($rec['STATUS_BARCODE'] == 1){

                array_push($count_barcode, $rec['BARCODE']);

                if(!in_array($rec['BARCODE'], $old_barcode)){
                    array_push($add_barcode, $rec['BARCODE']);
                }

                if(!in_array($rec['KODE_PELLET'], array_column($list_bst, 'KODE_PELLET'))){
                    $list_bst[] = [
                        'NO_BST' => $NoTrans, // No BST
                        'KODE_PELLET' => $rec['KODE_PELLET'],
                        'NAMA_PELLET' => $rec['NAMA_PELLET'],
                        'NAMA_LABEL' => $rec['NAMA_LABEL'],
                        'KG' => $total_item[$rec['KODE_PELLET']], //Tambahkan KG jika field sudah diupdate
                        'QTY' => $total[$rec['KODE_PELLET']],
                        'SATUAN' => 'SAK',
                        'KETERANGAN' => null
                    ];
                } 

            } else {
                array_push($del_barcodes, $rec['BARCODE']); // Masukkan barcode yg dihapus ke dalam array del_barcodes
            }
            
        }

        $this->_setVariable('BST', $newpt, $pt_nama, $gudang, $dari_dept_id, $dari_dept_nama, $dari_dept_area, $NoTrans, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        $data_bst = [
            'NO_BST' => $NoTrans,
            //'USERNAME' => $username,
            'LAST_UPDATE' => date('Y-m-d H:i:s'),
            'TOTAL' => count($count_barcode)
        ];

        DB::beginTransaction();

        try {

            DB::statement(DB::raw("SET @USER_LOGIN='" . $username . "', @DEVICE_LOGIN='" . $records[0]['DEVICE_LOGIN'] . "'"));
            $update = Bst::where('NO_BST', $NoTrans)->update($data_bst);
            //DB::table('erasyste_2012.bst_pellet_item')->delete
            DB::table('erasystem_2012.bst_pellet_item')->where('NO_BST', $NoTrans)->delete();
            DB::table('erasystem_2012.bst_pellet_item')->insert($list_bst); // Delete list bst dan masukkan kembali

            DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $add_barcode)->update(['LAST_UPDATE' => $datetime]);
            
            DB::statement(DB::raw("SET @AKSI='HAPUS'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $del_barcodes)->update(['LAST_UPDATE' => $datetime]);

            $out = [
                'message' => 'Submit sukses',
                'code' => 200
            ];
            DB::commit();

        } catch ( QueryException $e) {

            $out = [
                'message' => 'Submit gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'code' => 500
            ];
            DB::rollBack();

        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function delete($notrans, $userid, $userdevice)
    {
        $resbarcode = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', ['KIRIM', $notrans, '1'])
        ->selectRaw('barcode_pellet_det.BARCODE')
        ->get();

        $barcode = $resbarcode->toArray();
        $listbarcode = array_column($barcode, 'BARCODE');
        $datetime = date('Y-m-d H:i:s');
        
        DB::beginTransaction();

        try {

            DB::statement(DB::raw("SET @USER_LOGIN='" . $userid . "', @DEVICE_LOGIN='" . $userdevice . "'"));
            $delete = Bst::where('NO_BST', $notrans)->delete();
            DB::statement(DB::raw("SET @AKSI = 'HAPUS'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $listbarcode)->update(['LAST_UPDATE' => $datetime]);

            $out = [
                'message' => 'Hapus sukses',
                'code' => 200
            ];
            DB::commit();

        } catch (QueryException $e){

            $out = [
                'message' => 'Hapus gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'code' => 500
            ];
            DB::rollBack();

        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checkFinalize($notrans)
    {
        $bstdraf = Bst::whereRaw('NO_BST = ? AND STATUS = ?', [$notrans, '0'])->first();

        if($bstdraf){
            $out = [
                'message' => 'success',
                'result' => $bstdraf
            ];  
            $code = 200;
        } else {
            $out = [
                'message' => 'BST sudah di-finalize, kembali ke menu utama untuk refresh!',
                'result' => []
            ];
            $code = 404;
        }

        return response()->json($out, $code, [], JSON_NUMERIC_CHECK);

    }

    public function getlistBarcode($pt, $gudang, $dep, $status, $notrans)
    {
        //$gudang = urldecode($gudang);
        $newstatus = $status == 'TERIMA' ? 'KIRIM' : 'KIRIM';
        //$newpt = $pt == '1' ? 'ERA' : 'ERI'; 

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', [$newstatus, $notrans, '1'])
            ->selectRaw('barcode_pellet_det.BARCODE, barcode_pellet.KODE_PELLET, barcode_pellet.NAMA_PELLET, barcode_pellet.NAMA_LABEL, barcode_pellet.KG')
            ->get();

        $arr_barcode = $result->toArray(); 
        $list_pellet_barcode = array_column($arr_barcode, 'KODE_PELLET');

        $item = DB::table('erasystem_2012.bst_pellet_item')->whereRaw('NO_BST = ?', [$notrans])->get()->toArray();
        
        $items = false;
        foreach($item as $row){

            $kode = $row->KODE_PELLET;
            if(!in_array($kode, $list_pellet_barcode)){
                $items[] = [
                    'BARCODE' => $kode . '_',
                    'KODE_PELLET' => $kode,
                    'NAMA_PELLET' => $row->NAMA_PELLET,
                    'NAMA_LABEL' => $row->NAMA_LABEL,
                    'KG' => 25
                ];
            }

        }

        if($items){
            $result = array_merge($arr_barcode, $items);
        }

        $out = [
            'message' => 'success',
            'result' => $result
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }


    
}
