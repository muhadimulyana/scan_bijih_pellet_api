<?php

namespace App\Http\Controllers;

use App\Bst;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

date_default_timezone_set("Asia/Jakarta");

class BstController extends Controller
{

    public function bstKirim($pt, $gudang, $dept)
    {
        //$data = Bst::where('STATUS', '1')->where('(SELECT(LEFT(NO_BST, 1))', '=', '1')->get();
 	$gudang = urldecode($gudang);

        $data = Bst::whereRaw('STATUS = ? AND LEFT(NO_BST, 1) = ? AND GUDANG = ? AND KE_DEPT_ID = ?', ['1', $pt, $gudang, $dept])->get();

        $out = [
            'message' => 'success',
            'result' => $data,
        ];

        return response()->json($out, 200);
    }

    public function getdrafBst($pt, $gudang, $dept) // ambil pt dan departemen si user login 
    {
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $gudang = urldecode($gudang);

        $data = Bst::whereRaw('STATUS = ? AND PT_ID = ? AND GUDANG = ? AND DARI_DEPT_ID = ?', ['0', $newpt, $gudang, $dept])->get();

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

        return response()->json($out, $out['code']);
    }

    public function getbstUser($pt, $dept) // Untuk user biasa (Menampilkan BST untuk di terima)
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

