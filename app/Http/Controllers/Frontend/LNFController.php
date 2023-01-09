<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Service\LNFList;
use App\Models\Service\LNFItemList;
use App\Models\Common\UserMeta;
use App\Modules\Functions;
use Excel;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;
use App\Models\Service\Location;
use App\Models\Service\LNFItemCategory;
use App\Models\Common\PropertySetting;
use App\Models\Common\Property;
use App\Models\Common\CommonUser;

define("DUE", 'Lost');
define("OK", 'Found');
define("RTIRED", 'Under Review');
define("FAULTY", 'Handed Over');
define("BREAK_DOWN", 'Rejected');
define("OVER_DUE", 'Found Lost');

class LNFController extends Controller
{
    public function ChangeLNFStatus(Request $request) {
        $lnf_id = $request->get('lnf_id',0);
        $status_name = $request->get('status_name','');
       
        $lnf = LNFItemList::where('id',$lnf_id)->first();
        $lnf->status_name = $status_name;
        $lnf->save();
        $ret =array();
        $ret['id'] = $lnf->id;
        
        return Response::json($ret);
    }

    public function createLNFFromMobile(Request $request) {

        $lnf = new LNFList();
        //$lnf->property_id = $request->get('property_id','');

        $lnf->location_type = $request->get('location_type', 'Room');
        $lnf->location_id = $request->get('location_id', 0);        
        $lnf->lnf_time = $request->get('lnf_time','');
        $lnf->lnf_type = $request->get('lnf_type','');
        if( $request->get('user_type', 1) )
        {
            $lnf->found_by = $request->get('found_by', 0);
            $lnf->custom_user = 0;
        }
        else
        {
            $lnf->found_by = 0;
            $lnf->custom_user = $request->get('custom_user', 0);
        }

        $lnf->received_by = $request->get('received_by','');
        $lnf->received_time = $request->get('received_time','');
        $lnf->guest_type = $request->get('guest_type', 1);
        if( $lnf->guest_type == 1 )
        {
            $lnf->guest_id = $request->get('guest_id', 0);
            $lnf->custom_guest = 0;
        }
        else
        {
            $lnf->guest_id = 0;
            $lnf->custom_guest = $request->get('custom_guest', 0);
        }
        
        $lnf->employee_id = $request->get('auth_id',0);

        $lnf->save();

        $items = $request->get('items', '[]');
        $item_list = json_decode($items, true);

        foreach($item_list as $item_info)
        {
            $item = new LNFItemList();
            $item->lnf_id = $lnf->id;       

            if( $lnf->lnf_type == 'Found' )        
                $item->status_name = 'Available';
            else
                $item->status_name = 'Inquired';

            $item->matched_id = '';
        
            if(empty($item_info["type_id"])) 
                $item_info["type_id"] = '';

            if(empty($item_info["comment"])) 
                $item_info["comment"] = '';

            if(empty($item_info["brand_id"]))             
                $item_info["brand_id"] = 0;

            if(empty($item_info["stored_location_id"])) 
                $item_info["stored_location_id"] = 0;

            if(empty($item_info["stored_time"])) 
                $item_info["stored_time"] = '';    

            if(empty($item_info["category_id"])) 
                $item_info["category_id"] = 0;        

            if(empty($item_info["tags"])) 
                $item_info["tags"] = '';   

            if(empty($item_info["path"])) 
                $item_info["path"] = '';   

            $item->stored_location_id = $item_info["stored_location_id"];    
            $item->stored_time = $item_info["stored_time"];    
            $item->brand_id = $item_info["brand_id"];
            $item->type_id = $item_info["type_id"];
            $item->category_id = $item_info["category_id"];
            $item->quantity = $item_info["quantity"];
            $item->comment = $item_info["comment"];
            $item->tags = $item_info["tags"];
            $item->images = $item_info["path"];
            
            $item->save();
        }

        $this->sendLnfEmail($lnf->id);

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $item_list;

        return Response::json($ret);
    }

