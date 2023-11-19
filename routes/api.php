<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\AuthLogin;
use App\Http\Controllers\api\CheckLogin;
use App\Http\Controllers\api\UserLogin;
use App\Http\Controllers\api\ExamController;

use App\Http\Middleware\EnsureTokenIsValid;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// DEFAULT
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// TEST
Route::get('/', function () {
    return "Hello World!";
});


// API LOGIN (username, password) > return token

Route::post('/v1/login', AuthLogin::class);

Route::get('/v1/check', CheckLogin::class);

Route::middleware([EnsureTokenIsValid::class])->group(function () {
    // Route::get('/v1/user', function () {
    //     // ...
    // });
    Route::get('/v1/user', UserLogin::class);
    Route::get('/v1/available_exams', [ExamController::class, 'available_exams']);
    Route::get('/v1/ongoing_exam', [ExamController::class, 'ongoing_exam']);
    Route::post('/v1/start_exam', [ExamController::class, 'start_exam']);
    Route::post('/v1/question', [ExamController::class, 'question']);
    Route::get('/v1/progress', [ExamController::class, 'progress_list']);
    Route::post('/v1/finish', [ExamController::class, 'finish']);
    Route::get('/v1/logout', [ExamController::class, 'logout']);
});
