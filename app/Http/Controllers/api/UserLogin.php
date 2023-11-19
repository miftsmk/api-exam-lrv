<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classs;
use App\Models\Room;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;

class UserLogin extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $clas = Classs::where('id', $request->user['class_id'])->first();
        $room = Room::where('id', $request->user['room'])->first();
        $datauser = new Collection($request->user);
        $datauser = $datauser->merge(['class' => $clas->makeHidden(['timestamp','id'])->toArray()]);
        $datauser = $datauser->merge(['room' => $room->makeHidden(['id'])->toArray()]);
        // $examdata = $datauser['examdata']; //student_question
        if (isset($datauser['examdata'])) {
            unset($datauser['examdata']->student_question);
        }
        
        $datauser = $datauser->except(['id','examgrouptype_id','password','pass_txt','timestamp','login_dt','islogin','role','class_id']);
        
        // return $request;
        return response()->json($datauser, Response::HTTP_OK);


        // {
        //     "username": "01.001",
        //     "name": "Adnan Wiryatama",
        //     "pict": null,
        //     "onexam": 0,
        //     "room": {
        //         "name": "Lab Barat",
        //         "proktor": "Mokhamad Miftakhurrohman"
        //     },
        //     "sesi": 1,
        //     "ipaddr": "127.0.0.1",
        //     "class": {
        //         "level": "X",
        //         "group": "PPLG",
        //         "classorder": "PPLG (RPL)"
        //     }
        // }
    }
}
