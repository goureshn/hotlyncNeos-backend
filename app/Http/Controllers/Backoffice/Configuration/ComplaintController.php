<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use App\Modules\Functions;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Controllers\UploadController;

use App\Models\Common\AdminArea;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;

use Excel;
use DB;
use Datatables;
use Redis;
use Response;

use App\Models\Common\PropertySetting;

class ComplaintController extends Controller
{

    function getComplaint(Request $request) {
        $property_id = $request->get('property_id' , 0);

        $ret = PropertySetting::getComplaintSetting($property_id);

        return Response::json($ret);
    }

    function saveSetting(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $fieldname = $request->get('fieldname' ,'');
        $fieldvalue = $request->get('fieldvalue','');

        $model = PropertySetting::where('property_id', $property_id)
            ->where('settings_key', $fieldname)
            ->first();

        if( empty($model) )
        {
            $model = new PropertySetting();

            $model->property_id = $property_id;
            $model->settings_key = $fieldname;
        }

        $model->value = $fieldvalue;

        $model->save();

        $ret= array();

        if($model)
            $ret['success'] = '200';

        return Response::json($ret);
    }

    public function getComplaintDataForEmail(Request $request) {
        $property_id = $request->input('property_id' , 0);

        $ret = [
            'all_department' => [
                'enable_flag' => false,
                'job_role' => false,
                'frequency' => [
                    'daily' => false,
                    'daily_time' => "",
                    'weekly' => false,
                    'weekly_day' => "Monday",
                    'weekly_time' => "",
                    'monthly' => false,
                    'monthly_time' => "",
                    'yearly' => false,
                    'yearly_time' => ""
                ]
            ]
        ];

        $data = DB::table('property_setting')
            ->where('settings_key', 'complaint_data_for_email')
            ->where('property_id', $property_id)
            ->first();

        if (!empty($data)) {
            $result = json_decode($data->value);
        } else {
            $result = $ret;
        }

        return Response::json($result);
    }

    public function saveComplaintDataForEmail(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $complaint_data_for_email = $request->get('complaint_data_for_email', "");

        $insert_data = "";
        if (!empty($complaint_data_for_email)) {
            $insert_data = json_encode($complaint_data_for_email);
        }

        $check = DB::table('property_setting')
            ->where('settings_key', 'complaint_data_for_email')
            ->where('property_id', $property_id)
            ->first();

        $data = "";
        if (!empty($check)) {
            $data = DB::table('property_setting')
                ->where('settings_key', 'complaint_data_for_email')
                ->where('property_id', $property_id)
                ->update(['value' => $insert_data]);
        } else {
            $data = DB::table('property_setting')
                ->insert(['value' => $insert_data, 'settings_key' => 'complaint_data_for_email', 'property_id' => $property_id]);
        }

        $ret = array();
        if($data)
            $ret['success'] = '200';

        return $this->getComplaintDataForEmail($request);
    }


    private function getJobRoleListForAll($jobList) {

        $temp = [];

        foreach ($jobList as $jobItem) {
            $temp[$jobItem['dept_id']]['department'] = $jobItem['department'];
            $temp[$jobItem['dept_id']]['id'] = $jobItem['dept_id'];

            $tempRole = [
                'id' => $jobItem['id'],
                'job_role' => $jobItem['job_role']
            ];

            $temp[$jobItem['dept_id']]['roles'][] = $tempRole;
        }

        $resultArr = [];

        foreach ($temp as $index => $item) {
            $resultArr[] = $item;
        }

        return $resultArr;
    }

    private function getUserArrFromJobRoleId($jobRoleId, $property_id) {
        $result = DB::table('common_users')
            ->whereRaw("email <> ''")
            ->whereNotNull('email')
            ->where('job_role_id', $jobRoleId)
            ->select(DB::raw('first_name, last_name, email'))
            ->get();

        if (empty($result)) {
            return [];
        } else {
            return $result;
        }
    }

