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

            if($request->filled('sql_query')) {
                DB::insert($input['sql_query']);
                $jsonResponse =  General::jsonResponse(1,$input['message'],[]);
                return $jsonResponse;
            }

            $inputs = json_decode($input['parameters'],true);
            $userdetail = array();
            $returndata = DB::table($table);
            $returndata = $returndata->insert($inputs);
            $userdetail[] = $returndata;
            $jsonResponse =  General::jsonResponse(1,$input['message'],[]);
            return $jsonResponse;

        break;

        case 'edit':

            if($request->filled('sql_query')) {
                DB::update($input['sql_query']);
                $jsonResponse = General::jsonResponse(1,$input['message'],array());
                return $jsonResponse;
            }

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
            $jsonResponse = General::jsonResponse(1,$input['message'],array());
            return $jsonResponse;


        break;

        case 'list':

            $userdetail = array();
            $returndata = DB::table($table);

            if($request->filled('sql_query')) {
                $returndata = DB::select($input['sql_query']);
                $jsonResponse =  General::jsonResponse(1,$input['message'],$returndata);
                return $jsonResponse;
            }
            if($request->filled('where')) {

                $where = json_decode($input['where'],true);
                $type = isset($where[0]['type']) ? $where[0]['type'] : 'where';
                $returndata = $this->whereQuery($type,$returndata,$where);
            }

            if($request->filled('joins')) {
                $joins = json_decode($input['joins'],true);
                foreach ($joins as $key => $row) {
                    $type = $row['type'];
                    $returndata->$type($row['tablename'],$row['cond1'],$row['cond2']);
                }
            }

            if($request->filled('union_table') && $request->filled('union_where')) {
                $returndata1 = DB::table($input['union_table']);
                $where_union = json_decode($input['union_where'],true);
                $returndata1->where($where_union);
                $returndata = $returndata->union($returndata1);
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
                if(!$request->filled('limit')){
                    $input['limit'] = 10 ;
                }

                $returndata = $returndata->paginate($input['limit'])->toArray();
                if($returndata['last_page'] == $input['page']){
                    $next = "false";
                }else{
                    $next = "true";
                }

                $jsonResponse = General::jsonResponse(1,$input['message'],$returndata['data'],$next,'','form');
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

                $jsonResponse =  General::jsonResponse(1,$input['message'],$returndata);
                return $jsonResponse;
            }

        break;

        case 'view':

            $where = json_decode($input['where'],true);
            $returndata = DB::table($table);
            $returndata = $returndata->where($where[0])->get()->first();
            $jsonResponse =  General::jsonResponse(1,$input['message'],$returndata);
            return $jsonResponse;

        break;

        case 'delete':

            if($request->filled('sql_query')) {
                DB::delete($input['sql_query']);
                $jsonResponse = General::jsonResponse(0,$input['message'],[]);
                return $jsonResponse;
            }

            $where = json_decode($input['where'],true);
            $inputs = json_decode($input['parameters'],true);
            $i = 0;
            foreach ($inputs as $key => $value) {
                $returndata = DB::table($table);
                $returndata = $returndata->where("id", $where[$i]['id']);
                $returndata = $returndata->update($value);
                $i++ ;
            }

            $jsonResponse = General::jsonResponse(0,$input['message'],[]);
            return $jsonResponse;

        break;

        }
    }

    public function whereQuery($type,$returndata,$where){

        switch ($type) {

            case 'whereBetween'://[{ "type" : "whereBetween"},["id",[1, 100]]]
            case 'whereNotBetween'://[{ "type" : "whereNotBetween"},["id",[1, 100]]]
            case 'whereIn'://[{ "type" : "whereIn"},["id",[51, 54, 62]]]
            case 'whereNotIn'://[{ "type" : "whereNotIn"},["id",[51, 54, 62]]]
            case 'whereDate'://[{ "type" : "whereDate"},["created_at","2019-09-16"]]
            case 'whereMonth'://[{ "type" : "whereMonth"},["created_at","08"]]
            case 'whereDay'://[{ "type" : "whereDay"},["created_at","08"]]
            case 'whereYear'://[{ "type" : "whereYear"},["created_at","2019"]]
                $returndata->$type($where[1][0],$where[1][1]);
            break;

            case 'whereNull'://[{ "type" : "whereNull"},["id"]]
            case 'whereNotNull'://[{ "type" : "whereNotNull"},["id"]]
                $returndata->$type($where[1][0]);
            break;

            case 'whereTime'://[{ "type" : "whereTime"},["created_at",">=","04:00:00"]]
                $returndata->whereTime($where[1][0],$where[1][1],$where[1][2]);
            break;

            case 'orWhere'://[{ "type" : "orWhere"},["created_at","08"]]
                unset($where[0]);
                $returndata->orWhere($where);
            break;

            /*[{ "type" : "multiple"},
                [{ "type" : "where"},["id",">=","50"],["id","<","55"]],
                [{ "type" : "orWhere"},["id","62"]],
                [{ "type" : "orWhere"},["created_at","08"]]
            ]*/
            case 'multiple':
                foreach (array_slice($where,1) as $key => $conditions) {
                    $type = isset($conditions[0]['type']) ? $conditions[0]['type'] : 'where';
                    $returndata = $this->whereQuery($type,$returndata,$conditions);
                }
            break;

            case 'where1'://[{ "type" : "where1"},[["id",">=","50"],["id","<","55"]]] || [{ "type" : "where1"},[["id",">=","50"]]]
                $returndata->whereColumn($where[1]);
            break;

            case 'where2'://[{ "type" : "where2"},["first_name","last_name"]]
                $returndata->whereColumn($where[1][0],$where[1][1]);
            break;

            case 'where3'://[{ "type" : "where3"},["id","<","55"]]
                $returndata->whereColumn($where[1][0],$where[1][1],$where[1][2]);
            break;

            case 'where'://[{ "type" : "where"},["id",">=","50"],["id","<","55"]] || [["id","<","55"]]
                if(isset($where[0]['type']))
                    unset($where[0]);
                $returndata->where($where);
            break;
        }
        return $returndata;
    }
}
