<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

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
            'username' => 'required|min:5|max:5',
            'password' => 'required'
        ],$messages);
        if($validation->fails()){
            return response()->json($validation->messages(), Response::HTTP_BAD_REQUEST);
        }
        // Validate account

        return true;
    }
}
