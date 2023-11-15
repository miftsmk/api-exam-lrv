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
        $res = DB::select('SELECT id,user_id,es.exam_id,es.exam_id,es.starttime,es.endtime,es.e_number active_number, es.student_question question, es.calc_question_score scoring, es.score, es.temp_score
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

    static function generate_exam($qg_id) {
        // return $qg_id;
        $dtq =  DB::select('SELECT q.id,q.q_type,q.ans,q.score,q.random,q.random_qt FROM questions q
        WHERE q.questiongroup_id = ?
        ORDER BY q.id ASC', [$qg_id]);
        // $dataquestion = new Collection($dtq);
        $arr_dtq = [];
        $arr_dtq_rand = [];
        foreach ($dtq as $key => $value) {
            if ($value->random_qt == 'N') {
                $arr_dtq[$key+1] = $value;
            } else {
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
        return $arr_dtq;
        // return DB::table('questions')->select('id','q_type','ans','score','random','random_qt')->where('questiongroup_id', $qg_id);
    }

    static function check_exam_status($exam_id,$user_id) {
        return DB::table('examstudents')->where('exam_id', $exam_id)->where('user_id', $user_id)->count();
    }

    static function shuffle_alphabet($number) {
        $str = '';
        for ($i = 0; $i < $number; $i++) {
            $str .= chr(97+$i);
        }
        return str_shuffle($str);
    }
}