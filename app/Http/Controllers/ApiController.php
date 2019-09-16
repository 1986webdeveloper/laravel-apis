<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JWTFactory;
use JWTAuth;
use App\User;
use App\ProjectDonationPayments;
use App\UserCardInfo;
use Mail;
use QrCode;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\UserApiRequest;

use App\Http\Controllers\GeneralController AS General;

class ApiController extends Controller
{
    //

    public function crud_manage(UserApiRequest $request){
    	$input = $request->all();
   		$table = $input['table_name'];

		switch ($input['action']) {

		case 'add':

			$input = json_decode($input['parameters'],true);
			$userdetail = array();
			$returndata = DB::table($table);
			$returndata = $returndata->insert($input);
			$userdetail[] = $returndata;
			$message = $userGiftDeclarations.' '.'has been added successfully.';
			$jsonResponse =  General::jsonResponse(1,$message,$userdetail);
			return $jsonResponse;

		break;

		case 'edit':

            $id = json_decode($input['where'],true);
            $inputs = json_decode($input['parameters'],true);
			$userdetail = array();
			$i=0;
			foreach ($inputs as $key => $value) {
	            $returndata = DB::table($table);
	            $returndata = $returndata->where("id", $id[$i]['id']);
	            $returndata = $returndata->update($value);
				$userdetail[] = $returndata;
				$i++ ;
			}

			$message = $userGiftDeclarations.' '.'has been updated successfully.';
			$jsonResponse =  General::jsonResponse(1,$message,$userdetail);
			return $jsonResponse;

		break;

		case 'list':

				$id = json_decode($input['where'],true);
				$userdetail = array();
                $returndata = DB::table($table);

				if($input['fields']){
					//$returndata  = $returndata->select(DB::raw($input['fields']));
                    $temp = str_replace("'", "", $input['fields']);
                    $returndata  = $returndata->select(DB::raw($temp));
			    }

                if($request->has('page')){
					$page = $input['page'];
					$perpage = 10;
					$calc  = $perpage * $page;
					$start = $calc - $perpage;
                      $returndata = $returndata->skip($start)->take(10)->get();
                }else{
                    $returndata  = $returndata->get();
                }


			   /* $returndata  = $returndata->get();*/

				if($request->has('page')){

					$page = $input['page'];
					$next = "false";
					$message = $userGiftDeclarations.' '.'list.';
					$jsonResponse = General::jsonResponse(1,$message,$returndata,$next,'','form');
					return $jsonResponse;

			    }else{

				$message = $userGiftDeclarations.' '.'list.';
				$jsonResponse =  General::jsonResponse(1,$message,$returndata);
				return $jsonResponse;

			    }
		break;

		case 'view':


			$id = json_decode($input['where'],true);
			$returndata = DB::table($table);
			$returndata = $returndata->where($id[0])->get()->first();
			$message = $userGiftDeclarations.' '.'detail.';
			$jsonResponse =  General::jsonResponse(1,$message,$returndata);
			return $jsonResponse;


		break;

		case 'delete':

				$id = json_decode($input['where'],true);
				$inputs = json_decode($input['parameters'],true);
				$i=0;
				foreach ($inputs as $key => $value) {
					$returndata = DB::table($table);
					$returndata = $returndata->where("id", $id[$i]['id']);
					$returndata = $returndata->update($value);
					$i++ ;
				}

				$message = $userGiftDeclarations.' '.'has been deleted successfully.';
				$jsonResponse = General::jsonResponse(0,$message,[]);
				return $jsonResponse;


		break;
		}


    }
}
