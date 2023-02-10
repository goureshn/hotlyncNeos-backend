<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use App\Models\Common\License;
use App\Modules\Functions;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;
use jlawrence\eos\Math;
use Matrix\Exception;
use Redis;
use Response;

class RequestController extends Controller
{
    function getRequestSettingInfo(Request $request) {
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
            ],
            'individual_department' => [
                'enable_flag' => false,
                'job_role' => false,
                'job_list' => [],
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
            ],
        ];

        $data = DB::table('property_setting')
            ->where('settings_key', 'request_data')
            ->where('property_id', $property_id)
            ->first();

        if (!empty($data)) {
            $result = json_decode($data->value);
        } else {
            $result = $ret;
        }

        return Response::json($result);
    }

    function getJobRoleListByDeptId($property_id, $dept_id, $bShow = true) {
        if ($dept_id === 0) {
            return [];
        }

        if ($bShow === true) {
            $result = DB::table('common_job_role as cj')
                ->where('cj.property_id', $property_id)
             //   ->where('cj.dept_id', $dept_id)
                ->select(DB::raw('cj.id, cj.job_role'))
                ->get();
        } else {
            $result = DB::table('common_job_role as cj')
                ->leftJoin('common_users as cu', 'cu.job_role_id', '=', 'cj.id')
                ->where('cj.property_id', $property_id)
                ->where('cj.dept_id', $dept_id)
                ->whereRaw('cu.email <> ""')
                ->whereNotNull('cu.email')
                ->select(DB::raw('cj.id, cj.job_role'))
                ->get();
        }


        return $result;
    }

    function getJobRoleList(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $dept_id = $request->get('dept_id', -1);

        $result = $this->getJobRoleListByDeptId($property_id, $dept_id);

        return Response::json($result);
    }

    // main part for 725
    public function sendRequestEmail(Request $request) {

        $property_id = $request->input('property_id', 0);
        $jobRole = $request->input('job_role');
        $type = $request->input('type');

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

        $jobList = $request->input('job_role_list', []);

        if ($jobRole === 'all') {

            $jobList = $this->getJobRoleListForAll($jobList);

            $this->getEmailArr($property_id, $jobList, $startDate, $endDate);

            $this->sendEmailToUsersForAll($jobList, $timeInfo, $type, $property_id);
        } else if ($jobRole === 'individual') {

            // test
            $jobList = $this->getJobRoleListForIndividual($jobList, $startDate, $endDate);

            $this->sendEmailToUsersForIndividual($jobList, $timeInfo, $type, $property_id);
        }
        return response()->json(['message' => 'Request completed']);
    }

    public function insertOtherInfoToArr(&$existArr, $new) {

        $bInserted = false;

        foreach ($existArr as $index => $existItem) {
            if ($new['count'] > $existItem['count']) {

                $newArr = [$new];
                array_splice($existArr, $index, 0, $newArr);

                $bInserted = true;
                break;
            }
        }

        if ($bInserted === false) {
            array_push($existArr, $new);
        }

        return $existArr;
    }

    public function changeStaffInfo($stffInfo) {
        $tempStaffInfo = $stffInfo;

        $staffInfo = [
            'ontime' => [],
            'escalated' => [],
            'timeout'   => [],
            'hold'      => []
        ];

        foreach ($tempStaffInfo as $key => $tempStaff) {
            if ($tempStaff['totalInfo']['ontime'] > 0) {

                $newItem = [
                    'label' => $tempStaff['first_name'] . ' ' . $tempStaff['last_name'],
                    'count'      => $tempStaff['totalInfo']['ontime']
                ];
                $this->insertOtherInfoToArr($staffInfo['ontime'], $newItem);
            }

            if ($tempStaff['totalInfo']['escalated'] > 0) {
                $newItem = [
                    'label' => $tempStaff['first_name'] . ' ' . $tempStaff['last_name'],
                    'count'      => $tempStaff['totalInfo']['escalated']
                ];

                $this->insertOtherInfoToArr($staffInfo['escalated'], $newItem);
            }

            if ($tempStaff['totalInfo']['timeout'] > 0) {
                $newItem = [
                    'label' => $tempStaff['first_name'] . ' ' . $tempStaff['last_name'],
                    'count'      => $tempStaff['totalInfo']['timeout']
                ];
                $this->insertOtherInfoToArr($staffInfo['timeout'], $newItem);
            }

            if ($tempStaff['totalInfo']['hold'] > 0) {
                $newItem = [
                    'label' => $tempStaff['first_name'] . ' ' . $tempStaff['last_name'],
                    'count'      => $tempStaff['totalInfo']['hold']
                ];
                $this->insertOtherInfoToArr($staffInfo['hold'], $newItem);
            }
        }

        return $staffInfo;
    }

    public function changeDepartInfo($deptInfo) {
        $tempDepartInfo = $deptInfo;

        $departInfo = [
            'ontime' => [],
            'escalated' => [],
            'timeout'   => [],
            'hold'      => []
        ];

        foreach ($tempDepartInfo as $key => $tempDepart) {
            if ($tempDepart['ontime'] > 0) {

                $newItem = [
                    'label' => $tempDepart['department'],
                    'count'      => $tempDepart['ontime']
                ];
                $this->insertOtherInfoToArr($departInfo['ontime'], $newItem);
            }

            if ($tempDepart['escalated'] > 0) {

                $newItem = [
                    'label' => $tempDepart['department'],
                    'count'      => $tempDepart['escalated']
                ];
                $this->insertOtherInfoToArr($departInfo['escalated'], $newItem);
            }

            if ($tempDepart['timeout'] > 0) {
                $newItem = [
                    'label' => $tempDepart['department'],
                    'count'      => $tempDepart['timeout']
                ];
                $this->insertOtherInfoToArr($departInfo['timeout'], $newItem);
            }

            if ($tempDepart['hold'] > 0) {
                $newItem = [
                    'label' => $tempDepart['department'],
                    'count'      => $tempDepart['hold']
                ];

                $this->insertOtherInfoToArr($departInfo['hold'], $newItem);
            }
        }

        return $departInfo;
    }

    public function sendEmail($param, $department = "", $jobRole = "all")
    {
        if ($param['type'] == 'daily')
            $type == "Daily";
        elseif ($param['type'] == 'weekly')
            $type == "Weekly";
        elseif ($param['type'] == 'monthly')
            $type == "Monthly";
        else
            $type == "Yearly";


        $message = [];
        $message['type'] = 'email';
        $message['to'] = $param['email'];
        $message['subject'] = (!empty($subject))? ('Hotlync ' . $type . ' Request Stream - ' . $subject) : 'Hotlync ' . $type . ' Request Stream';
        $message['title'] = '';

        $info = [];
        $info['timeInfo'] = $param['timeInfo'];
        $info['property_name'] = $param['property_name'];

        if ($department === "") {
            $info['mainTitle'] = ucfirst($param['type']) . " Request Stream";
        } else {
            $info['mainTitle'] = ucfirst($param['type']) . " " . ucfirst($department) . " Request Stream";
        }
        $info['totalInfo'] = $param['totalInfo'];
        $info['otherInfo'] = $param['otherInfo'];
        $info['itemInfo'] = $param['itemInfo'];
        $info['locationInfo'] = $param['locationInfo'];
        $info['jobRole'] = $jobRole;

        $message['content'] = view('emails.guest_service_request', ['info' => $info])->render();
        $message['smtp'] = Functions::getMailSetting($param['property_id'], 'notification_');

        Redis::publish('notify', json_encode($message));
    }

    private function sendEmailToUsersForIndividual($jobList, $timeInfo, $type, $property_id) {


        $property = DB::table('common_property')->where('id', $property_id)->first();
		if (empty($property)) {
			echo "Property does not exist";
			return;
		}
		$property_name = $property->name;

        foreach ($jobList as $jobRow) {
            $email = $jobRow['email'];
            $firstName = $jobRow['first_name'];
            $lastName = $jobRow['last_name'];

            $totalInfo = $jobRow['totalInfo'];
            $department = $jobRow['department'];
            $itemInfo = $jobRow['itemInfo'];
            $locationInfo = $jobRow['locationInfo'];

            $otherInfo = $this->changeStaffInfo($jobRow['staffInfo']);

            $param = [
                'email' => $email,
                'property_id' => $property_id,
                'property_name' => $property_name,
                'timeInfo' => $timeInfo,
                'type' => $type,
                'totalInfo' => $totalInfo,
                'otherInfo' => $otherInfo,
                'itemInfo' => $itemInfo,
                'locationInfo' => $locationInfo
            ];

            $this->sendEmail($param, $department, 'individual');
        }
    }

    private function sendEmailToUsersForAll($jobList, $timeInfo, $type, $property_id) {

        $property = DB::table('common_property')->where('id', $property_id)->first();
		if (empty($property)) {
			echo "Property does not exist";
			return;
		}
		$property_name = $property->name;

        foreach ($jobList as $jobRow) {
            $roles = $jobRow['roles'];
            $totalInfo = $jobRow['total'];
            $itemInfo = $jobRow['itemInfo'];
            $locationInfo = $jobRow['locationInfo'];

            $departInfo = $this->changeDepartInfo($jobRow['by_department']);

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
                        'property_name' => $property_name,
                        'timeInfo' => $timeInfo,
                        'type' => $type,
                        'totalInfo' => $totalInfo,
                        'otherInfo' => $departInfo,
                        'itemInfo' => $itemInfo,
                        'locationInfo' => $locationInfo
                    ];

                    $this->sendEmail($param, "", 'all');
                }
            }
        }
    }

    private function getTotalCountInfoByDepartment($dept_id, $startDate, $endDate) {
        $country_query = DB::table('services_task as st')
            ->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
            ->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
            ->where('st.department_id', $dept_id)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate);

        $totalInfo = $country_query->select(DB::raw('
        CAST(COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) AS UNSIGNED) as ontime,
                        CAST(COALESCE(sum(st.status_id = 3), 0) AS UNSIGNED) as timeout,
                        CAST(COALESCE(sum(st.escalate_flag), 0) AS UNSIGNED) as escalated,
                        CAST(COALESCE(sum((st.status_id = 1 or st.status_id = 2 ) and st.running = 0), 0) AS UNSIGNED) as hold
        '))
            ->first();

        $ret = [];
        if( empty($totalInfo) )
        {
            $ret['ontime'] = 0;
            $ret['timeout'] = 0;
            $ret['escalated'] = 0;
            $ret['hold'] = 0;
        }
        else
        {
            $ret['ontime'] = $totalInfo->ontime;
            $ret['timeout'] = $totalInfo->timeout;
            $ret['escalated'] = $totalInfo->escalated;
            $ret['hold'] = $totalInfo->hold;
        }
        return $ret;
    }

    private function getItemInfoForAll($startDate, $endDate) {
        $resultArr = [];
        $result = DB::table('services_task as st')
            ->leftJoin('services_task_list as sl', 'sl.id', '=', 'st.location_id')
            ->where('st.task_list', '>', 0)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate)
            ->groupBy('st.task_list')
            ->select(DB::raw('COUNT(*) as count, st.task_list as task_list, sl.task as task_name'))
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        foreach ($result as $locationInfo) {
            $temp = [];
            $temp['id'] = $locationInfo->task_list;
            $temp['name'] = $locationInfo->task_name;
            $temp['count'] = $locationInfo->count;

            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    private function getTotalCountInfo($startDate, $endDate) {
        $country_query = DB::table('services_task as st')
            ->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
            ->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate);

        $totalInfo = $country_query->select(DB::raw('
        CAST(COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) AS UNSIGNED) as ontime,
                        CAST(COALESCE(sum(st.status_id = 3), 0) AS UNSIGNED) as timeout,
                        CAST(COALESCE(sum(st.escalate_flag), 0) AS UNSIGNED) as escalated,
                        CAST(COALESCE(sum((st.status_id = 1 or st.status_id = 2 ) and st.running = 0), 0) AS UNSIGNED) as hold
        '))
            ->first();

        $ret = [];
        if( empty($totalInfo) )
        {
            $ret['ontime'] = 0;
            $ret['timeout'] = 0;
            $ret['escalated'] = 0;
            $ret['hold'] = 0;
        }
        else
        {
            $ret['ontime'] = $totalInfo->ontime;
            $ret['timeout'] = $totalInfo->timeout;
            $ret['escalated'] = $totalInfo->escalated;
            $ret['hold'] = $totalInfo->hold;
        }
        return $ret;
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

    private function getEachDepartmentInfo($property_id, $startDate, $endDate) {

        $departmentList = DB::table('services_task as st')
            ->leftJoin('common_department as cd', 'cd.id', '=', 'st.department_id')
            ->where('cd.property_id', $property_id)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate)
            ->where('cd.id', '>', 0)
            ->groupBy('st.department_id')
            ->select(DB::raw('cd.id as id, cd.department as department'))
            ->get();

        $by_depart = [];

        foreach ($departmentList as $index => $department) {
            $dept_id = $department->id;

            $temp = $this->getTotalCountInfoByDepartment($dept_id, $startDate, $endDate);
            $temp['department'] = $department->department;
            $by_depart[] = $temp;
        }

        return $by_depart;
    }

    private function getEmailArr($property_id, &$jobList, $startDate, $endDate) {

        $totalInfo = $this->getTotalCountInfo($startDate, $endDate);
        $itemInfo = $this->getItemInfoForAll($startDate, $endDate);
        $locationInfo = $this->getLocationInfoForAll($startDate, $endDate);

        foreach ($jobList as $index => &$resultRow) {
            $resultRow['total'] = $totalInfo;
            $resultRow['itemInfo'] = $itemInfo;
            $resultRow['locationInfo'] = $locationInfo;

            $resultRow['by_department'] = $this->getEachDepartmentInfo($property_id, $startDate, $endDate);
            $roles = &$resultRow['roles'];

            foreach ($roles as &$role) {
                $role['userArr'] = $this->getUserArrFromJobRoleId($role['id'], $property_id);
            }
        }
    }

    private function getDepartmentListByPropertyId($property_id, $bShow = true) {

        if ($bShow === true) {
            $result = DB::table('common_department as cd')
                ->leftJoin('common_job_role as cj', 'cj.dept_id', '=', 'cd.id')
                ->where('cd.property_id', $property_id)
                ->where('cd.id','>', '0')
                ->select(DB::raw('cd.id as id, cd.department'))
                ->groupby('cj.dept_id')
                ->get();
        } else {
            $result = DB::table('common_department as cd')
                ->leftJoin('common_job_role as cj', 'cj.dept_id', '=', 'cd.id')
                ->leftJoin('common_users as cu', 'cu.job_role_id', '=', 'cj.id')
                ->where('cd.property_id', $property_id)
                ->whereRaw('cu.email <> ""')
                ->whereNotNull('cu.email')
                ->select(DB::raw('cd.id as id, cd.department'))
                ->groupby('cj.dept_id')
                ->get();
        }

        return $result;
    }

    public function getJobListForAll(Request $request) {
        $property_id = $request->get('property_id' , 0);

        $result = DB::table('common_department as cd')
            ->leftJoin('common_job_role as cj', 'cj.dept_id', '=', 'cd.id')
            ->where('cd.property_id', '=', $property_id)
            ->where('cj.id', '>', 0)
            ->select(DB::raw('cd.id as dept_id, cd.department as department, cj.id as id, cj.job_role as job_role'))
            ->get();

        return Response::json($result);
    }

    public function getDepartmentList(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $result = $this->getDepartmentListByPropertyId($property_id);

        return Response::json($result);
    }

    private function getStaffInfo($dept_id, $startDate, $endDate) {

        $resultArr = [];
        $result = DB::table('services_task as st')
            ->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
            ->where('st.department_id', '=', $dept_id)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate)
            ->where('st.dispatcher', '>', 0)
            ->groupBy('st.dispatcher')
            ->select(DB::raw('st.dispatcher as id, cu.first_name as first_name, cu.last_name as last_name'))
            ->get();

        foreach ($result as $userInfo) {
            $temp = [];
            $temp['id'] = $userInfo->id;
            $temp['first_name'] = $userInfo->first_name;
            $temp['last_name'] = $userInfo->last_name;
            $temp['totalInfo'] = $this->getTotalInfoByUserId($dept_id, $userInfo->id, $startDate, $endDate);

            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    private function getTotalInfoByUserId($dept_id, $user_id, $startDate, $endDate) {
        $country_query = DB::table('services_task as st')
            ->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
            ->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
            ->where('st.department_id', $dept_id)
            ->where('st.dispatcher', $user_id)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate);

        $totalInfo = $country_query->select(DB::raw('
        CAST(COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) AS UNSIGNED) as ontime,
                        CAST(COALESCE(sum(st.status_id = 3), 0) AS UNSIGNED) as timeout,
                        CAST(COALESCE(sum(st.escalate_flag), 0) AS UNSIGNED) as escalated,
                        CAST(COALESCE(sum((st.status_id = 1 or st.status_id = 2 ) and st.running = 0), 0) AS UNSIGNED) as hold
        '))
            ->first();

        $ret = [];
        if( empty($totalInfo) )
        {
            $ret['ontime'] = 0;
            $ret['timeout'] = 0;
            $ret['escalated'] = 0;
            $ret['hold'] = 0;
        }
        else
        {
            $ret['ontime'] = $totalInfo->ontime;
            $ret['timeout'] = $totalInfo->timeout;
            $ret['escalated'] = $totalInfo->escalated;
            $ret['hold'] = $totalInfo->hold;
        }
        return $ret;
    }

    private function getItemInfoForIndividual($dept_id, $startDate, $endDate) {
        $resultArr = [];
        $result = DB::table('services_task as st')
            ->leftJoin('services_task_list as sl', 'sl.id', '=', 'st.location_id')
            ->where('st.department_id', '=', $dept_id)
            ->where('st.task_list', '>', 0)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate)
            ->groupBy('st.task_list')
            ->select(DB::raw('COUNT(*) as count, st.task_list as task_list, sl.task as task_name'))
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        foreach ($result as $locationInfo) {
            $temp = [];
            $temp['id'] = $locationInfo->task_list;
            $temp['name'] = $locationInfo->task_name;
            $temp['count'] = $locationInfo->count;

            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    private function getLocationInfoForAll($startDate, $endDate) {
        $resultArr = [];
        $result = DB::table('services_task as st')
            ->leftJoin('services_location as sl', 'sl.id', '=', 'st.location_id')
            ->where('st.location_id', '>', 0)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate)
            ->groupBy('st.location_id')
            ->select(DB::raw('COUNT(*) as count, st.location_id as location_id, sl.name as location_name'))
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        foreach ($result as $locationInfo) {
            $temp = [];
            $temp['id'] = $locationInfo->location_id;
            $temp['name'] = $locationInfo->location_name;
            $temp['count'] = $locationInfo->count;

            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    function getLocationInfoForIndividual($dept_id, $startDate, $endDate) {
        $resultArr = [];
        $result = DB::table('services_task as st')
            ->leftJoin('services_location as sl', 'sl.id', '=', 'st.location_id')
            ->where('st.department_id', '=', $dept_id)
            ->where('st.location_id', '>', 0)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate)
            ->groupBy('st.location_id')
            ->select(DB::raw('COUNT(*) as count, st.location_id as location_id, sl.name as location_name'))
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get();

        foreach ($result as $locationInfo) {
            $temp = [];
            $temp['id'] = $locationInfo->location_id;
            $temp['name'] = $locationInfo->location_name;
            $temp['count'] = $locationInfo->count;

            $resultArr[] = $temp;
        }

        return $resultArr;
    }

    private function getTotalInfoByDepartment($dept_id, $startDate, $endDate) {
        $country_query = DB::table('services_task as st')
            ->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
            ->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
            ->where('st.department_id', $dept_id)
            ->where('st.start_date_time', '>=', $startDate)
            ->where('st.start_date_time', '<', $endDate);

        $totalInfo = $country_query->select(DB::raw('
        CAST(COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) AS UNSIGNED) as ontime,
                        CAST(COALESCE(sum(st.status_id = 3), 0) AS UNSIGNED) as timeout,
                        CAST(COALESCE(sum(st.escalate_flag), 0) AS UNSIGNED) as escalated,
                        CAST(COALESCE(sum((st.status_id = 1 or st.status_id = 2 ) and st.running = 0), 0) AS UNSIGNED) as hold
        '))
            ->first();

        $ret = [];
        if( empty($totalInfo) )
        {
            $ret['ontime'] = 0;
            $ret['timeout'] = 0;
            $ret['escalated'] = 0;
            $ret['hold'] = 0;
        }
        else
        {
            $ret['ontime'] = $totalInfo->ontime;
            $ret['timeout'] = $totalInfo->timeout;
            $ret['escalated'] = $totalInfo->escalated;
            $ret['hold'] = $totalInfo->hold;
        }
        return $ret;
    }

    private function getJobRoleListForIndividual($jobList, $startDate, $endDate) {
        $resultArr = [];

        foreach ($jobList as $item) {
            $dept_id = $item['id'];
            $department = $item['department'];
            if (empty($item['roles'])) {
                continue;
            }

            $totalInfo = $this->getTotalInfoByDepartment($dept_id, $startDate, $endDate);
            $staffInfo = $this->getStaffInfo($dept_id, $startDate, $endDate);

//            get itemInfo
            $locationInfo = $this->getLocationInfoForIndividual($dept_id, $startDate, $endDate);
            $itemInfo = $this->getItemInfoForIndividual($dept_id, $startDate, $endDate);

            foreach ($item['roles'] as $roleItem) {
                $roleId = $roleItem['id'];

                $tempResultArr = DB::table('common_users')
                    ->where('job_role_id', $roleId)
                    ->whereRaw("email <> ''")
                    ->whereNotNull('email')
                    ->select(DB::raw('id, first_name, last_name, email'))
                    ->get();

                if (!empty($tempResultArr)) {
                    foreach ($tempResultArr as $result) {
                        $temp = [];
                    $temp['id'] = $result->id;
                    $temp['first_name'] = $result->first_name;
                    $temp['last_name'] = $result->last_name;
                    $temp['email'] = $result->email;

                    $temp['dept_id'] = $dept_id;
                    $temp['department'] = $department;

                    $temp['totalInfo'] = $totalInfo;
                    $temp['staffInfo'] = $staffInfo;
                    $temp['locationInfo'] = $locationInfo;
                    $temp['itemInfo'] = $itemInfo;
                    $resultArr[] = $temp;
                    }
                }
            }
        }

        return $resultArr;
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

    public function saveRequestSettingInfo(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $request_data = $request->get('request_data', "");

        $insert_data = "";
        if (!empty($request_data)) {
            $insert_data = json_encode($request_data);
        }

        $check = DB::table('property_setting')
            ->where('settings_key', 'request_data')
            ->where('property_id', $property_id)
            ->first();

        $data = "";
        if (!empty($check)) {
            $data = DB::table('property_setting')
                ->where('settings_key', 'request_data')
                ->where('property_id', $property_id)
                ->update(['value' => $insert_data]);
        } else {
            $data = DB::table('property_setting')
                ->insert(['value' => $insert_data, 'settings_key' => 'request_data', 'property_id' => $property_id]);
        }

        $ret = array();
        if($data)
            $ret['success'] = '200';

        return $this->getRequestSettingInfo($request);
    }
}
