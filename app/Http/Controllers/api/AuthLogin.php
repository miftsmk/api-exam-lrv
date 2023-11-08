<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator;

class AuthLogin extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // validate POST username & password
        $user = $request->username;
        $pass = $request->password;
        // $validatedData = $request->validate([
        //     'title' => ['required', 'unique:posts', 'max:255'],
        //     'body' => ['required'],
        // ]);

        $validator = Validator::make($data, [
                'field' => ['rule', 'another_rule'],
        ]);
        if($validator->fails()){
            return response()->json($validator->messages(), 200);
        }
        // Validate account

        return true;
    }
}
