<?php

namespace App\Http\Controllers;

use App\DeviceInfo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class DeviceInfoController extends Controller
{
    //Tidak terpakai
    public function show($uDeviceId, $uAppId, $uName)
    {
        $data = DeviceInfo::whereRaw('device_id = ? and app_id = ? and user = ?', [$uDeviceId, $uAppId, $uName])->first();

        if($data){
            $out = [
                'message' => 'success',
                'result' => $data,
                'code' => 200,
            ];
        } else {
            $out = [
                'message' => 'empty',
                'result' => [],
                'code' => 404,
            ];
        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function getDevice(Request $request)
    {
        $this->validate($request, [
            'deviceId' => 'required',
            'appId' => 'required',
            'uName' => 'required'
        ]);

        $uDeviceId = $request->input('deviceId');
        $uAppId = $request->input('appId');
        $uName = $request->input('uName');
        

        try {

            $data = DeviceInfo::whereRaw('device_id = ? and app_id = ? and user = ?', [$uDeviceId, $uAppId, $uName])->first();
    
            if($data){

                if($data->active == 1){

                    $out = [
                        'message' => 'success',
                        'status' => true,
                        'isRegistered' => true,
                        'isActivated' => $data->active,
                        'result' => $data,
                        'code' => 200,
                    ];

                } else {

                    $out = [
                        'message' => 'Device belum diaktifkan, coba login kembali',
                        'status' => true,
                        'isRegistered' => true,
                        'isActivated' => $data->active,
                        'result' => $data,
                        'code' => 200,
                    ];

                }

            } else {
                $out = [
                    'message' => 'Device tidak terdaftar',
                    'status' => true,
                    'isRegistered' => false,
                    'isActivated' => 0,
                    'result' => [],
                    'code' => 200,
                ];
            }

        } catch (QueryException $e) {

            $out = [
                'message' =>  'Error: [' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'status' => false,
                'result' => [],
                'code' => 500
            ];

        }


        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'deviceId' => 'required',
            'appName' => 'required',
            'uName' => 'required',
            'deviceModel' => 'required',
            'packageName' => 'required'
        ]);

        
        $deviceId = $request->input('deviceId');
        $appName = $request->input('appName');
        $uName = $request->input('uName');
        $deviceModel = $request->input('deviceModel');
        $packageName = $request->input('packageName');

        
        try {
            
            $app_info = DB::table('android_data.app_info')->where('package_name', $packageName)->select('isRegister')->first();
    
            $active = 0;
            
            if($app_info){
                $active = $app_info->isRegister;
            } 
    
            $data = [
                'device_id' => $deviceId,
                'app_name' => $appName,
                'user' => $uName,
                'app_id' => $packageName,
                'device_model' => $deviceModel,
                'active' => $active,
                'created_date' => date('Y-m-d H:i:s')
            ];
            
            $insert = DeviceInfo::insertOrIgnore($data);

            if ($insert) {
                $out = [
                    'message' => 'Device berhasil didaftarkan',
                    'status' => true,
                    'code' => 201,
                ];
            } else {
                $out = [
                    'message' => 'Device sudah terdaftar, silakan login kembali',
                    'status' => false,
                    'code' => 200,
                ];
            }

        } catch ( QueryException $e ){
            $out = [
                'message' =>  'Error: [' . $e->errorInfo[1] . '] ' . $e->errorInfo[2],
                'status' => false,
                'code' => 500,
            ];
        }

        

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }


}
