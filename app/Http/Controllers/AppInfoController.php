<?php

namespace App\Http\Controllers;

use App\AppInfo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        //Select untuk versi
        $appInfo = AppInfo::whereRaw('package_name = ?', [$packageName])->selectRaw('package_name, version, message')->first();
        
        if($appInfo){
            
            if($appInfo->version == $version){
                $out = [
                    'result' => [],
                    'status' =>false,
                    'code' => 200
                ];
            } else {
                $out = [
                    'result' => $appInfo->message,
                    'status' => true,
                    'code' => 200
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
