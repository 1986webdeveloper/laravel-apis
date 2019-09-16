<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Exceptions\HttpResponseException;

class GeneralController extends Controller
{
    public static function isEmpty($value){
        return (!empty($value) ? $value : '');
    }

    public static function jsonResponse($status, $message, $data ='', $next=null, $jwt_token=null,$type='form'){

        //DAta Empty Then SEt Object Type DEfault Array 
        if(empty($data) || $data == ''){
            $data = (object)array();
        }
        if($jwt_token==null && $type =='form' && $next != null){
            return response()->json([
                "meta" => [
                    "status" => $status,
                    "message" => $message,
                    "next" => $next,
                ],
                "data" => $data
                    ], 200);
        }
        elseif($jwt_token==null && $type =='datelist' && $next != null){
            return response()->json([
                "meta" => [
                    "status" => $status,
                    "message" => $message,
                    "date" => $next,
                ],
                "data" => $data
                    ], 200);
        }
        elseif($jwt_token==null && $type =='datelist-form' && $next != null){
           
            return response()->json([
                "meta" => [
                    "status" => $status,
                    "message" => $message,
                    "date" => $next,
                ],
                "data" => array()
                    ], 200);
        }
        elseif($jwt_token==null && $type =='form'){
            return response()->json([
                "meta" => [
                    "status" => $status,
                    "message" => $message,
                ],
                "data" => $data
                    ], 200);
        }
        elseif($jwt_token==null && $type =='front_form' && $next != null){
            
            return response()->json([
                "meta" => [
                    "status" => $status,
                    "message" => $message,
                    "next" => $next,
                ],
                "data" => array()
                    ], 200);
        }
        elseif($type =='api'){
            throw new HttpResponseException(response()->json([
                "meta" => [
                    "status" => $status,
                    "message" => $message,
                ],
                "data" => $data
                    ], 200));
            /*echo response()->json([
                "meta" => [
                    "status" => $status,
                    "message" => $message,
                ],
                "data" => $data
                    ], 200);*/
            die;
        }
        else{
            return response()->json([
                "meta" => [
                    "status" => $status,
                    "message" => $message,
                ],                
                "data" => $data,
                "jwtToken" => $jwt_token
                    ], 200);
        }
    }
    
    public static function upload_file($file, $file_name, $folder_name, $new_name = "") {
        if (!empty($file)) {
            ini_set('upload_max_filesize', '100M');
            ini_set('post_max_size', '100M');
            ini_set('memory_limit', '100M');
            ini_set('max_execution_time', 100);
            $disk = Storage::disk('public');
            $name = $file->getClientOriginalName();
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $content = File::get($file);
            $new_name = time() . "_{$file_name}.{$ext}";
            $disk->put("public/storage/{$folder_name}/" . $new_name, $content);
        }
        return $new_name;
    }

    public static function get_file_src($file_name, $path = "user_images") {
         /*echo public_path('storage/public/storage/'.$path) .'/'. $file_name; exit;*/
        if (!empty($file_name) && file_exists(public_path('storage/public/storage/'.$path) .'/'. $file_name)) {
            return asset('public/storage/public/storage/' . $path .'/'. $file_name);
        } else  {
            return asset('public/storage/public/storage/' . $path . '/default.png');
        }
    }

      public static function date_mysql_format($date, $format = "m/d/Y") {
    if ($date == "" || $date == "00-00-0000") {
        return "";
    }
    if ($format == 'd/m/Y') {
        return date('Y-m-d', strtotime(preg_replace('/(\d{2})\/(\d{2})\/(\d{4})/', '$3-$2-$1', $date)));
    } else if ($format == 'm/d/Y') {
        return date('Y-m-d', strtotime(preg_replace('/(\d{2})\/(\d{2})\/(\d{4})/', '$3-$1-$2', $date)));
    } else if ($format == 'Y/m/d') {
        return date('Y-m-d', strtotime(preg_replace('/(\d{4})\/(\d{2})\/(\d{2})/', '$1-$2-$3', $date)));
    }
    return date('Y-m-d', strtotime($date));
  }

}
