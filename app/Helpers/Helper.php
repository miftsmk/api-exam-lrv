<?php
namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Helper {
    static function available_exam($user_id) {
        return DB::select('SELECT ex.id,ex.questiongroup_id,qg.subject_id,sj.name subject_name,qg.examgrouptype_id,egt.name examgrouptype_name,qg.kode exam_code,ex.active,ex.`show`,ex.startdatetime,ex.duration,ex.sesi,qg.`level`,qg.`group`,qg.tot_question FROM exams ex
        LEFT JOIN questiongroups qg ON qg.id=ex.questiongroup_id
        LEFT JOIN subjects sj ON sj.id=qg.subject_id
        LEFT JOIN examgrouptypes egt ON egt.id=qg.examgrouptype_id
        WHERE egt.`status` = ? AND ex.id NOT IN (SELECT exam_id FROM examstudents esd WHERE esd.user_id=?)', [1,$user_id]);

    // [
    //         {
    //             "id": 2,
    //             "questiongroup_id": 2,
    //             "subject_id": 8,
    //             "subject_name": "Pendidikan Pancasila dan Kewarganegaraan",
    //             "examgrouptype_id": 4,
    //             "examgrouptype_name": "PSAT 2022/2023",
    //             "exam_code": "2/PAS-CAT/X.2/S/2023",
    //             "active": 1,
    //             "show": 1,
    //             "startdatetime": "2023-05-29 09:15:00",
    //             "duration": 90,
    //             "sesi": 0,
    //             "level": "X",
    //             "group": "[\"AKL\",\"PM\",\"MPLB\",\"PPLG\",\"TJKT\"]",
    //             "tot_question": 40
    //         }
    // ]
    }

    static function ongoing_exam($user_id) {
        $date = Carbon::now()->toDateTimeString();
        $res = DB::select('SELECT id,user_id,es.exam_id,es.exam_id,es.starttime,es.endtime,es.e_number active_number
        FROM examstudents es
        WHERE es.user_id = ? AND score IS NULL', [$user_id]);
        if ($res) {
            $res[0]->status = $date >= $res[0]->endtime ? 'overtime' : 'ongoing';
        }
        return $res ? $res[0] : null;
        // {
        //     "id": 1,
        //     "user_id": 3,
        //     "exam_id": 3,
        //     "starttime": "2023-11-15 07:34:33",
        //     "endtime": "2023-11-15 08:34:42",
        //     "active_number": 1,
        //     "question": null,
        //     "scoring": null,
        //     "score": null,
        //     "temp_score": null,
        //     "status": "ongoing"
        // }
    }

    static function get_exam($exam_id,$class_id,$sesi) {
        $class = DB::table('classes')->where('id', $class_id)->first();
        $grouptype = DB::table('examgrouptypes')->where('status', 1)->first();
        // return $class->level;
        $res = DB::select('SELECT ex.id,ex.questiongroup_id,ex.active,ex.`show`,ex.startdatetime,ex.duration,ex.sesi,qg.kode,qg.examgrouptype_id,qg.`level`,qg.`group`,qg.tot_question 
        FROM exams ex
        LEFT JOIN questiongroups qg ON qg.id=ex.questiongroup_id
        WHERE ex.id = ? AND qg.`level` = ? AND qg.`group` LIKE ? AND ex.active = ? AND ex.show = ? AND (ex.sesi = ? OR ex.sesi=?) AND qg.examgrouptype_id = ?', [$exam_id,$class->level,'%'.$class->group.'%',1,1,0,$sesi,($grouptype ? $grouptype->id : null)]);
        return count($res) ? $res[0] : false;
    }

    static function generate_exam($qg_id,$user_id,$exam_id,$duration) {
        // return $qg_id;
        // $dtq =  DB::select('SELECT q.id,q.ans,q.score,q.random rand,q.random_qt rand_qt FROM questions q
        // WHERE q.questiongroup_id = ?
        // ORDER BY q.id ASC', [$qg_id]);
        $dtq =  DB::select('SELECT q.id,q.ans `key`,q.score,q.random_qt rand_qt FROM questions q
        WHERE q.questiongroup_id = ?
        ORDER BY q.id ASC', [$qg_id]);
        // $dataquestion = new Collection($dtq);
        $arr_dtq = [];
        $arr_dtq_rand = [];
        foreach ($dtq as $key => $value) {
            // $value->shf = $value->rand=='Y' ? 1 : 0;
            // $value->shf = Helper::shuffle_alphabet(10);
            $value->shf = null;
            $value->point = 0;
            $value->ans = null;
            if ($value->rand_qt == 'N') {
                unset($value->rand_qt);
                $arr_dtq[$key+1] = $value;
            } else {
                unset($value->rand_qt);
                $arr_dtq_rand[] = $value;
            }
        }
        shuffle($arr_dtq_rand);
        $no = 0;
        foreach ($dtq as $key => $value) {
            if (!isset($arr_dtq[$key+1])) {
                $arr_dtq[$key+1] = $arr_dtq_rand[$no];
                $no++;
            }
        }

        $now = Carbon::now();
        $starttime = $now->toDateTimeString();
        $endtime = $now->addMinutes($duration)->toDateTimeString();
        // $arr_dtq > generate exam random 
        $arr_val = [
            'user_id' => $user_id, 
            'exam_id' => $exam_id,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'duration' => $duration,
            'student_question' => json_encode($arr_dtq)
        ];
        // $id = DB::table('examstudents')->insertGetId($arr_val);
        $transactionResult = DB::transaction(function() use ($user_id,$arr_val) {
            $id = DB::table('examstudents')->insertGetId($arr_val);
            $affected = DB::table('users')->where('id', $user_id)->update(['onexam' => 1]);
            return $id;
        });
        return $transactionResult;

        // return $arr_dtq;
        // return strlen(json_encode($arr_dtq));
        // return DB::table('questions')->select('id','q_type','ans','score','random','random_qt')->where('questiongroup_id', $qg_id);
    }

    static function check_exam_status($exam_id,$user_id) {
        // jika sudah mengerjakan
        $count = DB::table('examstudents')->where('exam_id', $exam_id)->where('user_id', $user_id)->count();
        if (!$count) {
            // jika masih mengerjakan
            $count = DB::table('examstudents')->whereNotNull('submit_time')->where('user_id', $user_id)->count();
        }
        return $count;
    }

    static function get_question($number,$arr_qt,$examstudentid,$answer) {
        $q_dt = DB::table('questions')
            ->select('id','questiongroup_id','img_q1','img_q1','audio_q1','question','ans_a','ans_b','ans_c','ans_d','ans_e','img_ans_a','img_ans_b','img_ans_c','img_ans_d','img_ans_e','ans_esy','q_type','ans','score','random','random_qt')
            ->where('id', $arr_qt[$number]['id'])->first();
        $numb_ans = 5;
        if ((is_null($q_dt->ans_e) || $q_dt->ans_e=='') && (is_null($q_dt->img_ans_e) || $q_dt->img_ans_e=='')) {
            $numb_ans = 4;
        }
        if ((is_null($q_dt->ans_d) || $q_dt->ans_d=='') && (is_null($q_dt->img_ans_d) || $q_dt->img_ans_d=='') && (is_null($q_dt->ans_e) || $q_dt->ans_e=='') && (is_null($q_dt->img_ans_e) || $q_dt->img_ans_e=='')) {
            $numb_ans = 3;
        }
        if (is_null($arr_qt[$number]['shf'])) {
            $arr_qt[$number]['shf'] = ($q_dt->random == 'Y') ? Helper::shuffle_alphabet($numb_ans,true) : Helper::shuffle_alphabet($numb_ans,false) ;
        }

        $helpme = str_split(Helper::shuffle_alphabet($numb_ans,false));
        $helpme_rand = str_split($arr_qt[$number]['shf']);
        $arr_question = [
            'number' => $number,
            'img_q1' => $q_dt->img_q1,
            'audio_q1' => $q_dt->audio_q1,
            'question' => $q_dt->question,
            'score' => $q_dt->score
        ];

        // $arr_qt[$number]['point'] = 0;
        if ($answer) {
            $arr_qt[$number]['point'] = 0;
            $arr_qt[$number]['ans'] = $answer;
        }
        
        // Cocokkan Jawaban
        foreach ($helpme as $k => $v) {
            $arr_question[$v] = $q_dt->{'ans_'.$helpme_rand[$k]};
            $arr_question['img_'.$v] = $q_dt->{'img_ans_'.$helpme_rand[$k]};
            if ($answer && strtolower($answer) == strtolower($v) && ($helpme_rand[$k] == strtolower($arr_qt[$number]['key']))) {
                
                $arr_qt[$number]['point'] = $arr_qt[$number]['score'];
            }
        }
        // $arr_question['number'] = $number;
        $arr_question['ans'] = $arr_qt[$number]['ans'];
        // update examstudents
        $affected = DB::table('examstudents')->where('id', $examstudentid)->update(['student_question' => $arr_qt,'e_number' => $number]);
        // return $arr_qt[$number];
        return $arr_question;
        // {
        //     "id": 68,
        //     "questiongroup_id": 2,
        //     "img_q1": null,
        //     "audio_q1": null,
        //     "question": "<div>Perwujudan waasan nusantara di bidang sosial budaya tampak dalam pernyataan....</div>                            \r\n                          ",
        //     "ans_a": "Pemilu harus dilaksanakan secara jurdil\r",
        //     "ans_b": "Ormas dan parpol yang berkembang harus berdasar Pancasila\r",
        //     "ans_c": "Dalam menyelesaikan masalah diutamakan melalui musyawarah mufakat\r",
        //     "ans_d": "Bahwa ideologi Indonesia adalah pancasila\r",
        //     "ans_e": "Bahwa perekonomian disusun sebagai usaha bersama berdasar asas kekeluargaan",
        //     "img_ans_a": null,
        //     "img_ans_b": null,
        //     "img_ans_c": null,
        //     "img_ans_d": null,
        //     "img_ans_e": null,
        //     "ans_esy": null,
        //     "q_type": null,
        //     "ans": "C",
        //     "score": 1,
        //     "random": "Y",
        //     "random_qt": "Y"
        // }
    }

    static function shuffle_alphabet($number,$shf) {
        $str = '';
        for ($i = 0; $i < $number; $i++) {
            $str .= chr(97+$i);
        }
        if (!$shf) {
            return $str;
        }
        return str_shuffle($str);
    }
}