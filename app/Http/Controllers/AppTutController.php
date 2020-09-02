<?php

namespace App\Http\Controllers;

use App\AppTut;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\DB;

class AppTutController extends Controller
{

    public function getTutor(Request $request)
    {
        $package_name = $request->input('package_name');

        $app = AppTut::selectRaw('CAST(id as CHAR) AS id_tut, package_name, tut_name, link, rev')->where('package_name', $package_name)->get();

        if($app){

            $out = [
                'message' => 'success',
                'result' => $app,
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



}