    private function sendLnfEmail($id)
    {
        $info = array();

        // get lnf 
        $lnf = DB::table('services_lnf as lnf')
                    ->leftJoin('services_location as sl', 'lnf.location_id', '=', 'sl.id')
                    ->leftJoin('common_users as cu', 'lnf.found_by', '=', 'cu.id')
                    ->leftJoin('common_users as cu1', 'lnf.received_by', '=', 'cu1.id')
                    ->where('lnf.id', '=', $id)
                    ->select(DB::raw('lnf.*, sl.property_id, sl.name as lgm_name, 
                                    CONCAT(cu.first_name, " ", cu.last_name) as found_by_name,
                                    CONCAT(cu1.first_name, " ", cu1.last_name) as received_by_name
                                    '))
                    ->first();
        
        if( empty($lnf) )
            return;

        $item_list = $this->generateItemQuery()
            ->where('lnf.id', '=', $id)
            ->get();            

        $info = array();
        
    //    $info['wholename'] = 'Hello';		
        $info['lnf'] = $lnf;
        $info['item_list'] = $item_list;
	//	$email_content = view('emails.lnf_item_creation', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($lnf->property_id, 'notification_');
        
        // echo $email_content;

        $rules = array();
        $rules['found_user_group_ids'] = '';
        $rules['inquiry_user_group_ids'] = '';
        
        $rules = PropertySetting::getPropertySettings($lnf->property_id, $rules);

        $query = DB::table('common_users as cu')
            ->join('common_user_group_members as cug', 'cu.id', '=', 'cug.user_id');
        
        $user_flag = false;
        if( $lnf->lnf_type == 'Found' )
        {            
            if( !empty($rules['found_user_group_ids']) )
            {
                $query->whereRaw("cug.group_id IN (" . $rules['found_user_group_ids'] . ")");
                $user_flag = true;
            }    
        }
        else
        {
            if( !empty($rules['inquiry_user_group_ids']) )
            {
                if( !empty($rules['inquiry_user_group_ids']) )
                {
                    $query->whereRaw("cug.group_ IN (" . $rules['inquiry_user_group_ids'] . ")");
                    $user_flag = true;
                }    
            }   
        }

        if( $user_flag == false )
            return;

        $user_list = $query
                        ->select(DB::raw('cu.*, CONCAT(cu.first_name, " ", cu.last_name) as wholename'))
                        ->groupBy('cu.email')
                        ->get();

        // echo json_encode($user_list);
        
        $message = array();
        $message['type'] = 'email';

        $attach_list = [];

        foreach($item_list as $item)
        {
            if( empty($item->images) )
                continue;

            $image_list = explode("|", $item->images);
            foreach($image_list as $row)
            {
                $path = $_SERVER["DOCUMENT_ROOT"] . "/" . $row;	
                $attach_list[] = $path;
            }    
        }

        // echo json_encode($attach_list);

        $message['attach'] = $attach_list;

        foreach($user_list as $row)
        {   
            $info['wholename'] = $row->wholename;     
            $email_content = view('emails.lnf_item_creation', ['info' => $info])->render();

            $message['to'] = $row->email;
            $message['subject'] = sprintf('New Lnf Item is created'); 
            $message['content'] = $email_content;
            $message['smtp'] = $smtp;

            Redis::publish('notify', json_encode($message));
        }        
    }

    public function testLnfEmail(Request $request)
    {
        $id = $request->get('id', 0);
        $this->sendLnfEmail($id);
    }

    public function getImage(Request $request) {
        $image_url = $request->get("image_url",'');
        if($image_url !='') {
            $path = $_SERVER["DOCUMENT_ROOT"] . $image_url;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            return Response::json($base64);
        }else {
            return Response::json('');
        }
    }
    public function parseExcelFile($path,&$count)
    {
        Excel::selectSheets('Equipment List')->load($path, function($reader) {
            date_default_timezone_set(env('TIMEZONE', 'Asia/Dubai'));
            $cur_time = date('Y-m-d H:i:s');
            $rows = $reader->all()->toArray();
            $count = $rows;
            for($i = 0; $i < count($rows); $i++ )
            {
                foreach( $rows[$i] as $data )
                {
                    //echo jswon_encode($data);
                    $inform = array();
                    $inform['name'] = $data['equipment_name'];
                    $inform['clr'] = $data['id'];
                    $codes = array();
                    if(!empty($data['id'])) {
                     $codes = explode("-", $data['id']);
                    }
                    $inform['description'] = $data['equipment_name'];
                    $critical = $data['critical'];
                    if(strtolower($critical) == 'yes') $inform['critical_flag'] = 1;
                    if(strtolower($critical) == 'no') $inform['critical_flag'] = 0;
                    $external_maintenance  = $data['external_maintenance'] ;
                    if(strtolower($external_maintenance) == 'yes') $inform['external_maintenance'] = 1;
                    if(strtolower($external_maintenance) == 'no') $inform['external_maintenance'] = 0;
                    //get external_maintenance_id  from external maintenance company
                    $brand = $data['brand'] ;
                    $inform['external_maintenance_id'] = $this->getId('lnf_external_maintenance', $brand, '');
                    //get dept_id from Department
                    $department = $data['department'];
                    $inform['dept_id'] = $this->getId('common_department', $department, '');
                    $inform['life'] = $data['life'];
                    $inform['life_unit'] = $data['life_unit'];
                    //get group id from Equipment group
                    $equipment_group = $data['equipment_group'];
                    $inform['equip_group_id'] = $this->getId('lnf_group', $equipment_group, $codes[1]);
                    //get part group id from parts group
                    $part_group = $data['parts_group'];
                    $inform['part_group_id'] = $this->getId('lnf_part_group', $part_group, $codes[3]);
                    //get group id from Equipment group
                    $property = $data['property'];
                    $inform['property_id'] = $this->getId('common_property', $property, '');
                    //get Location id from location
                    $location = $data['location'];
                    $locations  = explode(" ", $location);
                    $inform['location_group_member_id'] = $this->getLocationid($locations[0], $locations[1], $inform['property_id']);
                    $inform['purchase_cost'] = $data['cost'];
                    $inform['purchase_date'] = $data['purchase_date'];
                    $inform['keywords'] = $data['manufacturer'];
                    //get status id from status
                    $status = $data['status'];
                    $inform['status_id'] = $this->getId('lnf_status', $status, '');
                    $inform['model'] = $data['model'];
                    $inform['barcode'] = $data['barcode'];
                    $inform['warranty_start'] = $data['warranty_start'];
                    $inform['warranty_end'] = $data['warranty_end'];
                    // get  supplier id  from supplier
                    $supplier = $data['supplier'];
                    $inform['supplier_id'] = $this->getId('lnf_supplier', $supplier, '');
                    $inform['maintenance_date'] = $cur_time;

                    if( LNFList::where('name', $inform['name'])->where('clr', $inform['clr'])->exists() )
                        continue;
                    LNFList::create($inform);
                }
            }
        });
    }

    public function parseExcelFilePart($path,&$count)
    {
        Excel::selectSheets('Part List')->load($path, function($reader) {
            date_default_timezone_set(env('TIMEZONE', 'Asia/Dubai'));
            $cur_time = date('Y-m-d H:i:s');
            $rows = $reader->all()->toArray();
            $count = $rows;
            for($i = 0; $i < count($rows); $i++ )
            {
                foreach( $rows[$i] as $data )
                {
                    echo json_encode($data);
                    $inform = array();
                    $inform['name'] = $data['part_name'];
                    $inform['part_id'] = $data['id'];
                    $codes = array();
                    if(!empty($data['id'])) {
                        $codes = explode("-", $data['id']);
                    }
                    $inform['description'] = $data['part_name'];
                    $critical = $data['critical'];
                    if(strtolower($critical) == 'yes') $inform['critical_flag'] = 1;
                    if(strtolower($critical) == 'no') $inform['critical_flag'] = 0;
                    $external_maintenance  = $data['external_maintenance'] ;
                    if(strtolower($external_maintenance) == 'yes') $inform['external_maintenance'] = 1;
                    if(strtolower($external_maintenance) == 'no') $inform['external_maintenance'] = 0;
                    //get external_maintenance_id  from external maintenance company
                    $brand = $data['brand'] ;
                    $inform['external_maintenance_id'] = $this->getId('lnf_external_maintenance', $brand, '');
                    //get dept_id from Department
                    $department = $data['department'];
                    $inform['dept_id'] = $this->getId('common_department', $department, '');
                    $inform['life'] = $data['life'];
                    $inform['life_unit'] = $data['life_unit'];
                    $inform['quantity'] = $data['quantity'];
                    $inform['minquantity'] = $data['minimum_quantity'];
                    $property = $data['property'];
                    $inform['property_id'] = $this->getId('common_property', $property, '');
                    //get Location id from location
                    $location = $data['location'];
                    $locations  = explode(" ", $location);
                    $inform['location_group_member_id'] = $this->getLocationid($locations[0], $locations[1], $inform['property_id']);
                    $inform['purchase_cost'] = $data['cost'];
                    $inform['purchase_date'] = $data['purchase_date'];
                    $inform['keywords'] = $data['manufacturer'];
                    //get status id from status
                    $status = $data['status'];
                    $inform['status_id'] = $this->getId('lnf_status', $status, '');
                    $inform['model'] = $data['model'];
                    $inform['barcode'] = $data['barcode'];
                    $inform['warranty_start'] = $data['warranty_start'];
                    $inform['warranty_end'] = $data['warranty_end'];
                    // get  supplier id  from supplier
                    $supplier = $data['supplier'];
                    $inform['supplier_id'] = $this->getId('/supplier', $supplier, '');
                    $inform['maintenance_date'] = $cur_time;

                    if( LNFPartList::where('name', $inform['name'])->where('part_id', $inform['part_id'])->exists() )
                        continue;
                    LNFPartList::create($inform);
                }
            }
        });
    }

    public function exceltest(Request $request) {
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/part/part_list.xlsx';
        $this->parseExcelFilePart($output_file, $list_count);

    }

    public function sendEmail(Request $request) {
        $to = $request->get('to','');
        $title = $request->get('title','');
        $content = $request->get('content','');
        $property_id = $request->get('property_id','');

        $message = array();
        $smtp = Functions::getMailSetting($property_id, '');
        $message['smtp'] = $smtp;

        $message['type'] = 'email';
        $message['to'] = $to;
        $message['subject'] = 'HotLync equipment maintenance';
        $message['title'] = $title;
        $message['content'] = $content;
        //$message['attach'] = array($admin_call_path);
        Redis::publish('notify', json_encode($message));
        return Response::json($request);
    }

    public function getLnfConfigList(Request $request)
    {
        $property_id = $request->get('property_id', 0);

        // LNF Item Store Location
        $list = DB::table('services_lnf_storedloc as ls')
            ->select(DB::raw('ls.*'))
            ->get();
        $model['store_loc'] = $list;

        // LNF Item Type
        $list = DB::table('services_lnf_item_type as ina')
            ->select(DB::raw('ina.*'))
            ->get();
        $model['item_type'] = $list;

        // LNF Item User
        $hotel_list = DB::table('common_users as cu')
                    ->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
                    ->select(DB::raw('cu.*, cd.department, CONCAT(cu.first_name, " ", COALESCE(cu.last_name,""), " - Hotel User") as fullname, 1 as user_type'))
                    ->get();

        $custom_list = DB::table('services_lnf_item_customuser as ta')
            ->select(DB::raw('ta.*, CONCAT(ta.first_name, " ", COALESCE(ta.last_name,""), " - Custom User") as fullname, 2 as user_type'))
            ->get();

        $list = array_merge($hotel_list , $custom_list);	
        $model['item_user'] = $list;
            
        // LNF Item Color
        $list = DB::table('services_lnf_item_color as ta')
            ->select(DB::raw('ta.*'))
            ->get();
        $model['item_color'] = $list;

        // LNF Item Brand
        $list = DB::table('services_lnf_item_brand as ta')
            ->select(DB::raw('ta.*'))
            ->get();
        $model['item_brand'] = $list;

        // LNF Item Status
        $list = DB::table('services_lnf_status as ta')
            ->select(DB::raw('ta.*'))
            ->get();
        $model['item_status'] = $list;

        // LNF Item Tag
        $model['item_tag'] = Functions::getTagList('services_lnf_item', 'tags');	

        // LNF Item Category
        $list = LNFItemCategory::get();						
        $model['item_category'] = $list;
        
        // Job Role
        $list = DB::table('common_job_role')->get();
        $model['item_jobrole'] = $list;

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $model;

        return Response::json($ret);
    }

    public function createItemColor(Request $request)
    {
        $input = $request->all();

        $id = DB::table('services_lnf_item_color')->insertGetId($input);
        $list = DB::table('services_lnf_item_color')->get();

        $ret = array();
        $ret['id'] = $id;
        $ret['list'] = $list;

        return Response::json($ret);
    }

    public function getLnf(Request $request){
        $user_id = $request->get('user_id', 0);
        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $filter = $request->get('filter');
        // $property_id = $request->get('property_id', '0');
        // $searchtext = $request->get('searchtext', '');
        $start_date = $request->get('start_date', '2016-01-01');
        $end_date = $request->get('end_date','2016-01-01');
        $lnf_time_range = sprintf("DATE(lnf.lnf_time) >= '%s' AND DATE(lnf.lnf_time) <= '%s'", $start_date, $end_date);
        
        if($pageSize < 0 )
            $pageSize = 20;
        $ret = array();
        
        if( empty($filter) )
        {
            $filter = UserMeta::getLostFoundFilter($user_id);
        }

        $comment_query = "(SELECT GROUP_CONCAT(COMMENT SEPARATOR ' ') FROM services_lnf_item WHERE services_lnf_item.lnf_id = lnf.id) as comment";
        $query = $this-> applyComplaintFilter($user_id, $filter);

        $data_list = $query
            // ->select(DB::raw('ml.id,ml.item_name,ml.item_stock,ml.alarm_count'))
            ->whereRaw($lnf_time_range)
            ->skip($skip)->take($pageSize)
            ->orderBy('lnf.lnf_time', 'desc')
            ->select(DB::raw('
                lnf.*,
                cus.username as common_name,
                cus.first_name as common_firstname,
                cus.last_name as common_lastname,
                cu.username as custom_username, 
                cu.first_name as custom_firstname, 
                cu.last_name as custom_lastname, 
                sl.name as location_name,                
                ls.status_name,'.$comment_query))
            ->get();
            
        $count_query = clone $query;
        $totalcount = $count_query->count();
        UserMeta::saveLostFoundFilter($user_id, $filter);
                
        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;
        $ret['query'] = $count_query;
        $ret['filter'] = $filter;
        
        return Response::json($ret);    
    }
    public function detail(Request $request)
    {

        $client_id = $request->get('client_id', 0);
        $lnf_id = $request->get('lnf_id', 0);
        $ret = array();

        return $ret;
    }

    private function generateItemQuery()
    {
        $query = DB::table('services_lnf_item as li')
        ->leftjoin('services_lnf as lnf', 'li.lnf_id', '=', 'lnf.id')
        ->leftjoin('common_users as cus', 'cus.id', '=', 'lnf.found_by')
        ->leftjoin('common_users as cur', 'cur.id', '=', 'lnf.received_by')
        ->leftjoin('common_guest as gu', 'gu.guest_id', '=', 'lnf.guest_id')
        ->leftjoin('services_lnf_customguest as cgu', 'cgu.id', '=', 'lnf.guest_id')
        ->leftjoin('services_lnf_item_customuser as cu', 'cu.id', '=', 'lnf.custom_user')
        ->leftJoin('services_location as sl', 'lnf.location_id', '=', 'sl.id')
        ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
        ->leftjoin('services_lnf_storedloc as loc',    'loc.id',  '=', 'li.stored_location_id')
        ->leftjoin('services_lnf_item_type as ty', 'ty.id', '=', 'li.type_id')
        ->leftjoin('services_lnf_item_color as cor', 'cor.id', '=', 'li.color_id')
        ->leftjoin('services_lnf_item_brand as br', 'br.id', '=', 'li.brand_id')
        ->leftjoin('services_lnf_item_category as slic', 'slic.id', '=', 'li.category_id')
        ->leftjoin('common_guest as gu1', 'gu1.guest_id', '=', 'li.return_guest_id')
        ->select(DB::raw('li.id as item_id, li.lnf_id as lnf_id, li.quantity, li.tags, li.images,
            li.created_at, li.status_name, li.matched_id, sl.property_id,
            li.comment, ty.type as item_type, li.type_id, cor.color, 
            li.brand_id, br.brand, lnf.*, 
            li.stored_location_id, li.stored_time, 
            li.category_id, slic.name as category, li.closed_flag, 
            li.return_date, li.return_mode, li.return_guest_id, gu1.guest_name as return_guest_name, li.staff_id_no, li.staff_name, li.staff_email, li.staff_contact_no, li.courier_company, li.courier_awb, li.return_comment,
            li.surrendered_date, li.surrendered_department, li.surrendered_to, li.surrendered_location, li.surrendered_comment,
            li.discarded_date, li.discarded_by, li.discarded_comment,
            cus.username as common_name, 
            cus.first_name as common_firstname, 
            cus.last_name as common_lastname, 
            cur.username as receiver_name, 
            cur.first_name as receiver_firstname, 
            cur.last_name as receiver_lastname, 
            cu.username as custom_username, 
            cu.first_name as custom_firstname, 
            cu.last_name as custom_lastname,                 
            loc.stored_loc, 
            sl.name as location_name, 
            slt.type as location_type, 
            gu.id as g_id, 
            gu.guest_id as guest_id, 
            gu.guest_name, 
            gu.email, 
            gu.mobile, 
            gu.checkout_flag, 
            gu.arrival, 
            gu.departure, 
            DATEDIFF(CURDATE(),lnf.lnf_time) as days, 
            cgu.id as customguest_id, 
            cgu.first_name as customguest_firstname, 
            cgu.last_name as customguest_lastname, 
            cgu.email as customguest_email, 
            cgu.contact_no as customguest_contactno'          
        ));

        return $query;
    }

    public function getLnfAllItems(Request $request)
    {
        $client_id = $request->get('client_id', 0);
        $lnf_type = $request->get('lnf_type', 'Found');

        $start_date = $request->get('start_date', '0000-00-00');
        $start_date .= ' 00:00:00';
        $end_date = $request->get('end_date', '0000-00-00');
        $end_date .= ' 23:59:59';
        $ret = array();
        $query = $this->generateItemQuery()
            ->where('lnf.lnf_time', '>=', $start_date)
            ->where('lnf.lnf_time', '<=', $end_date)
            ->where('lnf.lnf_type', $lnf_type);


        $filters = json_decode($request->get('filters', '[]'));

        $filtername = $filters->filtername;
        $filtervalue = $filters->filtervalue;
        if($filtername == 'status_name') {
            if( !empty($filtervalue) && !(strpos($filtervalue, 'All') !== false) )
			    $query->whereRaw("(FIND_IN_SET(li.status_name, '".$filtervalue . "'))");            
        }

        // Filter Tags
        $filter_tags = json_decode($request->get('filter_tags', '[]'));
        if( count($filter_tags) > 0 )
        {
            $query->where(function ($sub_query) use ($filter_tags) {
        		$filter_tags_array = 'CONCAT(",", %s, ",") REGEXP ",(' . implode('|', $filter_tags) . '),"';

                $sub_query->whereIn('ty.type', $filter_tags)            
                        ->orWhereIn('sl.name', $filter_tags)
                        ->orWhereIn('br.brand', $filter_tags)
            			->orWhereRaw(sprintf($filter_tags_array, 'li.tags'));
            });
        }
        

        $count_query = clone $query;
        $data_query = clone $query;

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'lnf_time');
        $sort = $request->get('sort', 'desc');
        
        $result_list = $data_query
            ->skip($skip)
            ->take($pageSize)
            ->orderBy($orderby, $sort)
            ->get();

        $totalcount = $count_query->count();

        $ret['datalist'] = $result_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function getFoundItemStatusCount(Request $request) 
    {
        $date = $request->get('date', 'D');
        $user_id = $request->get('user_id', 0);
        
        $property_id = CommonUser::getPropertyID($user_id);

        Functions::getDateRange($date, $start_date, $end_date);
        $start_date .= ' 00:00:00';
        $end_date .= ' 23:59:59';
        
        $ret = array();

        $status_list = [
            'Available',
            'Matched',
            'Returned',
            'Discarded',
            'Disposed',
            'Surrendered',
        ];

        $select_sql = 'count(*) as total';

        foreach($status_list as $row)
        {
            $select_sql .= ",COALESCE(sum(li.status_name = '$row'), 0) as $row";
        }

        $query = DB::table('services_lnf_item as li')
                    ->leftjoin('services_lnf as lnf', 'li.lnf_id', '=', 'lnf.id');
          
        $count = $query
            ->where('lnf.lnf_time', '>=', $start_date)
            ->where('lnf.lnf_time', '<=', $end_date)
            ->where('lnf.lnf_type', 'Found')
            ->select(DB::raw($select_sql))
            ->first();

        $list = array();

        $list[] = [
            'name' => 'All',
            'count' => $count->total,
        ];
        
        foreach($status_list as $row)
        {
            $list[] = [
                'name' => $row,
                'count' => $count->{"$row"},
            ];
        }

        $ret['code'] = 200;
        $ret['content'] = $list;        
        
        return Response::json($ret);
    }

    public function getMyLnfItems(Request $request)
    {
        $client_id = $request->get('client_id', 0);
        $lnf_type = $request->get('lnf_type', 'Found');
        $last_id = $request->get('last_id', -1);
        $page_size = $request->get('page_size', 10);
        $date = $request->get('date', 'D');
        $status_list = $request->get('status_list', "");

        Functions::getDateRange($date, $start_date, $end_date);
        $start_date .= ' 00:00:00';
        $end_date .= ' 23:59:59';

        $ret = array();
        $query = $this->generateItemQuery()
            ->where('lnf.lnf_time', '>=', $start_date)
            ->where('lnf.lnf_time', '<=', $end_date)
            ->where('lnf.lnf_type', $lnf_type);


        // status filter    
        if( !empty($status_list) )
        {            
            $status_array = explode(",", $status_list);
            $query->whereIn('li.status_name', $status_array);         
        }     

        $count_query = clone $query;
        $data_query = clone $query;

        if( $last_id > 0 )
            $data_query->where('li.id', '<', $last_id);
    
        $data_list = $data_query            
            ->take($page_size)
            ->orderBy('li.id', 'desc')
            ->get();

        $totalcount = $count_query->count();

        $ret['code'] = 200;
        $ret['content'] = $data_list;
        $ret['totalcount'] = $totalcount;
        
        return Response::json($ret);
    }

    public function getInquiryItems(Request $request)
    {
        $start_date = $request->get('start_date', '0000-00-00');
        $start_date .= ' 00:00:00';
        $end_date = $request->get('end_date', '0000-00-00');
        $end_date .= ' 23:59:59';

        $ret = array();
        $query = $this->generateItemQuery()
            ->where('lnf.lnf_time', '>=', $start_date)
            ->where('lnf.lnf_time', '<=', $end_date)
            ->where('li.closed_flag', 0)
            ->where('li.status_name', 'Inquired')
            ->where('lnf.lnf_type', 'Inquiry');
   

        // Filter Tags
        $filter_tags = json_decode($request->get('filter_tags', '[]'));
        if( count($filter_tags) > 0 )
        {
            $query->where(function ($sub_query) use ($filter_tags) {
        		$filter_tags_array = 'CONCAT(",", %s, ",") REGEXP ",(' . implode('|', $filter_tags) . '),"';

                $sub_query->whereIn('ty.type', $filter_tags)            
                        ->orWhereIn('sl.name', $filter_tags)
                        ->orWhereIn('br.brand', $filter_tags)
            			->orWhereRaw(sprintf($filter_tags_array, 'li.tags'));
            });
        }
        

        $count_query = clone $query;
        $data_query = clone $query;

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'lnf_time');
        $sort = $request->get('sort', 'desc');
        
        $result_list = $data_query
            ->skip($skip)
            ->take($pageSize)
            ->orderBy($orderby, $sort)
            ->get();

        $totalcount = $count_query->count();

        $ret['datalist'] = $result_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function getAvailableItems(Request $request)
    {
        $start_date = $request->get('start_date', '0000-00-00');
        $start_date .= ' 00:00:00';
        $end_date = $request->get('end_date', '0000-00-00');
        $end_date .= ' 23:59:59';

        $ret = array();

        $query = $this->generateItemQuery()
            ->where('lnf.lnf_time', '>=', $start_date)
            ->where('lnf.lnf_time', '<=', $end_date)
            ->where('li.status_name', 'Available')
            ->where('lnf.lnf_type', 'Found');
        
        // Filter Tags
        $filter_tags = json_decode($request->get('filter_tags', '[]'));
        if( count($filter_tags) > 0 )
        {
            $query->where(function ($sub_query) use ($filter_tags) {
        		$filter_tags_array = 'CONCAT(",", %s, ",") REGEXP ",(' . implode('|', $filter_tags) . '),"';

                $sub_query->whereIn('ty.type', $filter_tags)            
                        ->orWhereIn('sl.name', $filter_tags)
                        ->orWhereIn('br.brand', $filter_tags)
            			->orWhereRaw(sprintf($filter_tags_array, 'li.tags'));
            });
        }

        // Consider selected Inquiry items
        $selected_inquiry_ids = $request->get('selected_inquiry_ids', '');
        $suggest_flag = $request->get('suggest_flag', true);

        // intelligence suggestion algorithm
        $type_id_list = [];    
        $brand_id_list = [];    
        $tags_list = [];    

        if( !empty($selected_inquiry_ids) && $suggest_flag == true )
        {
            $selected_item_list = DB::table('services_lnf_item')
                ->whereRaw("FIND_IN_SET(id, '".  $selected_inquiry_ids . "')")
                ->select(DB::raw('type_id, brand_id, tags'))            
                ->get();        
            
            foreach($selected_item_list as $row)
            {
                $type_id_list[] = $row->type_id;
                $brand_id_list[] = $row->brand_id;
                $tags_list = array_merge($tags_list, explode(',', $row->tags));            
            }

            $type_id_list = array_unique($type_id_list, SORT_REGULAR);
            $type_id_list = array_merge($type_id_list, array());

            $brand_id_list = array_unique($brand_id_list, SORT_REGULAR);
            $brand_id_list = array_merge($brand_id_list, array());

            $tags_list = array_unique($tags_list, SORT_REGULAR);
            $tags_list = array_merge($tags_list, array());


            // get item list
            $query->where(function ($sub_query) use ($type_id_list, $brand_id_list, $tags_list) {
                $filter_tags_array = 'CONCAT(",", %s, ",") REGEXP ",(' . implode('|', $tags_list) . '),"';

                $sub_query->whereIn('li.type_id', $type_id_list)            
                        ->orWhereIn('li.brand_id', $brand_id_list)                        
                        ->orWhereRaw(sprintf($filter_tags_array, 'li.tags'));
            });

            // ranking
            $filter_tags_array = 'CONCAT(",", %s, ",") REGEXP ",(' . implode('|', $tags_list) . '),"';
            $query->orderByRaw('(
                                    FIND_IN_SET(li.type_id, \'' . implode(',', $type_id_list) . '\') * 3.0 +
                                    FIND_IN_SET(li.brand_id, \'' . implode(',', $brand_id_list) . '\') * 2.0 +
                                    (' . sprintf($filter_tags_array, 'li.tags') . ') * 1.0 
                                ) desc');
            
        }
            

        $count_query = clone $query;
        $data_query = clone $query;

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'lnf_time');
        $sort = $request->get('sort', 'desc');
        
        $result_list = $data_query
            ->skip($skip)
            ->take($pageSize)
            ->orderBy($orderby, $sort)
            ->get();

        $totalcount = $count_query->count();

        $ret['datalist'] = $result_list;
        $ret['totalcount'] = $totalcount;
        $ret['type_id_list'] = $type_id_list;
        $ret['brand_id_list'] = $brand_id_list;
        $ret['tags_list'] = $tags_list;

        return Response::json($ret);
    }

    public function getSearchTagsAll(Request $request)
    {
        $tags = array();
        $property_id = $request->get('property_id', 0);

        $items = DB::table('services_lnf_item as li')->get();

        foreach($items as $item)
        {
            $tag_arr = explode(',',rtrim($item->tags,','));
            $tags = array_unique(array_merge($tags , $tag_arr));
        }

        $locations = DB::table('services_location')
                        ->where('property_id', $property_id)
                        ->get();

        $item_arr = array();
        foreach($locations as $item)
        {
            $item_arr[] = $item->name;           
        }
        $tags = array_unique(array_merge($tags , $item_arr));

        $types = DB::table('services_lnf_item_type as ty')->get();

        $type_arr = array();
        foreach($types as $item)
        {
            $type_arr[] = $item->type;
        }
        $tags = array_unique(array_merge($tags , $type_arr));

        $brands = DB::table('services_lnf_item_brand as br')->get();
        $brand_arr = array();
        foreach($brands as $item)
        {
            $brand_arr[] = $item->brand;
        }
        $tags = array_unique(array_merge($tags , $brand_arr));

        $res = array_filter($tags,'strlen');

        $ret['datalist'] = $res;

        return Response::json($ret);

    }

    public function getLnfItemStatusDetail(Request $request)
    {
        $lnf_id = $request->get('lnf_id', 0);
        $status_id = $request->get('status_id', 0);
        $ret = array();
        $query = DB::table('services_lnf_item_log as hi');
        $data_query = clone $query;
        $data_list = $data_query
            ->leftjoin('common_users as cu', 'cu.id', '=', 'hi.login_user')
            ->leftjoin('common_users as cua', 'cua.id', '=', 'hi.action_by')
            ->leftjoin('services_lnf_item_customuser as cus', 'cus.id', '=', 'hi.action_by')
            ->where('hi.item_id',$lnf_id)
            ->where('hi.field_name','Status')
            ->where('hi.new_value',$status_id)
            ->select(['hi.id','hi.item_id','hi.comment','hi.created_at','hi.custom_user',
                'cu.first_name as login_fistname','cu.last_name as login_lastname',
                'cua.first_name as action_firstname' , 'cua.last_name as action_lastname',
                'cus.first_name as custom_action_firstname' , 'cus.last_name as custom_action_lastname'])
            ->orderBy('hi.created_at','desc')
            ->first();
        $ret['datalist'] = $data_list;
        return Response::json($ret);
    }

    public function getNonMatchedItemList(Request $request) 
    {
        $item_id = $request->get('item_id', 0);
        $matched_id = $request->get('matched_id', 0);
        $location_id = $request->get('location_id', 0);

        if( $matched_id == -1 )     // Inquiry Item
        {
            // find Found Item
            $matched_id = -2;
        }
        
        if( $matched_id == -2 )     // Found Item
        {
            // find Inquiry Item
            $matched_id = -1;
        }
        
        $list = DB::table('services_lnf_item as sli')
            ->leftjoin('services_lnf_item_type as ty', 'ty.id', '=', 'sli.type_id')
            ->join('services_lnf as sl', 'sli.lnf_id', '=', 'sl.id')
            ->join('services_location as slo', 'sl.location_id', '=', 'slo.id')
            ->join('services_location_type as slt', 'slo.type_id', '=', 'slt.id')
            ->where('sli.matched_id', $matched_id)
            // ->where('sl.location_id', $location_id)
            ->select(DB::raw('ty.type, sli.*, slo.name as loc_name, slt.type as loc_type, 
                            CONCAT(ty.type, "-", slo.name, " ", slt.type) AS fullstr'))
            ->get();

        return Response::json($list);
    }

    public function createNewGuest(Request $request)
    {
        $input = $request->all();
        $id = DB::table('services_lnf_customguest')->insertGetId($input);
        $list = DB::table('services_lnf_customguest')->get();
        $ret = array();
        $ret['code'] = 200;

        $data = array();
        $data['id'] = $id;
        $data['list'] = $list;

        $ret['content'] = $data;

        return Response::json($ret);
    }

    public function statusChange(Request $request)
    {
        $item_id = $request->get('item_id', 0);

        $old_status = DB::table('services_lnf_item as it')
            ->where('it.id',$item_id)
            ->select(['st.id','it.status_name'])
            ->first();

        $old_status_name = $old_status->status_name;

        $status_name = $request->get('status_name', "");
   
        $custom_user = $request->get('custom_user', 0);
        $login_user = $request->get('user_id', 0);
        $action_by = $request->get('action_by', 0);

        DB::table('services_lnf_item')->where('id', $item_id)->update(
            [
                'status_name'=> $status_name,
            ]
        );

        $created_user = $request->get('created_user', []);
        if($custom_user > 0)
        {
            $customuser_id = DB::table('services_lnf_item_customuser')->insertGetId(
                ['first_name' => $created_user["first_name"], 'last_name' => $created_user["last_name"],'created_by'=>$login_user]
            );
            $action_by = $customuser_id;
        }
        $comment = 'Status changed from '.$old_status_name.' to '.$status_name_name;
        DB::table('services_lnf_item_log')->insert(
            [
                'item_id' => $item_id,
                'field_name' =>"Status",
                'new_value' => $status_name,
                'old_value' => $old_status_name,
                'login_user'=>$login_user,
                'action_by' =>$action_by,
                'custom_user' =>$custom_user,
                'action'  =>"Change",
                'comment'   => $comment,
            ]
        );

        $ret = array();
        $query = DB::table('services_lnf_item_log as hi');
        $data_query = clone $query;
        $data_list = $data_query
            ->leftjoin('common_users as cu', 'cu.id', '=', 'hi.login_user')
            ->where('hi.item_id',$item_id)
            ->select(['hi.id','hi.item_id','hi.comment','hi.created_at','cu.first_name','cu.last_name',])
            ->orderBy('hi.created_at','desc')
            ->get();

        $ret['datalist'] = $data_list;

        return Response::json($ret);
    }

    public function saveMatchedItem(Request $request)
    {
        $item_id = $request->get('item_id', 0);
        $matched_id = $request->get('matched_id', 0);

        $old_status = DB::table('services_lnf_item as it')            
            ->where('it.id',$item_id)
            ->select(['it.status_name'])
            ->first();

        $old_status_name = $old_status->status_name;

        // get return status
        $status_name = 'Matched';

        $login_user = $request->get('user_id', 0);
        $action_by = $request->get('user_id', 0);
        
        DB::table('services_lnf_item')->where('id', $item_id)->update(
            [
                'status_name' => $status_name,           
                'matched_id' => $matched_id,           
            ]
        );

        $comment = 'Status changed from '.$old_status_name.' to '.$status_name;
        DB::table('services_lnf_item_log')->insert(
            [
                'item_id' => $item_id,
                'field_name' =>"Status",
                'new_value' => $status_name,
                'old_value' => $old_status_name,
                'login_user'=>$login_user,
                'action_by' =>$action_by,
                'custom_user' => '',
                'action'  =>"Change",
                'comment'   => $comment,
            ]
        );

        $ret = array();
        $query = DB::table('services_lnf_item_log as hi');
        $data_query = clone $query;
        $data_list = $data_query
            ->leftjoin('common_users as cu', 'cu.id', '=', 'hi.login_user')
            ->where('hi.item_id', $item_id)
            ->select(['hi.id','hi.item_id','hi.comment','hi.created_at','cu.first_name','cu.last_name',])
            ->orderBy('hi.created_at','desc')
            ->get();

        $ret['datalist'] = $data_list;

        return Response::json($ret);
    }

    public function getLnfDetail(Request $request)
    {
        $id = $request->get("id", 0);

        $data = array();

        $item_list = $this->generateItemQuery()
            ->where('li.lnf_id', '=', $id)            
            ->get();

        $detail = array();
        if( count($item_list) > 0 )
            $detail = $item_list[0];

        $comment_list = DB::table('services_lnf_item_comment as co')
            ->leftJoin('common_users as cu', 'cu.id', '=', 'co.user_id')
            ->leftJoin('services_lnf_item as li', 'co.lnf_item_id', '=', 'li.id')
            ->where('li.lnf_id', $id)
            ->select(['co.id','co.lnf_item_id','co.comment','co.created_at','cu.first_name','cu.last_name','cu.picture'])
            ->orderBy('co.created_at','desc')
            ->get();
        
        $data['font-size'] = '7px';
        $data['property'] = DB::table('common_property')->where('id', $detail->property_id)->first();        
        $data['created_at'] = $detail->created_at;
        $data['completed_by'] = '';
        $data['name'] = sprintf("F%05d", $id);

        $data['item_list'] = $item_list;
        $data['comment_list'] = $comment_list;
        $data['lnf'] = $detail;

        return $data;

    }

    public function applyComplaintFilter($user_id, $filter){
        $query = DB::table('services_lnf as lnf')
            ->leftjoin('common_users as cus', 'cus.id', '=', 'lnf.found_by')
            ->leftjoin('services_lnf_item_customuser as cu', 'cu.id', '=', 'lnf.found_by')
            ->leftJoin('services_location as sl', 'lnf.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id');

        if($filter['all_flag']){
            return $query;
        }

        // add location type filter
        $location_types = [];
        foreach ($filter['location_type_filter'] as $key => $row) {
            if($row == true){
                $location_types[] = $key;
            }
        }
        $query ->whereIn('lnf.location_type', $location_types);


        return $query;
    }

    public function checkItems(Request $request)
    {
      //  $this->checkItemElapsedStatus();
    }

    public function checkItemElapsedStatus()
    {
        date_default_timezone_set(env('TIMEZONE', 'Asia/Dubai'));
        $cur_time = date('Y-m-d H:i:s');

        $list = DB::table('services_lnf_item as sli')
            ->leftJoin('services_lnf_item_type as slit', 'sli.type_id', '=', 'slit.id')
            ->leftJoin('services_lnf as sl', 'sli.lnf_id', '=', 'sl.id')
            ->leftJoin('services_lnf_item_category as slic', 'sli.category_id', '=', 'slic.id')
            ->leftJoin('services_lnf_item_brand as slib', 'sli.brand_id', '=', 'slib.id')
            ->leftJoin('services_location as slo', 'sli.stored_location_id', '=', 'slo.id')
            ->leftJoin('services_location_type as slt', 'slo.type_id', '=', 'slt.id')
            ->where('sl.lnf_type', 'Found')
            ->where('slic.id', '>', 0)
            ->where('sli.status_name', '<>', 'Returned')
            ->where('sli.notify_flag', '<', 2)
            ->select(DB::raw('sli.*, slit.type as item_name, slic.name as category, slic.max_time, slic.elapsed_time, slic.notify_job_role_id, slic.notify_type, slic.notify_flag, slic.status_name as category_status,
                                sli.status_name, slib.brand, slo.name as loc_name, slt.type as loc_type'))                            
            ->get();

        foreach($list as $row)
        {
            // start time
            if( $row->notify_flag == 0 )    // check elapsed time
            {
                $diff_hour = round($row->max_time * $row->elapsed_time / 100, 1);
                $remain_hour = $row->max_time - $diff_hour;
                $check_time = date('Y-m-d H:i:s', strtotime($diff_hour . ' hours', strtotime($row->created_at)));                
            }
            if( $row->notify_flag == 1 )    // check max time
            {
                $diff_hour = $row->max_time;
                $remain_hour = $row->max_time;
                $check_time = date('Y-m-d H:i:s', strtotime($diff_hour . ' hours', strtotime($row->created_at)));
            }

            if( $cur_time >= $check_time )  // time is elapsed
            {
                $query = DB::table('common_users as cu')
                    ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
                    ->where('cu.job_role_id', $row->notify_job_role_id)
                    ->where('cu.deleted', 0)
                    ->select(DB::raw('cu.*, jr.property_id'));                
                
                $info = array();

                $info['category'] = $row->category; 
                $info['during'] = $diff_hour; 
                $info['status_name'] = $row->status_name; 
                $info['item_id'] = $row->id; 
                $info['brand'] = $row->brand; 
                $info['item_name'] = $row->item_name; 
                $info['quantity'] = $row->quantity; 
                $info['stored_loc'] = $row->loc_name . ' - ' . $row->loc_type; 
                $info['category_status'] = $row->category_status; 

                $subject = sprintf('ID F%05d %s to be %s', $row->id, $row->item_name, $row->category_status);

                if ($row->notify_flag == 1 && strpos($row->notify_type, 'Email') !== false) {
                    $email_query = clone $query;    
                    $email_list = $email_query
                        ->where('cu.email', '<>', '')
                        ->groupBy('cu.email')
                        ->get();

                    foreach($email_list as $row1)
                    {

                        $info['first_name'] = $row1->first_name;
                        $info['last_name'] = $row1->last_name;

                        $email_content = view('emails.lnf_category_notification', ['info' => $info])->render();

                        $message = array();
                        $smtp = Functions::getMailSetting($row1->property_id, '');
                        $message['smtp'] = $smtp;

                        $message['type'] = 'email';
                        $message['to'] = $row1->email;
                        $message['subject'] = $subject;
                        $message['title'] = 'Title';
                        $message['content'] = $email_content;

                        Redis::publish('notify', json_encode($message));
                    }    

                    // echo $email_content;
                }	
                    
                if (strpos($row->notify_flag == 1 && $row->notify_type, 'SMS') !== false) {
                    $mobile_query = clone $query;    
                    $mobile_list = $mobile_query
                        ->where('cu.mobile', '<>', '')
                        ->groupBy('cu.mobile')
                        ->get();    

                    $sms_message = sprintf('Item need to be %s in the next %s:
                                                \n\n 
                                                ID F%05d \n 
                                                %s \n
                                                %s \n
                                                %s \n
                                                %s - %s\n',
                                            $row->category, $remain_hour, $row->id, $row->brand, $row->item_name, $row->quantity, $row->loc_name, $row->loc_type);
                                            

                    $number_list = array_map(function($item) {
                        return $item->mobile;
                    }, $mobile_list);   
                    
                    if( count($number_list) > 0 )
                    {
                        $message = array();
                        $message['type'] = 'sms';
                        
                        $message['to'] = implode("|", $number_list);

                        $message['subject'] = $subject;
                        $message['content'] = $sms_message;

                        Redis::publish('notify', json_encode($message));
                    }                                        
                }

                $lnf_item = LNFItemList::find($row->id);
                $lnf_item->notify_flag = $lnf_item->notify_flag + 1;
                $lnf_item->save();
            }
        }    
    }

    public function testDailyAutomatedReprt(Request $request)
	{
		$message = $this->sendDailyAutomatedReprt(4);

		echo json_encode($message);
	}

	public function sendDailyAutomatedReprt($property_id) {
		date_default_timezone_set(config('app.timezone'));

		$setting = PropertySetting::getComplaintSetting($property_id);

		$data = array();
		$data['report_type'] = 'Summary';
		$data['report_by'] = 'Summary';
		$data['property_id'] = $property_id;
		$data['start_date'] = date('Y-m-d');
		$data['end_date'] = date('Y-m-d');
		$data['property'] = Property::find($property_id);		
		
		$filename = 'Daily_Feedback_Report' . '_' . date('d_M_Y_H_i');

		$folder_path = public_path() . '/uploads/reports/';
		$path = $folder_path . $filename . '.html';
		$pdf_path = $folder_path . $filename . '.pdf';

		ob_start();

		$content = view('frontend.report.daily_feedback_report_pdf', compact('data'))->render();
		echo $content;

		file_put_contents($path, ob_get_contents());

		ob_clean();

		// $email_list = [];
		// foreach($participants as $row)
		// 	$email_list[] = $row->email;

		// $request = array();
		// $request['filename'] = $filename . '.pdf';
		// $request['folder_path'] = $folder_path;
		// $request['to'] = implode(',', $email_list);

		// $subject = 'Complaint Briefing Summary Report';

		// $request['subject'] = $subject;
		// $request['html'] = $subject;
		// $request['content'] = view('emails.complaint_briefing_summary')->render();

		// $smtp = Functions::getMailSetting($property_id, '');
		// $request['smtp'] = $smtp;

		// $options = array();
		// $options['html'] = $path;
		// $options['pdf'] = $pdf_path;		
		// $options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');		
		// $options['subject'] = "night_audit";
		// $request['options'] = $options;

		// $message = array();
		// $message['type'] = 'report_pdf';
		// $message['content'] = $request;

		// Redis::publish('notify', json_encode($message));

		return $message;
	}
}
