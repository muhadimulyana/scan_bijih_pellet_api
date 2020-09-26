<?php

namespace App\Http\Controllers;

use App\AppInfo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class AppInfoController extends Controller
{
    public function show($packageName)
    {

        $data = AppInfo::where('package_name', $packageName)->get();

        $out = [
            'message' => 'success',
            'result' => $data,
            'code' => 200,
        ];

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function checkUpdate(Request $request)
    {
        $this->validate($request, [
            'packageName' => 'required',
            'version' => 'required',
        ]);

        $packageName = $request->input('packageName');
        $version = $request->input('version');

        try {
            //Select untuk versi
            $appInfo = AppInfo::whereRaw('package_name = ?', [$packageName])->selectRaw('*')->first();
            
            if($appInfo){
                
                if($appInfo->version == $version){
                    $out = [
                        'message' => 'Tidak ada update',
                        'result' => [],
                        'status' => false,
                        'code' => 200
                    ];
                } else {
                    $out = [
                        'message' => $appInfo->message,
                        'result' => $appInfo,
                        'status' => true,
                        'code' => 200
                    ];
                }
                
            } else {
                $out = [
                    'message' => 'Aplikasi tidak ditemukan',
                    'result' => [],
                    'status' => false,
                    'code' => 200
                ];
            }

        } catch (QueryException $e){
            $out = [
                'message' =>  'Error: [' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'result' => [],
                'status' => false,
                'code' => 500
            ];
        } 

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }


    //Tidak dipakai
    public function store(Request $request)
    {

        $this->validate($request, [
            'packageName' => 'required',
            'appName' => 'required',
            'version' => 'required'
        ]);

        $packageName = $request->input('packageName');
        $appName = $request->input('appName');
        $version = $request->input('version');
        $message = $request->input('message');
        $link = $request->input('link');
        $level = $request->input('level');
        $isRegist = $request->input('isRegister');


        $data = [
            'package_name' => $packageName,
            'app_name' => $appName,
            'version' => $version,
            'message' => $message,
            'link' => $link
        ];

        $insert = AppInfo::create($data);

        if ($insert) {
            $out = [
                "message" => "success",
                "results" => $data,
                "code" => 201,
            ];
        } else {
            $out = [
                "message" => "failed",
                "results" => $data,
                "code" => 404,
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }
}
