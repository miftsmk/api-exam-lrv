<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\Helper;
use Carbon\Carbon;

class ExamController extends Controller {
    
    public function available_exams(Request $request) {
        if ($request->user['onexam']) {
            $resp = ['error' => 'Sedang mengerjakan Ujian'];
            return response()->json($resp, Response::HTTP_PRECONDITION_REQUIRED );
        }
        $exams = Helper::available_exam($request->user['id']);
        // $resp = ['data' => $request->all()];
        $resp = ['data' => $exams];
        return response()->json($resp, Response::HTTP_OK);
    }

    public function ongoing_exam(Request $request) {
        $exam = null;
        if ($request->user['onexam']) {
            $exam = Helper::ongoing_exam($request->user['id']);
        }
        // $rand = Helper::shuffle_alphabet(3);
        $resp = ['data' => $exam];
        return response()->json($resp, Response::HTTP_OK);
    }

    public function start_exam(Request $request) {
        // sudah pernah mengikuti?
        // return $request->user['class_id'];
        if ($request->user['onexam']) {
            $resp = ['error' => 'Sedang mengerjakan ujian'];
            return response()->json($resp, Response::HTTP_BAD_REQUEST);
        }
        $count = Helper::check_exam_status($request->exam_id,$request->user['id']);
        // return $count;
        // return $request->all();
        if (!$count && is_numeric($request->exam_id)) {
            // boleh diakses ?
            $exam = Helper::get_exam($request->exam_id,$request->user['class_id'],$request->user['sesi']);
            if($exam){
                // Buat Data Ujian Siswa
                $qt = Helper::generate_exam($exam->questiongroup_id,$request->user['id'],$exam->id,$exam->duration);
                $resp = ['data' => $qt];
                return response()->json($resp, Response::HTTP_OK);
            }
        }
        // $exam = Helper::get_exam($request->exam_id);
        
        $resp = ['error' => 'Ujian tidak tersedia / Ujian sedang berlangsung'];
        return response()->json($resp, Response::HTTP_BAD_REQUEST);
    }

    public function question(Request $request) {
        // pastikan status sedang ujian dan waktu belum habis
        if (!$request->user['onexam']) {
            $resp = ['error' => 'Tidak ada ujian yang sedang berlangsung'];
            return response()->json($resp, Response::HTTP_BAD_REQUEST);
        }

        $now = Carbon::now()->toDateTimeString();
        if ($now > $request->user['examdata']->endtime) {
            $resp = ['status' => '0', 'message' => 'Waktu telah habis'];
            return response()->json($resp, Response::HTTP_OK);
        }

        $resp = ['status' => '1', 'message' => 'ujian sedang berlangsung'];
        // # jika tidak ada post ambil soal
        $number = isset($request->number) ? $request->number : $request->user['examdata']->e_number;
        $doubt = isset($request->doubt) ? 1 : 0;
        $question = null;
        $questions = json_decode($request->user['examdata']->student_question,true);
        if (!isset($request->answer)) {
            $question = Helper::get_question($number,$questions,$request->user['examdata']->id,null,$doubt);
        } else {
            $jawaban = strtolower(substr(trim($request->answer),0,1));
            $question = Helper::get_question($number,$questions,$request->user['examdata']->id,$jawaban,$doubt);
        }
        // return $question;
        $resp['data'] = $question;
        return response()->json($resp, Response::HTTP_OK);
    }

    public function progress_list(Request $request) {
        $data = json_decode($request->user['examdata']->student_question,true);
        $res = [];
        foreach ($data as $key => $value) {
            $res[$key]['score'] = $value['score'];
            $res[$key]['answer'] = $value['ans'];
            $res[$key]['doubt'] = $value['doubt'];
        }
        $resp['data'] = $res;
        return response()->json($resp, Response::HTTP_OK);
    }

    public function finish(Request $request) {
        // return $request->user;
        if (!$request->user['onexam']) {
            $resp['error'] = "Tidak ada ujian yang sedang berlangsung";
            return response()->json($resp, Response::HTTP_BAD_REQUEST);
        }
        if (isset($request->approve) && $request->approve == 1) {
            $res = Helper::finish_exam($request->user['id'],$request->user['examdata']->id);
            $resp = ['status' => '1', 'message' => 'ujian berhasil diakhiri'];
            return response()->json($resp, Response::HTTP_OK);
        }
        $resp = ['status' => '0', 'message' => 'ujian batal diakhiri'];
        return response()->json($resp, Response::HTTP_OK);
        
    }

    public function logout(Request $request) {
        if ($request->user['id']) {
            // return $request->all();
            $affected = Helper::logout($request->user['id']);
            $resp = ['status' => '1', 'message' => 'Logout Berhasil'];
            return response()->json($resp, Response::HTTP_OK);
        }
        $resp = ['status' => '0', 'message' => 'Logout Gagal'];
        return response()->json($resp, Response::HTTP_OK);
    }
}
