<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classs;
use App\Models\Room;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function user(Request $request) {
        // return gettype($request);
        $clas = Classs::where('id', $request['class_id'])->first();
        $room = Room::where('id', $request['room'])->first();
        $request->merge(['class' => $clas->makeHidden(['timestamp','id'])->toArray()]);
        $request->merge(['room' => $room->makeHidden(['id'])->toArray()]);
        $dt = $request->except(['id','examgrouptype_id','password','pass_txt','timestamp','login_dt','islogin','role','class_id']);
        // return $dt;
        return response()->json($dt, Response::HTTP_OK);
    }
}
