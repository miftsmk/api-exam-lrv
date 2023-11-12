<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
// use Symfony\Component\HttpFoundation\Request;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check Database


        // If Invalid
        // if ($request->input('token') !== 'my-secret-token') {
        //     return redirect('home');
        // }

        $header = $request->header('Authorization');
        if ($header){
            $decrypted = Crypt::decryptString($header);
            $dt = explode("|",$decrypted);
            if (count($dt) == 2) {
                $users = DB::select('select * from users where username = ? AND ROLE="S"', [addslashes($dt[1])]);
                if($users) {
                    $user = $users[0];
                    if ($user->login_dt == $dt[0]) {
                        // $request['user'] = $user;
                        // $request->add(['user' => $user]);
                        // $request->merge(['user' => $user]);
                        $request->merge($this->objectToArray($user));
                        // $req = new Request(['user' => $user]);
                        // $request->attributes->add(["foo" => "bar"]);
                        // return $user;
                        // $requests = Request::create(uri: 'my-api-address');
                        // $request->data->add(['key => 'value']); 
                        return $next($request);
                    }
                }
            }
        } 
        $resp = ['failed' => 'Token InValid'];
        return response()->json($resp, Response::HTTP_UNAUTHORIZED);
 
        // return $next($request);
    }

    private function objectToArray(&$object) {
        return @json_decode(json_encode($object), true);
    }
}
