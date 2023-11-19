<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class CheckLogin extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $header = $request->header('Authorization');
        if ($header){
            $decrypted = Crypt::decryptString($header);
            $dt = explode("|",$decrypted);
            if (count($dt) == 2) {
                $users = DB::select('select * from users where username = ? AND ROLE="S" AND islogin=?', [addslashes($dt[1]),1]);
                if($users) {
                    $user = $users[0];
                    if ($user->login_dt == $dt[0]) {
                        $resp = ['success' => 'Token Valid'];
                        return response()->json($resp, Response::HTTP_OK);
                    }
                }
            }
        } 
        $resp = ['failed' => 'Token InValid'];
        return response()->json($resp, Response::HTTP_UNAUTHORIZED);
    }
}
