<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $uName = $request->input('uName');
        $uPass = $request->input('uPass');
        //Bisa menggunakan function CallPE
        $login = DB::table('user_management.user_right')
            ->join('hrd.viewkaryawan', 'viewkaryawan.nik', '=', 'user_right.nik')
            ->join('hrd.m_cabang', 'viewkaryawan.Cabang', '=', 'm_cabang.Kode')
            //->join('erasystem_2012.whole_system', 'user_management.user_right.nik', '=', 'erasystem_2012.whole_system.Nik')
            ->whereRaw('BINARY user_management.user_right.User_Name = ? ', [$uName])
            ->selectRaw("user_right.nik, user_right.User_Name, user_right.Pass, viewkaryawan.Nama, viewkaryawan.Cabang, viewkaryawan.KoDep, viewkaryawan.Departemen, viewkaryawan.Kode_Jabatan AS KoJab, viewkaryawan.jabatan, viewkaryawan.anggota_group, viewkaryawan.Grup, viewkaryawan.Section, m_cabang.KaCab, (CASE WHEN m_cabang.Cabang = 'PT. Elite Recycling Indonesia Extention' THEN 'PT. Elite Recycling Indonesia' ELSE m_cabang.Cabang END) AS pt_nama, (CASE WHEN m_cabang.Kode = 'ERA 88' THEN 1 WHEN m_cabang.Kode = 'ERI' THEN 2 WHEN m_cabang.Kode = 'ERIX' THEN 2 WHEN m_cabang.Kode = 'EPI' THEN 3 ELSE 4 END) AS pt_id")
            ->first();

        if ($login) {

            $user = DB::table('erasystem_2012.whole_system')
            ->whereRaw('wh_sys_id = ? AND val1 = ?', ['BARCODE-ALL-ACCESS', $login->nik])
            ->first();

            $data = [
                "nik" => $login->nik,
                "User_Name" => $login->User_Name,
                "Pass" => $login->Pass,
                "Nama" => $login->Nama,
                "Cabang" => $login->Cabang,
                "KoDep" => $login->KoDep,
                "Departemen" => $login->Departemen,
                "KoJab" => $login->KoJab,
                "jabatan" => $login->jabatan,
                "anggota_group" => $login->anggota_group,
                "grup" => $login->grup,
                "section" => $login->section,
                "KaCab" => $login->KaCab,
                "pt_nama" => $login->pt_nama,
                "pt_id" => $login->pt_id,
                'user_status' => ($user) ? 1 : 0
            ];

            $out = [
                'message' => 'success',
                'result' => $data,
                'status' => true,
                'code' => 200,
            ];
        } else {
            $out = [
                'message' => 'User atau password salah!',
                'result' => [],
                'status' => false,
                'code' => 200,
            ];
        }

        return response()->json($out, $out['code']);
    }


}
