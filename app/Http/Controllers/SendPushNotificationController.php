<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Edujugon\PushNotification\PushNotification;

class SendPushNotificationController extends Controller
{
    public function sendPushNotificationApi(Request $request)
    {
        if($request->input('title') && $request->input('body') && $request->input('device_type') && $request->input('device_token')){
            $data = (!empty($request->input('data'))) ? json_decode($request->input('data')) : [];
            
            try{
                if($request->input('device_type') == 'android'){
                    $push = new PushNotification('fcm');
                    $push->setMessage([
                            'notification' => [
                                'title'=>$request->input('title'),
                                'body'=>$request->input('body'),
                                'sound' => 'default'
                            ],
                            'data' => $data
                        ])
                    ->setDevicesToken([$request->input('device_token')])
                    ->send();
                }
                if($request->input('device_type') == 'Ios'){
                    $push = new PushNotification('apn');
                    $push->setMessage([
                        'aps' => [
                            'alert' => [
                                'title'=>$request->input('title'),
                                'body'=>$request->input('body'),
                            ],
                            'sound' => 'default',
                            'badge' => 1
            
                        ],
                        'extraPayLoad' => [
                            'custom' => $data,
                        ]
                    ])
                    ->setDevicesToken([$request->input('device_token')])
                    ->send();
                }
                $response = array(
                    'meta' => array(
                        'status' => 200,
                        'code' => 1,
                        'message' => 'notification push successfully'
                    ),
                    'data' => []
                );
                $status = 200;
                return response(json_encode($response), $status);
            }
            catch(Exception $e){
                print_r($e);
                $response = array(
                    'meta' => array(
                        'status' => 500,
                        'code' => 0,
                        'message' =>$e->getMessage()
                    ),
                    'data' => []
                );
                $status = 500;
                return response(json_encode($response), $status);
            }
        }else{
            $response = array(
                'meta' => array(
                    'status' => 400,
                    'code' => 0,
                    'message' => 'please provide valid data'
                ),
                'data' => []
            );
            $status = 400;
            return response(json_encode($response), $status);
        }
    }
}
