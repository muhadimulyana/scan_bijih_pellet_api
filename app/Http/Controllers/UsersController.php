<?php

namespace App\Http\Controllers;

use App\Users;
use App\Http\Controllers\Controller;
//use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{

    public function checkPin($username, $pin)
    {
        $result = Users::whereRaw('User_Name = ? AND pin = ?', [$username, $pin])->select('*')->first();
        
        if($result){
            $out = [
                'message' => 'success',
                'code' => 200
            ];

        } else {

            $out = [
                'message' => 'Pin yang anda masukkan salah!',
                'code' => 404
            ];

        }

        return response()->json($out, $out['code'], [], JSON_NUMERIC_CHECK);
    }


}