        return response()->json($out, $out['code']);
    }


    public function gettotalBarcode($notrans)
    {
        $barcode = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ?', ['KIRIM', $notrans])
        ->selectRaw('barcode_pellet_det.NOTRANS, count(*) as TOTAL')
        ->first();
        

        $out = [
            'message' => 'success',
            'total' => $barcode->TOTAL,
            'notrans' => $barcode->NOTRANS
        ];

        return response()->json($out, 200);
    }

    public function checklistBst($notrans)
    {
        $result = DB::table('erasystem_2012.bst_pellet')
            ->join('erasystem_2012.list_bst_kirim', function ($join) {
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

        return response()->json($out, 200);
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
                'message' => 'Area yg dipilih tidak tersedia',
                'code' => 404
            ];
        }

        return response()->json($out, $out['code']);
    }
    

    public function _setVariable($jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $deptArea, $notrans, $username, $status, $ket)
    {
        DB::statement(DB::raw("SET @JENIS_DOK='" . $jenisDok . "', @PT_ID='" . $ptId . "', @PT_NAMA='" . $ptNama . "', @GUDANG='" . $gudang . "', @DEPT_ID='" . $deptId . "', @DEPT_NAMA='" . $deptNama . "', @AREA='" . $deptArea ."', @NOTRANS='" . $notrans . "', @UserLoginAktif='" . $username . "', @STATUS_='" . $status . "', @KETERANGAN='" . $ket . "'"));
        //$this->var = [$jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $deptArea, $notrans, $username, $status, $ket];
    }


    public function store(Request $request)
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
            '*.STATUS' => 'required'
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
        $barcodes = [];
        $list_kode = [];
        $kode_pellet = [];

        foreach ($records as $rec) {

            array_push($barcodes, $rec['BARCODE']); // Masukkan barcode awal ke dalam array barcodes
            array_push($kode_pellet, substr($rec['BARCODE'], 0, 13)); // Masukkan kode pellet berdasarkan kode barcode 13digi
            
        }

        $total = array_count_values($kode_pellet); // Total kode pellet yg sudah dimasukkan
        $list_bst = [];
        foreach($barcodes as $key => $value){

            $pellet = DB::table('erasystem_2012.barcode_pellet')->select('NAMA_PELLET', 'NAMA_LABEL', 'KG')->where('BARCODE', $barcodes[$key])->first(); // cari detail nama, dan nama label berdasarkan kode pellet
            $kd_pellet = substr($barcodes[$key], 0, 13);

            if(!in_array($kd_pellet, array_column($list_bst, 'KODE_PELLET'))) // Jika kode pellet belum ada masukkan kedalam list kode untuk barcode list pellet
            {
                $list_bst[] = [
                    'NO_BST' => $NoTrans, // No BST
                    'KODE_PELLET' => $kd_pellet,
                    'NAMA_PELLET' => $pellet->NAMA_PELLET,
                    'NAMA_LABEL' => $pellet->NAMA_LABEL,
                    'KG' => $pellet->KG, //Tambahkan KG jika field sudah diupdate
                    'QTY' => $total[$kd_pellet],
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
            'TOTAL' => count($barcodes)
        ];


        DB::beginTransaction();

        $insert = Bst::create($data_bst);
        DB::table('erasystem_2012.bst_pellet_item')->insert($list_bst);
        DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
        DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $barcodes)->update(['LAST_UPDATE' => $datetime]);
        //$insert = true;

        if($insert){
            $out = [
                'message' => 'success',
                'code' => 201
            ];
            DB::commit();

        } else {
            $out = [
                'message' => 'failed',
                'code' => 404
            ];
            DB::rollBack();

        }

        return response()->json($out, $out['code']);

    }


    public function terimaBst(Request $request)
    {
        if($request->method('post')) {

            //Status terima minimal
            $this->validate($request, [
                'USERNAME' => 'required',
                'PT_ID' => 'required',
                'PT_NAMA' => 'required',
                //'GUDANG' => 'required',
                //'DARI_DEPT_ID' => 'required',
                //'DARI_DEPT_NAMA' => 'required',
                //'DARI_DEPT_AREA' => 'required',
                'STATUS' => 'required',
                'NOTRANS' => 'required'
            ]);

            $status = $request->input('STATUS');
            $username = $request->input('USERNAME');
            $noBst = $request->input('NOTRANS');
            $ptId = $request->input('PT_ID');
            $ptNama = $request->input('PT_NAMA');
            //$gudang = $request->input('GUDANG');
            //$area = $request->input('AREA');
            //Username Login
            $bst = Bst::select('GUDANG', 'KE_DEPT_ID', 'KE_DEPT_NAMA', 'KE_DEPT_AREA')->where('NO_BST', $noBst)->first();
            $dariDeptId = $bst->KE_DEPT_ID;
            $dariDeptNama = $bst->KE_DEPT_NAMA;
            $dariDeptArea = $bst->KE_DEPT_AREA;
            $gudang = $bst->GUDANG;

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
            $update = Bst::where('NO_BST', $noBst)->update($data);
            DB::statement(DB::raw("SET @AKSI = 'TAMBAH'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $listbarcode)->update(['LAST_UPDATE' => $datetime]);
            // foreach($barcode as $row){
            //     DB::statement(DB::raw("SET @AKSI = 'TAMBAH'"));
            //     DB::table('erasystem_2012.barcode_pellet')
            //         ->where('BARCODE',  $row->BARCODE)
            //         ->update(['LAST_UPDATE' => $datetime]);
            // }
                
            //});

            //$update = DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $listbarcode)->update(['LAST_UPDATE' => $datetime]);
            //End transaction

            if ($update) {
                $out = [
                    'message' => 'success',
                    'result' => $data
                ];
                $code = 201; DB::commit();
            } else {
                $out = [
                    'message' => 'failed',
                    'result' => $data
                ];
                $code = 404; DB::rollBack();
            }

            return response()->json($out, $code);

        }
    }

    public function checkBarcode(Request $request) // cek barcode ketika akan kirim || cari barcode berdasarkan data pengirim yg login PT, Gudang, Dept dan Area
    {

        $pt = $request->input('PT_ID');
        $gudang = $request->input('GUDANG');
        $dept = $request->input('DEPT_ID');
        $area = $request->input('DEPT_AREA');
        $barcode = $request->input('BARCODE');
        $newstatus = 'TERIMA';
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
            ->selectRaw("barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG, barcode_pellet.NAMA_LABEL")
            ->first();
            //Belum diberikan return data ketika scan

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

        return response()->json($out, $out['code']);
    }

    public function checkbarcodeDraf(Request $request)
    {
        $bst = $request->input('NO_BST');
        $barcode = $request->input('BARCODE');
        $newstatus = 'KIRIM';

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $bst, $newstatus, '1'])
            ->selectRaw("barcode_pellet_det.*")
            ->first();

        if ($result) {
            $out = [
                'message' => 'success',
                'data' => $barcode,
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'empty',
                'data' => $barcode,
                'code' => 404
            ];
        }

        return response()->json($out, $out['code']);
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
        //$ke_dept_id = $bst->KE_DEPT_ID;
        $dari_dept_nama = $bst->DARI_DEPT_NAMA;
        //$ke_dept_nama = $bst->KE_DEPT_NAMA;
        $dari_dept_area = $bst->DARI_DEPT_AREA;
        //$ke_dept_area = $bst->KE_DEPT_AREA;
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
        $barcodes = []; // Barcode yg ditambahkan
        $barcodez = [];
        $del_barcodes = []; // Barcode yg dihapus
        $list_kode = []; //List barcode yg akan dimasukkan kedalam bst_item
        $kode_pellet = []; // Kode pellet 13 digit


        //Untuk membedakan mana yg dihapus dan ditambah bisa disini
        foreach ($records as $rec) {

            if($rec['STATUS_BARCODE'] == 1){
                //array_push($barcodes, $rec['BARCODE']); // Masukkan barcode awal ke dalam array barcodes
                array_push($kode_pellet, substr($rec['BARCODE'], 0, 13)); // Masukkan kode pellet berdasarkan kode barcode 13digit - ini untuk jumlah di BST
                array_push($barcodez, $rec['BARCODE']);
                if(!in_array($rec['BARCODE'], $old_barcode)){
                    array_push($barcodes, $rec['BARCODE']); // Masukkan barcode awal ke dalam array barcodes untuk diupdate
                }

            } else {
                array_push($del_barcodes, $rec['BARCODE']); // Masukkan barcode yg dihapus ke dalam array del_barcodes
            }
            
        }

        $total = array_count_values($kode_pellet); // Total kode pellet yg sudah dimasukkan
        $list_bst = [];
        foreach($barcodez as $key => $value){

            $pellet = DB::table('erasystem_2012.barcode_pellet')->select('NAMA_PELLET', 'NAMA_LABEL', 'KG')->where('BARCODE', $barcodez[$key])->first(); // cari detail nama, dan nama label berdasarkan kode pellet
            $kd_pellet = substr($barcodez[$key], 0, 13);

            if(!in_array($kd_pellet, array_column($list_bst, 'KODE_PELLET'))) // Jika kode pellet belum ada masukkan kedalam list kode untuk barcode list pellet
            {
                $list_bst[] = [
                    'NO_BST' => $NoTrans, // No BST
                    'KODE_PELLET' => $kd_pellet,
                    'NAMA_PELLET' => $pellet->NAMA_PELLET,
                    'NAMA_LABEL' => $pellet->NAMA_LABEL,
                    'KG' => $pellet->KG, //Tambahkan KG jika field sudah diupdate
                    'QTY' => $total[$kd_pellet],
                    'SATUAN' => 'SAK',
                    'KETERANGAN' => null
                ];
            }
        }

        $this->_setVariable('BST', $newpt, $pt_nama, $gudang, $dari_dept_id, $dari_dept_nama, $dari_dept_area, $NoTrans, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        $data_bst = [
            'NO_BST' => $NoTrans,
            //'USERNAME' => $username,
            'LAST_UPDATE' => date('Y-m-d H:i:s'),
            'TOTAL' => count($kode_pellet)
        ];

        DB::beginTransaction();

        $update = Bst::where('NO_BST', $NoTrans)->update($data_bst);
        //DB::table('erasyste_2012.bst_pellet_item')->delete
        DB::table('erasystem_2012.bst_pellet_item')->where('NO_BST', $NoTrans)->delete();
        DB::table('erasystem_2012.bst_pellet_item')->insert($list_bst); // Delete list bst dan masukkan kembali


        DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
        DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $barcodes)->update(['LAST_UPDATE' => $datetime]);
        
        DB::statement(DB::raw("SET @AKSI='HAPUS'"));
        DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $del_barcodes)->update(['LAST_UPDATE' => $datetime]);
        //$insert = true;

        if($update){
            $out = [
                'message' => 'success',
                'code' => 200
            ];
            DB::commit();

        } else {
            $out = [
                'message' => 'failed',
                'code' => 404
            ];
            DB::rollBack();

        }

        return response()->json($out, $out['code']);
    }

    
}

