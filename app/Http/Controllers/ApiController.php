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
   		$tableName = ucfirst(mb_substr($input['table_name'], 0, -1));
   		$table = $input['table_name'];

		switch ($input['action']) {

		case 'add':

			$input = json_decode($input['parameters'],true);
			$userdetail = array();
			$returndata = DB::table($table);
			$returndata = $returndata->insert($input);
			$userdetail[] = $returndata;
			$message = $tableName.' '.'has been added successfully.';
			$jsonResponse =  General::jsonResponse(1,$message,$userdetail);
			return $jsonResponse;

		break;

		case 'edit':

            $where = json_decode($input['where'],true);
            $inputs = json_decode($input['parameters'],true);
			$userdetail = array();
			$i = 0;

			foreach ($inputs as $key => $value) {
	            $returndata = DB::table($table);
	            $returndata = $returndata->where("id", $where[$i]['id']);
	            $returndata = $returndata->update($value);
				$userdetail[] = $returndata;
				$i++ ;
			}

			$message = $tableName.' '.'has been updated successfully.';
			$jsonResponse =  General::jsonResponse(1,$message,$userdetail);
			return $jsonResponse;

		break;

		case 'list':

				$id = json_decode($input['where'],true);
				$userdetail = array();
                $returndata = DB::table($table);

				if($input['fields']) {
					//$returndata  = $returndata->select(DB::raw($input['fields']));
                    //$fields = str_replace("'", "", $input['fields']);
                    $returndata  = $returndata->selectRaw($input['fields']);
			    }

                if($input['page']) {
					$page = $input['page'];
					$perpage = 10;
					$calc  = $perpage * $page;
					$start = $calc - $perpage;
                      $returndata = $returndata->skip($start)->take(10)->get();
                }else{

					if($input['count']) {
					   $returndata  = $returndata->count();
					}
					if($input['max']) {
						$max = str_replace("'", "", $input['max']);
					    $returndata  = $returndata->max($max);
					}
					if($input['avg']) {
						$avg = str_replace("'", "", $input['avg']);
					    $returndata  = $returndata->exists();
					}
					if($input['groupby']) {
						$groupby = str_replace("'", "", $input['groupby']);
					    $returndata  = $returndata->groupBy($groupby);
					}

					if($input['orderby']) {
						//test1
						$orderby = str_replace("'", "", $input['orderby']);
						$returndata  = $returndata->orderByRaw($orderby);

						//test2
						/*$orderby = str_replace("'", "", $input['orderby']);
						$orderby = explode(',', $orderby);
						$returndata  = $returndata->orderByRaw($orderby[0].' '.$orderby[1]);*/
						
					    /*$orderby = str_replace("'", "", $input['orderby']);
					    $returndata  = $returndata->orderBy($orderby);*/
					}
					
					$returndata  = $returndata->get();
					if($input['get_single_row']) {
					   $returndata  = $returndata->first();
					}
                }

			   /* $returndata  = $returndata->get();*/
				if($input['page']){
       				$page = $input['page'];
					$next = "false";
					$message = $tableName.' '.'list.';
					$jsonResponse = General::jsonResponse(1,$message,$returndata,$next,'','form');
					return $jsonResponse;
			    }else{
    				$message = $tableName.' '.'list.';
    				$jsonResponse =  General::jsonResponse(1,$message,$returndata);
    				return $jsonResponse;
			    }
		break;

		case 'view':


			$where = json_decode($input['where'],true);
			$returndata = DB::table($table);
			$returndata = $returndata->where($where[0])->get()->first();
			$message = $tableName.' '.'detail.';
			$jsonResponse =  General::jsonResponse(1,$message,$returndata);
			return $jsonResponse;


		break;

		case 'delete':

				$where = json_decode($input['where'],true);
				$inputs = json_decode($input['parameters'],true);
				$i = 0;
				foreach ($inputs as $key => $value) {
					$returndata = DB::table($table);
					$returndata = $returndata->where("id", $where[$i]['id']);
					$returndata = $returndata->update($value);
					$i++ ;
				}

				$message = $tableName.' '.'has been deleted successfully.';
				$jsonResponse = General::jsonResponse(0,$message,[]);
				return $jsonResponse;


		break;
		}


    }
}
