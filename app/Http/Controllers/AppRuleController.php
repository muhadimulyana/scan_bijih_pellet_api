<?php

namespace App\Http\Controllers;

use App\AppRule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

date_default_timezone_set("Asia/Jakarta");

class AppRuleController extends Controller
{
    public function show($uName, $packageName, $pageName, $ruleName)
    {
        $data = AppRule::whereRaw('user = ? and package_name = ? and page_name = ? and rule_name = ?', [$uName, $packageName, $pageName, $ruleName])->get();

        $out = [
            'message' => 'success',
            'result' => $data,
        ];

        return response()->json($out, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getRule(Request $request)
    {
        $this->validate($request, [
            'uName' => 'required',
            'packageName' => 'required',
            'pageName' => 'required',
            'ruleName' => 'required'
        ]);

        $uName = $request->input('uName');
        $packageName = $request->input('packageName');
        $pageName = $request->input('pageName');
        $ruleName = $request->input('ruleName');
        $currentTime = date('Y-m-d H:i:s');

        $data = AppRule::whereRaw('user = ? and package_name = ? and page_name = ? and rule_name = ?', [$uName, $packageName, $pageName, $ruleName])->selectRaw('user, package_name, page_name, rule_name, rule_access')->first();

        if($data){

            if($data->rule_access == 1){
                $out = [
                    'message' => 'success',
                    'result' => $data
                ];
            } else {
                $out = [
                    'message' => 'Anda tidak memiliki akses!',
                    'result' => $data
                ];
            }

            $data_update = ['updated_at' => $currentTime];

            $appRule = AppRule::where('user', $uName)
                ->where('package_name', $packageName)
                ->where('page_name', $pageName)
                ->where('rule_name', $ruleName)
                ->update($data_update);

            $code = 200;

        } else {

            $data_insert = [
                'user' => $uName,
                'package_name' => $packageName,
                'page_name' => $pageName,
                'rule_name' => $ruleName,
                'created_at' => $currentTime,
                'rule_access' => 0
            ];

            $insert = AppRule::create($data_insert);

            $out = [
                'message' => 'Anda tidak memiliki akses!',
                'result' => $data_insert
            ];

            $code  = 201;
        }

        return response()->json($out, $code, [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'uName' => 'required',
            'packageName' => 'required',
            'pageName' => 'required',
            'ruleName' => 'required',
        ]);

        $uName = $request->input('uName');
        $packageName = $request->input('packageName');
        $pageName = $request->input('pageName');
        $ruleName = $request->input('ruleName');

        $data = [
            'user' => $uName,
            'package_name' => $packageName,
            'page_name' => $pageName,
            'rule_name' => $ruleName,
        ];

        $insert = AppRule::create($data);

        if ($insert) {
            $out = [
                'message' => 'success',
                'result' => $data,
                'code' => 200,
            ];
        } else {
            $out = [
                'message' => 'failed',
                'result' => $data,
                'code' => 404,
            ];
        }

        return response()->json($out, [], JSON_NUMERIC_CHECK);
    }

    public function update(Request $request)
    {
        if ($request->method('patch')) {

            $this->validate($request, [
                'uName' => 'required',
                'packageName' => 'required',
                'pageName' => 'required',
                'ruleName' => 'required',
            ]);

            $uName = $request->input('uName');
            $packageName = $request->input('packageName');
            $pageName = $request->input('pageName');
            $ruleName = $request->input('ruleName');
            $currentTime = date("yy-m-d H:i:s");

            $data = ['updated_at' => $currentTime];

            $appRule = AppRule::where('user', $uName)
                ->where('package_name', $packageName)
                ->where('page_name', $pageName)
                ->where('rule_name', $ruleName)
                ->update($data);

            if ($update) {
                $out = [
                    'message' => 'success',
                    'result' => $data,
                    'code' => 200,
                ];

            } else {
                $out = [
                    'message' => 'failed',
                    'result' => $data,
                    'code' => 404,
                ];
            }

            return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
        };
    }
}
