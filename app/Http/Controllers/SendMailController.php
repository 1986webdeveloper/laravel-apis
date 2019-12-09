<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\GeneralMail;
use Mail;

class SendMailController extends Controller
{
    public function sendMailApi(Request $request)
    {
        if ($request->input('email_subject') && $request->input('email_to') ) {

            $data = array(
                'subject' => $request->input('email_subject'),
                'template_name' => $request->input('template_name'),
                'from_name' => $request->input('from_name'),
                'from_email' => $request->input('from_email'),
            );
            if ($request->input('parameters')) {
                $extra_variable = (array) json_decode($request->input('parameters'));
                $data = array_merge($data, $extra_variable);
            }
            // print_r($data);
            // exit;
            $mailType = $request->input('email_type');
            // $model_name = '\\App\\Mail\\' . $mailType;
            // $model = new $model_name($data);
            Mail::to($request->input('email_to'))->send(new GeneralMail($data));
            $response = array(
                'meta' => array(
                    'status' => 200,
                    'code' => 0,
                    'message' => 'mail send successfully'
                ),
                'data' => []
            );
            $status = 200;
            return response(json_encode($response), $status);
        } else {
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
        return response(json_encode($response), $status);
    }
}
