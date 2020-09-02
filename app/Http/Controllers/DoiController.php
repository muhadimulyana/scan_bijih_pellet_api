<?php

namespace App\Http\Controllers;

use App\Doi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoiController extends Controller
{
    public function test()
    {
        $pt = '2';
        $newpt = $ptId == '1' ? 'ERA' : ( $ptId == '2' ? 'ERI' : 'EPI'); 
        return $newpt;
    }

    public function _setVariable($jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $deptArea, $notrans, $username, $status, $ket)
    {
        DB::statement(DB::raw("SET @JENIS_DOK='" . $jenisDok . "', @PT_ID='" . $ptId . "', @PT_NAMA='" . $ptNama . "', @GUDANG='" . $gudang . "', @DEPT_ID='" . $deptId . "', @DEPT_NAMA='" . $deptNama . "', @AREA='" . $deptArea ."', @NOTRANS='" . $notrans . "', @UserLoginAktif='" . $username . "', @STATUS_='" . $status . "', @KETERANGAN='" . $ket . "'"));
        //$this->var = [$jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $deptArea, $notrans, $username, $status, $ket];
    }

    public function getSo($pt, $gudang) // Untuk melakukan kirim DO
    { 
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $gudang = urldecode($gudang); 

        $result = DB::table('erasystem_2012.soi_pellet')->select('ID_SO')->whereRaw('DARI_PT_ID = ? AND DARI_GUDANG = ?', [$newpt, $gudang])->get();

        if($result){
            $out = [
                'message' => 'success',
                'result' => $result,
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

    public function getJadwal2($idSo) // Bisa menampilkan nopol dan jumlah sisa SO yg tersedia
    {
        
        $result = DB::table('erasystem_2012.jpi_pellet')
        ->join('erasystem_2012.jpi_pellet_det', 'jpi_pellet.ID_JADWAL', '=', 'jpi_pellet_det.ID_JADWAL')
        ->select('*')->whereRaw('jpi_pellet.ID_SO = ? AND jpi_pellet_det.TARIK_DO = ?', [$idSo, '0'])->get();

        if($result){
            $out = [
                'message' => 'success',
                'result' => $result,
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

    public function getJadwal($pt, $gudang)
    {
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $gudang = urldecode($gudang); 

        $result = DB::table('erasystem_2012.jpi_pellet')
        ->join('erasystem_2012.jpi_pellet_det', 'jpi_pellet.ID_JADWAL', '=', 'jpi_pellet_det.ID_JADWAL')
        ->join('erasystem_2012.soi_pellet', 'jpi_pellet.ID_SO', '=', 'soi_pellet.ID_SO')
        ->leftJoin('erasystem_2012.doi_pellet', 'jpi_pellet.ID_SO', '=', 'doi_pellet.ID_SO')
        ->selectRaw('soi_pellet.TOTAL, jpi_pellet.ID_SO, jpi_pellet.ID_JADWAL, jpi_pellet_det.NOPOL, doi_pellet.TOTAL AS TOTAL_DO')->whereRaw('soi_pellet.DARI_PT_ID = ? AND soi_pellet.DARI_GUDANG = ? AND jpi_pellet_det.TARIK_DO = ?', [$newpt, $gudang, '0'])->get();

        //Mengambil semua hasil
        foreach($result as $row){

            $doi = Doi::selectRaw('SUM(TOTAL) AS TOTAL_DO')->where('ID_SO', $row->ID_SO)->first();
            
            if($doi->TOTAL_DO == null){
                $total_do = 0;
            } else {
                $total_do = $doi->TOTAL_DO;
            }

            $newtotal = $row->TOTAL - $total_do;

            $data[] = [
                'ID_JADWAL' => $row->ID_JADWAL,
                'ID_SO' => $row->ID_SO,
                'NOPOL' => $row->NOPOL,
                'TOTAL' => $newtotal 
            ];

        }

        if($result->count()){

            $out = [
                'message' => 'success',
                'result' => $data
            ];

            $code = 200;
            
        } else {
            $out = [
                'message' => 'empty'
            ];
            $code = 404;

        }

        return response()->json($out, $code, [], JSON_NUMERIC_CHECK, [], JSON_NUMERIC_CHECK);
    }

    public function getdrafDoi($pt, $gudang) // ambil pt dan departemen si user login 
    {
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $gudang = urldecode($gudang);

        //$data = Bst::whereRaw('STATUS = ? AND PT_ID = ? AND GUDANG = ? AND DARI_DEPT_ID = ?', ['0', $newpt, $gudang, $dept])->get();

        $data = DB::table('erasystem_2012.doi_pellet')
        ->join('erasystem_2012.soi_pellet', 'doi_pellet.ID_SO', '=', 'soi_pellet.ID_SO')
        ->selectRaw('doi_pellet.ID_DO, doi_pellet.ID_SO, doi_pellet.ID_JADWAL, doi_pellet.NOPOL')
        ->whereRaw('soi_pellet.DARI_PT_ID = ? AND soi_pellet.DARI_GUDANG = ? AND doi_pellet.STATUS = ?', [$newpt, $gudang, '0'])->get();

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

    public function checkBarcode2(Request $request) //Cek barcode melaui scan hp || Jangan lupa database tes dan erasystem_2012 || Cari bercode berdasarkan data pengirim || Tambahkan parameter ID SO
    {   
        //Jika pilih PT & gudang pengirim
        $pt = $request->input('PT_ID'); // yg kirim
        $gudang = $request->input('GUDANG'); // yg kirim
        $barcode = $request->input('BARCODE');
        $id_so = $request->input('ID_SO');
        //$id_do = $request->input('ID_DO');

        $kode = substr($barcode, 0, 13);
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

        if(strpos($id_so, 'DO') !== false){
            $doi = Doi::select('ID_SO')->where('ID_DO', $id_do)->first();
            $id_so = $doi->ID_SO;
        }

        //Jika pilih PT & gudang penerima
        //$id_so = $request->input('ID_SO');
        //$soi = DB::table('erasystem_2012.soi_pellet')->selectRaw('DARI_PT_ID, DARI_GUDANG')->where('ID_SO', $id_so)->first();
        //$newpt = $soi->DARI_PT_ID; // check barcode dari PT pengirim
        //$gudang = $soi->DARI_GUDANG; // cek barcode dari gudang pengirim
        
        $dept = 'PPC';
        $area = 'Plastics Pellet Warehouse';
        $newstatus = 'TERIMA';

        $soi_item = DB::table('erasystem_2012.soi_pellet_item')
        ->whereRaw('soi_pellet_item.KODE_PELLET = ? AND soi_pellet_item.ID_SO = ?', [$kode, $id_so])->select('*')->first();//Tambahkan where SO

        $result = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
        ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
        ->first();

        if($soi_item){
            if ($result) {
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
        } else {
            $out = [
                'message' => 'empty',
                'result' => [],
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checkbarcodeUpdate(Request $request) //Untuk update
    {
        //Jika pilih PT & gudang pengirim
        $pt = $request->input('PT_ID'); // yg kirim
        $gudang = $request->input('GUDANG'); // yg kirim
        $barcode = $request->input('BARCODE');
        $id_do = $request->input('ID_DO');

        $kode = substr($barcode, 0, 13);
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

        if(strpos($id_do, 'DO') !== false){
            $doi = Doi::select('ID_SO')->where('ID_DO', $id_do)->first();
            $id_so = $doi->ID_SO;
        }
        
        $dept = 'PPC';
        $area = 'Plastics Pellet Warehouse';
        $newstatus = 'TERIMA';

        $soi_item = DB::table('erasystem_2012.soi_pellet_item')
        ->whereRaw('soi_pellet_item.KODE_PELLET = ? AND soi_pellet_item.ID_SO = ?', [$kode, $id_so])->select('*')->first();//Tambahkan where SO

        if($soi_item){

            $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
            ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
            ->first();

            if ($result) {

                $soi = DB::table('erasystem_2012.soi_pellet_item')
                    ->whereRaw('ID_SO = ? AND KODE_PELLET = ?' , [$id_so, $kode])->selectRaw('QTY AS QTY_SO')
                    ->first();

                $doi = DB::table('erasystem_2012.doi_pellet_item')
                    ->leftJoin('erasystem_2012.doi_pellet', 'doi_pellet.ID_DO', '=', 'doi_pellet_item.ID_DO')
                    ->selectRaw('SUM(doi_pellet_item.QTY) AS QTY_DO')
                    ->whereRaw('doi_pellet_item.ID_DO <> ? AND doi_pellet.ID_SO = ? AND doi_pellet_item.KODE_PELLET = ?', [$id_do, $id_so, $kode])
                    ->first();

                $result->SISA = $soi->QTY_SO - $doi->QTY_DO;

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
        } else {
            $out = [
                'message' => 'empty',
                'result' => [],
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checkBarcode(Request $request) //Untuk tambah
    {
        //Jika pilih PT & gudang pengirim
        $pt = $request->input('PT_ID'); // yg kirim
        $gudang = $request->input('GUDANG'); // yg kirim
        $barcode = $request->input('BARCODE');
        $id_so = $request->input('ID_SO');
        $id_do = '';

        $kode = substr($barcode, 0, 13);
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

        $dept = 'PPC';
        $area = 'Plastics Pellet Warehouse';
        $newstatus = 'TERIMA';

        $soi_item = DB::table('erasystem_2012.soi_pellet_item')
        ->whereRaw('soi_pellet_item.KODE_PELLET = ? AND soi_pellet_item.ID_SO = ?', [$kode, $id_so])->select('*')->first();//Tambahkan where SO

        if($soi_item){

            $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
            ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
            ->first();

            if ($result) {

                $soi = DB::table('erasystem_2012.soi_pellet_item')
                    ->whereRaw('ID_SO = ? AND KODE_PELLET = ?' , [$id_so, $kode])->selectRaw('QTY AS QTY_SO')
                    ->first();

                $doi = DB::table('erasystem_2012.doi_pellet_item')
                    ->leftJoin('erasystem_2012.doi_pellet', 'doi_pellet.ID_DO', '=', 'doi_pellet_item.ID_DO')
                    ->selectRaw('SUM(doi_pellet_item.QTY) AS QTY_DO')
                    ->whereRaw('doi_pellet.ID_SO = ? AND doi_pellet_item.KODE_PELLET = ?', [$id_so, $kode])
                    ->first();

                if($doi){
                    $result->SISA = $soi->QTY_SO - $doi->QTY_DO;
                } else {
                    $result->SISA = $soi->QTY_SO;
                }

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
        } else {
            $out = [
                'message' => 'empty',
                'result' => [],
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checkBarcodeKirim(Request $request)
    {
        //Jika pilih PT & gudang pengirim
        $pt = $request->input('PT_ID'); // yg kirim
        $gudang = $request->input('GUDANG'); // yg kirim
        $barcode = $request->input('BARCODE');
        $id_so = $request->input('ID_SO');
        $id_do = '';

        $kode = substr($barcode, 0, 13);
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

        $dept = 'PPC';
        $area = 'Plastics Pellet Warehouse';
        $newstatus = 'TERIMA';

        $check1 = $result = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
        ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
        ->first();

        if($check1){
            
            $soi_item = DB::table('erasystem_2012.soi_pellet_item')
            ->whereRaw('soi_pellet_item.KODE_PELLET = ? AND soi_pellet_item.ID_SO = ?', [$kode, $id_so])->select('*')->first();//Tambahkan where SO
    
            if($soi_item){
    
                $result = DB::table('erasystem_2012.barcode_pellet')
                ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                    $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                })
                ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
                ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
                ->first();
    
                if ($result) {
    
                    $soi = DB::table('erasystem_2012.soi_pellet_item')
                        ->whereRaw('ID_SO = ? AND KODE_PELLET = ?' , [$id_so, $kode])->selectRaw('QTY AS QTY_SO')
                        ->first();
    
                    $doi = DB::table('erasystem_2012.doi_pellet_item')
                        ->leftJoin('erasystem_2012.doi_pellet', 'doi_pellet.ID_DO', '=', 'doi_pellet_item.ID_DO')
                        ->selectRaw('SUM(doi_pellet_item.QTY) AS QTY_DO')
                        ->whereRaw('doi_pellet.ID_SO = ? AND doi_pellet_item.KODE_PELLET = ?', [$id_so, $kode])
                        ->first();
    
                    if($doi){
                        $result->SISA = $soi->QTY_SO - $doi->QTY_DO;
                    } else {
                        $result->SISA = $soi->QTY_SO;
                    }
    
                    $out = [
                        'message' => 'success',
                        'result' => $result,
                        'status' => TRUE,
                        'code' => 200
                    ];
    
                } else {
                    $out = [
                        'message' => 'Barcode tidak tersedia untuk area ' . $area . '. Detail status barcode saat ini: ' ,
                        'result' => $check1,
                        'status' => FALSE,
                        'code' => 200
                    ];
                }
            } else {
                $out = [
                    'message' => 'Item tidak sesuai dengan SO' . $id_so . '. Detail status barcode saat ini: ',
                    'result' => $check1,
                    'status' => FALSE,
                    'code' => 200
                ];
            }

        } else {
            $out = [
                'message' => 'Barcode tidak terdaftar!',
                'result' => [],
                'status' => FALSE,
                'code' => 200
            ];
        }


        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checktotalItem($idSo, $kode, $total)
    {
        $kode = urldecode($kode);
        
        if(strpos($idSo, 'DO') !== false){
            $doi = Doi::select('ID_SO')->where('ID_DO', $idSo)->first();
            $idSo = $doi->ID_SO;
        }

        $cek_do = Doi::select('*')->where('ID_SO', $idSo)->first();

        if($cek_do){

            $soi = DB::table('erasystem_2012.soi_pellet_item')
            ->whereRaw('soi_pellet_item.KODE_PELLET = ? AND soi_pellet_item.ID_SO = ?', [$kode, $idSo])->selectRaw('QTY AS QTY_SO')
            ->first();

            $doi = DB::table('erasystem_2012.doi_pellet')
            ->leftJoin('erasystem_2012.doi_pellet_item', 'doi_pellet.ID_DO', '=', 'doi_pellet_item.ID_DO')
            ->whereRaw('doi_pellet_item.KODE_PELLET = ? AND doi_pellet.ID_SO = ?', [$kode, $idSo])
            ->selectRaw('SUM(QTY) AS QTY_DO')
            ->first();

            $jml_do = $doi->QTY_DO;
            $jml_so = $soi->QTY_SO;

        } else {

            $soi = DB::table('erasystem_2012.soi_pellet_item')->whereRaw('ID_SO = ? AND KODE_PELLET = ?', [$idSo, $kode])->selectRaw('QTY AS QTY_SO')->first();

            $jml_do = 0;
            $jml_so = $soi->QTY_SO;

        }

        $subtotal = $jml_do + $total;
        
        if($jml_so >= $subtotal){
            $out = [
                'message' => 'success',
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'Total scan melebihi item yang tersedia!',
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function cektotalSo($idSo, $total)
    {
        $soi = DB::table('erasystem_2012.soi_pellet')->where('ID_SO', $idSo)->selectRaw('CAST(TOTAL AS SIGNED) as TOTAL_SO')->first();
        $do = Doi::selectRaw('SUM(TOTAL) AS TOTAL_DO')->where('ID_SO', $idSo)->first();

        if($do){
            $total_do = (int)$do->TOTAL_DO;
        } else {
            $total_do = 0;
        }

        $sub_do = $total + $total_do;

        if($sub_do <= $soi->TOTAL_SO){
            $out = [
                'message' => 'success',
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'failed',
                'code' => 404
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);

    }

    public function store(Request $request) //Butuh data PT dan Gudang user pengirim dari SOI Pellet
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
            '*.NOPOL' => 'required'
        ]);

        // handle data doi pilihan
        $username = $records[0]['USERNAME'];
        $pt = $records[0]['PT_ID'];
        $pt_nama = $records[0]['PT_NAMA'];
        $gudang = $records[0]['GUDANG'];
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        //
        $dari_dept_id = 'PPC';
        $ke_dept_id = 'PPC';
        $dari_dept_nama = 'PPIC';
        $ke_dept_nama = 'PPIC';
        $dari_dept_area = 'Plastics Pellet Warehouse';
        $ke_dept_area = 'Receiving';
        $status = $records[0]['STATUS'];
        //$newstatus = 'TERIMA';
        $id_so = $records[0]['ID_SO'];
        $id_jadwal = $records[0]['ID_JADWAL'];
        $nopol = $records[0]['NOPOL'];
        $ket = null;

        $datetime = date('Y-m-d H:i:s');
        $date = date('Y-m-d');

        //Data user yg kirim
        $soi = DB::table('erasystem_2012.soi_pellet')->selectRaw('DARI_PT_ID, DARI_PT_NAMA, DARI_GUDANG')->where('ID_SO', $id_so)->first();
        // $pt_user = $soi->DARI_PT_ID;
        // $pt_nama_user = $soi->DARI_PT_NAMA;
        // $gudang_user = $soi->DARI_GUDANG;
        //$newpt_user = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        
        $n_tahun = date('y', strtotime($date));
        $n_Tahun = date('Y', strtotime($date));
        $n_bulan = date('m', strtotime($date));
        $n_tanggal = date('d', strtotime($date));
         //cari nilai max doi_pellet
        $doi = Doi::selectRaw('CAST(MAX(RIGHT(ID_DO, 3)) AS SIGNED) AS LAST_NO')->whereRaw('LEFT(ID_DO, 1) = ? AND MID(ID_DO, 3, 3) = ? AND MONTH(TANGGAL) = ? AND YEAR(TANGGAL) = ?', [$pt, $dari_dept_id, $n_bulan, $n_Tahun])->first();

         //inisialisasi tgl , bulan, tahun
        
        if($doi){ // Jika DOI ada
            $no = $doi->LAST_NO;
            $no++;
            $IdDo = $pt . '/' . $dari_dept_id . '/DO/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  sprintf("%03s", $no);
        } else { // Jika null
            $IdDo = $pt . '/' . $dari_dept_id . '/DO/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  '001';
        }

        //handle request barcode
        $barcodes = array_column($records, 'BARCODE');
        $kode_pellet = array_column($records, 'KODE_PELLET');
        $total = array_count_values($kode_pellet);
        $list_doi = [];

        $total_item = [];
        foreach($records as $a){

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
            if(!in_array($rec['KODE_PELLET'], array_column($list_doi, 'KODE_PELLET'))){
                $list_doi[] = [
                    'ID_DO' => $IdDo, // No BST
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

        //Set variabel
        $this->_setVariable('DO', $pt_user, $pt_nama_user, $gudang_user, $dari_dept_id, $dari_dept_nama, $dari_dept_area, $IdDo, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        $data_doi = [
            'ID_DO' => $IdDo,
            'ID_SO' => $id_so,
            'ID_JADWAL' => $id_jadwal,
            'NOPOL' => $nopol,
            'KETERANGAN' => $ket,
            'STATUS' => 0,
            'USERNAME' => $username,
            'LAST_UPDATE' => date('Y-m-d H:i:s'),
            'TANGGAL' => date('Y-m-d'),
            'TANGGAL_BUAT' => date('Y-m-d H:i:s'),
            'TOTAL' => count($barcodes)
        ];

        DB::beginTransaction();

        $insert = Doi::create($data_doi);
        DB::table('erasystem_2012.doi_pellet_item')->insert($list_doi);
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
            //'*.STATUS' => 'required',
            '*.ID_DO' => 'required'
        ]);

        // handle data doi pilihan
        $username = $records[0]['USERNAME'];
        $pt = $records[0]['PT_ID'];
        $pt_nama = $records[0]['PT_NAMA'];
        $gudang = $records[0]['GUDANG'];
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        //
        $dari_dept_id = 'PPC';
        $ke_dept_id = 'PPC';
        $dari_dept_nama = 'PPIC';
        $ke_dept_nama = 'PPIC';
        $dari_dept_area = 'Plastics Pellet Warehouse';
        $ke_dept_area = 'Receiving';
        $status = 'KIRIM';
        //$newstatus = 'TERIMA';
        // $id_so = $records[0]['ID_DO'];
        // $id_jadwal = $records[0]['ID_JADWAL'];
        // $nopol = $records[0]['NOPOL'];
        $ket = null;

        $datetime = date('Y-m-d H:i:s');
        $date = date('Y-m-d');

         //cari nilai max doi_pellet
        $IdDo = $records[0]['ID_DO'];

        //Munculkan list barcode dalam bentuk array
        $q_old = DB::table('erasystem_2012.barcode_pellet_det')
        ->join('erasystem_2012.barcode_pellet', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.NOTRANS = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$IdDo, 'KIRIM', '1'])
        ->select('barcode_pellet_det.BARCODE')->get();
        $arr_q_old = $q_old->toArray();
        $old_barcode = array_column($arr_q_old, 'BARCODE');

        //handle request barcode
        $barcodes = array_column($records, 'BARCODE');
        $kode_pellet = array_column($records, 'KODE_PELLET');
        $add_barcode = [];
        $del_barcodes = [];
        $count_barcode =  [];
        $list_doi = [];

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

                if(!in_array($rec['KODE_PELLET'], array_column($list_doi, 'KODE_PELLET'))){
                    $list_doi[] = [
                        'ID_DO' => $IdDo, // No DO
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

        //Set variabel
        $this->_setVariable('DO', $newpt, $pt_nama, $gudang, $dari_dept_id, $dari_dept_nama, $dari_dept_area, $IdDo, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        $data_doi = [
            'ID_DO' => $IdDo,
            //'USERNAME' => $username,
            'LAST_UPDATE' => date('Y-m-d H:i:s'),
            'TOTAL' => count($count_barcode)
        ];

        DB::beginTransaction();

        $update = Doi::where('ID_DO', $IdDo)->update($data_doi);
        //DB::table('erasyste_2012.bst_pellet_item')->delete
        DB::table('erasystem_2012.doi_pellet_item')->where('ID_DO', $IdDo)->delete();
        DB::table('erasystem_2012.doi_pellet_item')->insert($list_doi); // Delete list bst dan masukkan kembali

        DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
        DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $add_barcode)->update(['LAST_UPDATE' => $datetime]);
        
        DB::statement(DB::raw("SET @AKSI='HAPUS'"));
        DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $del_barcodes)->update(['LAST_UPDATE' => $datetime]);

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

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    /* =========================================== UNTUK BAGIAN TERIMA =================================================== */

    public function getlistDoi($pt, $gudang) // Untuk terima DO
    {
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $gudang = urldecode($gudang); 

        $result = DB::table('erasystem_2012.doi_pellet')
        ->join('erasystem_2012.soi_pellet', 'doi_pellet.ID_SO', '=', 'soi_pellet.ID_SO')
        ->selectRaw('doi_pellet.ID_DO, doi_pellet.ID_SO, doi_pellet.ID_JADWAL, doi_pellet.NOPOL')
        ->whereRaw('soi_pellet.DARI_PT_ID = ? AND soi_pellet.DARI_GUDANG = ? AND doi_pellet.STATUS = ?', [$newpt, $gudang, '2'])->get();

        if($result->count()){
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

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function getlistdoiUser($pt, $gudang)
    {
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $gudang = urldecode($gudang);
        $dept = 'PPC';

        $result = DB::table('erasystem_2012.doi_pellet')
        ->join('erasystem_2012.soi_pellet', 'doi_pellet.ID_SO', '=', 'soi_pellet.ID_SO')
        ->selectRaw('doi_pellet.ID_DO, doi_pellet.ID_SO, doi_pellet.ID_JADWAL, doi_pellet.NOPOL')
        ->whereRaw('soi_pellet.KE_PT_ID = ? AND soi_pellet.KE_GUDANG = ? AND doi_pellet.STATUS = ?', [$newpt, $gudang, '2'])->get();

        if($result->count()){
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

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function getlistBarcodeUpdate($IdDo)
    {
        //Kirim jumlah yg sudah ada ke client
        $doi = Doi::select('ID_SO')->where('ID_DO', $IdDo)->first();
        $IdSo = $doi->ID_SO;

        $q_soi = DB::table('erasystem_2012.soi_pellet_item')
            ->whereRaw('soi_pellet_item.ID_SO = ?', [$IdSo])->selectRaw('KODE_PELLET, QTY AS QTY_SO')
            ->orderBy('soi_pellet_item.KODE_PELLET')
            ->get()
            ->toArray();

        $q_doi = DB::table('erasystem_2012.doi_pellet_item')
            ->leftJoin('erasystem_2012.doi_pellet', 'doi_pellet.ID_DO', '=', 'doi_pellet_item.ID_DO')
            ->selectRaw('doi_pellet_item.KODE_PELLET, SUM(doi_pellet_item.QTY) AS QTY_DO')
            ->whereRaw('doi_pellet_item.ID_DO <> ? AND doi_pellet.ID_SO = ?', [$IdDo, $IdSo])
            ->groupBy('doi_pellet_item.KODE_PELLET')->orderBy('doi_pellet_item.KODE_PELLET')
            ->get()
            ->toArray();

        // $subtracted = array_map(function ($x, $y) { return $x-$y; } , $q_soi, $q_doi);
        // $result     = array_combine(array_keys($q_soi), $subtracted);

        $doi = array_column($q_doi, 'QTY_DO');

        $i = 0;
        foreach($q_soi as $row){

            $sisa[] = [
                'KODE_PELLET' => $row->KODE_PELLET,
                'SISA' => $row->QTY_SO - $doi[$i]
            ];
            $i++;
        }

        $newstatus = 'KIRIM';

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', [$newstatus, $IdDo, '1'])
            ->selectRaw('barcode_pellet_det.BARCODE, barcode_pellet_det.NOTRANS, barcode_pellet.KODE_PELLET, barcode_pellet.NAMA_PELLET, barcode_pellet.NAMA_LABEL, barcode_pellet.KG')
            ->get();

        $out = [
            'message' => 'success',
            'result' => $result,
            'sisa' => $sisa
        ];

        return response()->json($out, 200);
    }

    public function getlistBarcode2($IdDo) 
    {
        //$newstatus = $status == "UPDATE" ? "KIRIM" : "TERIMA";

        $newstatus = 'KIRIM';

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', [$newstatus, $IdDo, '1'])
            ->selectRaw('barcode_pellet_det.BARCODE, barcode_pellet_det.NOTRANS, barcode_pellet.KODE_PELLET, barcode_pellet.NAMA_PELLET, barcode_pellet.NAMA_LABEL, barcode_pellet.KG')
            ->get();

        $out = [
            'message' => 'success',
            'result' => $result
        ];

        return response()->json($out, 200);
    }

    public function checkBarcodeTerima(Request $request)
    {
        $barcode = $request->input('BARCODE');

        $check1  = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
        ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
        ->first();

        if($check1){
            $out = [
                'message' => 'Barcode tidak tersedia untuk terima DOI. Detail status barcode saat ini: ',
                'result' => $check1,
                'status' => FALSE,
                'code' => 200
            ];
        } else {
            $out = [
                'message' => 'Barcode tidak terdaftar!',
                'result' => [],
                'status' => FALSE,
                'code' => 404
            ];
        }

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function gettotalBarcode($IdDo) // Untuk terima
    {
        $barcode = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ?', ['KIRIM', $IdDo])
        ->selectRaw('barcode_pellet_det.NOTRANS, count(*) as TOTAL')
        ->first();

        $out = [
            'message' => 'success',
            'total' => $barcode->TOTAL,
            'notrans' => $barcode->NOTRANS
        ];

        return response()->json($out, 200);
    }

    public function terimaDoi(Request $request) // Pastikan yg kirim PT dan Gudang itu adalah yg login (penerima)
    {
        //Status terima minimal
        $this->validate($request, [
            'USERNAME'  => 'required',
            'PT_ID'     => 'required', // PT User
            'PT_NAMA'   => 'required', // PT Nama User
            'GUDANG'    => 'required', // Gudang User
            'STATUS'    => 'required',
            'NOTRANS'   => 'required'
        ]);

        $status = $request->input('STATUS');
        $username = $request->input('USERNAME');
        $idDo = $request->input('NOTRANS'); // Cari barcode berdasarkan notrans

        //Harus data user yg terima
        //$doi = Doi::
        $doi = DB::table('erasystem_2012.doi_pellet')
        ->join('erasystem_2012.soi_pellet', 'doi_pellet.ID_SO', '=', 'soi_pellet.ID_SO')
        ->selectRaw('soi_pellet.KE_PT_ID, soi_pellet.KE_PT_NAMA, soi_pellet.KE_GUDANG')
        ->whereRaw('doi_pellet.ID_DO = ?', [$idDo])->first();

        $ptId = $doi->KE_PT_ID;
        $ptNama = $doi->KE_PT_NAMA;
        $gudang = $doi->KE_GUDANG;

        $dariDeptId = 'PPC';
        $dariDeptNama = 'PPIC';
        $dariDeptArea = 'Receiving';
        
        $ket = $request->input('KETERANGAN');
        $datetime = date('Y-m-d H:i:s');
        $date = date('Y-m-d'); 

        //$newpt = $ptId == '1' ? 'ERA' : ( $ptId == '2' ? 'ERI' : 'EPI');  

        //Set variabel tambahkan area
        $this->_setVariable('DO', $ptId, $ptNama, $gudang, $dariDeptId, $dariDeptNama, $dariDeptArea, $idDo, $username, $status, $ket);

        $data = [
            'STATUS' => 3, //Status terima
            'TERIMA_FINAL' => $username,
            'TERIMA_FINAL_TANGGAL' => $datetime,
            'LAST_UPDATE' => $datetime
        ];

        $newstatus = 'KIRIM';

        $resbarcode = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', [$newstatus, $idDo, '1'])
            ->selectRaw('barcode_pellet_det.BARCODE')
            ->get();
        $barcode = $resbarcode->toArray();
        $listbarcode = array_column($barcode, 'BARCODE');

        DB::beginTransaction();
        $update = Doi::where('ID_DO', $idDo)->update($data);
        DB::statement(DB::raw("SET @AKSI = 'TAMBAH'"));
        DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $listbarcode)->update(['LAST_UPDATE' => $datetime]);
        //$update = true;
        
        if ($update) {
            $out = [
                'message' => 'success',
                'result' => $data
            ];
            $code = 201;
            DB::commit();
        } else {
            $out = [
                'message' => 'failed',
                'result' => $data
            ];
            $code = 404;
            DB::rollBack();
        }

        return response()->json($out, $code, [], JSON_NUMERIC_CHECK);


    }

    public function checkFinalize($notrans)
    {
        $doidraf = Doi::whereRaw('ID_DO = ? AND STATUS = ?', [$notrans, '0'])->first();

        if($doidraf){
            $out = [
                'message' => 'success',
                'result' => $doidraf
            ];  
            $code = 200;
        } else {
            $out = [
                'message' => 'DOI sudah di-finalize, kembali ke menu utama untuk refresh!',
                'result' => []
            ];
            $code = 404;
        }

        return response()->json($out, $code, [], JSON_NUMERIC_CHECK);

    }

    //Kurang Hapus






}
