<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\Helper;

class ExamController extends Controller {
    
    public function available_exams(Request $request) {
        $exams = Helper::available_exam($request->user['id']);
        // $resp = ['data' => $request->all()];
        $resp = ['data' => $exams];
        return response()->json($resp, Response::HTTP_OK);
    }

    public function ongoing_exam(Request $request) {
        $exam = Helper::ongoing_exam($request->user['id']);
        // $rand = Helper::shuffle_alphabet(3);
        $resp = ['data' => $exam];
        return response()->json($resp, Response::HTTP_OK);
    }

    public function start_exam(Request $request) {
        // sudah pernah mengikuti?
        // return $request->user['class_id'];
        $count = Helper::check_exam_status($request->exam_id,$request->id);
        if (!$count && is_numeric($request->exam_id)) {
            // boleh diakses ?
            $exam = Helper::get_exam($request->exam_id,$request->user['class_id'],$request->user['sesi']);
            if($exam){
                // Buat Data Ujian Siswa
                $qt = Helper::generate_exam($exam->questiongroup_id);
                $resp = ['data' => $qt];
                return response()->json($resp, Response::HTTP_OK);
            }
        }
        // $exam = Helper::get_exam($request->exam_id);
        
        $resp = ['error' => 'Exam Unavailable'];
        return response()->json($resp, Response::HTTP_BAD_REQUEST);
    }

    public function get_question(Request $request) {

    }
}
