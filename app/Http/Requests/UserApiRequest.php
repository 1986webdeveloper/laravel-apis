<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Controllers\GeneralController AS General;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserApiRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'action' => 'required',
            'table_name' => 'required',
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'action.required' => 'Please enter action',
            'table_name.required' => 'Please enter table name.'
            
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        $errorData =  $validator->errors();
        $message = 'Validation Error';
        // print_r($errorData->messages);exit;
        // throw new HttpResponseException(response()->json([
        //     "meta" => [
        //         "status" => 2,
        //         "message" => $message,
        //     ],
        //     "data" => $errorData
        //         ], 200));
            return General::jsonResponse(2,$message,$errorData,'','',$type='api');
    }
}
