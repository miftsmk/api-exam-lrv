<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class AuthLogin extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $messages = [
            'required'  => ':attribute harap di isi.',
            'min' => ':attribute tidak boleh kurang dari :min digit.',
            'max' => ':attribute tidak boleh lebih dari :max digit.'
            // 'unique'    => ':attribute sudah digunakan',
        ];

        $validation = Validator::make($request->all(),[ 
            // 'username' => 'required|unique:users, username',
            'username' => 'required|max:6|min:6',
            'password' => 'required'
        ],$messages);

        if($validation->fails()){
            return response()->json($validation->messages(), Response::HTTP_BAD_REQUEST);
        }

        // Validate account
        $users = DB::select('select * from users where username = ? AND ROLE="S"', [addslashes($request->username)]);
        if (!$users) {
            $resp = ['error' => 'username tidak terdaftar'];
            return response()->json($resp, Response::HTTP_BAD_REQUEST);
        }
        if(!password_verify($request->password, $users[0]->password)) {
            $resp = ['error' => 'username / password tidak sesuai'];
            return response()->json($resp, Response::HTTP_BAD_REQUEST);
        }
        if($users[0]->islogin) {
            $resp = ['error' => 'akun user terdeteksi sedang login, minta pada proktor untuk reset login'];
            return response()->json($resp, Response::HTTP_BAD_REQUEST);
        }

        $date = Carbon::now()->toDateTimeString();
        // $encrypted = Crypt::encryptString($date.'|'.$request->username);
        // $decrypted = Crypt::decryptString($encrypted);
        DB::table('users')->where('username', $request->username)->update(array('login_dt' => $date,'islogin' => 1,'ipaddr' => $request->ip())); 
        $encrypted = Crypt::encryptString($date.'|'.$request->username);
        $resp = ['token' => $encrypted];
        return response()->json($resp, Response::HTTP_OK);
        // return $encrypted;
    }
}
