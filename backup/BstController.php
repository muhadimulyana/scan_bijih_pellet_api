<?php

namespace App\Http\Controllers;

use App\Bst;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

date_default_timezone_set("Asia/Jakarta");

class BstController extends Controller
{
    protected $var = [];

    protected $db = 'tes.';

    public function bstKirim($pt, $gudang, $dept)
    {
        //$data = Bst::where('STATUS', '1')->where('(SELECT(LEFT(NO_BST, 1))', '=', '1')->get();

        $data = Bst::whereRaw('STATUS = ? AND LEFT(NO_BST, 1) = ? AND GUDANG = ? AND DARI_DEPT_ID = ?', ['1', $pt, $gudang, $dept])->get();

        $out = [
            'message' => 'success',
            'result' => $data,
        ];

        return response()->json($out, 200);
    }
    

    public function _setVariable($jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $notrans, $username, $status, $ket)
    {
        DB::statement(DB::raw("SET @JENIS_DOK='" . $jenisDok . "', @PT_ID='" . $ptId . "', @PT_NAMA='" . $ptNama . "', @GUDANG='" . $gudang . "', @DEPT_ID='" . $deptId . "', @DEPT_NAMA='" . $deptNama . "', @NOTRANS='" . $notrans . "', @UserLoginAktif='" . $username . "', @STATUS_='" . $status . "', @KETERANGAN='" . $ket . "'"));
        $this->var = [$jenisDok, $ptId, $ptNama, $gudang, $deptId, $deptNama, $notrans, $username, $status, $ket];
    }

    public function tesVar()
    {
        $this->_setVariable('BST', 'ERI', 'Elite', 'ERI', 'PRO', 'Produksi', '001', 'adi', 'TERIMA', 'ABC');
        DB::statement(DB::raw("SET @aksi = 'TAMBAH'"));
        $data = DB::select("select @JENIS_DOK, @aksi");

        $out = [
            'message' => 'success',
            'result' => $data
        ];
        return response()->json($out, 200);
    }

    public function testBst($pt)
    {

        $data = DB::select(DB::raw("select @JENIS_DOK, @aksi"));

        $out = [
            'message' => 'success',
            'result' => $data,
        ];
        return response()->json($out, 200);
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

        return response()->json($out, 200);
    }

    public function terimaBst(Request $request)
    {
        if($request->method('post')) {

            //Tambahkan validate data
            //if status = terima / status = kirim beda validasi
            // $this->validate($request, [
            //     'USERNAME' => 'required',
            //     'PT_ID' => 'required',
            //     'PT_NAMA' => 'required',
            //     'DARI_DEPT_ID' => 'required',
            //     'DARI_DEPT_NAMA' => 'required',
            //     'KE_DEPT_ID' => 'required',
            //     'KE_DEPT_NAMA' => 'required',
            //     'KETERANGAN' => 'required',
            //     'TOTAL' => 'required',
            //     'STATUS' => 'required',
            // ]);
            //Status terima minimal
            $this->validate($request, [
                'USERNAME' => 'required',
                'PT_ID' => 'required',
                'PT_NAMA' => 'required',
                'GUDANG' => 'required',
                'DARI_DEPT_ID' => 'required',
                'DARI_DEPT_NAMA' => 'required',
                'STATUS' => 'required',
                'NOTRANS' => 'required'
            ]);

            $status = $request->input('STATUS');
            $username = $request->input('USERNAME');
            $noBst = $request->input('NOTRANS');
            $ptId = $request->input('PT_ID');
            $ptNama = $request->input('PT_NAMA');
            $gudang = $request->input('GUDANG');
            $area = $request->input('AREA');
            //Username Login
            $dariDeptId = $request->input('DARI_DEPT_ID');
            $dariDeptNama = $request->input('DARI_DEPT_NAMA');
            
            $kedeptId = $request->input('KE_DEPT_ID');
            $kedeptNama = $request->input('KE_DEPT_NAMA');
            $ket = $request->input('KETERANGAN');
            $datetime = date('Y-m-d H:i:s');
            $date = date('Y-m-d'); // Bisa dari requestnya 
            // $datetimepost = $request->input('TANGGAL');
            // $datepost = date('Y-m-d', strtotime($datetimepost));
            //$total = $request->input('TOTAL');
            $newpt = $ptId == '1' ? 'ERA' : 'ERI'; 

            //Set variabel
            $this->_setVariable('BST', $newpt, $ptNama, $gudang, $dariDeptId, $dariDeptNama, $noBst, $username, $status, $ket);

            $data = [
                'STATUS' => 2,
                'TERIMA_FINAL' => $username,
                'TERIMA_FINAL_TANGGAL' => $datetime,
                'LAST_UPDATE' => $datetime
            ];

            $newstatus = 'KIRIM';

            //Perlukah cari barcode berdasarkan join dan where dari dept/ke dept || Perbaiki seleksi barcode
            $resbarcode = DB::table('tes.barcode_pellet')
            ->join('tes.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ?', [$newpt, $gudang, $dariDeptId, $newstatus, $noBst])
            ->selectRaw('barcode_pellet_det.BARCODE')
            ->get();
            $barcode = $resbarcode->toArray();
            //$listbarcode = array_column($barcode, 'BARCODE');
            //Perlu transaction
            //DB::beginTransaction();

            $update = Bst::where('NO_BST', $noBst)->update($data);
            //Bisa juga ditaruh diatas update bst
            foreach($barcode as $row){
                DB::statement(DB::raw("SET @AKSI = 'TAMBAH'"));
                DB::table('tes.barcode_pellet')
                    ->where('BARCODE',  $row->BARCODE)
                    ->update(['LAST_UPDATE' => $datetime]);
            }

            //$update = DB::table('tes.barcode_pellet')->whereIn('BARCODE',  $listbarcode)->update(['LAST_UPDATE' => $datetime]);
            //End transaction

            if ($update) {
                $out = [
                    'message' => 'success',
                    'result' => $data
                ];
                $code = 201;
            } else {
                $out = [
                    'message' => 'failed',
                    'result' => $data
                ];
                $code = 404;
            }

            return response()->json($out, $code);

        }
    }

