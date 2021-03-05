<?php

namespace App\Http\Controllers;

use App\Penjualan;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller 
{
    public function _setVariable($jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $deptArea, $notrans, $username, $status, $ket)
    {
        DB::statement(DB::raw("SET @JENIS_DOK='" . $jenisDok . "', @PT_ID='" . $ptId . "', @PT_NAMA='" . $ptNama . "', @GUDANG='" . $gudang . "', @DEPT_ID='" . $deptId . "', @DEPT_NAMA='" . $deptNama . "', @AREA='" . $deptArea . "', @NOTRANS='" . $notrans . "', @UserLoginAktif='" . $username . "', @STATUS_='" . $status . "', @KETERANGAN='" . $ket . "'"));
        //$this->var = [$jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $deptArea, $notrans, $username, $status, $ket];
    }

    public function getListBarcodeKirim($notrans) 
    {
        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', ['KIRIM', $notrans, '1'])
            ->selectRaw('barcode_pellet_det.BARCODE, barcode_pellet.KODE_PELLET, barcode_pellet.NAMA_PELLET, barcode_pellet.NAMA_LABEL, barcode_pellet.KG')
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

    public function getDrafPengeluaranBarang($kocab)
    {   
        $kocab = urldecode($kocab);
        $result = Penjualan::select('NoTrans', 'No_DO')->where('Dept', 'MAR')->where('KoCab', $kocab)->where('No_DO', '<>', '')->whereNotNull('No_DO')->whereNull('OTORISASI_PPIC_USER')->get();

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
        $kocab = urldecode($kocab);
        $result = DB::table('erasystem_2012.jp_pellet as A')
            ->selectRaw("A.ID_JADWAL, A.ID_SO, C.NOPOL, A.PT_ID, A.GUDANG, B.NoTrans AS NOTRANS, (SELECT SUM(Jml) FROM erasystem_2012.det_bpb_retur WHERE REPLACE(Notrans_BPB, '.B01', '') = B.NoTrans) AS TOTAL_KG")
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
        $id_so = '';
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

            $item = DB::table('erasystem_2012.det_bpb_retur')->whereRaw('Notrans_BPB = ? AND KoHan = ?', [$no_trans . '.B01', $kode])->first();

            if($item) {

                $result = DB::table('erasystem_2012.barcode_pellet')
                    ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                        $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                    })
                    ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $gudang, $dept, $area, $newstatus, '1'])
                    ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
                    ->first();

                if($result) {

                    $result->SISA_KG = $item->Jml;
                    $code = 200;
                    $out = [
                        'message' => 'success',
                        'result' => $result,
                        'status' => true
                    ];

                } else {
                    $code = 200;
                    $out = [
                        'message' => 'Barcode tidak tersedia untuk area ' . $area . '. Detail barcode saat ini: ',
                        'result' => $result,
                        'status' => false
                    ];
                }

            } else {
                $code = 200;
                $out = [
                    'message' => 'Item tidak sesuai dengan nomor SO',
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
            '*.NOTRANS' => 'required',
            '*.NOPOL' => 'required',
        ]);

        // handle data doi pilihan
        $username = $records[0]['USERNAME'];
        $pt = $records[0]['PT_ID'];
        $pt_nama = $records[0]['PT_NAMA'];
        $gudang = $records[0]['GUDANG'];
        $newpt = $pt == '1' ? 'ERA' : ($pt == '2' ? 'ERI' : 'EPI');
        //
        $dept_id = 'PPC';
        $dept_nama = 'PPIC';
        $dept_area = 'Plastics Pellet Warehouse';
        $status = 'KIRIM';
        //$newstatus = 'TERIMA';
        //$id_so = $records[0]['ID_SO'];
        //$id_jadwal = $records[0]['ID_JADWAL'];
        $nopol = $records[0]['NOPOL'];
        $no_trans = $records[0]['NOTRANS'];
        $ket = null;

        $datetime = date('Y-m-d H:i:s');
        $date = date('Y-m-d');

        $n_tahun = date('y', strtotime($date));
        $n_Tahun = date('Y', strtotime($date));
        $n_bulan = date('m', strtotime($date));
        $n_tanggal = date('d', strtotime($date));
        //cari nilai max dox_
        $dox = Penjualan::selectRaw('CAST(MAX(RIGHT(No_DO, 2)) AS SIGNED) AS LAST_NO')->whereRaw('LEFT(No_DO, 1) = ? AND MID(No_DO, 3, 3) = ? AND MONTH(Tgl) = ? AND YEAR(Tgl) = ? AND Dept = ?', [$pt, $dept_id, $n_bulan, $n_Tahun, 'MAR'])->first();

        if ($dox->count()) { // Jika DOX ada
            $no = $dox->LAST_NO;
            $no++;
            $IdDo = $pt . '/' . $dept_id . '/DO/' . $n_tahun . '/' . $n_bulan . '/' . $n_tanggal . '/' . 'P' . sprintf("%02s", $no);
        } else { // Jika null
            $IdDo = $pt . '/' . $dept_id . '/DO/' . $n_tahun . '/' . $n_bulan . '/' . $n_tanggal . '/' . 'P01';
        }

        //handle request barcode
        $barcodes = array_column($records, 'BARCODE');

        //Set variabel
        $this->_setVariable('DO-X', $newpt, $pt_nama, $gudang, $dept_id, $dept_nama, $dept_area, $IdDo, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        $update = [
            'No_DO' => $IdDo,
            'NoKen' => $nopol
        ];

        DB::beginTransaction();

        try {

            Penjualan::where('NoTrans', $no_trans)->update($update);
            DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE', $barcodes)->update(['LAST_UPDATE' => $datetime]);
            $code = 201;
            $out = [
                'message' => 'Submit sukses'
            ];
            DB::commit();

        } catch (QueryException $e) {

            $code = 500;
            $out = [
                'message' => 'Submit gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2]
            ];
            DB::rollBack();

        }
        return response()->json($out, $code, [], JSON_NUMERIC_CHECK);

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
            '*.NO_DO' => 'required'
        ]);

        // handle data doi pilihan
        $username = $records[0]['USERNAME'];
        $pt = $records[0]['PT_ID'];
        $pt_nama = $records[0]['PT_NAMA'];
        $gudang = $records[0]['GUDANG'];
        $newpt = $pt == '1' ? 'ERA' : ($pt == '2' ? 'ERI' : 'EPI');
        //
        $dept_id = 'PPC';
        $dept_nama = 'PPIC';
        $dept_area = 'Plastics Pellet Warehouse';
        $status = 'KIRIM';
        //$newstatus = 'TERIMA';
        //$id_so = $records[0]['ID_SO'];
        //$id_jadwal = $records[0]['ID_JADWAL'];
        $no_do = $records[0]['NO_DO'];
        $ket = null;

        $datetime = date('Y-m-d H:i:s');
        $date = date('Y-m-d');

        //Munculkan list barcode dalam bentuk array
        $q_old = DB::table('erasystem_2012.barcode_pellet_det')
            ->join('erasystem_2012.barcode_pellet', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.NOTRANS = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$no_do, 'KIRIM', '1'])
            ->select('barcode_pellet_det.BARCODE')->get();
        $arr_q_old = $q_old->toArray();
        $old_barcode = array_column($arr_q_old, 'BARCODE');

        //handle request barcode
        $barcodes = array_column($records, 'BARCODE');
        $add_barcode = [];
        $del_barcode = [];

        foreach($records as $rec){
            if($rec['STATUS_BARCODE'] == 1) {
                if (!in_array($rec['BARCODE'], $old_barcode)) {
                    array_push($add_barcode, $rec['BARCODE']);
                }
            } else {
                array_push($del_barcode, $rec['BARCODE']);
            }
        }

        //Set variabel
        $this->_setVariable('DO-X', $newpt, $pt_nama, $gudang, $dept_id, $dept_nama, $dept_area, $IdDo, $username, $status, $ket); // Set variabel untuk memasukkan data barcode pellet det, ketika update barcode pellet

        DB::beginTransaction();

        try {

            DB::statement(DB::raw("SET @AKSI='TAMBAH'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE', $add_barcode)->update(['LAST_UPDATE' => $datetime]);
            DB::statement(DB::raw("SET @AKSI='HAPUS'"));
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE', $del_barcode)->update(['LAST_UPDATE' => $datetime]);

            $code = 201;
            $out = [
                'message' => 'Submit sukses'
            ];
            DB::commit();

        } catch (QueryException $e) {

            $code = 500;
            $out = [
                'message' => 'Submit gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2]
            ];
            DB::rollBack();

        }
        return response()->json($out, $code, [], JSON_NUMERIC_CHECK);
    }

}