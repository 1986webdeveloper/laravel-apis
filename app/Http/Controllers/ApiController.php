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
    public function crud_manage(UserApiRequest $request){

        $input = $request->all();
        $table = $input['table_name'];

        switch ($input['action']) {

            case 'add':
                try {
                    if($request->filled('sql_query')) {
                        DB::insert($input['sql_query']);
                        $id = DB::getPdo()->lastInsertId();
                        $jsonResponse = General::jsonResponse(1,$input['message'],array('id'=>$id));
                    }
                    if($request->filled('parameters')) {
                        $inputs = json_decode($input['parameters'],true);
                        DB::table($table)->insert($inputs);
                        $id = DB::getPdo()->lastInsertId();
                        $jsonResponse = General::jsonResponse(1,$input['message'],array('id'=>$id));
                    }
                } catch(\Illuminate\Database\QueryException $ex){
                    $jsonResponse = General::jsonResponse(0,'Error: '.$ex->getMessage(),[]);
                }
                return $jsonResponse;
            break;

            case 'edit':
                try {

                    if($request->filled('sql_query')) {
                        DB::update($input['sql_query']);
                        $jsonResponse = General::jsonResponse(1,$input['message'],[]);
                    }
                    if($request->filled('where')) {
                        $where = json_decode($input['where'],true);
                        $where = $this->array_flatten($where);
                        $inputs = json_decode($input['parameters'],true);
                        $inputs = $this->array_flatten($inputs);
                        DB::table($table)->where($where)->update($inputs);
                        $jsonResponse = General::jsonResponse(1,$input['message'],[]);
                    }
                } catch(\Illuminate\Database\QueryException $ex){
                    $jsonResponse = General::jsonResponse(0,'Error: '.$ex->getMessage(),[]);
                }
                return $jsonResponse;
            break;

            case 'view':
                try {
                    if($request->filled('sql_query')) {
                        $returndata = DB::select($input['sql_query']);
                        $jsonResponse = General::jsonResponse(1,$input['message'],$returndata);
                    }
                    if($request->filled('where')) {
                        $where = json_decode($input['where'],true);
                        $where = $this->array_flatten($where);
                        $returndata = DB::table($table);
                        if($request->filled('fields')) {
                            $returndata = $returndata->selectRaw($input['fields']);
                        }
                        $returndata = $returndata->where($where)->get()->first();
                        $jsonResponse = General::jsonResponse(1,$input['message'],$returndata);
                    }
                } catch(\Illuminate\Database\QueryException $ex){
                    $jsonResponse = General::jsonResponse(0,'Error: '.$ex->getMessage(),[]);
                }
                return $jsonResponse;
            break;

            case 'delete':
                try {
                    if($request->filled('sql_query')) {
                        DB::delete($input['sql_query']);
                        $jsonResponse = General::jsonResponse(1,$input['message'],[]);
                    }
                    if($request->filled('where')) {
                        $where = json_decode($input['where'],true);
                        $where = $this->array_flatten($where);
                        DB::table($table)->where($where)->delete();
                        $jsonResponse = General::jsonResponse(1,$input['message'],[]);
                    }
                } catch(\Illuminate\Database\QueryException $ex){
                    $jsonResponse = General::jsonResponse(0,'Error: '.$ex->getMessage(),[]);
                }
                return $jsonResponse;
            break;

            case 'list':
                try {
                    if($request->filled('sql_query')) {
                        $returndata = DB::select($input['sql_query']);
                        $jsonResponse = General::jsonResponse(1,$input['message'],$returndata);
                    }
                    $returndata = DB::table($table);
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
                            $input['limit'] = 10;
                        }
                        $returndata = $returndata->paginate($input['limit'])->toArray();
                        if($returndata['last_page'] == $input['page']){
                            $next = "false";
                        }else{
                            $next = "true";
                        }
                        $jsonResponse = General::jsonResponse(1,$input['message'],$returndata['data'],$next,'','form');
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
                        $jsonResponse = General::jsonResponse(1,$input['message'],$returndata);
                    }
                } catch(\Illuminate\Database\QueryException $ex){
                    $jsonResponse = General::jsonResponse(0,'Error: '.$ex->getMessage(),[]);
                }
                return $jsonResponse;
            break;

        }
    }

    public function array_flatten($array = null) {

        $result = array();
        if (!is_array($array)) {
            $array = func_get_args();
        }
        foreach($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            }else {
                $result = array_merge($result, array($key => $value));
            }
        }
        return $result;
    }

    public function whereQuery($type,$returndata,$where){

        switch ($type) {

            case 'whereBetween'://[{ "type" : "whereBetween"},["id",[1, 100]]]//ok
            case 'whereNotBetween'://[{ "type" : "whereNotBetween"},["id",[1, 100]]]//ok
            case 'whereIn'://[{ "type" : "whereIn"},["id",[51, 54, 62]]]//ok
            case 'whereNotIn'://[{ "type" : "whereNotIn"},["id",[51, 54, 62]]]//ok
            case 'whereDate'://[{ "type" : "whereDate"},["created_at","2019-09-16"]]//ok
            case 'whereMonth'://[{ "type" : "whereMonth"},["created_at","08"]]//ok
            case 'whereDay'://[{ "type" : "whereDay"},["created_at","08"]]//ok
            case 'whereYear'://[{ "type" : "whereYear"},["created_at","2019"]]//ok
                $returndata->$type($where[1][0],$where[1][1]);
            break;
            case 'whereNull'://[{ "type" : "whereNull"},["id"]]//ok
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