    public function kirimBst(Request $request) // Jika status kirim
    {
        if($request->methode('post')){

            $status = $request->input('STATUS');
            $username = $request->input('USERNAME');
            $noBst = $request->input('NOTRANS');
            $ptId = $request->input('PT_ID');
            $ptNama = $request->input('PT_NAMA');
            $gudang = $request->input('GUDANG');
            //Username Login
            $dariDeptId = $request->input('DARI_DEPT_ID');
            $dariDeptNama = $request->input('DARI_DEPT_NAMA');
            $kedeptId = $request->input('KE_DEPT_ID');
            $kedeptNama = $request->input('KE_DEPT_NAMA');
            $ket = $request->input('KETERANGAN');
            $datetime = date('Y-m-d H:i:s');
            $date = date('Y-m-d'); // Bisa dari requestnya 
            $datetimepost = $request->input('TANGGAL');
            $datepost = date('Y-m-d', strtotime($datetimepost));
            $total = $request->input('TOTAL');
            $newpt = $pt == '1' ? 'ERA' : 'ERI'; 

            //Set variabel
            $this->_setVariable('BST', $newpt, $ptNama, $gudang, $dariDeptId, $dariDeptNama, $noBst, $username, $status, $ket);

            $bst = Bst::selectRaw('CAST(MAX(RIGHT(tes.bst_pellet.NO_BST,2)) AS SIGNED) AS LAST_NO')->whereRaw('tes.bst_pellet.PT_ID = ? AND tes.bst_pellet.DARI_DEPT_ID = ? AND tes.bst_pellet.TANGGAL = ?', [$newpt, $dariDeptId, $date])->first();
    
            //$ptId = $pt == 'ERA' ? '1' : '2';
            $n_tahun = date('Y', strtotime($date));
            $n_bulan = date('m', strtotime($date));
            $n_tanggal = date('d', strtotime($date));
            
            if($bst){
                $no = $bst->LAST_NO;
                $no++;
                $NoTrans = $ptId . '/' . $dariDeptId . '/BST/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  sprintf("%02s", $no);
            } else {
                $NoTrans = $ptId . '/' . $dariDeptId . '/BST/' . $n_tahun . '/' . $n_bulan . '/' .  $n_tanggal . '/' .  '01';
            }
    
            $data = [
                'NO_BST' => $noBst,
                'TANGGAL_BUAT' => $datetime,
                'TANGGAL' => $date,
                'PT_ID' => $newpt,
                'PT_NAMA' => $ptNama,
                'GUDANG' => $gudang,
                'DARI_DEPT_ID' => $dariDeptId,
                'DARI_DEPT_NAMA' => $dariDeptNama,
                'KE_DEPT_ID' => $kedeptId,
                'KE_DEPT_NAMA' => $kedeptNama,
                'TOTAL' => $total,
                'KETERANGAN' => $ket,
                'STATUS' => 1,
                'USERNAME' => $username,
                'LAST_UPDATE' => $datetime
            ];
    
            $insert = Bst::create($data);

            $newstatus = 'TERIMA';

            //Perlukah cari barcode berdasarkan join dan where dari dept/ke dept
            $resbarcode = DB::table('tes.barcode_pellet')
            ->join('tes.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.STATUS = ?', [$newpt, $gudang, $newstatus])
            ->selectRaw("count(*) as TOTAL, barcode_pellet_det.*, barcode_pellet.KODE_PELLET, barcode_pellet.NAMA_PELLET")
            ->get();
            $barcode = $resbarcode->toArray();

            foreach($barcode as $row){
                $items[] = [
                    'NO_BST' => $noBst,
                    'KODE_PELLET' => $row->KODE_PELLET,
                    'NAMA_PELLET' => $row->NAMA_PELLET,
                    'NAMA_LABEL' => $row->NAMA_LABEL,
                    'QTY' => $row->TOTAL,
                    'SATUAN' => 'SAK',
                    'KETERANGAN' => ''
                ];
            }

            //dd($barcode);

            if ($insert) {
                $out = [
                    'message' => 'success',
                    'result' => $data
                ];
                $code = 201;
            } else {
                $out = [
                    'message' => 'failed',
                    'result' => $data
                ];
                $code = 404;
            }
    
            return response()->json($out, $code);
        }
    }


    
}