    private function getStatusInfoForAll($property_id, $startDate, $endDate) {
        $resultArr = [];
        $result = DB::table('services_complaint_request')
            ->where('property_id', $property_id)
            ->where('discuss_start_time', '>=', $startDate)
            ->where('discuss_start_time', '<', $endDate)
            ->groupBy('status')
            ->select(DB::raw('COUNT(*) as count, status'))
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        foreach ($result as $statusInfo) {
            $temp = [];
            $temp['name'] = $statusInfo->status;
            $temp['count'] = $statusInfo->count;

            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    private function getServiceInfoForAll($property_id, $startDate, $endDate) {
        $resultArr = [];
        $result = DB::table('services_compensation_request as scnr')
            ->join('services_complaint_request as sctr', 'sctr.id', '=', 'scnr.complaint_id')
            ->leftJoin('services_compensation as sc', 'sc.id', '=', 'scnr.item_id')
            ->where('sctr.property_id', $property_id)
            ->where('sctr.discuss_start_time', '>=', $startDate)
            ->where('sctr.discuss_end_time', '<', $endDate)
            ->groupBy('sc.id')
            ->select(DB::raw('COUNT(*) as count, sc.id as compensation_id, sc.compensation as compensation'))
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        foreach ($result as $serviceInfo) {
            $temp = [];
            $temp['id'] = $serviceInfo->compensation_id;
            $temp['name'] = $serviceInfo->compensation;
            $temp['count'] = $serviceInfo->count;

            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    private function getCategoryInfoForAll($property_id, $startDate, $endDate) {
        $resultArr = [];
        $result = DB::table('services_complaint_request as sctr')
            ->join('services_complaint_maincategory as scm', 'scm.id', '=', 'sctr.category_id')
            ->join('common_division as cd', 'cd.id', '=', 'scm.division_id')
            ->where('sctr.property_id', $property_id)
            ->where('sctr.discuss_start_time', '>=', $startDate)
            ->where('sctr.discuss_end_time', '<', $endDate)
            ->groupBy('sctr.category_id')
            ->groupBy('scm.id')
            ->select(DB::raw('COUNT(*) as count, cd.division as division, scm.name as category_name'))
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        foreach ($result as $categoryInfo) {
            $temp = [];
            $temp['name'] = $categoryInfo->category_name . " - " . $categoryInfo->division;
            $temp['count'] = $categoryInfo->count;
            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    private function getNationalityInfoForAll($property_id, $startDate, $endDate) {
        $resultArr = [];
        $result = DB::table('services_complaint_request as sctr')
            ->join('common_guest_profile as cgp', 'cgp.id', '=', 'sctr.guest_id')
            ->where('sctr.property_id', $property_id)
            ->where('sctr.discuss_start_time', '>=', $startDate)
            ->where('sctr.discuss_end_time', '<', $endDate)
            ->whereNotNull('cgp.nationality')
            ->where('cgp.nationality', '!=', '')
            ->groupBy('cgp.nationality')
            ->select(DB::raw('COUNT(*) as count, cgp.nationality as nationality'))
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        foreach ($result as $categoryInfo) {
            $temp = [];
            $temp['name'] = $categoryInfo->nationality;
            $temp['count'] = $categoryInfo->count;

            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    private function getEmailArr($property_id, &$jobList, $startDate, $endDate) {

        $statusInfo = $this->getStatusInfoForAll($property_id, $startDate, $endDate);
        $serviceInfo = $this->getServiceInfoForAll($property_id, $startDate, $endDate);
        $categoryInfo = $this->getCategoryInfoForAll($property_id, $startDate, $endDate);
        $nationalityInfo = $this->getNationalityInfoForAll($property_id, $startDate, $endDate);

        foreach ($jobList as $index => &$resultRow) {
            $resultRow['statusInfo'] = $statusInfo;
            $resultRow['serviceInfo'] = $serviceInfo;
            $resultRow['categoryInfo'] = $categoryInfo;
            $resultRow['nationalityInfo'] = $nationalityInfo;

            $roles = &$resultRow['roles'];

            foreach ($roles as &$role) {
                $role['userArr'] = $this->getUserArrFromJobRoleId($role['id'], $property_id);
            }
        }
    }

    public function sendEmail($param, $department = "", $jobRole = "all") {
        $message = [];
        $message['type'] = 'email';
        $message['to'] = $param['email'];
        $message['subject'] = (!empty($subject))? ('Hotlync Notification - ' . $subject) : 'Hotlync Notification';
        $message['title'] = '';

        $info = [];
        $info['timeInfo'] = $param['timeInfo'];
        if ($department === "") {
            $info['mainTitle'] = ucfirst($param['type']) . " Complaint Stream";
        } else {
            $info['mainTitle'] = ucfirst($param['type']) . " " . ucfirst($department) . " Complaint Stream";
        }
        $info['statusInfo'] = $param['statusInfo'];
        $info['serviceInfo'] = $param['serviceInfo'];
        $info['categoryInfo'] = $param['categoryInfo'];
        $info['nationalityInfo'] = $param['nationalityInfo'];
        $info['jobRole'] = $jobRole;

        $message['content'] = view('emails.complaint_stream_report', ['info' => $info])->render();
        $message['smtp'] = Functions::getMailSetting($param['property_id'], 'notification_');

        Redis::publish('notify', json_encode($message));
    }

    private function sendEmailToUsersForAll($jobList, $timeInfo, $type, $property_id) {

        foreach ($jobList as $jobRow) {
            $roles = $jobRow['roles'];
            $statusInfo = $jobRow['statusInfo'];
            $serviceInfo = $jobRow['serviceInfo'];
            $categoryInfo = $jobRow['categoryInfo'];
            $nationalityInfo = $jobRow['nationalityInfo'];

            foreach ($roles as $role) {
                $jobRole = $role['job_role'];
                $userArr = $role['userArr'];

                foreach ($userArr as $user) {
                    $firstName = $user->first_name;
                    $lastName = $user->last_name;
                    $email = $user->email;

                    $param = [
                        'email' => $email,
                        'property_id' => $property_id,
                        'timeInfo' => $timeInfo,
                        'type' => $type,
                        'statusInfo' => $statusInfo,
                        'serviceInfo' => $serviceInfo,
                        'categoryInfo' => $categoryInfo,
                        'nationalityInfo' => $nationalityInfo
                    ];

                    $this->sendEmail($param, "", 'all');
                }
            }
        }
    }

    public function sendComplaintEmail(Request $request) {
        $property_id = $request->input('property_id', 0);
        $jobRole = $request->input('job_role');
        $type = $request->input('type');

////        for test
//        $property_id = 4;
//        $jobRole = 'all';
//        $type = 'yearly';
//
////        end test

        $weeks = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        $startDate = "";
        $endDate = "";
        $timeInfo = "";

        if ($type === 'daily') {
            $startDate = date('Y-m-d', strtotime("-1 days")) . " 00:00:00";
            $endDate = date('Y-m-d') . " 00:00:00";

            $timeInfo = date('d-M-y', strtotime("-1 days")) . " 12MN to " . date('d-M-y', strtotime("-1 days")) . " 11:59PM";
        } else if ($type === 'weekly') {
            $dayNumber = $request->input('day_number', 0);

            $prevDayNumber = $dayNumber - 1;
            if ($prevDayNumber < 0) {
                $prevDayNumber = 6;
            }
            $prevStartDay = $weeks[$dayNumber];

            $prevEndDay = $weeks[$prevDayNumber];

            $startDate = date('Y-m-d', strtotime('last week ' . $prevStartDay)) . " 00:00:00";
            $endDate = date('Y-m-d') . " 00:00:00";

            $timeInfo = $prevStartDay . " 12MN to " . $prevEndDay . " 11:59PM";
        } else if ($type === 'monthly') {
            $startDate = date('Y-m', strtotime("-1 month")) . "-01 00:00:00";
            $endDate = date('Y-m') . "-01 00:00:00";

            $timeInfo = date('M-y', strtotime("-1 month"));
        } else if ($type === 'yearly') {
            $startDate = date("Y", strtotime("-1 year")) . "-01-01 00:00:00";
            $endDate = date("Y") . "-01-01 00:00:00";

            $timeInfo = '01-Jan-' . date("y", strtotime("-1 year")) . ' to ' . '31-Dec-' . date("y", strtotime("-1 year"));
        }

//        for test
//
//        $jobList = [
//            [ "dept_id"=> "2", "department"=> "Security", "id"=> "5", "job_role"=> "Security Supervisor" ],
//            [ "dept_id"=> "3", "department"=> "Sales & Marketing ", "id"=> "10", "job_role"=> "Marketing and Comm Mngr" ],
//            [ "dept_id"=> "3", "department"=> "Sales & Marketing ", "id"=> "7", "job_role"=> "Director of Sales & Marketing" ],
//            [ "dept_id"=> "3", "department"=> "Sales & Marketing ", "id"=> "8", "job_role"=> "Director Of Sales" ],
//            [ "dept_id"=> "3", "department"=> "Sales & Marketing ", "id"=> "11", "job_role"=> "Graphic Artist" ],
//            [ "dept_id"=> "3", "department"=> "Sales & Marketing ", "id"=> "9", "job_role"=> "Administrative Assistant" ],
//            [ "dept_id"=> "2", "department"=> "Security", "id"=> "4", "job_role"=> "Dir of Gov Relations/Security" ],
//            [ "dept_id"=> "3", "department"=> "Sales & Marketing ", "id"=> "16", "job_role"=> "Sales Manager Government" ],
//            [ "dept_id"=> "3", "department"=> "Sales & Marketing ", "id"=> "17", "job_role"=> "Sales Manager" ],
//            [ "dept_id"=> "3", "department"=> "Sales & Marketing ", "id"=> "18", "job_role"=> "Sales Coordinator" ],
//            [ "dept_id"=> "1", "department"=> "Executive Office", "id"=> "3", "job_role"=> "Env, Health & Safety Manager" ],
//            [ "dept_id"=> "4", "department"=> "Events", "id"=> "19", "job_role"=> "Director of Events" ],
//            [ "dept_id"=> "4", "department"=> "Events", "id"=> "20", "job_role"=> "Event Sales Manager" ]
//        ];

        // end test

        $jobList = $request->input('job_role_list', []);

        if ($jobRole === 'all') {

            $jobList = $this->getJobRoleListForAll($jobList);
            $this->getEmailArr($property_id, $jobList, $startDate, $endDate);
            $this->sendEmailToUsersForAll($jobList, $timeInfo, $type, $property_id);
        }
        return response()->json(['message' => 'Request completed']);
    }
}
