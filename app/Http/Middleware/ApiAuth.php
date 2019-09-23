<?php

//namespace App\Http\Controllers;

namespace App\Http\Middleware;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\View;
use App\Model;
use App\Http\Controllers\GeneralController as General;

class ApiAuth {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $post = function ($field) use ($request) {
            return ($request->has($field) && $request->input($field)) ? $request->input($field) : "";
        };
        $user_id = intval($post("user_id"));
        $api = $request->segment(2);
        $role_data = [
            2 => ["key" => "company", "text" => "Company"],
            3 => ["key" => "technician", "text" => "Technician"],
            4 => ["key" => "customer", "text" => "Customer"],
            5 => ["key" => "company_employee", "text" => "Company Employee"],
        ];
        $role_id_data = [
            "company" => 2,
            "technician" => 3,
            "customer" => 4,
            "company_employee" => 5
        ];
        if (in_array($api, ["get_customer_chemistry_data", "get_equipment_list", "get_service_extra_equipment_part_list", "add_service_extra_equipment_part", "edit_service_extra_equipment_part", "delete_service_extra_equipment_part", "get_customer_faq_list", "edit_extra_service_request", "country_code_list","add_pool","delete_pool","change_virtual_tour_video","forgot_password","update_company_service","update_qbid","add_dialog","get_chat_list","get_chat_list_test","update_extra_equipment_part"])) {
            return $next($request);
        }
        if (in_array($api, ["login"])) {
            if (!General::is_duplicate_user_name($post("user_name"))) {
                return General::json_response([], "Username don't match.", 400);
            }
            return $next($request);
        }
        if (in_array($api, ["forgot_password"])) {
            if (!General::is_duplicate_email($post("email"))) {
                return General::json_response((object) [], "User Authentication Required.", 400);
            }
            return $next($request);
        }
        if (in_array($api, ["get_equipment_part_list"])) {
            $customer_user_id = intval($post("customer_user_id"));
            if ($customer_user_id <= 0 || !(General::is_customer_user($customer_user_id))) {
                return General::json_response([], "Customer User Authentication Required.", 400);
            }
            $company_user_id = General::get_company_user_id_from_child_user_id($customer_user_id);
            $request->merge(["company_user_id" => $company_user_id]);
            if ($company_user_id <= 0) {
                return General::json_response((object) [], "Company User Authentication Required.", 400);
            }
            return $next($request);
        }
        if ($user_id <= 0) {
            return General::json_response((object) [], "User Authentication Required.", 400);
         }
        $company_user_id = General::get_company_user_id_from_child_user_id($user_id);
        $request->merge(["company_user_id" => $company_user_id]);
        if ($company_user_id <= 0) {
            return General::json_response((object) [], "Company User Authentication Required.", 400);
        }
        if (in_array($api, ["change_password", "change_notification_status", "get_notification_list", "get_notification_count", "get_notification_count", "contact_us_details"])) {
            if (!General::is_user($user_id)) {
                return General::json_response((object) [], "User Authentication Required.", 400);
            }
            return $next($request);
        }
        if (in_array($api, ["update_customer_profile", "change_virtual_tour_video"])) {
            if (!General::is_customer_user($user_id)) {
                return General::json_response((object) [], "{$role_data[4]["text"]} User Authentication Required.", 400);
            }
            return $next($request);
        }
        if (in_array($api, ["add_extra_service_request","delete_extra_service_request"])) {
            if (!General::is_customer_user($user_id)) {
                return General::json_response((object) [], "{$role_data[4]["text"]} User Authentication Required.", 400);
            }
            $service_request_id = General::get_customer_service_request_id($user_id);
            if ($service_request_id <= 0) {
                return General::json_response((object) [], "Any Active Service Request Not Found for This Customer.", 400);
            }
            $request->merge(["service_request_id" => $service_request_id]);
            return $next($request);
        }
        if (in_array($api, ["update_technician_profile"])) {
            if (!General::is_technician_user($user_id)) {
                return General::json_response((object) [], "{$role_data[3]["text"]} User Authentication Required.", 400);
            }
            return $next($request);
        }
        $check_add_edit_user = function () use ($api, $post, $role_data, $role_id_data) {
            $role_name = str_replace(["add_", "edit_", "delete_"], ["", ""], $api);
            $id = intval($post("{$role_name}_user_id"));
            if (in_array($api, ["edit_{$role_name}", "delete_{$role_name}"])) {
                if ($id <= 0 || !(General::{"is_{$role_name}_user"}($id))) {
                    return "{$role_data[$role_id_data[$role_name]]["text"]} User Authentication Required.";
                }
            }
            if (in_array($api, ["add_{$role_name}", "edit_{$role_name}"])) {
                if (General::is_duplicate_email($post("email"), $id)) {
                    return "Email Already Exists.";
                }
                if (General::is_duplicate_mobile($post("mobile"), $id)) {
                    return "Mobile Number Already Exists.";
                }
                if (General::is_duplicate_user_name($post("user_name"), $id)) {
                    return "Username Already Exists.";
                }
            }
            return "";
        };
        if (in_array($api, ["update_company_profile", "get_company_employee_list", "add_company_employee", "edit_company_employee", "delete_company_employee","create_event","event_list"])) {
            if (!General::is_company_user($user_id)) {
                return General::json_response((object) [], "{$role_data[2]["text"]} User Authentication Required.", 400);
            }
            if (in_array($api, ["add_company_employee", "edit_company_employee", "delete_company_employee"])) {
                $error = $check_add_edit_user();
                if (!empty($error)) {
                    return General::json_response([], $error, 400);
                }
            }
            return $next($request);
        }
        if (in_array($api, ["update_company_employee_profile"])) {
            if (!General::is_company_employee_user($user_id)) {
                return General::json_response((object) [], "{$role_data[5]["text"]} User Authentication Required.", 400);
            }
            return $next($request);
        }
        if (in_array($api, ["get_dashboard_counts", "get_customer_list",  "add_customer", "edit_customer", "delete_customer", "get_technician_list", "add_technician", "edit_technician", "delete_technician", "get_category_list", "get_tutorial_video_list", "add_tutorial_video", "edit_tutorial_video", "delete_tutorial_video", "get_document_category_list", "get_document_list", "add_document", "edit_document", "delete_document", "add_service_request", "get_work_order_list", "get_extra_service_request_list", "get_customer_select_list", "get_technician_select_list","add_faq","edit_faq"])) {
            if (!General::is_company_user($user_id) && !General::is_company_employee_user($user_id)) {
                return General::json_response((object) [], "Company Or Company Employee User Authentication Required.", 400);
            }
            if (in_array($api, ["add_customer", "add_technician", "edit_customer", "edit_technician", "delete_customer", "delete_technician"])) {
                $error = $check_add_edit_user();
                if (!empty($error)) {
                    return General::json_response([], $error, 400);
                }
            }
            if (in_array($api, ["add_service_request"])) {
                $technician_user_id = intval($post("technician_user_id"));
                if ($technician_user_id <= 0 || !(General::is_technician_user($technician_user_id))) {
                    return General::json_response([], "Technician User Authentication Required.", 400);
                }
                $customer_user_id = intval($post("customer_user_id"));
                $pool_id = intval($post("pool_id"));
                if ($customer_user_id <= 0 || !(General::is_customer_user($customer_user_id))) {
                    return General::json_response([], "Customer User Authentication Required.", 400);
                }
                if (General::get_customer_service_request_id_request($customer_user_id,$pool_id) > 0) {
                    return General::json_response([], "You don't allow to create service request for this customer, Because your 1 service request already running for this customer.", 400);
                }
            }
            return $next($request);
        }

        if (in_array($api, ["get_customer_pool_list"])) {
              if ($user_id <= 0 || !(General::is_customer_user($user_id))) {
                return General::json_response((object) [], "Company Or Company Employee User Authentication Required.", 400);
            }
            return $next($request);
        }
        if (in_array($api, ["get_service_list_test","get_service_list", "edit_service_request_data"])) {
            $role_id = intval($post("role_id"));
            if ($role_id <= 0 || !array_key_exists($role_id, $role_data)) {
                return General::json_response((object) [], "Role Authentication Required.", 400);
            } else if (!General::{"is_{$role_data[$role_id]["key"]}_user"}($user_id)) {
                return General::json_response((object) [], "User Authentication Required.", 400);
            }
            return $next($request);
        }
        return General::json_response((object) [], "Sorry, the page you are looking for could not be found.", 400);
    }

}
