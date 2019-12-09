<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/crud_manage', 'ApiController@crud_manage');
Route::post('/sendMail', 'SendMailController@sendMailApi'); 
Route::post('/sendPushNotification', 'SendPushNotificationController@sendPushNotificationApi'); 
