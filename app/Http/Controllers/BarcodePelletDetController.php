<?php

namespace App\Http\Controllers;

use App\BarcodePelletDet;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarcodePelletDetController extends Controller
{
    public function index()
    {
        $result = BarcodePelletDet::orderBY('TANGGAL')->get();

        return response()->json($result);
    }

    public function getlistBarcode($pt, $gudang, $dep, $status, $notrans)
    {
        $gudang = urldecode($gudang);
        $newstatus = $status == 'TERIMA' ? 'KIRIM' : 'KIRIM';
        $newpt = $pt == '1' ? 'ERA' : 'ERI';

        $result = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ?', [$newstatus, $notrans, '1'])
            ->selectRaw('barcode_pellet_det.BARCODE, barcode_pellet.KODE_PELLET, barcode_pellet.NAMA_PELLET, barcode_pellet.NAMA_LABEL, barcode_pellet.KG')
            ->get()->toArray();

        $bst = DB::table('erasystem_2012.bst_pellet')->where('NO_BST', $notrans)->first();

        if (count($result) == $bst->TOTAL) {

            $out = [
                'message' => 'success',
                'result' => $result,
            ];

        } else {

            $res = [];
            $list_bst = DB::table('erasystem_2012.bst_pellet_item')->where('NO_BST', $notrans)->get();
            $area = $bst->DARI_DEPT_AREA;

            foreach ($list_bst as $row) {

                $item = $row->KODE_PELLET;
                $jml_item = $row->QTY;

                $barcode = DB::table('erasystem_2012.barcode_pellet')
                    ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                        $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                    })
                    ->whereRaw('barcode_pellet_det.STATUS = ? AND barcode_pellet_det.NOTRANS = ? AND barcode_pellet.AKTIF = ? AND barcode_pellet.KODE_PELLET = ?', [$newstatus, $notrans, '1', $item])
                    ->selectRaw('COUNT(*) AS JML_BARCODE')
                    ->first();

                $pellet = DB::table('erasystem_2012.barcode_pellet')
                    ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                        $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                    })
                    ->whereRaw('barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.GUDANG = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.KODE_PELLET = ? AND barcode_pellet.AKTIF = ?', [$newpt, $gudang, $dep, $area, 'KIRIM', $item, '1'])
                    ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET")
                    ->first();

                $jml_barcode = $barcode->JML_BARCODE;
                $iterasi = $jml_item - $jml_barcode;

                for ($i = 1; $i <= $iterasi; $i++) {

                    $data[] = [
                        'BARCODE' => $item . ' (' . $i . ')',
                        'KODE_PELLET' => $item,
                        'NAMA_PELLET' => $pellet->NAMA_PELLET,
                        'NAMA_LABEL' => $pellet->NAMA_LABEL,
                        'KG' => 25,
                    ];
                }

            }

            //$resdata = (object) $data;
            $result = array_merge($result, $data);

            $out = [
                'message' => 'success',
                'result' => $result,
            ];

        }

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

        if ($check1) {
            $out = [
                'message' => 'Detail status barcode saat ini:',
                'result' => $check1,
                'isRegistered' => true,
                'status' => false,
                'code' => 200,
            ];
        } else {
            $out = [
                'message' => 'Barcode tidak terdaftar!',
                'result' => [],
                'isRegistered' => false,
                'status' => false,
                'code' => 200,
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function getkodePellet()
    {
        $result = DB::table('erasystem_2012.pellet')->select('KODE', 'NAMA', 'NAMA_LABEL')->get();
        $out = [
            'message' => 'success',
            'result' => $result,
        ];

        return response()->json($out, 200, []);
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

        }
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

    public function checkBarcodeNonAktif(Request $request)
    {
        $pt = $request->input('PT_ID');
        //$gudang = $request->input('GUDANG');
        $dept = 'QUA';
        $area = 'In Process';
        $barcode = $request->input('BARCODE');
        $newstatus = 'TERIMA';
        $newpt = $pt == '1' ? 'ERA' : ($pt == '2' ? 'ERI' : 'EPI');

        $check1 = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
            ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
            ->first();

        if ($check1) {

            $result = DB::table('erasystem_2012.barcode_pellet')
                ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                    $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                })
                ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $dept, $area, $newstatus, '1'])
                ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
                ->first();

            if ($result) {

                $out = [
                    'message' => 'success',
                    //'result' => $result,
                    'status' => true,
                    'code' => 200,
                    'isRegistered' => true,
                ];

            } else {

                $out = [
                    'message' => 'Barcode tidak tersedia untuk area ' . $area . '. Detail status barcode saat ini: ',
                    //'result' => $check1,
                    'status' => false,
                    'code' => 200,
                    'isRegistered' => true,
                ];

            }

        } else {

            $out = [
                'message' => 'Barcode tidak terdaftar!',
                //'result' => [],
                'status' => false,
                'code' => 200,
                'isRegistered' => false,
            ];

        }

        return response()->json($out, $out['code'], []);

    }

    public function nonAktifBarcode(Request $request)
    {
        $records = $request->all();

        $this->validate($request, [
            '*.USERNAME' => 'required',
            '*.KETERANGAN' => 'required',
            '*.BARCODE' => 'required',
        ]);

        $barcode = array_column($records, 'BARCODE');

        $data = [
            'USERNAME' => $records[0]['USERNAME'],
            'KETERANGAN' => $records[0]['KETERANGAN'],
            'AKTIF' => 0,
        ];

        DB::beginTransaction();

        try {

            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE', $barcode)->update($data);

            $out = [
                'message' => 'Submit sukses',
                'result' => [],
            ];
            $code = 201;
            DB::commit();

        } catch (QueryException $e) {

            $out = [
                'message' => 'Submit gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'result' => $data,
            ];
            $code = 500;
            DB::rollBack();

        }

        return response()->json($out, $code, []);
    }

    public function checkBarcodeUpdate(Request $request)
    {
        $pt = $request->input('PT_ID');
        //$gudang = $request->input('GUDANG');
        $dept = 'QUA';
        $area = 'In Process';
        $barcode = $request->input('BARCODE');
        $newstatus = 'TERIMA';
        $newpt = $pt == '1' ? 'ERA' : ($pt == '2' ? 'ERI' : 'EPI');

        $check1 = DB::table('erasystem_2012.barcode_pellet')
            ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
            })
            ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet.AKTIF = ?', [$barcode, '1'])
            ->selectRaw("barcode_pellet_det.PT_ID, barcode_pellet_det.PT_NAMA, barcode_pellet_det.GUDANG, barcode_pellet_det.DEPT_ID, barcode_pellet_det.DEPT_NAMA, barcode_pellet_det.DEPT_AREA, barcode_pellet_det.STATUS")
            ->first();

        if ($check1) {

            $result = DB::table('erasystem_2012.barcode_pellet')
                ->join('erasystem_2012.barcode_pellet_det', function ($join) {
                    $join->on('barcode_pellet.BARCODE', '=', 'barcode_pellet_det.BARCODE')->on('barcode_pellet.LAST_UPDATE', '=', 'barcode_pellet_det.TANGGAL');
                })
                ->whereRaw('barcode_pellet_det.BARCODE = ? AND barcode_pellet_det.PT_ID = ? AND barcode_pellet_det.DEPT_ID = ? AND barcode_pellet_det.DEPT_AREA = ? AND barcode_pellet_det.STATUS = ? AND barcode_pellet.AKTIF = ?', [$barcode, $newpt, $dept, $area, $newstatus, '1'])
                ->selectRaw("barcode_pellet.NAMA_LABEL, barcode_pellet.NAMA_PELLET, barcode_pellet.KODE_PELLET, barcode_pellet.KG")
                ->first();

            if ($result) {

                $out = [
                    'message' => 'success',
                    'result' => $result,
                    'status' => true,
                    'code' => 200,
                    'isRegistered' => true,
                ];

            } else {

                $out = [
                    'message' => 'Barcode tidak tersedia untuk area ' . $area . '. Detail status barcode saat ini: ',
                    'result' => [],
                    'status' => false,
                    'code' => 200,
                    'isRegistered' => true,
                ];

            }

        } else {

            $out = [
                'message' => 'Barcode tidak terdaftar!',
                'result' => [],
                'status' => false,
                'code' => 200,
                'isRegistered' => false,
            ];

        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function updateGradeBarcode(Request $request)
    {
        $records = $request->all();

        $this->validate($request, [
            //'*' => 'required|array',
            '*.USERNAME' => 'required',
            '*.PT_ID' => 'required',
            '*.PT_NAMA' => 'required',
            '*.GUDANG' => 'required',
            '*.BARCODE' => 'required',
            '*.KODE_PELLET' => 'required', //Kode pellet lama
            '*.NAMA_PELLET' => 'required', // Nama pellet lama
            '*.NAMA_LABEL' => 'required', // Nama label lama
            '*.KG' => 'required', // KG dari hasil scan
            '*.KODE_PELLET2' => 'required', // Kode pellet baru
        ]);

        // handle data bst
        $username = $records[0]['USERNAME'];
        $pt = $records[0]['PT_ID']; //PT dari user login
        $pt_nama = $records[0]['PT_NAMA']; // Same
        $gudang = $records[0]['GUDANG']; // Gudang dari pilihan dan dibuat default yg login
        $dept = 'QUA'; //Default
        $dept_nama = 'Quality Assurance'; //Default
        $newpt = $pt == '1' ? 'ERA' : ($pt == '2' ? 'ERI' : 'EPI');
        $barcode = array_column($records, 'BARCODE'); //Array barcode untuk nonaktif barcode lama

        $data_non = [
            'USERNAME' => $records[0]['USERNAME'],
            'AKTIF' => 0,
        ]; // Data non aktif barcode

        $max_old_barcode = DB::table('erasystem_2012.barcode_pellet')->selectRaw('CAST(MAX(RIGHT(BARCODE, 4)) AS SIGNED) AS LAST_NO')->whereRaw('DATE(TANGGAL) = ?', [date('Y-m-d')])->first(); // Get last no urut barcode hari ini

        if ($max_old_barcode) {
            $no_urut = $max_old_barcode->LAST_NO + 1; // Jika ada no urut hari ini tambahkan 1
        } else {
            $no_urut = 1; // Jika tidak ada set menjadi 1
        }

        foreach ($records as $row) { // fetch and loop record

            $year_barcode = '20' . substr($row['BARCODE'], 22, 2); // digit year
            $month_barcode = substr($row['BARCODE'], 25, 2); // digit month
            $day_barcode = substr($row['BARCODE'], 28, 2); //digit day
            $group1 = substr($row['BARCODE'], 16, 1); //digit group 1
            $group2 = substr($row['BARCODE'], 17, 1); // digit group 2
            $mesin = substr($row['BARCODE'], 19, 2); // nomor/kode mesin

            $str_date_barcode = substr($row['BARCODE'], 22, 8); // Str tanggal untuk new barcode
            $date_barcode = $year_barcode . '-' . $month_barcode . '-' . $day_barcode; // tanggal barcode

            $pellet = DB::table('erasystem_2012.pellet')->select('NAMA_LABEL', 'NAMA')->where('KODE', $row['KODE_PELLET2'])->first(); //Get nama pellet dan label dari master kode pellet

            $new_barcode = $row['KODE_PELLET2'] . '.' . $row['PT_ID'] . '.' . $group1 . $group2 . '.' . $mesin . '.' . $str_date_barcode . '.' . sprintf("%04s", $no_urut); // Barcode baru

            $data_newbarcode[] = [
                'BARCODE' => $new_barcode,
                'TANGGAL_BUAT' => date('Y-m-d H:i:s'),
                'TANGGAL' => date('Y:m:d H:i:s'),
                'PT_ID' => $newpt,
                'PT_NAMA' => $pt_nama,
                'GUDANG' => $gudang,
                'DEPT_ID' => $dept,
                'DEPT_NAMA' => $dept_nama,
                'KODE_PELLET' => $row['KODE_PELLET2'],
                'NAMA_PELLET' => $pellet->NAMA,
                'NAMA_LABEL' => $pellet->NAMA_LABEL,
                'KG' => $row['KG'],
                'GROUP1' => $group1,
                'GROUP2' => $group2,
                'MESIN' => $mesin,
                'USERNAME' => $row['USERNAME'],
                'KETERANGAN' => 'Pengganti barcode ' . $row['BARCODE'],
                'LAST_UPDATE' => date('Y-m-d H:i:s'),
            ]; // Data new barcode yg diinsert

            $no_urut++; // Tambah no urut

            $data_log[] = [
                'TANGGAL' => date('Y-m-d H:i:s'),
                'BARCODE_BARU' => $new_barcode,
                'KODE_PELLET_BARU' => $row['KODE_PELLET2'],
                'NAMA_PELLET_BARU' => $pellet->NAMA,
                'LABEL_PELLET_BARU' => $pellet->NAMA_LABEL,
                'BARCODE_LAMA' => $row['BARCODE'],
                'KODE_PELLET_LAMA' => $row['KODE_PELLET'],
                'NAMA_PELLET_LAMA' => $row['NAMA_PELLET'],
                'LABEL_PELLET_LAMA' => $row['NAMA_LABEL'],
                'KETERANGAN' => 'Pengganti barcode ' . $row['BARCODE'],
            ]; // Data log

        }

        DB::beginTransaction();

        try {

            DB::statement(DB::raw("SET @UserLoginAktif='" . $username . "'")); // set variabel user login untuk barcode_pellet_det
            DB::table('erasystem_2012.barcode_pellet')->whereIn('BARCODE', $barcode)->update($data_non); //Nonaktifkan barcode
            DB::table('erasystem_2012.barcode_pellet')->insert($data_newbarcode); // Insert barcode baru
            DB::table('erasystem_2012.barcode_pellet_perubahan')->insert($data_log); // Insert log perubahan

            $out = [
                'message' => 'Submit sukses',
                'result' => [],
            ];
            $code = 201;
            DB::commit();

        } catch (QueryException $e) {

            $out = [
                'message' => 'Submit gagal: ' . '[' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'result' => [],
            ];
            $code = 500;
            DB::rollBack();

        }

        return response()->json($out, $code, []);

    }

}
