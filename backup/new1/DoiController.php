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

        return response()->json($out, $out['code']);
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

        return response()->json($out, $code);
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

        return response()->json($out, $out['code']);
    }


    public function checkBarcode(Request $request) //Cek barcode melaui scan hp || Jangan lupa database tes dan erasystem_2012 || Cari bercode berdasarkan data pengirim || Tambahkan parameter ID SO
    {   
        //Jika pilih PT & gudang pengirim
        $pt = $request->input('PT_ID'); // yg kirim
        $gudang = $request->input('GUDANG'); // yg kirim
        $barcode = $request->input('BARCODE');
        $kode = substr($barcode, 0, 13);
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI');  

        //Jika pilih PT & gudang penerima
        //$id_so = $request->input('ID_SO');
        //$soi = DB::table('erasystem_2012.soi_pellet')->selectRaw('DARI_PT_ID, DARI_GUDANG')->where('ID_SO', $id_so)->first();
        //$newpt = $soi->DARI_PT_ID; // check barcode dari PT pengirim
        //$gudang = $soi->DARI_GUDANG; // cek barcode dari gudang pengirim
        
        $dept = 'PPC';
        $area = 'Plastics Pellet Warehouse';
        $newstatus = 'TERIMA';

        $soi_item = DB::table('erasystem_2012.soi_pellet_item')
        ->whereRaw('soi_pellet_item.KODE_PELLET = ?', [$kode])->select('*')->first();

        $result = DB::table('erasystem_2012.barcode_pellet')
        ->join('erasystem_2012.barcode_pellet_det', function ($join) {
            $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
        })
        ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
        ->selectRaw("barcode_pellet_det.*")
        ->first();

        if($soi_item){
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
        } else {
            $out = [
                'message' => 'empty',
                'data' => $barcode,
                'code' => 404
            ];
        }

        return response()->json($out, $out['code']);
    }

    public function checktotalItem($idSo, $kode, $total)
    {
        $kode = urldecode($kode);

        // $soi = DB::table('tes.soi_pellet_item')
        // ->leftJoin('tes.doi_pellet', 'soi_pellet_item.ID_SO', '=', 'doi_pellet.ID_SO')
        // ->leftJoin('tes.doi_pellet_item', 'doi_pellet_item.ID_DO', '=', 'doi_pellet.ID_DO')
        // ->whereRaw('doi_pellet.ID_SO = ? AND soi_pellet_item.KODE_PELLET = ? AND doi_pellet_item.KODE_PELLET = ?', [$idSo, $kode, $kode])->selectRaw('soi_pellet_item.QTY AS QTY_SO, doi_pellet_item.QTY AS QTY_DO')->first();

        $cek_do = DB::table('erasystem_2012.soi_pellet_item')
        ->join('erasystem_2012.doi_pellet', 'soi_pellet_item.ID_SO', '=', 'doi_pellet.ID_SO')
        ->whereRaw('doi_pellet.ID_SO = ?', [$idSo])
        ->first();

        if($cek_do){

            $soi = DB::table('erasystem_2012.soi_pellet_item')
            ->leftJoin('erasystem_2012.doi_pellet', 'soi_pellet_item.ID_SO', '=', 'doi_pellet.ID_SO')
            ->leftJoin('erasystem_2012.doi_pellet_item', 'doi_pellet.ID_DO', '=', 'doi_pellet_item.ID_DO')
            ->whereRaw('soi_pellet_item.ID_SO = ? AND doi_pellet.ID_SO = ? AND soi_pellet_item.KODE_PELLET = ?', [$idSo, $idSo, $kode])
            ->selectRaw('soi_pellet_item.QTY AS QTY_SO, doi_pellet_item.QTY AS QTY_DO')->first();

            $jml_do = $soi->QTY_DO;
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

        return response()->json($out, $out['code']);
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
        $pt_user = $soi->DARI_PT_ID;
        $pt_nama_user = $soi->DARI_PT_NAMA;
        $gudang_user = $soi->DARI_GUDANG;
        //$newpt_user = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

         //cari nilai max doi_pellet
        $doi = Doi::selectRaw('CAST(MAX(RIGHT(ID_DO,2)) AS SIGNED) AS LAST_NO')->whereRaw('LEFT(ID_DO, 1) = ? AND MID(ID_DO, 3, 3) = ? AND TANGGAL = ?', [$pt, $dari_dept_id, $date])->first();

         //inisialisasi tgl , bulan, tahun
        $n_tahun = date('y', strtotime($date));
        $n_bulan = date('m', strtotime($date));
        $n_tanggal = date('d', strtotime($date));
        
        if($doi->first()){ // Jika DOI ada
            $no = $doi->LAST_NO;
            $no++;
            $IdDo = $pt . '/' . $dari_dept_id . '/DO/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  sprintf("%02s", $no);
        } else { // Jika null
            $IdDo = $pt . '/' . $dari_dept_id . '/DO/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  '01';
        }

        //handle request barcode
        $barcodes = [];
        $list_kode = [];
        $kode_pellet = [];

        foreach ($records as $rec) {

            array_push($barcodes, $rec['BARCODE']); // Masukkan barcode awal ke dalam array barcodes
            array_push($kode_pellet, substr($rec['BARCODE'], 0, 13)); // Masukkan kode pellet berdasarkan kode barcode 13digit
            
        }

        $total = array_count_values($kode_pellet); // Total kode pellet yg sudah dimasukkan
        $list_doi = [];
        foreach($barcodes as $key => $value){

            $pellet = DB::table('erasystem_2012.barcode_pellet')->select('NAMA_PELLET', 'NAMA_LABEL', 'KG')->where('BARCODE', $barcodes[$key])->first(); // cari detail nama, dan nama label berdasarkan kode pellet
            $kd_pellet = substr($barcodes[$key], 0, 13);

            if(!in_array($kd_pellet, array_column($list_doi, 'KODE_PELLET'))) // Jika kode pellet belum ada masukkan kedalam list kode untuk barcode list DO
            {
                $list_doi[] = [
                    'ID_DO' => $IdDo, // No DO
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
        // foreach($list_kode as $key => $value){

        //     $pellet = DB::table('erasystem_2012.pellet')->select('NAMA', 'NAMA_LABEL', 'KG')->where('KODE', $list_kode[$key])->first(); // cari detail nama, dan nama label berdasarkan kode pellet

        //     $list_doi[] = [
        //         'ID_DO' => $IdDo, // No BST
        //         'KODE_PELLET' => $list_kode[$key],
        //         'NAMA_PELLET' => $pellet->NAMA,
        //         'NAMA_LABEL' => $pellet->NAMA_LABEL,
        //         'KG' => $pellet->KG, //Tambahkan KG jika field sudah diupdate
        //         'QTY' => $total[$list_kode[$key]],
        //         'SATUAN' => 'SAK',
        //         'KETERANGAN' => null
        //     ];
        // }

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

        //Data user yg kirim
        // $soi = DB::table('erasystem_2012.soi_pellet')->selectRaw('DARI_PT_ID, DARI_PT_NAMA, DARI_GUDANG')->where('ID_SO', $id_so)->first();
        // $pt_user = $soi->DARI_PT_ID;
        // $pt_nama_user = $soi->DARI_PT_NAMA;
        // $gudang_user = $soi->DARI_GUDANG;
        //$newpt_user = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 

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
        $barcodes = []; // Barcode yg ditambahkan
        $barcodez = [];
        $del_barcodes = []; // Barcode yg dihapus
        $list_kode = []; //List barcode yg akan dimasukkan kedalam bst_item
        $kode_pellet = []; // Kode pellet 13 digit

        //Untuk membedakan mana yg dihapus dan ditambah bisa disini
        foreach ($records as $rec) {

            if($rec['STATUS_BARCODE'] == 1){
                //array_push($barcodes, $rec['BARCODE']); // Masukkan barcode awal ke dalam array barcodes
                array_push($kode_pellet, substr($rec['BARCODE'], 0, 13)); // Masukkan kode pellet berdasarkan kode barcode 13digit - ini untuk jumlah di DOI
                array_push($barcodez, $rec['BARCODE']);
                if(!in_array($rec['BARCODE'], $old_barcode)){
                    array_push($barcodes, $rec['BARCODE']); // Masukkan barcode awal ke dalam array barcodes untuk diupdate
                }

            } else {
                array_push($del_barcodes, $rec['BARCODE']); // Masukkan barcode yg dihapus ke dalam array del_barcodes
            }
            
        }

        $total = array_count_values($kode_pellet); // Total kode pellet yg sudah dimasukkan
        $list_doi = [];
        foreach($barcodez as $key => $value){

            $pellet = DB::table('erasystem_2012.barcode_pellet')->select('NAMA_PELLET', 'NAMA_LABEL', 'KG')->where('BARCODE', $barcodez[$key])->first(); // cari detail nama, dan nama label berdasarkan kode pellet
            $kd_pellet = substr($barcodez[$key], 0, 13);

            if(!in_array($kd_pellet, array_column($list_doi, 'KODE_PELLET'))) // Jika kode pellet belum ada masukkan kedalam list kode untuk barcode list pellet
            {
                $list_doi[] = [
                    'ID_DO' => $IdDo, // No BST
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
        // foreach($list_kode as $key => $value){

        //     $pellet = DB::table('erasystem_2012.pellet')->select('NAMA', 'NAMA_LABEL', 'KG')->where('KODE', $list_kode[$key])->first(); // cari detail nama, dan nama label berdasarkan kode pellet

        //     $list_doi[] = [
        //         'ID_DO' => $IdDo, // No BST
        //         'KODE_PELLET' => $list_kode[$key],
        //         'NAMA_PELLET' => $pellet->NAMA,
        //         'NAMA_LABEL' => $pellet->NAMA_LABEL,
        //         'KG' => $pellet->KG, //Tambahkan KG jika field sudah diupdate
        //         'QTY' => $total[$list_kode[$key]],
        //         'SATUAN' => 'SAK',
        //         'KETERANGAN' => null
        //     ];
        // }


        //Set variabel
        $this->_setVariable('DO', $newpt, $pt_nama, $gudang, $dari_dept_id, $dari_dept_nama, $dari_dept_area, $IdDo, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        $data_doi = [
            'ID_DO' => $IdDo,
            //'USERNAME' => $username,
            'LAST_UPDATE' => date('Y-m-d H:i:s'),
            'TOTAL' => count($kode_pellet)
        ];

        DB::beginTransaction();

        $update = Doi::where('ID_DO', $IdDo)->update($data_doi);
        //DB::table('erasyste_2012.bst_pellet_item')->delete
        DB::table('erasystem_2012.doi_pellet_item')->where('ID_DO', $IdDo)->delete();
        DB::table('erasystem_2012.doi_pellet_item')->insert($list_doi); // Delete list bst dan masukkan kembali

        DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
        DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE',  $barcodes)->update(['LAST_UPDATE' => $datetime]);
        
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

        return response()->json($out, $out['code']);
    }

    /* =========================================== UNTUK BAGIAN TERIMA =================================================== */

    public function getlistDoi($pt, $gudang) // Untuk terima DO
    {
        $newpt = $pt == '1' ? 'ERA' : ( $pt == '2' ? 'ERI' : 'EPI'); 
        $gudang = urldecode($gudang); 

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

        return response()->json($out, $out['code']);
    }

    public function getlistuserDoi($pt, $gudang)
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

        return response()->json($out, $out['code']);
    }

    public function getlistBarcode($IdDo)
    {
        $newstatus = 'KIRIM';

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', [$newstatus, $IdDo, '1'])
            ->selectRaw('barcode_pellet_det.BARCODE, barcode_pellet_det.NOTRANS')
            ->get();

        $out = [
            'message' => 'success',
            'result' => $result
        ];

        return response()->json($out, 200);
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
            'STATUS' => 3,
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

        return response()->json($out, $code);


    }






}
