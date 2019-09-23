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
            $jsonResponse =  General::jsonResponse(1,$input['message'],$userdetail);
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
            $jsonResponse =  General::jsonResponse(1,$input['message'],$userdetail);
            return $jsonResponse;

        break;

        case 'list':

            $userdetail = array();
            $returndata = DB::table($table);

            if($request->filled('where')) {

                $where = json_decode($input['where'],true);
                foreach ($where as $key => $value) {
                    $value = array_map('htmlentities', $value);
                    $cond = html_entity_decode(json_encode($value,true));
                    $returndata->where([$cond]);
                }
            }

            if($request->filled('joins')) {

                //final
                $joins = json_decode($input['joins'],true);
                foreach ($joins as $key => $row) {
                    $type = $row['type'];
                    $returndata->$type($row['tablename'],$row['cond1'],$row['cond2']);
                }

                //test 2
                /*$joins = explode(',', $value['joins']);
                $tablename = str_replace("'", '', $value['tablename']);
                $condition1 = str_replace("'", '', $value['cond1']);
                $condition2 = str_replace("'", '', $value['cond2']);
                $returndata = $returndata->$join_type($tablename,$condition1,$condition2)->toSql();*/

                //test 1
                /*$joins = explode(', ', $input['joins']);
                $returndata = $returndata->$join_type($joins[0],$joins[1],$joins[2]);*/
            }

            if($request->filled('groupby')) {
                $returndata = $returndata->groupBy($input['groupby']);
            }

            if($request->filled('orderby')) {
                $returndata = $returndata->orderByRaw($input['orderby']);
            }

            if($request->filled('fields')) {
                $returndata = $returndata->selectRaw($input['fields']);
            }

            if($request->filled('page')) {

                $page = $input['page'];
                $perpage = 10;
                $calc  = $perpage * $page;
                $start = $calc - $perpage;
                $returndata = $returndata->skip($start)->take($perpage)->get();
                $next = "false";
                $message = $tableName.' '.'list.';
                $jsonResponse = General::jsonResponse(1,$input['message'],$returndata,$next,'','form');
                return $jsonResponse;

            }else{

                if($request->filled('max')) {
                    $returndata = $returndata->max($input['max']);

                }else if($request->filled('avg')) {
                    $returndata = $returndata->avg($input['avg']);

                }else if($request->filled('count')) {
                    $returndata = $returndata->count();

                }else {
                    $returndata = $returndata->get();
                }

                if($request->filled('get_first')) {
                    $returndata = $returndata->first();

                }else if($request->filled('get_last')) {
                    $returndata = $returndata->last();
                }

                $message = $tableName.' '.'list.';
                $jsonResponse =  General::jsonResponse(1,$input['message'],$returndata);
                return $jsonResponse;
            }

        break;

        case 'view':


            $where = json_decode($input['where'],true);
            $returndata = DB::table($table);
            $returndata = $returndata->where($where[0])->get()->first();
            $message = $tableName.' '.'detail.';
            $jsonResponse =  General::jsonResponse(1,$input['message'],$returndata);
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
                $jsonResponse = General::jsonResponse(0,$input['message'],[]);
                return $jsonResponse;


        break;
        }


    }
}
