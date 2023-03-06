<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Call\AdminCall;
use App\Models\Call\BCCall;
use App\Models\Call\CarrierGroup;
use App\Models\Call\Destination;
use App\Models\Call\GroupDestination;
use App\Modules\Functions;
use App\Models\Call\Phonebook;
use App\Models\Call\MobileCalls;
use App\Models\Call\CallComment;
use App\Models\Call\CallCommentMobile;
use App\Models\Call\StaffExternal;
use App\Models\Common\PropertySetting;
use App\Models\Call\MobileTrack;
use App\Models\Call\ClassifyReminder;
use App\Models\Common\CommonUser;
use App\Models\Call\Csvdata;
use App\Models\Service\ShiftGroupMember;
use App\Models\Common\Guest;
use DateInterval;
use DateTime;
use DB;
use Excel;
use Redis;
use Illuminate\Http\Request;
use Response;
use Cache;
use File;

define("DEFAULT_URL", 'https://hotlync-d73c6.firebaseio.com');
define("DEFAULT_TOKEN", 'AIzaSyBvjFk4pxbLTE_j9GI7BwnSBaFmMTXTaQY');
define("DEFAULT_PATH", '/callaccount/guest_call');


class CallaccountController extends Controller
{
	public function getGuestCalls(Request $request) {
		set_time_limit(0);
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date('Y-m-d');

		$cur_time = date('Y-m-d H:i:s');
		$last_time = new DateTime($cur_time);
		$last_time->sub(new DateInterval('P1D'));
		$last_time = $last_time->format('Y-m-d H:i:s');

		$property_id = $request->get('property_id', 0);
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$call_type = $request->get('call_type', 'All');
		$buildings = $request->get('buildings');
		$search = $request->get('search', '');
		$call_date = $request->get('call_date', '');
		$searchoption = $request->get('searchoption', '');
		$totalcharge = $request->get('totalcharge', 0);

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('call_guest_call as gc')
				->join('common_room as cr', 'gc.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
				->join('call_guest_extn as ge', 'gc.extension_id', '=', 'ge.id')
				->leftJoin('call_destination as cd', 'gc.destination_id', '=', 'cd.id')
				->leftJoin('call_guest_charge_map as gcm', 'gc.guest_charge_rate_id', '=', 'gcm.id')
				->leftJoin('call_carrier_charges as cc', 'gcm.carrier_charges', '=', 'cc.id')
				->leftJoin('call_hotel_charges as hc', 'gcm.hotel_charges', '=', 'hc.id')
				->leftJoin('call_tax as tax', 'gcm.tax', '=', 'tax.id')
				// ->leftJoin('common_guest as cg', 'gc.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) use($property_id) {
					$join->on('gc.guest_id', '=', 'cg.guest_id');
					$join->on('cb.property_id', '=', 'cg.property_id');
				})			
				->join('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')	
				->where('cb.property_id', $property_id);
		if($totalcharge > 0)
			$query->where('gc.total_charges', '>', 0);

		$lasttime_range = sprintf("CONCAT(call_date, ' ', start_time) > '%s'", $last_time);

		if( $searchoption == '' )
		{
			if( $call_date == $cur_date )	// if today
				$query->whereRaw($lasttime_range);							
			else
				$query->where('gc.call_date', $call_date);				
			
			if( $call_type != 'All' )
				$query->where('call_type', $call_type);

			if(!empty($buildings)) {
					$query->whereIn('cf.bldg_id', $buildings);
				}
			
		}
		else
		{
			if( $call_date == $cur_date )	// if today
				$query->whereRaw($lasttime_range);
			else
				$query->where('gc.call_date', $call_date);

//			$where = sprintf("%s and
//								(cr.room like '%%%s%%' or
//								ge.extension like '%%%s%%' or
//								gc.call_date like '%%%s%%' or
//								gc.start_time like '%%%s%%' or
//								gc.called_no like '%%%s%%' or
//								gc.call_type like '%%%s%%' or
//								gc.trunk like '%%%s%%' or
//								cd.country like '%%%s%%' or
//								cd.country like '%%%s%%')",
//					$lasttime_range,
//					$searchoption, $searchoption, $searchoption, $searchoption,
//					$searchoption, $searchoption, $searchoption,$searchoption,
//					$searchoption, $searchoption);
			$where = sprintf(" (cr.room like '%%%s%%' or								
								ge.extension like '%%%s%%' or
								cb.name like '%%%s%%' or								
								gc.called_no like '%%%s%%' or
								cg.guest_name like '%%%s%%' or								
								cd.country like '%%%s%%')",
				$searchoption, $searchoption, $searchoption, $searchoption,$searchoption,$searchoption
				);
			$query->whereRaw($where);
		}

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('gc.*,cb.name as building, cg.guest_name, cr.room,rt.type as roomtype,vc.name as vip_name, ge.extension, ge.primary_extn, cd.country, cd.code, cc.charge as carrier_rate, hc.charge as hotel_rate, tax.value as tax_rate'))
				->skip($skip)->take($pageSize)
				->get();

		// get guest names
		//Guest::getGuestList($data_list);

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$sum_query = clone $query;
		$total = $sum_query->select(DB::raw('ROUND(sum(gc.total_charges), 2) as total, ROUND(sum(gc.total_charges) - sum(gc.carrier_charges), 2) as profit'))
			->first();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['total_cost'] = $total->total;
		$ret['total_profit'] = $total->profit;

		return Response::json($ret);
	}
    public function getBCCalls(Request $request) {
		set_time_limit(0);
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date('Y-m-d');

		$cur_time = date('Y-m-d H:i:s');
		$last_time = new DateTime($cur_time);
		$last_time->sub(new DateInterval('P1D'));
		$last_time = $last_time->format('Y-m-d H:i:s');

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$call_type = $request->get('call_type', 'All');
		$search = $request->get('search', '');
		$call_date = $request->get('call_date', '');
		$searchoption = $request->get('searchoption', '');
		$totalcharge = $request->get('totalcharge', 0);

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('call_bc_calls as bc')
				->join('call_staff_extn as se', 'bc.extension_id', '=', 'se.id')
				->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				->join('common_building as cb', 'cs.building_id', '=', 'cb.id')
				->leftJoin('call_destination as cd', 'bc.destination_id', '=', 'cd.id')
				->leftJoin('call_guest_charge_map as gcm', 'bc.guest_charge_rate_id', '=', 'gcm.id')
				->leftJoin('call_carrier_charges as cc', 'gcm.carrier_charges', '=', 'cc.id')
				->leftJoin('call_hotel_charges as hc', 'gcm.hotel_charges', '=', 'hc.id')
				
				->leftJoin('call_tax as tax', 'gcm.tax', '=', 'tax.id')
				->where('cb.property_id', $property_id);
		if($totalcharge > 0)
			$query->where('bc.total_charges', '>', 0);

		$lasttime_range = sprintf("CONCAT(call_date, ' ', start_time) > '%s'", $last_time);

		if( $searchoption == '' )
		{
			if( $call_date == $cur_date )	// if today
				$query->whereRaw($lasttime_range);
			else
				$query->where('bc.call_date', $call_date);

			if( $call_type != 'All' )
				$query->where('call_type', $call_type);
		}
		else
		{
			if( $call_date == $cur_date )	// if today
				$query->whereRaw($lasttime_range);
			else
				$query->where('bc.call_date', $call_date);

//			$where = sprintf("%s and
//								(se.extension like '%%%s%%' or
//								bc.call_date like '%%%s%%' or
//								bc.start_time like '%%%s%%' or
//								bc.called_no like '%%%s%%' or
//								bc.call_type like '%%%s%%' or
//								bc.trunk like '%%%s%%' or
//								cd.country like '%%%s%%' or
//								cd.country like '%%%s%%')",
//					$lasttime_range,
//					$searchoption, $searchoption, $searchoption, $searchoption,
//					$searchoption, $searchoption, $searchoption,$searchoption,
//					$searchoption, $searchoption);
			$where = sprintf(" and
					(se.extension like '%%%s%%' or
					bc.call_date like '%%%s%%' or
					bc.start_time like '%%%s%%' or
					bc.called_no like '%%%s%%' or
					bc.call_type like '%%%s%%' or
					bc.trunk like '%%%s%%' or
					cd.country like '%%%s%%' or
					cd.country like '%%%s%%')",
					$searchoption, $searchoption, $searchoption, $searchoption,
					$searchoption, $searchoption, $searchoption,$searchoption,
					$searchoption, $searchoption);
			$query->whereRaw($where);
		}

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('bc.*,se.extension, cd.country, cd.code, cc.charge as carrier_rate, hc.charge as hotel_rate, tax.value as tax_rate'))
				->skip($skip)->take($pageSize)
				->get();

		// get guest names
		

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$sum_query = clone $query;
		$total = $sum_query->select(DB::raw('ROUND(sum(bc.total_charges), 2) as total, ROUND(sum(bc.total_charges) - sum(bc.carrier_charges), 2) as profit'))
			->first();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['total_cost'] = $total->total;
		$ret['total_profit'] = $total->profit;

		return Response::json($ret);
	}
	public function getGuestCallsForReport(Request $request) {
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$call_type = $request->get('call_type', 'All');
		$call_date = $request->get('call_date', '');


		$ret = array();

		$query = DB::table('call_guest_call as gc')
				->join('common_room as cr', 'gc.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('call_guest_extn as ge', 'gc.extension_id', '=', 'ge.id')
				->leftJoin('call_destination as cd', 'gc.destination_id', '=', 'cd.id')
				->leftJoin('call_guest_charge_map as gcm', 'gc.guest_charge_rate_id', '=', 'gcm.id')
				->leftJoin('call_carrier_charges as cc', 'gcm.carrier_charges', '=', 'cc.id')
				->leftJoin('call_hotel_charges as hc', 'gcm.hotel_charges', '=', 'hc.id')
				->leftJoin('call_tax as tax', 'gcm.tax', '=', 'tax.id')
//				->where('gc.call_date', $call_date)
				->where('cb.property_id', $property_id);
//
//		if( $call_type != 'All' )
//			$query->where('call_type', $call_type);

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('gc.*, cr.room, ge.extension, ge.primary_extn, cd.country, cd.code, cc.charge as carrier_rate, hc.charge as hotel_rate, tax.value as tax_rate'))
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}


	public function getAdminCalls(Request $request) {
		set_time_limit(0);
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date('Y-m-d');
//		$cur_date = '2016-10-12';

		$cur_time = date('Y-m-d H:i:s');
		$start_datetime = date('Y-m-d H:i:s',strtotime("-1 days"));//last 24 hous
		$start_date = date('Y-m-d',strtotime($start_datetime));
		$start_time = date('H:i:s',strtotime($start_datetime));


		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$call_type = $request->get('call_type', 'All');
		$buildings = $request->get('buildings');
		$search = $request->get('search', '');
		$call_date = $request->get('call_date', '');
		$searchoption = $request->get('searchoption', '');
		$totalcharge = $request->get('totalcharge', 0);

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('call_admin_calls as ac')
				->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				// ->join('call_section as cs', 'ac.section_id', '=', 'cs.id')
				->join('common_building as cb', 'ac.building_id', '=', 'cb.id')
				->leftJoin('call_destination as cd', 'ac.destination_id', '=', 'cd.id')
				->leftJoin('call_admin_charge_map as acm', 'ac.admin_charge_rate_id', '=', 'acm.id')
				->leftJoin('call_carrier_charges as cc', 'acm.carrier_charges', '=', 'cc.id')
				->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('cb.property_id', $property_id);
		if($totalcharge > 0)
			$query->where('ac.carrier_charges', '>', 0);

		if( $searchoption == '' )
		{
			if( $call_date == $cur_date )	// if today
			{
				$query->where(function ($subquery) use ($cur_date, $start_date, $start_time) {				
					$subquery->where("ac.call_date", $cur_date);
					$subquery->orWhere(function ($subquery) use ($start_date, $start_time) {				
						$subquery->where("ac.call_date", $start_date);
						$subquery->where("ac.start_time", '>=', $start_time);
					});
				});
			}							
			else
				$query->where('ac.call_date', $call_date);				

			if( $call_type != 'All' )
				$query->where('call_type', $call_type);

			if(!empty($buildings)) {
					$query->whereIn('ac.building_id', $buildings);
				}

			if( !empty($search) )
			{
				$search = '%' . $search . '%';
				$query->where('called_no', 'like', $search);
			}
		}else{
			if( $call_date == $cur_date )	// if today
			{
				$query->where(function ($subquery) use ($cur_date, $start_date, $start_time) {				
					$subquery->where("ac.call_date", $cur_date);
					$subquery->orWhere(function ($subquery) use ($start_date, $start_time) {				
						$subquery->where("ac.call_date", $start_date);
						$subquery->where("ac.start_time", '>=', $start_time);
					});
				});
			}
			else
				$query->where('ac.call_date', $call_date);

				$where = sprintf("(
								CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%%%s%%' or
								se.extension like '%%%s%%' or
								cb.name like '%%%s%%' or
								ac.call_date like '%%%s%%' or
								ac.start_time like '%%%s%%' or
								ac.called_no like '%%%s%%' or
								ac.call_type like '%%%s%%' or
								ac.trunk like '%%%s%%' or
								cd.country like '%%%s%%' or
								ac.classify like '%%%s%%' or
								ac.approval like '%%%s%%')",
				$searchoption, $searchoption, $searchoption, $searchoption,
				$searchoption, $searchoption, $searchoption,$searchoption,
				$searchoption, $searchoption, $searchoption);
			$query->whereRaw($where);
		}

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('ac.*, cb.name as building, se.extension, se.description, cd.country, cd.code, cc.charge as carrier_rate, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->skip($skip)->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$sum_query = clone $query;
		$total = $sum_query->select(DB::raw('ROUND(sum(ac.carrier_charges), 2) as total'))
			->first();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['total_cost'] = $total->total;

		return Response::json($ret);
	}

	public function getMobileTrackList(Request $request) {
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'created_at');
		$sort = $request->get('sort', 'desc');
		$property_id = $request->get('property_id', '0');

		if($pageSize < 0 )
			$pageSize = 20;
		$ret = array();

		$query = DB::table('call_mobile_track as mt');
			//->where('re.property_id', $property_id);

		$data_query = clone $query;

		$data_list = $data_query
			->orderBy($orderby, $sort)
			->select(DB::raw('mt.*'))
			->skip($skip)->take($pageSize)
			->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}
public function syncstatusChange(Request $request){

$details = $request->get('row', '');
$row= MobileTrack::find($details['id']);
		if($row->status=='Not Started');
		$row->status='Sync Start';
		$row->save();
		$ret = array();
		$ret['message'] = "Sync started successfully.";
		

		return Response::json($ret);
}
public function readFile(&$details,$property_id)
{
	ini_set('memory_limit','-1');
	ini_set('max_execution_time', 3000);
	set_time_limit(0);
	 $csv_data = [];
		 $i=0; $j=0;
		if(($handle = fopen ( public_path () . $details->folder_path.$details->filename, 'r' )) !== FALSE) 
		{
			while ( ($data = fgetcsv ( $handle, 1000, ',' )) !== FALSE ) 
			{
				if($j>1)	
				{
					if($i>=0 && $i<100000)
					{
						if($data[0] != "" || $data[0] != null || is_numeric($data[0]) || 
						$data[1] != "" || $data[1] != null || is_numeric($data[1]) || 
						$data[2] != "" || $data[2] != null ||
						$data[3] != "" || $data[3] != null ||
						$data[4] != "" || $data[4] != null || 
						$data[5] != "" || $data[5] != null ||
						$data[6] != "" || $data[6] != null || is_numeric($data[6]) || 
						$data[7] != "" || $data[7] != null || is_numeric($data[7]) ){
							$csv_data[] = array('request_date' => $data [0],'call_from' => $data [1],'date' => $data [2],'time' => $data [3],
									'call_to' => $data [4],'country' => $data [5],'duration' => $data [6],'charges' => $data [7]);
						}
					}	
					$i++;
					if($i==100000)
					{
						foreach (array_chunk($csv_data,1000) as $t) 
						{

						//	MobileCalls::insert($t);
						$processed=[];$unprocessed=[];
						$this->processData($t,$processed,$unprocessed,$property_id,$details->id);
						MobileCalls::insert($processed);
						Csvdata::insert($unprocessed);
						}
						$csv_data = [];
						
						$i=0;
					}				
				}
				$j++;
			}
			fclose ( $handle );
		}

		
if(count($csv_data)>0)
{
	foreach (array_chunk($csv_data,1000) as $t) 
					{
					$processed=[];$unprocessed=[];
					$this->processData($t,$processed,$unprocessed,$property_id,$details->id);
					MobileCalls::insert($processed);
					Csvdata::insert($unprocessed);
					}
}
$data=Csvdata::all();
$filename='Error_Mobile_List_'. date('d_M_Y_H_i');
$content1 = Excel::create($filename, function ($excel) use ($data) {
				$excel->sheet('', function ($sheet) use ($data) {
					$sheet->setOrientation('landscape');

					
					$row_num = 3;
					foreach ($data as $key => $value) {
					$row = array(
					$value->request_date, $value->call_from, $value->call_from, $value->date, $value->time, $value->call_to, $value->country, $value->duration, $value->charges );
					$sheet->row($row_num, $row);
			
					$row_num += 1;	# code...
					}
					
				});
			})->store('csv', public_path() .'/uploads/callaccount/',true);
			$content_path = $content1['full'];
		Csvdata::truncate();
			$details->status='Finished';
			$details->error_filename=$filename.'.csv';
			$details->error_path='/uploads/callaccount/';
			 $details->save();
			  $this->sendSyncNotify($details);
}
public function syncMobileTrackList(Request $request) {
		
		//$details = $request->get('row', '');
		$property_id = 4;
		 //$row= MobileTrack::find($details['id']);
		 $details=MobileTrack::where('status','Sync Start')->get();
		 //$handle = fopen ( public_path () . '/uploads/callaccount/JUL18-10851879_Call_Details.csv', 'r' );
	
		if(!empty($details))
		{

			//check admin ext
		//$rules = PropertySetting::getClassifyRuleSetting($property_id);
		//$smtp = Functions::getMailSetting($property_id, 'notification_');

		// $status = CommonStatusPerProperty::find($property_id);
		// if( empty($status) )
		// {
			// set max call no based on 1 month ago
			//$status = new CommonStatusPerProperty();
			//$status->id = $property_id;

			

			//$status->max_admin_call_no = $max_admin_call_no;
	//	}

		$total_start = microtime(true);	
		 foreach ($details as $key => $value) {
			 date_default_timezone_set(config('app.timezone'));
			$month_ago = date("Y-m-d", strtotime('-1 month'));
			//$monthNum  = 3;
			
			$dateObj   = DateTime::createFromFormat('F', $value->type);
			$monthNum = $dateObj->format('m');
			date_default_timezone_set(config('app.timezone'));
			$month_start = date("Y-m-d", strtotime('1 '.$value->type));
			$cur_yr = date('Y');

		
		$month = new DateTime($month_start);
		$days=cal_days_in_month(CAL_GREGORIAN,$monthNum,$cur_yr)-1 ;
		$month->add(new DateInterval('P'.$days.'D'));
		$month_end = $month->format('Y-m-d');

			// $month_ago = '2000-10-10';
$range=array($month_start, $month_end);
			
			 $value->status='Processing';
			 $value->save();
		
			   $this->sendSyncNotify($value);
			  $this->readFile($value,$property_id);

			 $users=DB::table('call_mobile_calls as mc')
				 ->leftJoin('common_users as cu','cu.id','=','mc.user_id')
				 ->join('common_property as cp', 'mc.property_id', '=', 'cp.id')
				 ->where('mc.track_id',$value->id)
				->whereBetween('mc.date',$range)
				 ->distinct('user_id')
				 ->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->get();
				  $ids=[];
				  foreach ($users as $key3 => $value3) {
				 	$ids[]=$value3->id;
				  }
		 $unclassified=DB::table('call_mobile_calls as ac')	
					 ->where('ac.classify','Unclassified')
					 ->where('ac.track_id',$value->id)
->whereBetween('ac.date', $range)

					->select(DB::raw("ac.user_id,  COUNT(ac.id) AS cnt, ROUND(sum(ac.charges), 2) as totalcharge, sum(ac.duration) as totalsec, 
					sum(1 * (call_type =  'International')) AS cnt_int, ROUND(sum(charges * (call_type = 'International')),2) as totalcharge_int,
					 sum(1 * (call_type = 'Mobile')) AS cnt_mob, ROUND(sum(charges * (call_type = 'Mobile')),2) as totalcharge_mob,
					 sum(1 * (call_type = 'Local')) AS cnt_loc, ROUND(sum(charges * (call_type = 'Local')),2) as totalcharge_loc,
					 sum(1 * (call_type = 'National')) AS cnt_nat, ROUND(sum(charges * (call_type = 'National')),2) as totalcharge_nat,
					 sum(1 * (call_type = 'Others')) AS cnt_oth, ROUND(sum(charges * (call_type = 'Others')),2) as totalcharge_oth"))->groupBy('ac.user_id')->get();
				$i=0;$j=5;
				
		foreach ($unclassified as $key => $val) {
			foreach ($users as $value1) {
				$cur_time = date('Y-m-d H:i:s');
				if($value1->id==$val->user_id)
				{
					


					if($i==5)
				{
					$i=0;
					$j=$j+5;
				}
				$last_time = new DateTime($cur_time);
				$last_time->add(new DateInterval('PT'.$j.'M'));
				$last_time = $last_time->format('Y-m-d H:i:s');
				$reminder =  new ClassifyReminder();
				$reminder->user_id=$value1->id;
				$reminder->property_id=$property_id;
				$reminder->reminder_flag=0;
				$reminder->status='Unsent';
				$reminder->type='Init';
				$reminder->month=$value->type;
				$reminder->range=json_encode($range);
				$reminder->updated_at=$cur_time;
				$reminder->send_start_at=$last_time;
				$reminder->unclassify_pending='Yes';
				$reminder->save();
				$i=$i+1;
				}
			}
		}
		$remaining = $this->checkUnapprovedAdminCall($property_id,  $range, $ids, $value->type,NULL);
		if(!empty($remaining))
		{
			$i=0;$j=5;
			foreach ($remaining as $keys => $values) {
				$cur_time = date('Y-m-d H:i:s');
				if(!empty($values['user']))
				{
					if($i==5)
				{
					$i=0;
					$j=$j+5;
				}
				$last_time = new DateTime($cur_time);
				$last_time->add(new DateInterval('PT'.$j.'M'));
				$last_time = $last_time->format('Y-m-d H:i:s');
				$reminder =  new ClassifyReminder();
				$reminder->user_id=$values['user']->id;
				$reminder->property_id=$property_id;
				$reminder->reminder_flag=0;
				$reminder->status='Unsent';
				$reminder->type='Init';
				$reminder->month=$value->type;
				$reminder->range=json_encode($range);
				$reminder->updated_at=$cur_time;
				$reminder->send_start_at=$last_time;
				$reminder->unclassify_pending='Yes';
				$reminder->save();
				$i=$i+1;
				}
				}
				
			//	}
			}
		}
		$ret = array();

		

		$ret['message'] = "Sync completed successfully.";
	

		return Response::json($ret);
		 }
		 

//$data=Csvdata::all();

		// if($pageSize < 0 )
		// 	$pageSize = 20;
            
		// if(count($processed)>0)
		// 		{
		// 			foreach (array_chunk($processed,1000) as $q) 
		// 			{
		// 				MobileCalls::insert($q);
		// 			}
		// 		}
		// if(count($unprocessed)>0)
		// 		{
		// 			foreach (array_chunk($unprocessed,1000) as $q) 
		// 			{
		// 				Csvdata::insert($q);
		// 			}
		// 		}		
		// $row= MobileTrack::find($details['id']);
		// $row->merge_flag=1;
		// $row->save();
		 
	}

	public function checkAdminCallClassify($property_id, $rules,  $range, $user_id) {
		

		
		//foreach($department_list as $dept) {
			// find extesion list in department
			$ext_list = StaffExternal::getMyextIds($user_id);

			$start = microtime(true);

			
			 $unclassified=DB::table('call_admin_calls as ac')	
					->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
					 ->where('ac.classify','Unclassified')
					 ->where('carrier_charges', '>', 0)
					->whereIn('extension_id', $ext_list)
					->whereBetween('call_date', $range)
					->select(DB::raw("ac.user_id,se.extension,  COUNT(ac.id) AS cnt, ROUND(sum(ac.carrier_charges), 2) as totalcharge, sum(ac.duration) as totalsec, 
					sum(1 * (call_type =  'International')) AS cnt_int, ROUND(sum(carrier_charges * (call_type = 'International')),2) as totalcharge_int,
					 sum(1 * (call_type = 'Mobile')) AS cnt_mob, ROUND(sum(carrier_charges * (call_type = 'Mobile')),2) as totalcharge_mob,
					 sum(1 * (call_type = 'Local')) AS cnt_loc, ROUND(sum(carrier_charges * (call_type = 'Local')),2) as totalcharge_loc,
					 sum(1 * (call_type = 'National')) AS cnt_nat, ROUND(sum(carrier_charges * (call_type = 'National')),2) as totalcharge_nat,
					 sum(1 * (call_type = 'Others')) AS cnt_oth, ROUND(sum(carrier_charges * (call_type = 'Others')),2) as totalcharge_oth"))->groupBy('ac.extension_id')->get();

			// find unapproved call in department
			
		
		return $unclassified;
	}

	public function callReminderMail(Request $request) {

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i");
		$property_id = $request->get('property_id', 0);

		$unclassified = $this->checkUnapprovedAdminCall($property_id,  NULL, [], NULL, NULL);
		$this->checkUnapprovedMobileCall($property_id,NULL,  $unclassified,NULL, NULL);
		echo json_encode($unclassified);
		if(!empty($unclassified))
		{
			$i=0;$j=5;
			foreach ($unclassified as $keys => $values) {
				if(!empty($values['user']))
				{
				if($i==5)
				{
					$i=0;
					$j=$j+5;
				}
				$last_time = new DateTime($cur_time);
				$last_time->add(new DateInterval('PT'.$j.'M'));
				$last_time = $last_time->format('Y-m-d H:i:s');
				$reminder =  new ClassifyReminder();
				$reminder->user_id=$values['user']->id;
				$reminder->property_id=$property_id;
				$reminder->reminder_flag=0;
				$reminder->status='Unsent';
				$reminder->type='Reminder';
				$reminder->updated_at=$cur_time;
				$reminder->send_start_at=$last_time;
				$reminder->unclassify_pending='Yes';
				$reminder->save();
				$i=$i+1;
					//$this->sendNotifyForClassify($values['user'],NULL,$values['admin']);
				}
			}
		}

	}

		public function callFinalMail(Request $request) {

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i");
		$property_id = $request->get('property_id', 0);

		$unclassified = $this->checkUnapprovedAdminCall($property_id,  NULL, [], NULL, NULL);
		$this->checkUnapprovedMobileCall($property_id,NULL,  $unclassified,NULL,  NULL);
		
		if(!empty($unclassified))
		{
			$i=0;$j=5;
			foreach ($unclassified as $keys => $values) {
				if(!empty($values['user']))
				{
				if($i==5)
				{
					$i=0;
					$j=$j+5;
				}
				$last_time = new DateTime($cur_time);
				$last_time->add(new DateInterval('PT'.$j.'M'));
				$last_time = $last_time->format('Y-m-d H:i:s');
				$reminder =  new ClassifyReminder();
				$reminder->user_id=$values['user']->id;
				$reminder->property_id=$property_id;
				$reminder->reminder_flag=0;
				$reminder->status='Unsent';
				$reminder->type='Final';
				$reminder->updated_at=$cur_time;
				$reminder->send_start_at=$last_time;
				$reminder->unclassify_pending='Yes';
				$reminder->save();
				$i=$i+1;
					//$this->sendNotifyForClassify($values['user'],NULL,$values['admin']);
				}
			}
		}

	}
	public function checkClassifyInit(Request $request) {
		

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$end_time = new DateTime($cur_time);
		$end_time->sub(new DateInterval('PT1M'));
		$end_time = $end_time->format('Y-m-d H:i:s');
		$range=array($end_time,$cur_time);

		$property_list = DB::table('common_property')
			->get();	

			echo $cur_time;
		foreach($property_list as $row1)
		{
			$reminder_list = DB::table('call_classify_reminder as ccr')
				->where('ccr.property_id', $row1->id)
				->whereBetween('ccr.send_start_at', $range)
				->where('ccr.status', 'Unsent')
				->where('ccr.type', 'Init')
				->select(DB::raw('ccr.*'))
				->get();
				
			
			foreach ($reminder_list as $key => $value) {
				$ids=[];
				$ids[]=$value->user_id;
		// 	}
		// //$setting = PropertySetting::getGSDeviceSetting($row->id);
		 echo $cur_time;
		// if(!empty($ids))
		// {
			// if($i==5)
					// {
					// 	usleep(300000000);
					// 	$i=0;
					// }
					// $unclassified_admin=$this->checkAdminCallClassify($property_id,  $range, $val->user_id);
				
					// $value1->month=$value->type;
					// $value1->mobile_flag=1;
					// if(!empty($value1->email))
					// $this->sendNotifyForClassify($value1,$val,$unclassified_admin,NULL,1);
					// $i++;
					//$this->sendNotifyForClassify($values['user'],NULL,$values['admin'],NULL,2);
					$reminder = ClassifyReminder::find($value->id);
					if($reminder->status=='Unsent')
					{
		$unclassified = $this->checkUnapprovedAdminCall($row1->id,  json_decode($value->range), [], $value->month, $ids);
		$this->checkUnapprovedMobileCall($row1->id,  json_decode($value->range),  $unclassified,$value->month, $ids);
	
		if(!empty($unclassified))
		{
			foreach ($unclassified as $keys => $values) {
					
					if(!empty($values['user']->email))
					{
					if(!empty($values['mobile']) && !empty($values['admin']))
					$this->sendNotifyForClassify($values['user'],$values['mobile'],$values['admin'], NULL,3);
					else if(!empty($values['mobile']))
					$this->sendNotifyForClassify($values['user'],$values['mobile'],NULL, NULL,4);
					else
					 $this->sendNotifyForClassify($values['user'],NULL,$values['admin'],NULL,5);
					}

				//	}	 
			}
			//	}
			}
			
			$reminder->reminder_flag=1;
			$reminder->status='Sent';
			$reminder->save();
		}
			
		}
		}
		
		
	}
	public function checkClassifyReminders(Request $request) {
		

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$end_time = new DateTime($cur_time);
		$end_time->sub(new DateInterval('PT1M'));
		$end_time = $end_time->format('Y-m-d H:i:s');
		$range=array($end_time,$cur_time);

		$property_list = DB::table('common_property')
			->get();	

		
		foreach($property_list as $row1)
		{
			$reminder_list = DB::table('call_classify_reminder as ccr')
				->where('ccr.property_id', $row1->id)
				->whereBetween('ccr.send_start_at', $range)
				->where('ccr.status', 'Unsent')
				->where('ccr.type', 'Reminder')
				->select(DB::raw('ccr.*'))
				->get();
			$ids=[];	
			
			foreach ($reminder_list as $key => $value) {
				$ids[]=$value->user_id;
			}
		//$setting = PropertySetting::getGSDeviceSetting($row->id);
		echo $cur_time;
		if(!empty($ids))
		{
			
		$unclassified = $this->checkUnapprovedAdminCall($row1->id,  NULL, [], NULL, $ids);
		$this->checkUnapprovedMobileCall($row1->id,NULL,  $unclassified,NULL,  $ids);
	
		if(!empty($unclassified))
		{
			foreach ($unclassified as $keys => $values) {
					
					if(!empty($values['user']->email))
					{
					if(!empty($values['mobile']) && !empty($values['admin']))
					$this->sendNotifyForClassify($values['user'],$values['mobile'],$values['admin'], $reminder_list,3);
					else if(!empty($values['mobile']))
					$this->sendNotifyForClassify($values['user'],$values['mobile'],NULL, $reminder_list,4);
					else
					 $this->sendNotifyForClassify($values['user'],NULL,$values['admin'],$reminder_list,5);
					}

				//	}	 
			}
			//	}
			}
			
		}
		}
		
		
	}

		public function checkClassifyFinalMail(Request $request) {
		

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$end_time = new DateTime($cur_time);
		$end_time->sub(new DateInterval('PT1M'));
		$end_time = $end_time->format('Y-m-d H:i:s');
		$range=array($end_time,$cur_time);

		$property_list = DB::table('common_property')
			->get();	

		
		foreach($property_list as $row1)
		{
			$reminder_list = DB::table('call_classify_reminder as ccr')
				->where('ccr.property_id', $row1->id)
				//->whereBetween('ccr.send_start_at', $range)
				->where('ccr.status', 'Unsent')
				->where('ccr.type', 'Final')
				->select(DB::raw('ccr.*'))
				->get();
			$ids=[];	
			
			foreach ($reminder_list as $key => $value) {
				$ids[]=$value->user_id;
			}
		//$setting = PropertySetting::getGSDeviceSetting($row->id);
		//echo $cur_time;
		$month = date("F", strtotime('-1 month'));
		$monthNum = date("m", strtotime('-1 month'));
		
		$month_start = date("Y-m-d", strtotime('1 '.$month));
		$cur_yr = date('Y');

		 $month_date = new DateTime($month_start);
		 $days=cal_days_in_month(CAL_GREGORIAN,$monthNum,$cur_yr)-1 ;
		 $month_date->add(new DateInterval('P'.$days.'D'));
		 $month_end = $month_date->format('Y-m-d');
		$range=array($month_start, $month_end);

		if(!empty($ids))
		{
			
		$personal = $this->checkPersonalAdminCall($row1->id,  $range, [], NULL, $ids);
		$this->checkPersonalMobileCall($row1->id,$range, $personal, $ids);
	
		if(!empty($personal))
		{
			foreach ($personal as $keys => $values) {
					
					
					if(!empty($values['mobile']) && !empty($values['admin']))
					$this->sendFinalNotifyForClassify($values['user'],$values['mobile'],$values['admin'], $reminder_list,$month);
					else if(!empty($values['mobile']))
					$this->sendFinalNotifyForClassify($values['user'],$values['mobile'],NULL, $reminder_list,$month);
					else
					 $this->sendFinalNotifyForClassify($values['user'],NULL,$values['admin'],$reminder_list,$month);

				//	}	 
			}
			//	}
			}
			
		}
		}
		
		
	}



	private function checkUnapprovedAdminCall($property_id,  $range, $ids, $type, $user_ids)
	{
		// find all department in a property
		$department_list = DB::table('common_department as cd')
				->where('cd.property_id', $property_id)
				->select(DB::raw('cd.*'))
				->get();

		$unmarked_users=[];
		if(empty($user_ids))
		{
		foreach($department_list as $dept) {
			// find extesion list in department
			$ext_list = StaffExternal::getExtIdsInDept($dept->id);

			//$start = microtime(true);
			
			 $query=DB::table('call_admin_calls as ac')
			->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
			->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('classify', 'Unclassified')
				->where('carrier_charges', '>', 0)
				->whereIn('extension_id', $ext_list)
				->whereNotIn('se.user_id', $ids);
				if(!empty($range))
				$query->whereBetween('call_date', $range);

				$unmarked_info = $query->select(DB::raw("ac.user_id,cu.first_name,se.extension,  COUNT(ac.id) AS cnt, ROUND(sum(ac.carrier_charges), 2) as totalcharge, sum(ac.duration) as totalsec, 
					sum(1 * (call_type =  'International')) AS cnt_int, ROUND(sum(carrier_charges * (call_type = 'International')),2) as totalcharge_int,
					 sum(1 * (call_type = 'Mobile')) AS cnt_mob, ROUND(sum(carrier_charges * (call_type = 'Mobile')),2) as totalcharge_mob,
					 sum(1 * (call_type = 'Local')) AS cnt_loc, ROUND(sum(carrier_charges * (call_type = 'Local')),2) as totalcharge_loc,
					 sum(1 * (call_type = 'National')) AS cnt_nat, ROUND(sum(carrier_charges * (call_type = 'National')),2) as totalcharge_nat,
					 sum(1 * (call_type = 'Others')) AS cnt_oth, ROUND(sum(carrier_charges * (call_type = 'Others')),2) as totalcharge_oth"))->groupBy('ac.extension_id')->get();
			
					 if(!empty($unmarked_info))
			foreach ($unmarked_info as $key => $value) {
				
					if(empty($unmarked_users[$value->user_id]))
					{
						$user = DB::table('common_users as cu')
						->leftJoin('common_department as de','cu.dept_id','=','de.id')
						->join('common_property as cp', 'de.property_id', '=', 'cp.id')
						->where('cu.id',$value->user_id)
						->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->first();
						if(!empty($user))
						{
						if(!empty($type))
						{
						$user->month=$type;
						}
						$user->mobile_flag=0;
						
						$unmarked_users[$value->user_id]['user']=$user;
				
						}
					}	

					$unmarked_users[$value->user_id]['admin'][]=$value;
					
			}
			

		}
	}
	else {
		//$ext_list = StaffExternal::getExtIdsInDept($dept->id);

			//$start = microtime(true);
			
			 $query=DB::table('call_admin_calls as ac')
			->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
			->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('classify', 'Unclassified')
				->where('carrier_charges', '>', 0)
				->whereIn('se.user_id', $user_ids);
				if(!empty($range))
				$query->whereBetween('call_date', $range);

				$unmarked_info = $query->select(DB::raw("ac.user_id,cu.first_name,se.extension,  COUNT(ac.id) AS cnt, ROUND(sum(ac.carrier_charges), 2) as totalcharge, sum(ac.duration) as totalsec, 
					sum(1 * (call_type =  'International')) AS cnt_int, ROUND(sum(carrier_charges * (call_type = 'International')),2) as totalcharge_int,
					 sum(1 * (call_type = 'Mobile')) AS cnt_mob, ROUND(sum(carrier_charges * (call_type = 'Mobile')),2) as totalcharge_mob,
					 sum(1 * (call_type = 'Local')) AS cnt_loc, ROUND(sum(carrier_charges * (call_type = 'Local')),2) as totalcharge_loc,
					 sum(1 * (call_type = 'National')) AS cnt_nat, ROUND(sum(carrier_charges * (call_type = 'National')),2) as totalcharge_nat,
					 sum(1 * (call_type = 'Others')) AS cnt_oth, ROUND(sum(carrier_charges * (call_type = 'Others')),2) as totalcharge_oth"))->groupBy('ac.extension_id')->get();
			
					 if(!empty($unmarked_info))
			foreach ($unmarked_info as $key => $value) {
				
					if(empty($unmarked_users[$value->user_id]))
					{
						$user = DB::table('common_users as cu')
						->leftJoin('common_department as de','cu.dept_id','=','de.id')
						->join('common_property as cp', 'de.property_id', '=', 'cp.id')
						->where('cu.id',$value->user_id)
						->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->first();
						if(!empty($user))
						{
						if(!empty($type))
						{
						$user->month=$type;
						}
						$user->mobile_flag=0;
						
						$unmarked_users[$value->user_id]['user']=$user;
						
						}
					}	

					$unmarked_users[$value->user_id]['admin'][]=$value;
					
			}
	}

		return $unmarked_users;
	}
	private function checkUnapprovedMobileCall($property_id,$range,  &$admin,$type, $user_ids)
	{
		// find all department in a property
	// $department_list = DB::table('common_department as cd')
	// 			->where('cd.property_id', $property_id)
	// 			->select(DB::raw('cd.*'))
	// 			->get();

		$unmarked_users=[];
		//foreach($department_list as $dept) {
			// find extesion list in department
			//$ext_list = StaffExternal::getExtIdsInDept($dept->id);

			//$start = microtime(true);
			
			if(empty($user_ids))
			{
			 $query=DB::table('call_mobile_calls as ac')
			->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('classify', 'Unclassified');
				if(!empty($range))
				$query->whereBetween('date', $range);

				$unmarked_info = $query->select(DB::raw("ac.user_id,  COUNT(ac.id) AS cnt, ROUND(sum(ac.charges), 2) as totalcharge, sum(ac.duration) as totalsec, 
					sum(1 * (call_type =  'International')) AS cnt_int, ROUND(sum(charges * (call_type = 'International')),2) as totalcharge_int,
					 sum(1 * (call_type = 'Mobile')) AS cnt_mob, ROUND(sum(charges * (call_type = 'Mobile')),2) as totalcharge_mob,
					 sum(1 * (call_type = 'Local')) AS cnt_loc, ROUND(sum(charges * (call_type = 'Local')),2) as totalcharge_loc,
					 sum(1 * (call_type = 'National')) AS cnt_nat, ROUND(sum(charges * (call_type = 'National')),2) as totalcharge_nat,
					 sum(1 * (call_type = 'Others')) AS cnt_oth, ROUND(sum(charges * (call_type = 'Others')),2) as totalcharge_oth"))->groupBy('ac.user_id')->get();
			
					 if(!empty($unmarked_info))
			foreach ($unmarked_info as $key => $value) {
		
					if(empty($admin[$value->user_id]))
					{
						$user = DB::table('common_users as cu')
						->leftJoin('common_department as de','cu.dept_id','=','de.id')
						->join('common_property as cp', 'de.property_id', '=', 'cp.id')
						->where('cu.id',$value->user_id)
						->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->first();
					if(!empty($user))
						{
						if(!empty($type))
						{
						$user->month=$type;
						}
						 $user->mobile_flag=1;
						$admin[$value->user_id]['user']=$user;
					
						}
						// $user->month=$type;
						
					}	
					$admin[$value->user_id]['user']->mobile_flag=1;
					$admin[$value->user_id]['mobile']=$value;
					
			}
		}
		else {
			 $query=DB::table('call_mobile_calls as ac')
			->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('classify', 'Unclassified')
				->whereIn('ac.user_id', $user_ids);
				if(!empty($range))
				$query->whereBetween('date', $range);

				$unmarked_info = $query->select(DB::raw("ac.user_id,  COUNT(ac.id) AS cnt, ROUND(sum(ac.charges), 2) as totalcharge, sum(ac.duration) as totalsec, 
					sum(1 * (call_type =  'International')) AS cnt_int, ROUND(sum(charges * (call_type = 'International')),2) as totalcharge_int,
					 sum(1 * (call_type = 'Mobile')) AS cnt_mob, ROUND(sum(charges * (call_type = 'Mobile')),2) as totalcharge_mob,
					 sum(1 * (call_type = 'Local')) AS cnt_loc, ROUND(sum(charges * (call_type = 'Local')),2) as totalcharge_loc,
					 sum(1 * (call_type = 'National')) AS cnt_nat, ROUND(sum(charges * (call_type = 'National')),2) as totalcharge_nat,
					 sum(1 * (call_type = 'Others')) AS cnt_oth, ROUND(sum(charges * (call_type = 'Others')),2) as totalcharge_oth"))->groupBy('ac.user_id')->get();
			
					 if(!empty($unmarked_info))
			foreach ($unmarked_info as $key => $value) {
				
					if(empty($admin[$value->user_id]))
					{
						$user = DB::table('common_users as cu')
						->leftJoin('common_department as de','cu.dept_id','=','de.id')
						->join('common_property as cp', 'de.property_id', '=', 'cp.id')
						->where('cu.id',$value->user_id)
						->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->first();
					
						// $user->month=$type;
						if(!empty($user))
						{
						if(!empty($type))
						{
						$user->month=$type;
						}
						 $user->mobile_flag=1;
						$admin[$value->user_id]['user']=$user;
					
						}
					}
					$admin[$value->user_id]['user']->mobile_flag=1;	

					$admin[$value->user_id]['mobile']=$value;
					
			}
		}
			

		//}
		//+return $unmarked_users;
	}	

	private function checkPersonalAdminCall($property_id,  $range, $ids, $type, $user_ids)
	{
		// find all department in a property
		$department_list = DB::table('common_department as cd')
				->where('cd.property_id', $property_id)
				->select(DB::raw('cd.*'))
				->get();

		$unmarked_users=[];
		if(empty($user_ids))
		{
		foreach($department_list as $dept) {
			// find extesion list in department
			$ext_list = StaffExternal::getExtIdsInDept($dept->id);

			//$start = microtime(true);
			
			 $query=DB::table('call_admin_calls as ac')
			->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
			->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
			->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('classify', 'Personal')
				->where('carrier_charges', '>', 0)
				->whereIn('extension_id', $ext_list)
				->whereNotIn('se.user_id', $ids);
				if(!empty($range))
				$query->whereBetween('call_date', $range);

				$unmarked_info = $query->select(DB::raw("ac.*,cu.first_name,se.extension, dest.country"))->get();
			
					 if(!empty($unmarked_info))
			foreach ($unmarked_info as $key => $value) {
				
					if(empty($unmarked_users[$value->user_id]))
					{
						$user = DB::table('common_users as cu')
						->leftJoin('common_department as de','cu.dept_id','=','de.id')
						->join('common_property as cp', 'de.property_id', '=', 'cp.id')
						->where('cu.id',$value->user_id)
						->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->first();
						if(!empty($user))
						{
						if(!empty($type))
						{
						$user->month=$type;
						}
						$user->mobile_flag=0;
						
						$unmarked_users[$value->user_id]['user']=$user;
				
						}
					}	

					$unmarked_users[$value->user_id]['admin'][]=$value;
					
			}
			

		}
	}
	else {
		//$ext_list = StaffExternal::getExtIdsInDept($dept->id);

			//$start = microtime(true);
			
			 $query=DB::table('call_admin_calls as ac')
			->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
			->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
			->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('classify', 'Personal')
				->where('carrier_charges', '>', 0)
				->whereIn('se.user_id', $user_ids);
				if(!empty($range))
				$query->whereBetween('call_date', $range);

				$unmarked_info = $query->select(DB::raw("ac.*,cu.first_name,se.extension, dest.country"))->get();
			
					 if(!empty($unmarked_info))
			foreach ($unmarked_info as $key => $value) {
				
					if(empty($unmarked_users[$value->user_id]))
					{
						$user = DB::table('common_users as cu')
						->leftJoin('common_department as de','cu.dept_id','=','de.id')
						->join('common_property as cp', 'de.property_id', '=', 'cp.id')
						->where('cu.id',$value->user_id)
						->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->first();
						if(!empty($user))
						{
						if(!empty($type))
						{
						$user->month=$type;
						}
						$user->mobile_flag=0;
						
						$unmarked_users[$value->user_id]['user']=$user;
						
						}
					}	

					$unmarked_users[$value->user_id]['admin'][]=$value;
					
			}
	}

		return $unmarked_users;
	}
	private function checkPersonalMobileCall($property_id,$range,  &$admin, $user_ids)
	{
		// find all department in a property
	// $department_list = DB::table('common_department as cd')
	// 			->where('cd.property_id', $property_id)
	// 			->select(DB::raw('cd.*'))
	// 			->get();

		$unmarked_users=[];
		//foreach($department_list as $dept) {
			// find extesion list in department
			//$ext_list = StaffExternal::getExtIdsInDept($dept->id);

			//$start = microtime(true);
			
			if(empty($user_ids))
			{
			 $query=DB::table('call_mobile_calls as ac')
			 ->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
			->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('classify', 'Personal');
				if(!empty($range))
				$query->whereBetween('date', $range);

				$unmarked_info = $query->select(DB::raw("ac.*,dest.country"))->get();
			
					 if(!empty($unmarked_info))
			foreach ($unmarked_info as $key => $value) {
		
					if(empty($admin[$value->user_id]))
					{
						$user = DB::table('common_users as cu')
						->leftJoin('common_department as de','cu.dept_id','=','de.id')
						->join('common_property as cp', 'de.property_id', '=', 'cp.id')
						->where('cu.id',$value->user_id)
						->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->first();
					if(!empty($user))
						{
						 $user->mobile_flag=1;
						$admin[$value->user_id]['user']=$user;
					
						}
						// $user->month=$type;
						
					}	
					$admin[$value->user_id]['user']->mobile_flag=1;
					$admin[$value->user_id]['mobile'][]=$value;
					
			}
		}
		else {
			 $query=DB::table('call_mobile_calls as ac')
			 ->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
			->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->where('classify', 'Personal')
				->whereIn('ac.user_id', $user_ids);
				if(!empty($range))
				$query->whereBetween('date', $range);

				$unmarked_info = $query->select(DB::raw("ac.*,dest.country"))->get();
			
					 if(!empty($unmarked_info))
			foreach ($unmarked_info as $key => $value) {
				
					if(empty($admin[$value->user_id]))
					{
						$user = DB::table('common_users as cu')
						->leftJoin('common_department as de','cu.dept_id','=','de.id')
						->join('common_property as cp', 'de.property_id', '=', 'cp.id')
						->where('cu.id',$value->user_id)
						->select(DB::raw('cu.*, cp.name as property_name, cp.id as prop_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))->first();
					
						// $user->month=$type;
						if(!empty($user))
						{
						 $user->mobile_flag=1;
						$admin[$value->user_id]['user']=$user;
					
						}
					}
					$admin[$value->user_id]['user']->mobile_flag=1;	

					$admin[$value->user_id]['mobile'][]=$value;
					
			}
		}
			

		//}
		//+return $unmarked_users;
	}	
	
	private function sendNotifyForClassify($user,$details,$unclassified_admin,$reminder_list,$f) {
	
	
$message_content = sprintf('The status of issue IT%05d',
										$user->id);
	 
			
			$ip = DB::table('property_setting as ps')
			    ->select(DB::raw('ps.value'))
			    ->where('ps.settings_key', 'hotlync_host')
				->first();
		$settings = array();
		$settings['default_password'] = 0;
		$settings['manual_link'] = $ip->value.'hotlync';
		$settings = PropertySetting::getPropertySettings($user->prop_id, $settings);
		

		if( empty($user) )
			return $message_content;

		//$it_manager = $userlist[0];

		$user->content = $message_content;
		//$issue->req_content = $message_content_2;
		$send_flag=0;
		if(($settings['default_password']==$user->password))	
		{
			$user1 = CommonUser::find($user->id);
			if($user1->first_send != '1')
			{
				$user1->first_send='1';
				$user1->save();
				$send_flag=1;
			}
		}			
			
						
			

		$info = array();
		$info['password'] = ($send_flag==1)?($settings['default_password']):'';
		$info['wholename'] = $user->wholename;
		$info['number'] = $user->mobile;
		$info['username'] = $user->username;
		$info['mobile_flag'] = ($user->mobile_flag) ? 1:0;
		$info['manual_link'] = $settings['manual_link'];
		if(!empty($details))
		{
				//echo json_encode($details);
		$details->total_time= gmdate('H:i:s',$details->totalsec) ;
		$info['count'] = $details->cnt;
		$info['charge'] = $details->totalcharge;
		$info['count_int'] = $details->cnt_int;
		$info['charge_int'] = $details->totalcharge_int;
		$info['count_loc'] = $details->cnt_loc;
		$info['charge_loc'] = $details->totalcharge_loc;
		$info['count_nat'] = $details->cnt_nat;
		$info['charge_nat'] = $details->totalcharge_nat;
		$info['count_mob'] = $details->cnt_mob;
		$info['charge_mob'] = $details->totalcharge_mob;
		$info['duration'] = $details->total_time;
		$info['count_oth'] = $details->cnt_oth;
		$info['charge_oth'] = $details->totalcharge_oth;
		
		

		$total_count=$details->cnt;
		$total_charge=$details->totalcharge;
		$time=explode(':', $details->total_time);
			  if(count($time)>2)
			  $elapse_seconds = (((($time[0])*60)+($time[1]))*60)+($time[2]);
			//   else 
			//    $elapse_seconds = (($time[0])*60);
			  
			  $total_sec=$elapse_seconds;
		
		} 
		else {
			$total_count=0;$total_charge=0;$total_sec=0;
			
		}
		if(!empty($unclassified_admin))
		{
		foreach ($unclassified_admin as $key1 => $value1) {
			$total_count=$total_count+$value1->cnt;
			$total_charge=$total_charge+$value1->totalcharge;
			$total_sec=$total_sec+$value1->totalsec;
		}
		}
		
		$total_time= gmdate('H:i:s',$total_sec) ;
			$info['grand_count']=$total_count;
			$info['grand_charge']=$total_charge;
			$info['grand_duration']=$total_time;
		   $info['host_url'] = $ip->value.'hotlync';


		$info['unclassified_admin'] = $unclassified_admin;
		
		// $info['raised_by'] = $issue->wholename;
		// $info['comment'] = $issue->comment;
		$info['subject'] = "Unclassified Calls";
		$info['dept_name'] = $user->property_name;
	
		if(!empty($user->month))
		$user->subject = sprintf('You have %d Unclassified Call(s) for the month of %s', $total_count,$user->month);
		else 
		$user->subject = sprintf('You have a total of %d Unclassified Call(s)', $total_count);

		$user->email_content = view('emails.call_classify_user', ['info' => $info])->render();

		// $this->sendComplaintNotification($issue->prop_id, $message_content, $issue->comment, $issue, $row->email, $row->mobile, $row->fcm_key, $issue->cc);
		
		// }
		// if($issue->subcat_mngr_flag =='1' && !empty($managers))
		// {
		// $info3['wholename'] = $managers->wholename;
		// $info3['category'] = $issue->category;
		// $info3['sub_cat'] = $issue->subcategory;
		// $info3['severity'] = $issue->sev;
		// $info3['raised_by'] = $issue->wholename;
		// $info3['comment'] = $issue->comment;
		// $info3['subject'] = $issue->issuesubject;
		// $info3['dept_name'] = $issue->property_name;
		// $info3['id'] = $issue->id;
		// $info3['ip'] = $ip->value;
		// $issue->subject = sprintf('IT%05d: Approval Required', $issue->id);
		// $issue->email_content = view('emails.it_issue_approve', ['info' => $info3])->render();

		// $this->sendComplaintNotification($issue->prop_id, $message_content, $issue->comment, $issue, $managers->email, $managers->mobile, $managers->fcm_key, $issue->cc);
			
		// }

		
		$this->sendClassifyNotification($user->prop_id, $message_content, "Unclassified Calls", $user, $user->email, $user->mobile, $user->fcm_key, NULL);
			if(!empty($reminder_list))
			{
				foreach ($reminder_list as $key => $value) {
					
						 if($value->user_id==$user->id && ($value->type=='Reminder'))
				{
					
					$reminder = ClassifyReminder::find($value->id);
					$reminder->reminder_flag=1;
					$reminder->status='Sent';
					$reminder->save();
				}

				}
			}
		return $message_content;
	}
	
	private function sendFinalNotifyForClassify($user,$details,$unclassified_admin,$reminder_list,$month) {
	$data=[];
	$property = DB::table('common_property')
				->where('id', $user->prop_id)
				->first();
		$data['property'] = $property;
	$data['user']=$user;
	$data['mobile']=$details;
	$data['admin']=$unclassified_admin;
	$data['month']=$month;
	//echo json_encode($data);
	$content = view('frontend.report.callaccount.callaccount_detail_total', compact('data'))->render();
	$filename = 'Detail_Report_By_Total_' . date('d_M_Y_H_i') . '_' . $user->id;
	$filename = str_replace(' ', '_', $filename);
		$folder_path = public_path() . '/uploads/reports/';
		$path = $folder_path . $filename . '.html';
		ob_start();
		echo $content;
		$pdf_path = $folder_path . $filename . '.pdf';
		file_put_contents($path, ob_get_contents());

		ob_clean();
		//echo json_encode($content);
	$message_content = sprintf('The status of issue IT%05d',
										$user->id);
	 
			
			$ip = DB::table('property_setting as ps')
			    ->select(DB::raw('ps.value'))
			    ->where('ps.settings_key', 'hotlync_host')
				->first();
		// $settings = array();
		// $settings['default_password'] = 0;
		
		// $settings = PropertySetting::getPropertySettings($user->prop_id, $settings);
		

		// if( empty($user) )
		// 	return $message_content;

		// //$it_manager = $userlist[0];

		// $user->content = $message_content;
		// //$issue->req_content = $message_content_2;
		// $send_flag=0;
		// if(($settings['default_password']==$user->password))	
		// {
		// 	$user1 = CommonUser::find($user->id);
		// 	if($user1->first_send != '1')
		// 	{
		// 		$user1->first_send='1';
		// 		$user1->save();
		// 		$send_flag=1;
		// 	}
		// }			
			
						
			

		$info = array();
		// $info['password'] = ($send_flag==1)?($settings['default_password']):'';
		// $info['wholename'] = $user->wholename;
		// $info['number'] = $user->mobile;
		// $info['username'] = $user->username;
		// $info['mobile_flag'] = ($user->mobile_flag) ? 1:0;
	
		// if(!empty($details))
		// {
		// 		//echo json_encode($details);
		// $details->total_time= gmdate('H:i:s',$details->totalsec) ;
		// $info['count'] = $details->cnt;
		// $info['charge'] = $details->totalcharge;
		// $info['count_int'] = $details->cnt_int;
		// $info['charge_int'] = $details->totalcharge_int;
		// $info['count_loc'] = $details->cnt_loc;
		// $info['charge_loc'] = $details->totalcharge_loc;
		// $info['count_nat'] = $details->cnt_nat;
		// $info['charge_nat'] = $details->totalcharge_nat;
		// $info['count_mob'] = $details->cnt_mob;
		// $info['charge_mob'] = $details->totalcharge_mob;
		// $info['duration'] = $details->total_time;
		// $info['count_oth'] = $details->cnt_oth;
		// $info['charge_oth'] = $details->totalcharge_oth;
		
		

		// $total_count=$details->cnt;
		// $total_charge=$details->totalcharge;
		// $time=explode(':', $details->total_time);
		// 	  if(count($time)>2)
		// 	  $elapse_seconds = (((($time[0])*60)+($time[1]))*60)+($time[2]);
		// 	//   else 
		// 	//    $elapse_seconds = (($time[0])*60);
			  
		// 	  $total_sec=$elapse_seconds;
		
		// } 
		// else {
		// 	$total_count=0;$total_charge=0;$total_sec=0;
			
		// }
		// if(!empty($unclassified_admin))
		// {
		// foreach ($unclassified_admin as $key1 => $value1) {
		// 	$total_count=$total_count+$value1->cnt;
		// 	$total_charge=$total_charge+$value1->totalcharge;
		// 	$total_sec=$total_sec+$value1->totalsec;
		// }
		// }
		
		// $total_time= gmdate('H:i:s',$total_sec) ;
		// 	$info['grand_count']=$total_count;
		// 	$info['grand_charge']=$total_charge;
		// 	$info['grand_duration']=$total_time;
		//    $info['host_url'] = $ip->value.'hotlync';


		// $info['unclassified_admin'] = $unclassified_admin;
		
		// // $info['raised_by'] = $issue->wholename;
		// // $info['comment'] = $issue->comment;
		// $info['subject'] = "Unclassified Calls";
		// $info['dept_name'] = $user->property_name;
	
		// if(!empty($user->month))
		// $user->subject = sprintf('You have %d Unclassified Call(s) for the month of %s', $total_count,$user->month);
		// else 
		// $user->subject = sprintf('You have a total of %d Unclassified Call(s)', $total_count);

		// $user->email_content = view('emails.call_classify_final', ['info' => $info])->render();




		$input = array();
		$input['ip'] = $ip->value;	
		$input['host_url'] = $ip->value . 'uploads/reports/' .$filename.'.pdf' ;
		$input['wholename'] = $user->first_name.' '.$user->last_name;
		$input['month'] = $data['month'];
		
		$request = array();
		
		$request['filename'] = $filename . '.pdf';
		$request['content'] = view('emails.call_classify_final', ['info' => $input])->render();
	
		
		//$request['path'] = $path;
		//$request['folder_path'] = $folder_path;
		$request['to'] = $user->email;
		//$request['subject'] = $settings['schedule_report_subject'];
		$request['subject'] = 'Deductions for the month of '.$input['month'];
		$request['html'] ='Call Classification Final';
		
		$smtp = Functions::getMailSetting($user->prop_id, '');
		$request['smtp'] = $smtp;

		$options = array();
		$options['html'] = $path;
		$options['pdf'] = $pdf_path;
		//$options['attach_flag'] = $attached;
		//$options['paperSize'] = array('format' => 'A4', 'orientation' => 'landscape');
		$options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');
		$request['options'] = $options;

		$message = array();
		$message['type'] = 'report_pdf';
		$message['content'] = $request;
		Redis::publish('notify', json_encode($message));
		
		//}
		// $this->sendComplaintNotification($issue->prop_id, $message_content, $issue->comment, $issue, $row->email, $row->mobile, $row->fcm_key, $issue->cc);
		
		// }
		// if($issue->subcat_mngr_flag =='1' && !empty($managers))
		// {
		// $info3['wholename'] = $managers->wholename;
		// $info3['category'] = $issue->category;
		// $info3['sub_cat'] = $issue->subcategory;
		// $info3['severity'] = $issue->sev;
		// $info3['raised_by'] = $issue->wholename;
		// $info3['comment'] = $issue->comment;
		// $info3['subject'] = $issue->issuesubject;
		// $info3['dept_name'] = $issue->property_name;
		// $info3['id'] = $issue->id;
		// $info3['ip'] = $ip->value;
		// $issue->subject = sprintf('IT%05d: Approval Required', $issue->id);
		// $issue->email_content = view('emails.it_issue_approve', ['info' => $info3])->render();

		// $this->sendComplaintNotification($issue->prop_id, $message_content, $issue->comment, $issue, $managers->email, $managers->mobile, $managers->fcm_key, $issue->cc);
			
		// }

		
		//$this->sendClassifyNotification($user->prop_id, $message_content, "Unclassified Calls", $user, $user->email, $user->mobile, $user->fcm_key, NULL);
			if(!empty($reminder_list))
			{
				foreach ($reminder_list as $key => $value) {
					
						 if($value->user_id==$user->id && ($value->type=='Final'))
				{
					
					$reminder = ClassifyReminder::find($value->id);
					$reminder->reminder_flag=1;
					$reminder->status='Sent';
					$reminder->save();
				}

				}
			}
			return Response::json($request);
		//return $message_content;
	}

	public function sendClassifyNotification($property_id, $subject, $content, $data, $email, $mobile, $pushkey, $cc) {
		
		$complaint_setting = PropertySetting::getComplaintSetting($property_id);

		// check notify mode(email, sms, mobile push)
		$alarm_mode = $complaint_setting['complaint_notify_mode'];

		$email_mode = false;
/*
		$sms_mode = false;
		$webapp_mode = false;
*/		
		if (strpos($alarm_mode, 'email') !== false) {
		    $email_mode = true;
		}

/*
		if (strpos($alarm_mode, 'sms') !== false) {
		    $sms_mode = true;
		}

		if (strpos($alarm_mode, 'webapp') !== false) {
		    $webapp_mode = true;
		}
*/

		if( $email_mode == true )
		{
			
			$smtp = Functions::getMailSetting($property_id, 'notification_');

			$message = array();
			$message['type'] = 'email';

			$message['to'] = $email;
			if(!empty($cc))
			$message['cc'] = $cc;
			if( !empty($data->subject) )
				$message['subject'] = $data->subject;
			else
				$message['subject'] = $subject;

			if( !empty($data->email_content) )
				$message['content'] = $data->email_content;
			else
				$message['content'] = $content;

			$message['smtp'] = $smtp;

			Redis::publish('notify', json_encode($message));
		}


	}
	public function sendFinalClassifyNotification($property_id, $subject, $content, $data, $email, $mobile, $pushkey, $cc) {
	}

	private function sendSyncNotify($data) {
	
		$message = array();
		$message['type'] = 'sync_start';
		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));
	}
	private function processData($data,&$processed,&$unprocessed,$property_id,$track_id)
	{
		
		 $i=0; $j=0;
		  foreach ($data as $key => $value) {
			 
			
			
			//  $string = $value['call_from'];
			//  $call_from  = (float) $string;
			
		
			//$search = '%' . $call_from . '%';
			$phonebook_common = Phonebook::where('user_id',0)->get();
			  $user_from = CommonUser::where('mobile', 'like', $value['call_from'] )->first();
			//  $value['call_from']=$call_from;
			// 	 $string = $value['call_to'];
			// 	 $call_to = (float) $string;
			// 	 $value['call_to']=$call_to;
			  if(!empty($user_from))
			{
				$time=explode('.',$value['duration']);
			  if(count($time)>1)
			  $elapse_seconds = (((intval($time[0]))*60)+(intval($time[1])));
			  else 
			   $elapse_seconds = ((intval($time[0]))*60);
			  
			  $value['duration']=$elapse_seconds;
				
				$format = 'd/m/Y';
				$date = DateTime::createFromFormat($format, $value['date']);
	
				$value['date']= $date->format('Y-m-d');	
				//$search = '%' . $call_to . '%';
				 $phonebook_user = Phonebook::where('contact_no', 'like',$value['call_to'])->where('user_id',$user_from->id)->first();
				if(!empty($phonebook_common))
				{
				 $phonebook_common_usr=[];
				 foreach ($phonebook_common as $key_bk => $value_bk) {
				
					if (strpos(strtolower($value['call_to']),strtolower($value_bk->contact_no))!==false)
					{
						$phonebook_common_usr=$value_bk;
						$value['call_to']=$value_bk->contact_no;
						
					}
					//if()

				 }
				
					if(empty($phonebook_user))
					$phonebook_user=$phonebook_common_usr;
				}	
			
				 $user_to= CommonUser::where('mobile', 'like',$value['call_to'])->first();
				if(!empty($user_to))
				{
					$processed[] = array('track_id' => $track_id,'user_id' => $user_from->id,'call_from' => $value['call_from'],'dept_id' => $user_from->dept_id,'to_user_id' => $user_to->id,'date' => $value['date'],'time' => $value['time'],'classify' => 'Business',
										'approval' => 'Pre-Approved','phonebk_flag' => '0','call_type' => 'Local','property_id' => $property_id,'destination_id' => null,'call_to' => $value['call_to'],'country' => $value['country'],'duration' => $value['duration'],'charges' => $value['charges']);
				}
				else if(!empty($phonebook_user))
				{
					if($phonebook_user->auto_classify==1)
					{
					if($phonebook_user->type=="Personal"){
					$approval='Closed';
					$uploaded_date = DB::table('call_mobile_track as cmt')	
								->where('cmt.id', $track_id)
								->select(DB::raw('ac.created_at'))
								->first();
					$classify_date = $uploaded_date->created_at;
					}
					else 
					$approval = 'Unclassified';

					$type=$phonebook_user->type;
					}
					else {
						$approval = 'Unclassified';
						$type='Unclassified';
					}

					$processed[] = array('track_id' => $track_id,'user_id' => $user_from->id,'call_from' => $value['call_from'],'dept_id' => $user_from->dept_id,'to_user_id' => null,'date' => $value['date'],'time' => $value['time'],'classify' => $type,
										'approval' => $approval,'phonebk_flag' => '1','call_type' => 'Others','property_id' => $property_id,'destination_id' => null,'call_to' => $value['call_to'],'country' => $value['country'],'duration' => $value['duration'],'charges' => $value['charges'],'classify_date' => $classify_date);
				
				}
				else 
				{
				 $call_info = $this->getCallerInfo($value['call_to'], $ret);
				 
					
					//$call_type=$call_info['call_type'];
					if(empty($call_info))
					{$call_type='Others';$destination_id=null;}
					else
					{$call_type=$call_info['call_type'];$destination_id=$call_info['destination']->id;}

					$rules = PropertySetting::getClassifyRuleSetting($property_id);
					$rule_calltypes = array();
					$rule_types = array();
					$rule_durations = array();
						if(!empty($rules['pre_approved_call_types'])) {
							$rule_types = explode("," , $rules['pre_approved_call_types']);
							foreach($rule_types as $type){
								$calltypetemps = explode(":" , $type);
								$rule_calltypes[] = $calltypetemps[0];
								$rule_durations[] = $calltypetemps[1];
							}
						}
						if( !empty($rule_calltypes) && !empty($call_type) && in_array($call_type ,$rule_calltypes)) {
						$index = 0;
							foreach($rule_calltypes as $key => $type){
								if($type == $call_type){
									$index = $key;
									break;
								}
							}
							if($rule_durations[$index] == 0){
								$approval = 'Pre-Approved';
								$classify = 'Business';
							}else if($elapse_seconds < $rule_durations[$index] * 60){
								$approval = 'Pre-Approved';
								$classify = 'Business';
							}else{
								$approval = 'Unclassified';
								$classify = 'Unclassified';
							}
						}
					
					else 
					{
						if(($value['charges']) <= 0) {
							$approval = 'No Approval';
							$classify = 'No Classify';
						}else 
						{
							if($elapse_seconds > $rules['min_approval_duration'] * 60 && ($value['charges']) > $rules['min_approval_amount'] ) {
								$approval = 'Unclassified';
								$classify = 'Unclassified';
							}
							else 
							{
									$approval = 'Pre-Approved';
									$classify = 'Business';
							}
						}
					}
					$processed[] = array('track_id' => $track_id,'user_id' => $user_from->id,'call_from' => $value['call_from'],'dept_id' => $user_from->dept_id,'to_user_id' => null,'date' => $value['date'],'time' => $value['time'],'classify' => $classify,
										'approval' => $approval,'phonebk_flag' => '0','call_type' => $call_type,'destination_id' => $destination_id,'property_id' => $property_id,'call_to' => $value['call_to'],'country' => $value['country'],'duration' => $value['duration'],'charges' => $value['charges']);
				
				}
                // if($i>=0 && $i<100000)
				// 	{
				//	}	
				// $i++;
				// if($i==100000)
				// {
				// 	foreach (array_chunk($processed,1000) as $t) 
				// 	{

				// 		MobileCalls::insert($t);
				// 	}
				// 	$processed = [];
					
				// 	$i=0;
				// }
				
			}
			else 
			{
				// if($j>=0 && $j<100000)
				// 	{
						$unprocessed[] = array('request_date' => $value['request_date'],'call_from' => $value['call_from'],'date' => $value['date'],'time' => $value['time'],
									'call_to' => $value['call_to'],'country' => $value['country'],'duration' => $value['duration'],'charges' => $value['charges']);
				// 	}	
				// $j++;
				// if($j==100000)
				// {
				// 	foreach (array_chunk($unprocessed,1000) as $s) 
				// 	{

				// 		Csvdata::insert($s);
				// 	}
				// 	$unprocessed = [];
					
				// 	$j=0;
				// }
			}

		}
	}


	 private function getCallerInfo($calleeid, &$ret) {
        $result = array();

        // get destination id
        for( $i = strlen( $calleeid ); $i > 0; --$i ) {
            $searchstr = substr($calleeid, 0, $i);
            $destination = Destination::where('code', $searchstr)->first();
            if(!empty($destination))
                break;
        }

        if (empty($destination)) // if there is no destination
        {
            // should be exception
            
            return ;
        }

        $dest_group = GroupDestination::where('destination_id', $destination->id)->first();
        if (empty($dest_group))        // There is no destination group
        {
           return ;
        }

        $carrier_group = CarrierGroup::find($dest_group->carrier_group_id);
        // if (empty($carrier_group)) {
        //     // should be exception
        //     $data = array();
        //     $data['type'] = 'error';
        //     $data['msg'] = 'Invalid Incoming call service request for received from {1} in %1$s.. Cause: There is no carrier group';
        //     array_push($alarm, $data);
        // }

        $call_type = 'Received';
      
        if (!empty($carrier_group)) {
            $call_type = $carrier_group->call_type;
           // $sales_outlet = $carrier_group->sales_outlet;
        }

        $result['code'] = 0;
        $result['destination'] = $destination;
        $result['dest_group'] = $dest_group;
        $result['call_type'] = $call_type;

        return $result;
    }
	public function deleteMobileTrackList(Request $request) {
		$details = $request->get('row', '');
		$property_id = $request->get('property_id', '0');
 		$row= MobileTrack::find($details['id'])->delete();
		// if($pageSize < 0 )
		// 	$pageSize = 20;
		$ret = array();

		

		$ret['message'] = "Row has been deleted successfully.";
		

		return Response::json($ret);
	}

	public function uploadMobileTrackList(Request $request) {
		$output_dir = "uploads/callaccount/";
		
		if(!File::isDirectory(public_path($output_dir)))
			File::makeDirectory(public_path($output_dir), 0777, true, true);

		$ret = array();
		$cur_time = date('Y-m-d H:i:s');
		$filekey = 'mobile_track';

		//$id = $request->get('id', 0);
		$user = $request->get('user', 0);
		$type1 = $request->get('type', 0);
		$type   =  date('F',strtotime($type1));
		$output_file = $_SERVER["DOCUMENT_ROOT"] . '/'.$output_dir ;
	
		if(!file_exists($output_file)) {
			mkdir($output_file, 0777, true);
		}
		// if($request->hasFile($filekey) === false )
		// 	return "No input file";
		
		//You need to handle  both cases
		//If Any browser does not support serializing of multiple files using FormData() 
		
		$fileCount = count($_FILES[$filekey]["name"]);
		$path = '';
		for ($i = 0; $i < $fileCount; $i++)
		{
			$fileName = $_FILES[$filekey]["name"][$i];
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);	
			$filename1 = 'Mobile_List_'. date('d_M_Y_H_i') . '.csv';
			
			$dest_path = $output_dir . $filename1;
			move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);						
			if( $i > 0 )
				$path .= '|';

			$path .=  $dest_path;			
		}

			$upload = new MobileTrack();
		
			$upload->filename = $filename1;
			$upload->folder_path = '/'.$output_dir;
			$upload->created_at = $cur_time;
			$upload->type = $type;
			$upload->user = $user;
			$upload->save();			

		$ret = array();
		$ret['files']=$_FILES[$filekey];
		$ret['path']=$path;
		return Response::json($ret);
	}

	public function getGuestExtensionList(Request $request)
	{
		$property_id = $request->get('property_id', '0');

		$datalist = DB::table('call_guest_extn as ge')
				->join('common_building as cb', 'ge.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id)
				->select(DB::raw('ge.*'))
				->get();

		return Response::json($datalist);
	}

	public function getCallStatistics(Request $request)
	{
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$during = $request->get('during', '');

		$ret = array();
		switch($period)
		{
			case 'Today';
				$ret = $this->getCallStaticsticsByToday($request);
				break;
			case 'Weekly';
				$ret = $this->getCallStaticsticsByDate($request, $end_date, 7);
				break;
			case 'Monthly';
				$ret = $this->getCallStaticsticsByDate($request, $end_date, 30);
				break;
			case 'Custom Days';
				$ret = $this->getCallStaticsticsByDate($request, $end_date, $during);
				break;
			case 'Yearly';
				$ret = $this->getCallStaticsticsByYearly($request, $end_date);
				break;
		}

		return Response::json($ret);
	}

	public function getCallStaticsticsByToday($request)
	{
		$property_id = $request->get('property_id', '0');
		$dept_id = $request->get('dept_id', '0');
		$user_id = $request->get('user_id', '0');
		$job_role = $request->get('job_role', '0');


		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$query = DB::table('call_admin_calls as ac')
				->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->whereRaw("call_date = '" . $cur_date . "'");

		if( $job_role == 'Manager' )
			$query->where('cu.dept_id', $dept_id);
		else if( $job_role == 'Finance' )
			$query->where('cd.property_id', $property_id);
		else
			$query->where('ac.user_id', $user_id);

		$ret = array();

		$count_info = array();

		$ticket_info = array();

		for($i = 0; $i < 12; $i++)
		{
			$start_time = sprintf("%02d:00:00", $i * 2);
			$end_time = sprintf("%02d:00:00", ($i + 1) * 2);

			$time_range = sprintf("'%s' <= start_time AND start_time < '%s'", $start_time, $end_time);

			$ticket_count = array();

			// Business
			$today_query = clone $query;
			$business = $today_query
					->whereRaw($time_range)
					->where('ac.classify', 'Business')
					->select(DB::raw('count(*) as cnt'))
					->first();
			if( empty($business) )
				$ticket_count['business'] = 0;
			else
				$ticket_count['business'] = $business->cnt;

			// Personal
			$today_query = clone $query;
			$personal = $today_query
					->whereRaw($time_range)
					->where('ac.classify', 'Personal')
					->select(DB::raw('count(*) as cnt'))
					->first();
			if( empty($personal) )
				$ticket_count['personal'] = 0;
			else
				$ticket_count['personal'] = $personal->cnt;

			$ticket_info['ticket_count'] = $ticket_count;

			$count_info[$i] = $ticket_info;
		}

		$ret['count_info'] = $count_info;

		// By task
		$today_query = clone $query;
		$by_called_cnt = $today_query
				->groupBy('ac.called_no')
				->orderBy('cnt', 'DESC')
				->select(DB::raw('count(*) as cnt, ac.called_no'))
				->get();

		$ret['by_called_cnt'] = $by_called_cnt;

		// By Department
		$today_query = clone $query;
		$by_cost = $today_query
				->groupBy('ac.called_no')
				->orderBy('cost', 'DESC')
				->select(DB::raw('sum(carrier_charges) as cost, ac.called_no'))
				->get();

		$ret['by_cost'] = $by_cost;

		return $ret;
	}


	public function getCallStaticsticsByDate($request, $end_date, $during)
	{
		$property_id = $request->get('property_id', '0');
		$dept_id = $request->get('dept_id', '0');
		$user_id = $request->get('user_id', '0');
		$job_role = $request->get('job_role', '0');

		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P' . $during . 'D'));

		$query = DB::table('call_admin_calls as ac')
				->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id');

		if( $job_role == 'Manager' )
			$query->where('cu.dept_id', $dept_id);
		else if( $job_role == 'Finance' )
			$query->where('cd.property_id', $property_id);
		else
			$query->where('ac.user_id', $user_id);

		$ret = array();

		$count_info = array();

		$ticket_info = array();

		for($i = 0; $i < $during; $i++)
		{
			$date->add(new DateInterval('P1D'));
			$cur_date = $date->format('Y-m-d');

			$ticket_count = array();

			$time_range = "call_date = '" . $cur_date . "'";

			// Business
			$today_query = clone $query;
			$business = $today_query
					->whereRaw($time_range)
					->where('ac.classify', 'Business')
					->select(DB::raw('count(*) as cnt'))
					->first();
			if( empty($business) )
				$ticket_count['business'] = 0;
			else
				$ticket_count['business'] = $business->cnt;

			// Personal
			$today_query = clone $query;
			$personal = $today_query
					->whereRaw($time_range)
					->where('ac.classify', 'Personal')
					->select(DB::raw('count(*) as cnt'))
					->first();
			if( empty($personal) )
				$ticket_count['personal'] = 0;
			else
				$ticket_count['personal'] = $personal->cnt;

			$ticket_info['ticket_count'] = $ticket_count;

			$count_info[$i] = $ticket_info;
		}

		$ret['count_info'] = $count_info;

		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P' . $during . 'D'));
		$start_date = $datetime->format('Y-m-d');

		$time_range = sprintf("'%s' < call_date AND call_date <= '%s'", $start_date, $end_date);

		// By task
		$today_query = clone $query;
		$by_called_cnt = $today_query
				->whereRaw($time_range)
				->groupBy('ac.called_no')
				->orderBy('cnt', 'DESC')
				->select(DB::raw('count(*) as cnt, ac.called_no'))
				->get();

		$ret['by_called_cnt'] = $by_called_cnt;

		// By Department
		$today_query = clone $query;
		$by_cost = $today_query
				->whereRaw($time_range)
				->groupBy('ac.called_no')
				->orderBy('cost', 'DESC')
				->select(DB::raw('sum(carrier_charges) as cost, ac.called_no'))
				->get();

		$ret['by_cost'] = $by_cost;

		return $ret;
	}

	public function getCallStaticsticsByYearly($request, $end_date)
	{
		$property_id = $request->get('property_id', '0');
		$dept_id = $request->get('dept_id', '0');
		$user_id = $request->get('user_id', '0');
		$job_role = $request->get('job_role', '0');

		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P1Y'));

		$query = DB::table('call_admin_calls as ac')
				->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id');

		if( $job_role == 'Manager' )
			$query->where('cu.dept_id', $dept_id);
		else if( $job_role == 'Finance' )
			$query->where('cd.property_id', $property_id);
		else
			$query->where('ac.user_id', $user_id);

		$ret = array();

		$count_info = array();

		$ticket_info = array();

		for($i = 0; $i < 12; $i++)
		{
			$date->add(new DateInterval('P1M'));
			$cur_month = $date->format('Y-m');

			$ticket_count = array();

			$time_range = "DATE_FORMAT(call_date, \"%Y-%m\") = '" . $cur_month . "'";

			// Business
			$today_query = clone $query;
			$business = $today_query
					->whereRaw($time_range)
					->where('ac.classify', 'Business')
					->select(DB::raw('count(*) as cnt'))
					->first();
			if( empty($business) )
				$ticket_count['business'] = 0;
			else
				$ticket_count['business'] = $business->cnt;

			// Personal
			$today_query = clone $query;
			$personal = $today_query
					->whereRaw($time_range)
					->where('ac.classify', 'Personal')
					->select(DB::raw('count(*) as cnt'))
					->first();
			if( empty($personal) )
				$ticket_count['personal'] = 0;
			else
				$ticket_count['personal'] = $personal->cnt;

			$ticket_info['ticket_count'] = $ticket_count;

			$count_info[$i] = $ticket_info;
		}

		$ret['count_info'] = $count_info;

		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P1Y'));
		$start_date = $datetime->format('Y-m-d');

		$time_range = sprintf("'%s' < call_date AND call_date <= '%s'", $start_date, $end_date);

		// By task
		$today_query = clone $query;
		$by_called_cnt = $today_query
				->whereRaw($time_range)
				->groupBy('ac.called_no')
				->orderBy('cnt', 'DESC')
				->select(DB::raw('count(*) as cnt, ac.called_no'))
				->get();

		$ret['by_called_cnt'] = $by_called_cnt;

		// By Department
		$today_query = clone $query;
		$by_cost = $today_query
				->whereRaw($time_range)
				->groupBy('ac.called_no')
				->orderBy('cost', 'DESC')
				->select(DB::raw('sum(carrier_charges) as cost, ac.called_no'))
				->get();

		$ret['by_cost'] = $by_cost;

		return $ret;
	}

	public function getCallRanks(Request $request)
	{
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$during = $request->get('during', '');

		$ret = array();
		switch($period)
		{
			case 'Today';
				$ret = $this->getCallRanksByToday($request);
				break;
			case 'Weekly';
			case 'Monthly';
			case 'Custom Days';
			case 'Yearly';
				$ret = $this->getCallRanksByDate($request, $end_date, $during);
				break;
		}

		return Response::json($ret);
	}

	public function getCallRanksByToday($request)
	{
		$property_id = $request->get('property_id', '0');

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$start_datetime = date('Y-m-d H:i:s',strtotime("-1 days"));//last 24 hous
		$start_date = date('Y-m-d',strtotime($start_datetime));
		$start_time = date('H:i:s',strtotime($start_datetime));

		$admin_query = DB::table('call_admin_calls as ac')
				// ->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				// ->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				// ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
				// ->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				//->whereRaw("ac.call_date >= '" . $start_date . "' and ac.start_time >= '".$start_time."'") //last 24 hours
				// ->whereRaw("CONCAT(ac.call_date, ' ', ac.start_time) >= '".$start_datetime."'")				
				->where(function ($subquery) use ($cur_date, $start_date, $start_time) {				
					$subquery->where("ac.call_date", $cur_date);
					$subquery->orWhere(function ($subquery) use ($start_date, $start_time) {				
						$subquery->where("ac.call_date", $start_date);
						$subquery->where("ac.start_time", '>=', $start_time);
					});
				})
				->where('ac.property_id', $property_id);
				// ->where('ac.destination_id', '>', 0);

		$ret = array();

		$start = microtime(true);

		// By destination
		$count_admin_query = clone $admin_query;
		$by_admin_cnt = $count_admin_query
				->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				->groupBy('ac.destination_id')
				->orderBy('cnt', 'DESC')
				->limit(10)
				->select(DB::raw('count(*) as cnt, dest.country'))
				->where('dest.country', '!=', 'internal')
				->get();

		$end = microtime(true);		

		$ret['by_admin_cnt'] = $by_admin_cnt;
		$ret['by_admin_cnt_time'] = $end - $start;

		$start = microtime(true);
		$subcount_query = clone $admin_query;
		
		$subcount_admin = $subcount_query
			->select(DB::raw("COALESCE(sum(ac.call_type = 'International'), 0) as international,
							COALESCE(sum(ac.call_type = 'National'), 0) as national,
                       		COALESCE(sum(ac.call_type = 'Mobile'), 0) as mobile,
							COALESCE(sum(ac.call_type = 'Local'), 0) as local,
							COALESCE(sum(ac.call_type = 'Received'), 0) as incoming,
							COALESCE(sum(ac.call_type = 'Internal'), 0) as internal
							"))
			->first();

		$end = microtime(true);	
		$ret['subcount_admin'] = $subcount_admin;
		$ret['subcount_admin_time'] = $end - $start;

		$start = microtime(true);
		// Duration
		$duration_admin_query = clone $admin_query;
		$by_duration_admin_cnt = $duration_admin_query
			->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			->where('ac.call_type', 'International')
			->groupBy('ac.dept_id')
			->orderBy('cnt', 'DESC')
			->limit(5)
			->select(DB::raw("sum(ac.duration) as cnt, dept.department"))
			->get();

		$end = microtime(true);	
		$ret['by_duration_admin_cnt'] = $by_duration_admin_cnt;
		$ret['by_duration_admin_cnt_time'] = $end - $start;


		$start = microtime(true);
		// call cost
		$cost_admin_query = clone $admin_query;
		$by_cost_admin_cnt = $cost_admin_query
		    ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			->where('ac.call_type', 'International')
			->groupBy('ac.dept_id')
			->orderBy('cnt', 'DESC')
			->limit(5)
			->select(DB::raw("ROUND(sum(ac.carrier_charges),2) as cnt, dept.department"))
			->get();

		$end = microtime(true);	
		$ret['by_cost_admin_cnt'] = $by_cost_admin_cnt;
		$ret['by_cost_admin_cnt_time'] = $end - $start;


		$guest_query = DB::table('call_guest_call as gc')
				->leftJoin('common_room as cr', 'gc.room_id', '=', 'cr.id')
				->leftJoin('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->leftJoin('call_destination as dest', 'gc.destination_id', '=', 'dest.id')
				->whereRaw("gc.call_date >= '" . $start_date . "' and gc.start_time >= '".$start_time."'")
				// ->where('gc.destination_id', '>', 0)
				->where('cb.property_id', $property_id);

		$start = microtime(true);		
		// By destination
		$count_guest_query = clone $guest_query;
		$by_guest_cnt = $count_guest_query
				->groupBy('gc.destination_id')
				->orderBy('cnt', 'DESC')
				->limit(10)
				->select(DB::raw('count(*) as cnt, dest.country'))
				->where('dest.country', '!=', 'internal')
				->get();

		$end = microtime(true);
		$ret['by_guest_cnt'] = $by_guest_cnt;
		$ret['by_guest_cnt_time'] = $end - $start;

		$start = microtime(true);
		$subcount_query = clone $guest_query;
		
		$subcount_guest = $subcount_query
			->select(DB::raw("COALESCE(sum(gc.call_type = 'International'), 0) as international,
							COALESCE(sum(gc.call_type = 'National'), 0) as national,
                       		COALESCE(sum(gc.call_type = 'Mobile'), 0) as mobile,
							COALESCE(sum(gc.call_type = 'Local'), 0) as local,
							COALESCE(sum(gc.call_type = 'Received'), 0) as incoming,
							COALESCE(sum(gc.call_type = 'Internal'), 0) as internal
							"))
			->first();	

		$end = microtime(true);	
		$ret['subcount_guest'] = $subcount_guest;
		$ret['subcount_guest_time'] = $end - $start;


		return $ret;
	}

	public function getCallRanksByDate($request, $end_date, $during)
	{
		$property_id = $request->get('property_id', '0');

		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P' . $during . 'D'));
		$start_date = $date->format('Y-m-d');

		$admin_query = DB::table('call_admin_calls as ac')
				// ->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				// ->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				// ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
				// ->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				->whereBetween('call_date', array($start_date, $end_date))
				->where('ac.property_id', $property_id);
				// ->where('ac.destination_id', '>', 0);

		$ret = array();

		$start = microtime(true);
		// By destination
		$count_admin_query = clone $admin_query;
		$by_admin_cnt = $count_admin_query
				->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				->groupBy('ac.destination_id')
				->orderBy('cnt', 'DESC')
				->limit(10)
				->select(DB::raw('count(*) as cnt, dest.country'))
				->where('dest.country', '!=', 'internal')
				->get();

		$ret['by_admin_cnt'] = $by_admin_cnt;
		$end = microtime(true);
		$ret['by_admin_cnt_time'] = $end - $start;

		$subcount_query = clone $admin_query;
		
		$start = microtime(true);
		$subcount_admin = $subcount_query
			->select(DB::raw("COALESCE(sum(ac.call_type = 'International'), 0) as international,
							COALESCE(sum(ac.call_type = 'National'), 0) as national,
                       		COALESCE(sum(ac.call_type = 'Mobile'), 0) as mobile,
							COALESCE(sum(ac.call_type = 'Local'), 0) as local,
							COALESCE(sum(ac.call_type = 'Received'), 0) as incoming,
							COALESCE(sum(ac.call_type = 'Internal'), 0) as internal
							"))
			->first();	
	

		$ret['subcount_admin'] = $subcount_admin;
		$end = microtime(true);
		$ret['subcount_admin_time'] = $end - $start;

		// Duration
		$start = microtime(true);
		$duration_admin_query = clone $admin_query;
		$by_duration_admin_cnt = $duration_admin_query
			->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			->where('ac.call_type', 'International')
			->groupBy('ac.dept_id')
			->orderBy('cnt', 'DESC')
			->limit(5)
			->select(DB::raw("sum(ac.duration) as cnt, dept.department"))
			->get();
		$ret['by_duration_admin_cnt'] = $by_duration_admin_cnt;
		$end = microtime(true);
		$ret['by_duration_admin_cnt_time'] = $end - $start;

		// call cost
		$cost_admin_query = clone $admin_query;
		$start = microtime(true);
		$by_cost_admin_cnt = $cost_admin_query
			->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			->where('ac.call_type', 'International')
			->groupBy('ac.dept_id')
			->orderBy('cnt', 'DESC')
			->limit(5)
			->select(DB::raw("ROUND(sum(ac.carrier_charges),2) as cnt, dept.department"))
			->get();
		$ret['by_cost_admin_cnt'] = $by_cost_admin_cnt;
		$end = microtime(true);
		$ret['by_cost_admin_cnt_time'] = $end - $start;


		$guest_query = DB::table('call_guest_call as gc')
				->leftJoin('common_room as cr', 'gc.room_id', '=', 'cr.id')
				->leftJoin('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->leftJoin('call_destination as dest', 'gc.destination_id', '=', 'dest.id')
				->whereBetween('call_date', array($start_date, $end_date))
				// ->where('gc.destination_id', '>', 0)
				->where('cb.property_id', $property_id);


		// By destination
		$count_guest_query = clone $guest_query;
		$start = microtime(true);
		$by_guest_cnt = $count_guest_query
				->groupBy('gc.destination_id')
				->orderBy('cnt', 'DESC')
				->limit(10)
				->select(DB::raw('count(*) as cnt, dest.country'))
				->where('dest.country', '!=', 'internal')
				->get();

		$ret['by_guest_cnt'] = $by_guest_cnt;
		$end = microtime(true);	
		$ret['by_guest_cnt_time'] = $end - $start;

		$subcount_query = clone $guest_query;
		$start = microtime(true);
		$subcount_guest = $subcount_query
			->select(DB::raw("COALESCE(sum(gc.call_type = 'International'), 0) as international,
							COALESCE(sum(gc.call_type = 'National'), 0) as national,
                       		COALESCE(sum(gc.call_type = 'Mobile'), 0) as mobile,
							COALESCE(sum(gc.call_type = 'Local'), 0) as local,
							COALESCE(sum(gc.call_type = 'Received'), 0) as incoming,
							COALESCE(sum(gc.call_type = 'Internal'), 0) as internal
							"))
			->first();		

		$ret['subcount_guest'] = $subcount_guest;
		$end = microtime(true);
		$ret['subcount_guest_time'] = $end - $start;

		return $ret;
	}

	public function getMyCallStats(Request $request)
	{
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$during = $request->get('during', '');

		$ret = array();
		switch($period)
		{
			case 'Today';
				$ret = $this->getMyCallStatsByToday($request);
				break;
			case 'Weekly';
			case 'Monthly';
			case 'Custom Days';
			case 'Yearly';
				$ret = $this->getMyCallStatsByDate($request, $end_date, $during);
				break;
		}

		return Response::json($ret);
	}

	public function getMyCallStatsByToday($request)
	{
		$property_id = $request->get('property_id', '0');
		$user_id = $request->get('user_id', '0');
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$start_datetime = date('Y-m-d');//last 24 hous
		$start_date = date('Y-m-d',strtotime($start_datetime));
		$start_time = date('H:i:s',strtotime($start_datetime));
		//echo $start_date;
		$admin_query = DB::table('call_admin_calls as ac')
			->where('ac.user_id','=',$user_id)
				// ->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				// ->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				// ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
				// ->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				//->whereRaw("ac.call_date >= '" . $start_date . "' and ac.start_time >= '".$start_time."'") //last 24 hours
				// ->whereRaw("CONCAT(ac.call_date, ' ', ac.start_time) >= '".$start_datetime."'")				
				->where(function ($subquery) use ($cur_date, $start_date, $start_time) {				
					$subquery->where("ac.call_date", $cur_date);
					$subquery->orWhere(function ($subquery) use ($start_date, $start_time) {				
						$subquery->where("ac.call_date", $start_date);
						$subquery->where("ac.start_time", '>=', $start_time);
					});
				})
				->where('ac.property_id', $property_id);
				// ->where('ac.destination_id', '>', 0);

		$mobile_query = DB::table('call_mobile_calls as mc')
			->where('mc.user_id','=',$user_id)
				// ->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				// ->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				// ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
				// ->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				//->whereRaw("ac.call_date >= '" . $start_date . "' and ac.start_time >= '".$start_time."'") //last 24 hours
				// ->whereRaw("CONCAT(ac.call_date, ' ', ac.start_time) >= '".$start_datetime."'")				
				->where(function ($subquery) use ($cur_date, $start_date, $start_time) {				
					$subquery->where("mc.date", $cur_date);
					$subquery->orWhere(function ($subquery) use ($start_date, $start_time) {				
						$subquery->where("mc.date", $start_date);
						$subquery->where("mc.time", '>=', $start_time);
					});
				})
				->where('mc.property_id', $property_id);		

		$ret = array();

//Total admin
		$start = microtime(true);
		
		$count_admin_total_query = clone $admin_query;
		$by_admin_total_cnt = $count_admin_total_query
				
				->groupBy('ac.classify')
				
				->select(DB::raw('count(*) as cnt, ac.classify'))
				->get();

		$ret['by_admin_total_cnt'] = $by_admin_total_cnt;
		$end = microtime(true);
		$ret['by_admin_total_cnt_time'] = $end - $start;

		//Total mobile
		$start = microtime(true);
		$count_mobile_total_query = clone $mobile_query;
		$by_mobile_total_cnt = $count_mobile_total_query
				
				->groupBy('mc.classify')
				->orderBy('cnt', 'DESC')
				->select(DB::raw('count(*) as cnt, mc.classify'))
				->get();

		$ret['by_mobile_total_cnt'] = $by_mobile_total_cnt;
		$end = microtime(true);
		$ret['by_mobile_total_cnt_time'] = $end - $start;

		$start = microtime(true);
		// By admin  destination
		$count_admin_query = clone $admin_query;
		$by_admin_cnt = $count_admin_query
				->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				->groupBy('ac.destination_id')
				->orderBy('cnt', 'DESC')
				->limit(4)
				->select(DB::raw('count(*) as cnt, dest.country'))
				->where('dest.country', '!=', 'internal')
				->get();

		$ret['by_admin_cnt'] = $by_admin_cnt;
		$end = microtime(true);
		$ret['by_admin_cnt_time'] = $end - $start;

		$start = microtime(true);
		// By mobile destination
		$count_mobile_query = clone $mobile_query;
		$by_mobile_cnt = $count_mobile_query
				->leftJoin('call_destination as dest', 'mc.destination_id', '=', 'dest.id')
				->groupBy('mc.destination_id')
				->orderBy('cnt', 'DESC')
				->limit(10)
				->select(DB::raw('count(*) as cnt, dest.country'))
				->where('dest.country', '!=', 'internal')
				->get();

		$ret['by_mobile_cnt'] = $by_mobile_cnt;
		$end = microtime(true);
		$ret['by_mobile_cnt_time'] = $end - $start;

		
		
		$start = microtime(true);
		$subcount_admin=[];
		$admin_call_types=DB::table('call_admin_calls as ac')->distinct('call_type')->select('call_type')->get();
		$subcount_query_personal = clone $admin_query;
		$subcount_admin_personal = $subcount_query_personal
			->where("ac.classify", '=', 'Personal')
			->groupBy('ac.call_type')
			->select(DB::raw("count(*) as personal,0 as business, 0 as unclassified, ac.call_type"))
			->get();
		$subcount_admin[]=$subcount_admin_personal;

		$subcount_query_business = clone $admin_query;
		$subcount_admin_business = $subcount_query_business
			->where("ac.classify", '=', 'Business')
			->groupBy('ac.call_type')
			->select(DB::raw("count(*) as business,0 as personal, 0 as unclassified, ac.call_type"))
			->get();
		$subcount_admin[]=$subcount_admin_business;

		$subcount_query_unclassified = clone $admin_query;
		$subcount_admin_unclassified = $subcount_query_unclassified
			->where("ac.classify", '=', 'Unclassified')
			->groupBy('ac.call_type')
			->select(DB::raw("count(*) as unclassified,0 as business, 0 as personal,ac.call_type"))
			->get();			
		$subcount_admin[]=$subcount_admin_unclassified;
		$admin_tot=[];
		foreach ($subcount_admin as $key=>$value) {
			foreach ($value as  $val) {
				$admin_tot[]=$val;
			}
		}
		$admin_stats=[];
		 foreach ($admin_call_types as  $value) {
			 $per=0;$bus=0;$uncl=0;
			 foreach ($admin_tot as $key => $val) {
				if($value->call_type==$val->call_type)
				{
					$per=$per+$val->personal;
					$bus=$bus+$val->business;
				 	$uncl=$uncl+$val->unclassified;
				}
			 }
			 $admin_stats[] = ['call_type'=>$value->call_type,'personal'=>$per,'business'=>$bus,'unclassified'=>$uncl];
		// 	$arr=array_keys($admin_stats,$value->call_type);
		// 	echo json_encode($value->call_type);
		// 	$per=0;$bus=0;$uncl=0;
		// 	// foreach ($arr as $key=> $val) {
		// 	// 	$per=$per+$admin_stats[$val]->personal;
		// 	// 	$bus=$bus+$admin_stats[$val]->business;
		// 	// 	$uncl=$uncl+$admin_stats[$val]->unclassified;
		// 	// 	if($key!=0)
		// 	// 	unset($admin_stats[$val]);
		// 	// }
		// 	// if(!empty($arr))
		// 	// {
		// 	// $admin_stats[$arr[0]]->personal=$per;
		// 	// $admin_stats[$arr[0]]->business=$bus;
		// 	// $admin_stats[$arr[0]]->unclassified=$uncl;
		// 	// }
		 }
		$ret['subcount_admin'] = $admin_stats;
		$end = microtime(true);
		$ret['subcount_admin_time'] = $end - $start;

		//Subcount Mobile
		$start = microtime(true);
		$subcount_mobile=[];
		$mobile_call_types=DB::table('call_mobile_calls as mc')->distinct('call_type')->select('call_type')->get();
		$subcount_query_personal = clone $mobile_query;
		$subcount_mobile_personal = $subcount_query_personal
			->where("mc.classify", '=', 'Personal')
			->groupBy('mc.call_type')
			->select(DB::raw("count(*) as personal,0 as business, 0 as unclassified, mc.call_type"))
			->get();
		$subcount_mobile[]=$subcount_mobile_personal;

		$subcount_query_business = clone $mobile_query;
		$subcount_mobile_business = $subcount_query_business
			->where("mc.classify", '=', 'Business')
			->groupBy('mc.call_type')
			->select(DB::raw("count(*) as business,0 as personal, 0 as unclassified, mc.call_type"))
			->get();
		$subcount_mobile[]=$subcount_mobile_business;

		$subcount_query_unclassified = clone $mobile_query;
		$subcount_mobile_unclassified = $subcount_query_unclassified
			->where("mc.classify", '=', 'Unclassified')
			->groupBy('mc.call_type')
			->select(DB::raw("count(*) as unclassified,0 as business, 0 as personal,mc.call_type"))
			->get();			
		$subcount_mobile[]=$subcount_mobile_unclassified;
		$mobile_tot=[];
		foreach ($subcount_mobile as $key=>$value) {
			foreach ($value as  $val) {
				$mobile_tot[]=$val;
			}
		}
		$mobile_stats=[];
		 foreach ($mobile_call_types as  $value) {
			 $per=0;$bus=0;$uncl=0;
			 foreach ($mobile_tot as $key => $val) {
				if($value->call_type==$val->call_type)
				{
					$per=$per+$val->personal;
					$bus=$bus+$val->business;
				 	$uncl=$uncl+$val->unclassified;
				}
			 }
			 $mobile_stats[] = ['call_type'=>$value->call_type,'personal'=>$per,'business'=>$bus,'unclassified'=>$uncl];
		}
		$ret['subcount_mobile'] = $mobile_stats;
		$end = microtime(true);
		$ret['subcount_mobile_time'] = $end - $start;
		// Duration Admin
		$start = microtime(true);
		$duration_admin_query = clone $admin_query;
		$by_duration_admin_cnt = $duration_admin_query
			//->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			->where('ac.duration','>', 0)
			->groupBy('ac.classify')
			->orderBy('cnt', 'DESC')
			->limit(5)
			->select(DB::raw("sum(ac.duration) as cnt,ac.classify"))
			->get();
		$ret['by_duration_admin_cnt'] = $by_duration_admin_cnt;
		$end = microtime(true);
		$ret['by_duration_admin_cnt_time'] = $end - $start;

		// Duration Mobile
		$start = microtime(true);
		$duration_mobile_query = clone $mobile_query;
		$by_duration_mobile_cnt = $duration_mobile_query
			// ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			 ->where('mc.duration','>', 0)
			 ->groupBy('mc.classify')
			->orderBy('cnt', 'DESC')
			->limit(5)
			->select(DB::raw("sum(mc.duration) as cnt,mc.classify"))
			->get();
		$ret['by_duration_mobile_cnt'] = $by_duration_mobile_cnt;
		$end = microtime(true);
		$ret['by_duration_mobile_cnt_time'] = $end - $start;

		// call cost
		$cost_admin_query = clone $admin_query;
		$start = microtime(true);
		$by_cost_admin_cnt = $cost_admin_query
			//->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			 ->where('ac.carrier_charges','>', 0)
			 ->groupBy('ac.classify')
			->orderBy('cnt', 'DESC')
			->select(DB::raw("ROUND(sum(ac.carrier_charges),2) as cnt, ac.classify"))
			->get();
		$ret['by_cost_admin_cnt'] = $by_cost_admin_cnt;
		$end = microtime(true);
		$ret['by_cost_admin_cnt_time'] = $end - $start;

		// call mobile cost
		$cost_mobile_query = clone $mobile_query;
		$start = microtime(true);
		$by_cost_mobile_cnt = $cost_mobile_query
			//->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			 //->where('mc.charges','>', 0)
			 ->groupBy('mc.classify')
			->orderBy('cnt', 'DESC')
			->select(DB::raw("ROUND(sum(mc.charges),2) as cnt, mc.classify"))
			->get();
		$ret['by_cost_mobile_cnt'] = $by_cost_mobile_cnt;
		$end = microtime(true);
		$ret['by_cost_mobile_cnt_time'] = $end - $start;


		return $ret;
	}

	public function getMyCallStatsByDate($request, $end_date, $during)
	{
		$property_id = $request->get('property_id', '0');
		$user_id = $request->get('user_id', '0');
		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P' . $during . 'D'));
		$start_date = $date->format('Y-m-d');

		$admin_query = DB::table('call_admin_calls as ac')
				// ->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				// ->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				// ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
				// ->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				->where('ac.user_id','=',$user_id)
				->whereBetween('call_date', array($start_date, $end_date))
				->where('ac.property_id', $property_id);
				// ->where('ac.destination_id', '>', 0);

		$mobile_query = DB::table('call_mobile_calls as mc')
				// ->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				// ->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				// ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
				// ->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				->where('mc.user_id','=',$user_id)
				->whereBetween('date', array($start_date, $end_date))
				->where('mc.property_id', $property_id);		

		$ret = array();
		//Total admin
		$start = microtime(true);
		
		$count_admin_total_query = clone $admin_query;
		$by_admin_total_cnt = $count_admin_total_query
				
				->groupBy('ac.classify')
				
				->select(DB::raw('count(*) as cnt, ac.classify'))
				->get();

		$ret['by_admin_total_cnt'] = $by_admin_total_cnt;
		$end = microtime(true);
		$ret['by_admin_total_cnt_time'] = $end - $start;

		//Total mobile
		$start = microtime(true);
		$count_mobile_total_query = clone $mobile_query;
		$by_mobile_total_cnt = $count_mobile_total_query
				
				->groupBy('mc.classify')
				->orderBy('cnt', 'DESC')
				->select(DB::raw('count(*) as cnt, mc.classify'))
				->get();

		$ret['by_mobile_total_cnt'] = $by_mobile_total_cnt;
		$end = microtime(true);
		$ret['by_mobile_total_cnt_time'] = $end - $start;

		$start = microtime(true);
		// By admin  destination
		$count_admin_query = clone $admin_query;
		$by_admin_cnt = $count_admin_query
				->leftJoin('call_destination as dest', 'ac.destination_id', '=', 'dest.id')
				->groupBy('ac.destination_id')
				->orderBy('cnt', 'DESC')
				->limit(4)
				->select(DB::raw('count(*) as cnt, dest.country'))
				->where('dest.country', '!=', 'internal')
				->get();

		$ret['by_admin_cnt'] = $by_admin_cnt;
		$end = microtime(true);
		$ret['by_admin_cnt_time'] = $end - $start;

		$start = microtime(true);
		// By mobile destination
		$count_mobile_query = clone $mobile_query;
		$by_mobile_cnt = $count_mobile_query
				->leftJoin('call_destination as dest', 'mc.destination_id', '=', 'dest.id')
				->groupBy('mc.destination_id')
				->orderBy('cnt', 'DESC')
				->limit(10)
				->select(DB::raw('count(*) as cnt, dest.country'))
				->where('dest.country', '!=', 'internal')
				->get();

		$ret['by_mobile_cnt'] = $by_mobile_cnt;
		$end = microtime(true);
		$ret['by_mobile_cnt_time'] = $end - $start;

		
		
		$start = microtime(true);
		$subcount_admin=[];
		$admin_call_types=DB::table('call_admin_calls as ac')->distinct('call_type')->select('call_type')->get();
		$subcount_query_personal = clone $admin_query;
		$subcount_admin_personal = $subcount_query_personal
			->where("ac.classify", '=', 'Personal')
			->groupBy('ac.call_type')
			->select(DB::raw("count(*) as personal,0 as business, 0 as unclassified, ac.call_type"))
			->get();
		$subcount_admin[]=$subcount_admin_personal;

		$subcount_query_business = clone $admin_query;
		$subcount_admin_business = $subcount_query_business
			->where("ac.classify", '=', 'Business')
			->groupBy('ac.call_type')
			->select(DB::raw("count(*) as business,0 as personal, 0 as unclassified, ac.call_type"))
			->get();
		$subcount_admin[]=$subcount_admin_business;

		$subcount_query_unclassified = clone $admin_query;
		$subcount_admin_unclassified = $subcount_query_unclassified
			->where("ac.classify", '=', 'Unclassified')
			->groupBy('ac.call_type')
			->select(DB::raw("count(*) as unclassified,0 as business, 0 as personal,ac.call_type"))
			->get();			
		$subcount_admin[]=$subcount_admin_unclassified;
		$admin_tot=[];
		foreach ($subcount_admin as $key=>$value) {
			foreach ($value as  $val) {
				$admin_tot[]=$val;
			}
		}
		$admin_stats=[];
		 foreach ($admin_call_types as  $value) {
			 $per=0;$bus=0;$uncl=0;
			 foreach ($admin_tot as $key => $val) {
				if($value->call_type==$val->call_type)
				{
					$per=$per+$val->personal;
					$bus=$bus+$val->business;
				 	$uncl=$uncl+$val->unclassified;
				}
			 }
			 $admin_stats[] = ['call_type'=>$value->call_type,'personal'=>$per,'business'=>$bus,'unclassified'=>$uncl];
		// 	$arr=array_keys($admin_stats,$value->call_type);
		// 	echo json_encode($value->call_type);
		// 	$per=0;$bus=0;$uncl=0;
		// 	// foreach ($arr as $key=> $val) {
		// 	// 	$per=$per+$admin_stats[$val]->personal;
		// 	// 	$bus=$bus+$admin_stats[$val]->business;
		// 	// 	$uncl=$uncl+$admin_stats[$val]->unclassified;
		// 	// 	if($key!=0)
		// 	// 	unset($admin_stats[$val]);
		// 	// }
		// 	// if(!empty($arr))
		// 	// {
		// 	// $admin_stats[$arr[0]]->personal=$per;
		// 	// $admin_stats[$arr[0]]->business=$bus;
		// 	// $admin_stats[$arr[0]]->unclassified=$uncl;
		// 	// }
		 }
		$ret['subcount_admin'] = $admin_stats;
		$end = microtime(true);
		$ret['subcount_admin_time'] = $end - $start;

		//Subcount Mobile
		$start = microtime(true);
		$subcount_mobile=[];
		$mobile_call_types=DB::table('call_mobile_calls as mc')->distinct('call_type')->select('call_type')->get();
		$subcount_query_personal = clone $mobile_query;
		$subcount_mobile_personal = $subcount_query_personal
			->where("mc.classify", '=', 'Personal')
			->groupBy('mc.call_type')
			->select(DB::raw("count(*) as personal,0 as business, 0 as unclassified, mc.call_type"))
			->get();
		$subcount_mobile[]=$subcount_mobile_personal;

		$subcount_query_business = clone $mobile_query;
		$subcount_mobile_business = $subcount_query_business
			->where("mc.classify", '=', 'Business')
			->groupBy('mc.call_type')
			->select(DB::raw("count(*) as business,0 as personal, 0 as unclassified, mc.call_type"))
			->get();
		$subcount_mobile[]=$subcount_mobile_business;

		$subcount_query_unclassified = clone $mobile_query;
		$subcount_mobile_unclassified = $subcount_query_unclassified
			->where("mc.classify", '=', 'Unclassified')
			->groupBy('mc.call_type')
			->select(DB::raw("count(*) as unclassified,0 as business, 0 as personal,mc.call_type"))
			->get();			
		$subcount_mobile[]=$subcount_mobile_unclassified;
		$mobile_tot=[];
		foreach ($subcount_mobile as $key=>$value) {
			foreach ($value as  $val) {
				$mobile_tot[]=$val;
			}
		}
		$mobile_stats=[];
		 foreach ($mobile_call_types as  $value) {
			 $per=0;$bus=0;$uncl=0;
			 foreach ($mobile_tot as $key => $val) {
				if($value->call_type==$val->call_type)
				{
					$per=$per+$val->personal;
					$bus=$bus+$val->business;
				 	$uncl=$uncl+$val->unclassified;
				}
			 }
			 $mobile_stats[] = ['call_type'=>$value->call_type,'personal'=>$per,'business'=>$bus,'unclassified'=>$uncl];
		}
		$ret['subcount_mobile'] = $mobile_stats;
		$end = microtime(true);
		$ret['subcount_mobile_time'] = $end - $start;
		// Duration Admin
		$start = microtime(true);
		$duration_admin_query = clone $admin_query;
		$by_duration_admin_cnt = $duration_admin_query
			//->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			->where('ac.duration','>', 0)
			->groupBy('ac.classify')
			->orderBy('cnt', 'DESC')
			->limit(5)
			->select(DB::raw("sum(ac.duration) as cnt,ac.classify"))
			->get();
		$ret['by_duration_admin_cnt'] = $by_duration_admin_cnt;
		$end = microtime(true);
		$ret['by_duration_admin_cnt_time'] = $end - $start;

		// Duration Mobile
		$start = microtime(true);
		$duration_mobile_query = clone $mobile_query;
		$by_duration_mobile_cnt = $duration_mobile_query
			// ->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			 ->where('mc.duration','>', 0)
			 ->groupBy('mc.classify')
			->orderBy('cnt', 'DESC')
			->limit(5)
			->select(DB::raw("sum(mc.duration) as cnt,mc.classify"))
			->get();
		$ret['by_duration_mobile_cnt'] = $by_duration_mobile_cnt;
		$end = microtime(true);
		$ret['by_duration_mobile_cnt_time'] = $end - $start;

		// call cost
		$cost_admin_query = clone $admin_query;
		$start = microtime(true);
		$by_cost_admin_cnt = $cost_admin_query
			//->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			 ->where('ac.carrier_charges','>', 0)
			 ->groupBy('ac.classify')
			->orderBy('cnt', 'DESC')
			->select(DB::raw("ROUND(sum(ac.carrier_charges),2) as cnt, ac.classify"))
			->get();
		$ret['by_cost_admin_cnt'] = $by_cost_admin_cnt;
		$end = microtime(true);
		$ret['by_cost_admin_cnt_time'] = $end - $start;

		// call mobile cost
		$cost_mobile_query = clone $mobile_query;
		$start = microtime(true);
		$by_cost_mobile_cnt = $cost_mobile_query
			//->leftJoin('common_department as dept', 'ac.dept_id', '=', 'dept.id')
			 //->where('mc.charges','>', 0)
			 ->groupBy('mc.classify')
			->orderBy('cnt', 'DESC')
			->select(DB::raw("ROUND(sum(mc.charges),2) as cnt, mc.classify"))
			->get();
		$ret['by_cost_mobile_cnt'] = $by_cost_mobile_cnt;
		$end = microtime(true);
		$ret['by_cost_mobile_cnt_time'] = $end - $start;


		return $ret;
	}

	public function getMyMobileCalls(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 25);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$groupby = $request->get('groupby', '');
		$property_id = $request->get('property_id', '0');
		$agent_id = $request->get('agent_id');
		$call_type = $request->get('call_type');
		$start_time = $request->get('start_time', '');
		$end_time = $request->get('end_time', '');;
		$classify = $request->get('classify');
		$approval = $request->get('approval');
		$searchoption = $request->get('searchoption','');
		$all_flag = $request->get('all_flag', 0);
		//$extensions = $request->get('extensions');

		if($pageSize < 0 )
			$pageSize = 20;

		//$ext_list = StaffExternal::getMyextIds($agent_id);

		$start = microtime(true);

		$ret = array();

			$date_range = sprintf("date between '%s' and '%s'", $start_time, $end_time);
			$query = DB::table('call_mobile_calls as ac')
				->leftJoin('phonebook as pb', 'ac.call_to', '=', 'pb.contact_no')
				->leftJoin('call_destination as cd', 'ac.destination_id', '=', 'cd.id')
				->whereRaw($date_range)
				->where('ac.user_id', $agent_id)
				->where('ac.property_id', $property_id);
			
			if(!$all_flag)
			{
				if (!empty($call_type))
					$query->whereIn('call_type', $call_type);

				if (!empty($classify)) {
					$query->whereIn('classify', $classify);
				}

				if (!empty($approval)) {
					$query->whereIn('approval', $approval);
				}

				if ($searchoption != '') {

					$where = sprintf(" (cd.country like '%%%s%%' or
									ac.call_to like '%%%s%%')",
						$searchoption, $searchoption, $searchoption, $searchoption, $searchoption, $searchoption
					);
					$query->whereRaw($where);
				}
			}	
			///////*****/////////
			$data_query = clone $query;			

			$count_query_key = json_encode($request->except(['page', 'pageSize', 'field', 'sort']));

			 if( empty($groupby) )	// only data
			 {
				$data_query
					->orderBy($orderby, $sort);
					
					//->groupBy('ac.call_to')
					$data_list = $data_query->select(DB::raw('ac.*,cd.country,pb.contact_name'))
					->skip($skip)->take($pageSize)
					->get();
					$ret['datalist'] = $data_list;
					$ret['total'] = 0;	
				$count_query_total = clone $query;
				$totalcount = $count_query_total->count();
			  }
				
		
			 else
			{
			
			 	$count_query = clone $query;
				
					$total=	$count_query->select(DB::raw('ac.call_to,ac.phonebk_flag,pb.contact_name, COUNT(ac.id) AS cnt, ROUND(sum(ac.charges), 2) as totalcharge, sum(ac.duration) as totalsec'))
					->skip($skip)->take($pageSize)->groupBy('ac.call_to')->get();
					$calls_to=[];
					foreach ($total as $value) {
						
					$value->total_time= gmdate('H:i:s',$value->totalsec) ;	
					$calls_to[]=$value->call_to;
					}
					$details_query = clone $query;
					$details=$details_query->whereIn('ac.call_to',$calls_to)->select(DB::raw('ac.*,cd.country,ac.phonebk_flag,pb.contact_name'))->get();
					
					foreach ($details as $key => $value1) {
							foreach ($total as $value) {
								if($value1->call_to == $value->call_to)
								$value->details[]=$value1;
						}
					}
				$ret['datalist'] = $total;
				
				$ret['total'] = $total;
				$count_query_total = clone $query;
				$count = $count_query_total->groupBy('ac.call_to')->get();
				$totalcount=count($count);
				}
			
		
		$end = microtime(true);		

			$ret['subcount'] = array();
			$ret['time'] = $end - $start;
			$ret['totalcount'] = $totalcount;
			//$ret['data_flag'] = $data_flag;

		return Response::json($ret);
	}


	public function getDetailMobileCall(Request $request){
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		
		$property_id = $request->get('property_id', '0');
		$user_id = $request->get('user_id');
		$call_to=$request->get('call_to', '');
		$call_type = $request->get('call_type');
		$classify = $request->get('classify');
		$approval = $request->get('approval');
		
		$query = DB::table('call_mobile_calls as ac')
				->leftJoin('phonebook as pb', 'ac.call_to', '=', 'pb.contact_no')
				->leftJoin('call_destination as cd', 'ac.destination_id', '=', 'cd.id')
				//->whereRaw($date_range)
				->where('ac.user_id', $user_id)
				->where('ac.call_to', $call_to)
				->where('ac.property_id', $property_id);

		if (!empty($call_type))
				$query->whereIn('call_type', $call_type);

			if (!empty($classify)) {
				$query->whereIn('classify', $classify);
			}

			if (!empty($approval)) {
				$query->whereIn('approval', $approval);
			}		
			$data = $query->select(DB::raw('ac.*,cd.country,ac.phonebk_flag,pb.contact_name'))->get();
		// $total_query=clone $query;
		//$total=$total_query->select(DB::raw('ROUND(sum(ac.charges), 2) as totalcharge, sum(ac.duration) as totalsec'))->get();
		//$ret['totalcount'] =count($data);
		$ret=array();
		$ret['datalist'] = $data;
			//$ret['totalcount'] = $totalcount;

				

			//$ret['total'] = $total;
return Response::json($ret);
			// $ret['time'] = $end - $start;
			// $ret['data_flag'] = $data_flag;	
		
	}
	public function getMyAdminCalls(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$agent_id = $request->get('agent_id');
		$call_type = $request->get('call_type');
		$start_time = $request->get('start_time', '');
		$end_time = $request->get('end_time', '');;
		$classify = $request->get('classify');
		$approval = $request->get('approval');
		$searchoption = $request->get('searchoption','');
		$data_flag = $request->get('data_flag', 0);
		$extensions = $request->get('extensions');

		if($pageSize < 0 )
			$pageSize = 20;
		
		$ext_list = StaffExternal::getMyextIds($agent_id);
		
		$start = microtime(true);

		$ret = array();

			$date_range = sprintf("call_date between '%s' and '%s'", $start_time, $end_time);
			$query = DB::table('call_admin_calls as ac')
				->leftJoin('phonebook as pb', 'ac.called_no', '=', 'pb.contact_no')
				->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				->leftJoin('call_destination as cd', 'ac.destination_id', '=', 'cd.id')
				->whereRaw($date_range)
				//->whereIn('extension_id', $ext_list)
				->where('ac.property_id', $property_id);
			
			if (!empty($call_type))
				$query->whereIn('call_type', $call_type);

			if (!empty($classify)) {
				$query->whereIn('classify', $classify);
			}

			if (!empty($approval)) {
				$query->whereIn('approval', $approval);
			}
			
			if(!empty($extensions)) {
				$query->whereIn('extension_id', $extensions);
			}else {
				$query->whereIn('extension_id', $ext_list);
			}

			if ($searchoption != '') {

				$where = sprintf(" (se.extension like '%%%s%%' or								
								cd.country like '%%%s%%' or
								ac.called_no like '%%%s%%')",
					$searchoption, $searchoption, $searchoption, $searchoption, $searchoption, $searchoption
				);
				$query->whereRaw($where);
			}

			///////*****/////////
			$data_query = clone $query;			

			$count_query_key = json_encode($request->except(['page', 'pageSize', 'field', 'sort']));
			
			if( $data_flag == 1 )	// only data
			{
				$data_list = $data_query
					->orderBy($orderby, $sort)
					->select(DB::raw('ac.*, se.extension, cd.country,pb.contact_name'))
					->skip($skip)->take($pageSize)
					->get();				

				$totalcount = Cache::get($count_query_key, function() {
				    return 200;
				});
			}
			else
			{
				$data_list = array();
				$count_query = clone $query;

				$totalcount = Cache::remember($count_query_key, 10, function() use ($count_query) {
				    return $count_query->count();	
				});
			}
			
			$ret['datalist'] = $data_list;
			$ret['totalcount'] = $totalcount;

		$end = microtime(true);		

			$ret['subcount'] = array();
			$ret['time'] = $end - $start;
			$ret['data_flag'] = $data_flag;

		return Response::json($ret);
	}
	public function getMyAdminCallsFromFinance(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');

		$property_id = $request->get('property_id', '0');
		$call_type = $request->get('call_type');
		$classify = $request->get('classify');
		$approval = $request->get('approval');
		$dept_id = $request->get('dept_id');
		$start = microtime(true);

		$ret = array();

		$query = DB::table('call_admin_calls as ac')
			->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
			->leftJoin('call_destination as cd', 'ac.destination_id', '=', 'cd.id')
			->where('ac.dept_id', $dept_id)
			->where('ac.property_id', $property_id);

		if (!empty($call_type))
			$query->whereIn('call_type', $call_type);

		if (!empty($classify) && $classify != '') {
			$query->where('classify', $classify);
		}

		if (!empty($approval) && $approval !='') {
			$query->where('approval', $approval);
		}

		$data_query = clone $query;
		$data_list = $data_query
			->orderBy($orderby, $sort)
			->skip($skip)->take($pageSize)
			->select(DB::raw('ac.*, se.extension, cd.country'))
			->get();

		$ret['datalist'] = $data_list;

		$count_query = clone $query;
		$totalcount = $count_query->count();
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function getMyMobileCallsFromFinance(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');

		$property_id = $request->get('property_id', '0');
		$call_type = $request->get('call_type');
		$classify = $request->get('classify');
		$approval = $request->get('approval');
		$dept_id = $request->get('dept_id');
		$start = microtime(true);

		$ret = array();

		$query = DB::table('call_mobile_calls as ac')
			->leftJoin('call_destination as cd', 'ac.destination_id', '=', 'cd.id')
			->where('ac.dept_id', $dept_id)
			->where('ac.property_id', $property_id);

		if (!empty($call_type))
			$query->whereIn('call_type', $call_type);

		if (!empty($classify) && $classify != '') {
			$query->where('classify', $classify);
		}

		if (!empty($approval) && $approval !='') {
			$query->where('approval', $approval);
		}

		$data_query = clone $query;
		$data_list = $data_query
			->orderBy($orderby, $sort)
			->skip($skip)->take($pageSize)
			->select(DB::raw('ac.*, cd.country'))
			->get();

		$ret['datalist'] = $data_list;

		$count_query = clone $query;
		$totalcount = $count_query->count();
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}


	public function getMyAdminExtensionList(Request $request)
	{
		$agent_id = $request->get('agent_id', '0');

//		$user_group = DB::table('common_user_group_members as ugm')
//						->where('ugm.user_id', $agent_id)
//						->first();
		$user_group = DB::table('common_user_group_members as ugm')
			->where('ugm.user_id', $agent_id)
			->groupBy('ugm.group_id')
			->get();

		 $group_id = 0;
                $group_ids = array();

               $group_ids_query = array();
               $where_sprint="(";
               $j=0;
                if( !empty($user_group) ) {
                        for($i =0; $i < count($user_group) ; $i++) {
                                $group_ids[$i] = $user_group[$i]->group_id;
                               $group_ids_query[$j++] = '['.$user_group[$i]->group_id;
                               $group_ids_query[$j++] = ','.$user_group[$i]->group_id;
                               if($i!=(count($user_group)-1))
                               $where_sprint.=" user_group_id like '%%%s%%' or user_group_id like '%%%s%%' or";
                               else
                               $where_sprint.=" user_group_id like '%%%s%%' or user_group_id like '%%%s%%')";
                        }

                       $where = vsprintf($where_sprint,$group_ids_query);
                }
               else
               $where = '';


                $datalist = DB::table('call_staff_extn as se')
                                                ->where('se.bc_flag', 0)
                                                ->where(function ($query) use ($agent_id, $group_id, $group_ids, $where) {
                                                        $query->where('se.user_id', $agent_id, $group_ids)
                                                                        ->orWhereIn('se.user_group_id', $group_ids);
                                                                        if($where != '')
                                                                        $query->orwhereRaw($where);
                                                })
                                                ->orderBy('se.extension')
                                                ->get();


		return Response::json($datalist);
	}

	public function submitApproval(Request $request) {
		$calls = $request->get('calls', array());


		for( $i = 0; $i < count($calls); $i++ )
		{
			$call = $calls[$i];

			AdminCall::where('id', $call['id'])
					->update($call);

//			if( empty($call['comment']) )
//				continue;

			$comment = new CallComment();

			$comment->call_id = $call['id'];
			$comment->user_id = $call['submitter'];
			$comment->comment = $call['comment'];
			$comment->approval = $call['approval'];
			$comment->save();
		}

		return Response::json($calls);
	}

	public function submitMobileApproval(Request $request) {
		$calls = $request->get('calls', array());
		json_encode($calls);
		$cur_time = date('Y-m-d H:i:s');

		for( $i = 0; $i < count($calls); $i++ )
		{
			$call = $calls[$i];

			MobileCalls::where('id', $call['id'])
					->update($call);
			MobileCalls::where('id', $call['id'])
					->update(['classify_date' => $cur_time]);

			if( empty($call['comment']) )
				continue;

			$comment = new CallComment();

			$comment->call_id = $call['id'];
			$comment->user_id = $call['submitter'];
			$comment->comment = $call['comment'];
			$comment->approval = $call['approval'];
			$comment->save();
		}

		return Response::json($calls);
	}

	public function getCallApprovalList(Request $request) {
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$agent_id = $request->get('agent_id');
		$call_type = $request->get('call_type', 'All');
		$search = $request->get('search', '');

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('call_admin_calls as ac')
				->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				->leftJoin('call_destination as cd', 'ac.destination_id', '=', 'cd.id')
				->leftJoin('call_admin_charge_map as acm', 'ac.admin_charge_rate_id', '=', 'acm.id')
				->leftJoin('call_carrier_charges as cc', 'acm.carrier_charges', '=', 'cc.id')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id');


		if( $call_type != 'All' )
			$query->where('call_type', $call_type);

		if( !empty($search) )
		{
			$search = '%' . $search . '%';
			$query->where('called_no', 'like', $search);
		}

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('ac.*, se.extension, cd.country, cd.code, cc.charge as carrier_rate, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->skip($skip)->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		$ret['pendinglist'] = DB::table('call_admin_calls as ac')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->where('ac.approval', 'Pending Approval')
				->select(DB::raw('ac.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();

		$ret['replylist'] = DB::table('call_admin_calls as ac')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->where('ac.approval', 'Reply')
				->select(DB::raw('ac.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();

		return Response::json($ret);
	}

	public function getCallCommentList(Request $request) {
		$call_id = $request->get('call_id', 0);

		$data_list = DB::table('call_comments as cc')
			->leftJoin('common_users as cu', 'cc.user_id', '=', 'cu.id')
			->where('cc.call_id', $call_id)
			->orderBy('cc.id', 'Desc')
			->take(5)
			->select(DB::raw('cc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();
		return Response::json($data_list);
	}

	public function getCallCommentListMobile(Request $request) {
		$call_id = $request->get('call_id', 0);

		$data_list = DB::table('call_comments_mobile as cc')
			->leftJoin('common_users as cu', 'cc.user_id', '=', 'cu.id')
			->where('cc.call_id', $call_id)
			->orderBy('cc.id', 'Desc')
			->take(5)
			->select(DB::raw('cc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();
		return Response::json($data_list);
	}

	public function getDestination(Request $request) {
		$destination_id = $request->get('destination_id', 0);

		$data_list = DB::table('call_destination as cd')
			->where('cd.id', $destination_id)
			->select(DB::raw('cd.*'))
			->first();
		return Response::json($data_list);
	}

	public function submitComment(Request $request) {
		$call_id = $request->get('call_id', 0);
		$comment = $request->get('comment', '');
		$submitter = $request->get('submitter', 0);
		$approval = $request->get('approval','');
		$classify =$request->get('classify','');
		$base64_string = $request->get('image_src','') ;
		$image_url = $request->get('image_url','');
		$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/';
		$temp_url = '';
		if(!file_exists($output_file)) {
			mkdir($output_file, 0777);
		}
		if($image_url == '') {
			$output_file = '';
			$temp_url = '';
		}else {
			$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/' . $image_url;
			$temp_url = '/uploads/classify/' . $image_url;
		}
		if($base64_string !='') {
			$ifp = fopen($output_file, "wb");
			$data = explode(',', $base64_string);
			fwrite($ifp, base64_decode($data[1]));
			fclose($ifp);
		}
//		$data_query = $data_query->whereIn('ac.id', $call_ids)
//			->update(['ac.approval' => $type, 'ac.approver' => $agent_id,'ac.comment'=>$comment,'ac.attachment'=>$temp_url]);
//		
		$call_comment = new CallComment();

		$call_comment->call_id = $call_id;
		$call_comment->user_id = $submitter;
		$call_comment->comment = $comment;
		$call_comment->approval = $approval;
		$call_comment->attachment = $temp_url;
		

		$call_comment->save();

		$admin_call = AdminCall::find($call_id);
		if( !empty($admin_call) )
		{
			$admin_call->comment = $comment;
			$admin_call->submitter = $submitter;
			$admin_call->classify = $classify;
			$admin_call->approval = $approval;
			$admin_call->comment = $comment;
			$admin_call->attachment = $temp_url;
			$admin_call->save();
		}

		return Response::json($call_comment);
	}

	public function submitCommentMobile(Request $request) {
		$call_id = $request->get('call_id', 0);
		$comment = $request->get('comment', '');
		$submitter = $request->get('submitter', 0);
		$approval = $request->get('approval','');
		$classify =$request->get('classify','');
		$base64_string = $request->get('image_src','') ;
		$image_url = $request->get('image_url','');
		$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/';
		$temp_url = '';
		if(!file_exists($output_file)) {
			mkdir($output_file, 0777);
		}
		if($image_url == '') {
			$output_file = '';
			$temp_url = '';
		}else {
			$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/' . $image_url;
			$temp_url = '/uploads/classify/mobile/' . $image_url;
		}
		if($base64_string !='') {
			$ifp = fopen($output_file, "wb");
			$data = explode(',', $base64_string);
			fwrite($ifp, base64_decode($data[1]));
			fclose($ifp);
		}
//		$data_query = $data_query->whereIn('ac.id', $call_ids)
//			->update(['ac.approval' => $type, 'ac.approver' => $agent_id,'ac.comment'=>$comment,'ac.attachment'=>$temp_url]);
//		
		$call_comment = new CallCommentMobile();

		$call_comment->call_id = $call_id;
		$call_comment->user_id = $submitter;
		$call_comment->comment = $comment;
		$call_comment->approval = $approval;
		$call_comment->attachment = $temp_url;
		

		$call_comment->save();

		$admin_call = AdminCall::find($call_id);
		if( !empty($admin_call) )
		{
			$admin_call->comment = $comment;
			$admin_call->submitter = $submitter;
			$admin_call->classify = $classify;
			$admin_call->approval = $approval;
			$admin_call->comment = $comment;
			$admin_call->attachment = $temp_url;
			$admin_call->save();
		}

		return Response::json($call_comment);
	}

	public function getApprovalUserList(Request $request) {
		$agent_id = $request->get('agent_id', 0);

		$property_id = $request->get('property_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date('Y-m-d');

		$param = array();
		$param['user_id'] = $agent_id;
		$param['property_id'] = $property_id;
		$param['job_role_id'] = 0;
		$param['dept_id'] = 0;
		$param['usergroup_id'] = 0;
		$param['date'] = $cur_date;
		$delegate_ids = [];
		$delegate_ids = ShiftGroupMember::getUserListUnderApprover($param);
		
		$delegate_ids[] = $agent_id;
		// foreach($delegated_list as $row) {
		// 	$delegate_ids[] = $row->id;
		// }

		// find possible dept ids with app.callaccounting.approval
		// $dept_list = DB::table('common_users as cu')
		// 	->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
		// 	->join('common_permission_members as pm', 'pm.perm_group_id', '=', 'jr.permission_group_id')
		// 	->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
		// 	->where('pr.name', config('constants.CALLACCONTING_APPROVAL_ACCESS'))
		// 	->whereIn('cu.id', $delegate_ids)
		// 	->groupBy('cu.dept_id')
		// 	->select(DB::raw('cu.dept_id'))
		// 	->get();

		// $dept_ids = [];
		// foreach($dept_list as $row)
		// 	$dept_ids[] = $row->dept_id;

		// $manager = CommonUser::find($agent_id);
		// if( empty($manager) )
		// 	return Response::json(array());

		// $dept_id = $manager->dept_id;

		// get department
		$agent_list = DB::table('call_admin_calls as ac')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->whereIn('cu.id', $delegate_ids)
				->where('ac.classify', 'Business')
				->groupBy('ac.submitter')
				->select(DB::raw("cu.*, round(sum(carrier_charges * (approval = 'Waiting for Approval')),2) as cost, sum((approval = 'Waiting for Approval')) as cnt,  CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename, jr.job_role"))
				->get();

		return Response::json($agent_list);
	}
	public function getApprovalMobileUserList(Request $request) {
		$agent_id = $request->get('agent_id', 0);

		$property_id = $request->get('property_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date('Y-m-d');

		$param = array();
		$param['user_id'] = $agent_id;
		$param['property_id'] = $property_id;
		$param['job_role_id'] = 0;
		$param['dept_id'] = 0;
		$param['usergroup_id'] = 0;
		$param['date'] = $cur_date;
		$delegate_ids = [];
		$delegate_ids = ShiftGroupMember::getUserListUnderApprover($param);
		
		$delegate_ids[] = $agent_id;
		// $delegated_list = ShiftGroupMember::getUserListUnderApprover($param);
		// $delegate_ids = [];
		// $delegate_ids[] = $agent_id;
		// foreach($delegated_list as $row) {
		// 	$delegate_ids[] = $row->id;
		// }
		
		// find possible dept ids with app.callaccounting.approval
		
		// $dept_list = DB::table('common_users as cu')
		// 	->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
		// 	->join('common_permission_members as pm', 'pm.perm_group_id', '=', 'jr.permission_group_id')
		// 	->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
		// 	->where('pr.name', config('constants.CALLACCONTING_APPROVAL_ACCESS'))
		// 	->whereIn('cu.id', $delegate_ids)
		// 	->groupBy('cu.dept_id')
		// 	->select(DB::raw('cu.dept_id'))
		// 	->get();
		
		// $dept_ids = [];
		// foreach($dept_list as $row)
		// 	$dept_ids[] = $row->dept_id;

		// $manager = CommonUser::find($agent_id);
		// if( empty($manager) )
		// 	return Response::json(array());

		// $dept_id = $manager->dept_id;

		// get department
		$agent_list = DB::table('call_mobile_calls as ac')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->whereIn('cu.id', $delegate_ids)
				->where('ac.classify', 'Business')
				->groupBy('ac.submitter')
				->select(DB::raw("cu.*, round(sum(charges * (approval = 'Waiting for Approval')),2) as cost, sum((approval = 'Waiting for Approval')) as cnt,  CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename, jr.job_role"))
				->get();

		return Response::json($agent_list);
	}
	public function updateApprovalApproveReject(Request $request){
		$property_id = $request->get('property_id', 0);
		$part = $request->get('part','');
		$type = $request->get('type','');
		$agent_id =$request->get('agent_id',0);
		$submitter_ids = json_decode($request->get('submitter_ids', []));
		$call_ids =json_decode($request->get('call_ids', []));
		$comment = $request->get('comment','');
		$query = DB::table('call_admin_calls as ac')
			->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('ac.classify', 'Business');
		if($part == 'agent') {
			$data_query = clone $query;
			$data_query = $data_query->whereIn('cu.id', $submitter_ids)
				->update(['ac.approval' => $type, 'ac.approver' => $agent_id]);
		}
		if($part == 'date') {
			$data_query = clone $query;
			if($type == 'Returned') {
				$base64_string = $request->get('image_src','') ;
				$image_url = $request->get('image_url','');
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/';
				$temp_url = '';
				if(!file_exists($output_file)) {
					mkdir($output_file, 0777, true);
				}
				if($image_url == '') {
					$output_file = '';
					$temp_url = '';
				}else {
					$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/' . $image_url;
					$temp_url = '/uploads/classify/' . $image_url;
				}
				if($base64_string !='') {
					$ifp = fopen($output_file, "wb");
					$data = explode(',', $base64_string);
					fwrite($ifp, base64_decode($data[1]));
					fclose($ifp);
				}
				$data_query = $data_query->whereIn('ac.id', $call_ids)
					->update(['ac.approval' => $type, 'ac.approver' => $agent_id,'ac.comment'=>$comment,'ac.attachment'=>$temp_url]);
			}else {
				$data_query = $data_query->whereIn('ac.id', $call_ids)
					->update(['ac.approval' => $type, 'ac.approver' => $agent_id]);
			}
		}

		for($i= 0 ; $i <count($call_ids) ;$i++) {
			$approval = $type;
			$base64_string = $request->get('image_src','') ;
			$image_url = $request->get('image_url','');
			$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/';
			$temp_url = '';
			if(!file_exists($output_file)) {
				mkdir($output_file, 0777, true);
			}
			if($image_url == '') {
				$output_file = '';
				$temp_url = '';
			}else {
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/' . $image_url;
				$temp_url = '/uploads/classify/' . $image_url;
			}
			if($base64_string !='') {
				$ifp = fopen($output_file, "wb");
				$data = explode(',', $base64_string);
				fwrite($ifp, base64_decode($data[1]));
				fclose($ifp);
			}

			$submitter_count = count($submitter_ids);

			if( $submitter_count > 0) {
				for( $j=0; $j < count($submitter_ids) ; $j++) {
					$call_comment = new CallComment();
					$call_comment->call_id = $call_ids[$i];
					//$call_comment->user_id = $submitter_ids[$j];
					$call_comment->user_id = $agent_id;
					$call_comment->comment = $comment;
					$call_comment->approval = $approval;
					$call_comment->attachment = $temp_url;
					$call_comment->save();
				}
			}
		}


		return Response::json($submitter_ids);
	}
public function updateApprovalMobileApproveReject(Request $request){
		$property_id = $request->get('property_id', 0);
		$part = $request->get('part','');
		$type = $request->get('type','');
		$agent_id =$request->get('agent_id',0);
		$submitter_ids = json_decode($request->get('submitter_ids', []));
		$call_ids =json_decode($request->get('call_ids', []));
		$comment = $request->get('comment','');
		$query = DB::table('call_mobile_calls as ac')
			->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('ac.classify', 'Business');
		if($part == 'agent') {
			$data_query = clone $query;
			$data_query = $data_query->whereIn('cu.id', $submitter_ids)
				->update(['ac.approval' => $type, 'ac.approver' => $agent_id]);
		}
		if($part == 'date') {
			$data_query = clone $query;
			if($type == 'Returned') {
				$base64_string = $request->get('image_src','') ;
				$image_url = $request->get('image_url','');
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/';
				$temp_url = '';
				if(!file_exists($output_file)) {
					mkdir($output_file, 0777, true);
				}
				if($image_url == '') {
					$output_file = '';
					$temp_url = '';
				}else {
					$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/' . $image_url;
					$temp_url = '/uploads/classify/mobile/' . $image_url;
				}
				if($base64_string !='') {
					$ifp = fopen($output_file, "wb");
					$data = explode(',', $base64_string);
					fwrite($ifp, base64_decode($data[1]));
					fclose($ifp);
				}
				$data_query = $data_query->whereIn('ac.id', $call_ids)
					->update(['ac.approval' => $type, 'ac.approver' => $agent_id,'ac.comment'=>$comment,'ac.attachment'=>$temp_url]);
			}else {
				$data_query = $data_query->whereIn('ac.id', $call_ids)
					->update(['ac.approval' => $type, 'ac.approver' => $agent_id]);
			}
		}

		for($i= 0 ; $i <count($call_ids) ;$i++) {
			$approval = $type;
			$base64_string = $request->get('image_src','') ;
			$image_url = $request->get('image_url','');
			$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/';
			$temp_url = '';
			if(!file_exists($output_file)) {
				mkdir($output_file, 0777, true);
			}
			if($image_url == '') {
				$output_file = '';
				$temp_url = '';
			}else {
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/' . $image_url;
				$temp_url = '/uploads/classify/mobile/' . $image_url;
			}
			if($base64_string !='') {
				$ifp = fopen($output_file, "wb");
				$data = explode(',', $base64_string);
				fwrite($ifp, base64_decode($data[1]));
				fclose($ifp);
			}

			$submitter_count = count($submitter_ids);

			if( $submitter_count > 0) {
				for( $j=0; $j < count($submitter_ids) ; $j++) {
					$call_comment = new CallComment();
					$call_comment->call_id = $call_ids[$i];
					//$call_comment->user_id = $submitter_ids[$j];
					$call_comment->user_id = $agent_id;
					$call_comment->comment = $comment;
					$call_comment->approval = $approval;
					$call_comment->attachment = $temp_url;
					$call_comment->save();
				}
			}
		}


		return Response::json($submitter_ids);
	}
	public function getStatusCount($request) {
		$property_id = $request->get('property_id', 0);
		$query = DB::table('call_admin_calls')
			->where('property_id',$property_id);
		$data = clone $query;
		$data_list = $data->where('classify', 'Unclassified')
			->select(DB::raw("count(*) as cnt , dept_id "))
			->groupBy('dept_id')
			->get();
		$count_list = array();
		foreach($data_list as $row)
		{
			$dept_id = $row->dept_id;

			if( empty($count_list[$dept_id.'key']) ) {
				$count_list[$dept_id.'key'] = array();
			}
			$count_list[$dept_id.'key']['unclasscount'] = $row->cnt ;
		}
		$data = clone $query;
		$data_list = $data->where('approval', 'Waiting For Approval')
			->select(DB::raw("count(*) as cnt , dept_id "))
			->groupBy('dept_id')
			->get();
		foreach($data_list as $row)
		{
			$dept_id = $row->dept_id;

			if( empty($count_list[$dept_id.'key']) ) {
				$count_list[$dept_id.'key'] = array();
			}
			$count_list[$dept_id.'key']['waitingcount'] = $row->cnt ;
		}
			return $count_list;
	}

	public function getStatusCountMobile($request) {
		$property_id = $request->get('property_id', 0);
		$query = DB::table('call_mobile_calls')
			->where('property_id',$property_id);
		$data = clone $query;
		$data_list = $data->where('classify', 'Unclassified')
			->select(DB::raw("count(*) as cnt , dept_id "))
			->groupBy('dept_id')
			->get();
		$count_list = array();
		foreach($data_list as $row)
		{
			$dept_id = $row->dept_id;

			if( empty($count_list[$dept_id.'key']) ) {
				$count_list[$dept_id.'key'] = array();
			}
			$count_list[$dept_id.'key']['unclasscount'] = $row->cnt ;
		}
		$data = clone $query;
		$data_list = $data->where('approval', 'Waiting For Approval')
			->select(DB::raw("count(*) as cnt , dept_id "))
			->groupBy('dept_id')
			->get();
		foreach($data_list as $row)
		{
			$dept_id = $row->dept_id;

			if( empty($count_list[$dept_id.'key']) ) {
				$count_list[$dept_id.'key'] = array();
			}
			$count_list[$dept_id.'key']['waitingcount'] = $row->cnt ;
		}
			return $count_list;
	}

	public function getClassifyStatusCount($request, &$count_list){
		$property_id = $request->get('property_id', 0);
		$classifystatus = $request->get('classifystatus','Business');
		$approvalstatus = '';
		if($classifystatus == 'Business') $approvalstatus = 'Approved';
		if($classifystatus == 'Personal') $approvalstatus = 'Closed';
		$data_list = DB::table('call_admin_calls as ac')
			->where('ac.classify',$classifystatus)
			->where('ac.approval',$approvalstatus)
			->where('ac.property_id',$property_id)
			->select(DB::raw("ac.dept_id as dept_id, round(sum(ac.carrier_charges * (ac.approval = '".$approvalstatus."')),2) as cost ,sum(ac.approval = '".$approvalstatus."') as cnt" ))
			->groupBy('dept_id')
			->get();
		foreach($data_list as $row)
		{
			$dept_id = $row->dept_id;
			if( empty($count_list[$dept_id.'key']) ) {
				$count_list[$dept_id.'key'] = array();
			}
			$count_list[$dept_id.'key']['cost'] = $row->cost ;
			$count_list[$dept_id.'key']['cnt'] = $row->cnt ;
		}
	}
	public function getClassifyStatusCountMobile($request, &$count_list){
		$property_id = $request->get('property_id', 0);
		$classifystatus = $request->get('classifystatus','Business');
		$approvalstatus = '';
		if($classifystatus == 'Business') $approvalstatus = 'Approved';
		if($classifystatus == 'Personal') $approvalstatus = 'Closed';
		$data_list = DB::table('call_mobile_calls as ac')
			->where('ac.classify',$classifystatus)
			->where('ac.approval',$approvalstatus)
			->where('ac.property_id',$property_id)
			->select(DB::raw("ac.dept_id as dept_id, round(sum(ac.charges * (ac.approval = '".$approvalstatus."')),2) as cost ,sum(ac.approval = '".$approvalstatus."') as cnt" ))
			->groupBy('dept_id')
			->get();
		foreach($data_list as $row)
		{
			$dept_id = $row->dept_id;
			if( empty($count_list[$dept_id.'key']) ) {
				$count_list[$dept_id.'key'] = array();
			}
			$count_list[$dept_id.'key']['cost'] = $row->cost ;
			$count_list[$dept_id.'key']['cnt'] = $row->cnt ;
		}
	}

	public  function getFinancelDepartList(Request $request){
		$property_id = $request->get('property_id', 0);
		$classifystatus = $request->get('classifystatus','Business');
		$approvalstatus = '';
		if($classifystatus == 'Business') $approvalstatus = 'Approved';
		if($classifystatus == 'Personal') $approvalstatus = 'Closed';
		// get department
		$department_list = DB::table('common_department as de')
			->where('de.property_id', $property_id)
			->orderBy('de.department')
			->select(DB::raw("de.*"))
			->get();
		$data_list = array();
//		$call_list = DB::table('call_admin_calls as ac')
//			->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
////			->leftJoin('common_department as cd' ,'cu.dept_id','=','cd.id')
//			->where('ac.classify',$classifystatus)
//			->where('ac.approval',$approvalstatus)
//			->select(DB::raw("cu.dept_id as id, sum(ac.classify = 'Unclassified') as unclasscount, sum(ac.approval = 'Waiting For Approval') as waitingcount, round(sum(ac.carrier_charges * (ac.approval = '".$approvalstatus."')),2) as cost ,sum(ac.approval = '".$approvalstatus."') as cnt" ))
////			->groupBy('ac.dept_id')
//			->groupBy('cu.dept_id') // note: submitter id
//			->get();
		$call_list = DB::table('call_admin_calls as ac')
			->where('ac.classify',$classifystatus)
			->where('ac.approval',$approvalstatus)
			->select(DB::raw("ac.dept_id as id, round(sum(ac.carrier_charges * (ac.approval = '".$approvalstatus."')),2) as cost ,sum(ac.approval = '".$approvalstatus."') as cnt" ))
			->groupBy('ac.dept_id')
			->get();

//		$sql = sprintf("SELECT
// 						 cu.dept_id AS id,  ROUND(SUM(ac.carrier_charges * (ac.approval = '%s')), 2) AS cost, SUM(ac.approval = '%s') AS cnt,
//  						 countsnum.unclasscount, countsnum.waitingcount
//						FROM call_admin_calls AS ac
//						LEFT JOIN common_users AS cu   ON ac.submitter = cu.id
//						LEFT JOIN (
//									SELECT
//								  		cu.dept_id AS id,ac.`submitter` AS submitter,
//								  		SUM(ac.classify = 'Unclassified') AS unclasscount,
//								  		SUM(ac.approval = 'Waiting For Approval') AS waitingcount
//									FROM call_admin_calls AS ac
//									LEFT JOIN common_users AS cu   ON ac.submitter = cu.id
//									WHERE ac.property_id = '%d'
//									GROUP BY cu.dept_id
//								) AS countsnum ON countsnum.submitter = ac.submitter
//						WHERE ac.classify = '%s'   AND ac.approval = '%s'  AND ac.property_id = '%d'
//						GROUP BY cu.dept_id ",$approvalstatus,$approvalstatus,$property_id, $classifystatus, $approvalstatus,$property_id);
//		$call_list =  DB::select($sql);
		$count_list = array();
		$count_list = $this->getStatusCount($request);
		$this->getClassifyStatusCount($request, $count_list);
		for( $i =0 ;$i <count($department_list);$i++) {
			$dept_id = $department_list[$i]->id;
			$department = $department_list[$i]->department;
//			$confirm_dept = false;
//			$current =0;
//			for ($j = 0; $j < count($call_list); $j++) {
//				if ($dept_id == $call_list[$j]->id) {
//					$confirm_dept = true;
//					$current = $j;
//					break;
//				}
//			}
			$data =array();
			if (array_key_exists($dept_id.'key',$count_list))
				$data = $count_list[$dept_id.'key'];
			if(!empty($data)) {
				$data_list[$i] = array();// init
				$data_list[$i]['id'] = $dept_id;
				$data_list[$i]['department'] = $department;
				if(!empty($data['unclasscount']))
					$data_list[$i]['unclasscount'] = $data['unclasscount'];
				else
					$data_list[$i]['unclasscount'] = '';
				if(!empty($data['waitingcount']))
					$data_list[$i]['waitingcount'] = $data['waitingcount'];
				else
					$data_list[$i]['waitingcount'] = '';
				if(!empty($data['cost']))
					$data_list[$i]['cost'] = $data['cost'];
				else
					$data_list[$i]['cost'] = '';
				if(!empty($data['cnt']))
					$data_list[$i]['cnt'] = $data['cnt'];
				else
					$data_list[$i]['cnt'] = '';
			}else {
				$data_list[$i] = array();// init
				$data_list[$i]['id'] = $dept_id;
				$data_list[$i]['department'] = $department;
				$data_list[$i]['unclasscount'] = '';
				$data_list[$i]['waitingcount'] = '';
				$data_list[$i]['cost'] = '';
				$data_list[$i]['cnt'] = '';
			}

		}

//		$sql = sprintf("select de.*, ROUND(SUM(ac.carrier_charges),2) AS cost, COUNT(ac.id) AS cnt
//							FROM common_department AS de
//							LEFT JOIN call_admin_calls AS ac ON de.id = ac.`dept_id` AND ac.classify = '%s' AND ac.approval = '%s'
//							WHERE de.property_id = %d
//							GROUP BY de.id", $classifystatus, $approvalstatus, $property_id);
//
//		$result =  DB::select($sql);

		$ret=array();	
		$settings = array();
		$settings['callaccounting_disable_approval'] = 0;
		
		$settings = PropertySetting::getPropertySettings($property_id, $settings);
		$ret['callaccounting_disable_approval'] = $settings['callaccounting_disable_approval'];
		$ret['data_list']=$data_list;
		return Response::json($ret);
	}

		public  function getFinanceDepartListMobile(Request $request){
		$property_id = $request->get('property_id', 0);
		$classifystatus = $request->get('classifystatus','Business');
		$approvalstatus = '';
		if($classifystatus == 'Business') $approvalstatus = 'Approved';
		if($classifystatus == 'Personal') $approvalstatus = 'Closed';
		// get department
		$department_list = DB::table('common_department as de')
			->where('de.property_id', $property_id)
			->orderBy('de.department')
			->select(DB::raw("de.*"))
			->get();
		$data_list = array();
//		$call_list = DB::table('call_admin_calls as ac')
//			->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
////			->leftJoin('common_department as cd' ,'cu.dept_id','=','cd.id')
//			->where('ac.classify',$classifystatus)
//			->where('ac.approval',$approvalstatus)
//			->select(DB::raw("cu.dept_id as id, sum(ac.classify = 'Unclassified') as unclasscount, sum(ac.approval = 'Waiting For Approval') as waitingcount, round(sum(ac.carrier_charges * (ac.approval = '".$approvalstatus."')),2) as cost ,sum(ac.approval = '".$approvalstatus."') as cnt" ))
////			->groupBy('ac.dept_id')
//			->groupBy('cu.dept_id') // note: submitter id
//			->get();
		$call_list = DB::table('call_mobile_calls as ac')
			->where('ac.classify',$classifystatus)
			->where('ac.approval',$approvalstatus)
			->select(DB::raw("ac.dept_id as id, round(sum(ac.charges * (ac.approval = '".$approvalstatus."')),2) as cost ,sum(ac.approval = '".$approvalstatus."') as cnt" ))
			->groupBy('ac.dept_id')
			->get();

//		$sql = sprintf("SELECT
// 						 cu.dept_id AS id,  ROUND(SUM(ac.carrier_charges * (ac.approval = '%s')), 2) AS cost, SUM(ac.approval = '%s') AS cnt,
//  						 countsnum.unclasscount, countsnum.waitingcount
//						FROM call_admin_calls AS ac
//						LEFT JOIN common_users AS cu   ON ac.submitter = cu.id
//						LEFT JOIN (
//									SELECT
//								  		cu.dept_id AS id,ac.`submitter` AS submitter,
//								  		SUM(ac.classify = 'Unclassified') AS unclasscount,
//								  		SUM(ac.approval = 'Waiting For Approval') AS waitingcount
//									FROM call_admin_calls AS ac
//									LEFT JOIN common_users AS cu   ON ac.submitter = cu.id
//									WHERE ac.property_id = '%d'
//									GROUP BY cu.dept_id
//								) AS countsnum ON countsnum.submitter = ac.submitter
//						WHERE ac.classify = '%s'   AND ac.approval = '%s'  AND ac.property_id = '%d'
//						GROUP BY cu.dept_id ",$approvalstatus,$approvalstatus,$property_id, $classifystatus, $approvalstatus,$property_id);
//		$call_list =  DB::select($sql);
		$count_list = array();
		$count_list = $this->getStatusCountMobile($request);
		$this->getClassifyStatusCountMobile($request, $count_list);
		for( $i =0 ;$i <count($department_list);$i++) {
			$dept_id = $department_list[$i]->id;
			$department = $department_list[$i]->department;
//			$confirm_dept = false;
//			$current =0;
//			for ($j = 0; $j < count($call_list); $j++) {
//				if ($dept_id == $call_list[$j]->id) {
//					$confirm_dept = true;
//					$current = $j;
//					break;
//				}
//			}
			$data =array();
			if (array_key_exists($dept_id.'key',$count_list))
				$data = $count_list[$dept_id.'key'];
			if(!empty($data)) {
				$data_list[$i] = array();// init
				$data_list[$i]['id'] = $dept_id;
				$data_list[$i]['department'] = $department;
				if(!empty($data['unclasscount']))
					$data_list[$i]['unclasscount'] = $data['unclasscount'];
				else
					$data_list[$i]['unclasscount'] = '';
				if(!empty($data['waitingcount']))
					$data_list[$i]['waitingcount'] = $data['waitingcount'];
				else
					$data_list[$i]['waitingcount'] = '';
				if(!empty($data['cost']))
					$data_list[$i]['cost'] = $data['cost'];
				else
					$data_list[$i]['cost'] = '';
				if(!empty($data['cnt']))
					$data_list[$i]['cnt'] = $data['cnt'];
				else
					$data_list[$i]['cnt'] = '';
			}else {
				$data_list[$i] = array();// init
				$data_list[$i]['id'] = $dept_id;
				$data_list[$i]['department'] = $department;
				$data_list[$i]['unclasscount'] = '';
				$data_list[$i]['waitingcount'] = '';
				$data_list[$i]['cost'] = '';
				$data_list[$i]['cnt'] = '';
			}

		}

//		$sql = sprintf("select de.*, ROUND(SUM(ac.carrier_charges),2) AS cost, COUNT(ac.id) AS cnt
//							FROM common_department AS de
//							LEFT JOIN call_admin_calls AS ac ON de.id = ac.`dept_id` AND ac.classify = '%s' AND ac.approval = '%s'
//							WHERE de.property_id = %d
//							GROUP BY de.id", $classifystatus, $approvalstatus, $property_id);
//
//		$result =  DB::select($sql);
		$ret=array();	
		$settings = array();
		$settings['callaccounting_disable_approval'] = 0;
		
		$settings = PropertySetting::getPropertySettings($property_id, $settings);
		$ret['callaccounting_disable_approval'] = $settings['callaccounting_disable_approval'];
		$ret['data_list']=$data_list;
		return Response::json($ret);
	}

	public  function updateFinanceDepartClose(Request $request){
		$property_id = $request->get('property_id', 0);
		$kind = $request->get('kind','');
		$type = $request->get('type','');
		$depart_ids = json_decode($request->get('depart_ids',[])) ;
		$agent_ids = json_decode($request->get('agent_ids',[]));
		$calls_ids = json_decode($request->get('calls_ids',[]));
		$comment = $request->get('comment','');

		if($kind == 'department') {
			//update approval colum to 'Closed' with same departmant, classify=Business, approval= Approved
			$query = DB::table('call_admin_calls as ac')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->whereIn('cu.dept_id', $depart_ids)
				->where('ac.classify', 'Business')
				->where('ac.approval', 'Approved')
				->update(['ac.approval' => 'Closed']);
		}
		if($kind == 'agent') {
			$classifystatus = $request->get('classifystatus','Business');
			$approvalstatus = '';
			if($classifystatus == 'Business') $approvalstatus = 'Approved';
			if($classifystatus == 'Personal') $approvalstatus = 'Closed';
			$query = DB::table('call_admin_calls as ac')
//				->where('ac.classify', 'Business')
//				->where('ac.approval', 'Approved');
				->where('ac.classify', $classifystatus )
				->where('ac.approval', $approvalstatus);
			if( $type == 'child'  ) {

				$data_query = clone $query ;
				$data = $data_query->whereIn('ac.id',$calls_ids)
					->update(['ac.approval' => 'Closed']);

			}else if($type == 'child_return'){
				$base64_string = $request->get('image_src','') ;
				$image_url = $request->get('image_url','');
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/';
				$temp_url = '';
				if(!file_exists($output_file)) {
					mkdir($output_file, 0777);
				}
				if($image_url == '') {
					$output_file = '';
					$temp_url = '';
				}else {
					$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/' . $image_url;
					$temp_url = '/uploads/classify/' . $image_url;
				}
				if($base64_string !='') {
					$ifp = fopen($output_file, "wb");
					$data = explode(',', $base64_string);
					fwrite($ifp, base64_decode($data[1]));
					fclose($ifp);
				}
				$data_query = clone $query ;
				$data = $data_query->whereIn('ac.id',$calls_ids)
					->update(['ac.approval' => 'Returned','ac.comment'=>$comment,'ac.attachment'=>$temp_url]);

			}else if( $type == 'child_return_unclassified') {
				$base64_string = $request->get('image_src','') ;
				$image_url = $request->get('image_url','');
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/';
				$temp_url = '';
				if(!file_exists($output_file)) {
					mkdir($output_file, 0777);
				}
				if($image_url == '') {
					$output_file = '';
					$temp_url = '';
				}else {
					$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/' . $image_url;
					$temp_url = '/uploads/classify/' . $image_url;
				}
				if($base64_string !='') {
					$ifp = fopen($output_file, "wb");
					$data = explode(',', $base64_string);
					fwrite($ifp, base64_decode($data[1]));
					fclose($ifp);
				}
				$data_query = clone $query ;
				$data = $data_query->whereIn('ac.id',$calls_ids)
					->update(['ac.classify' => 'Unclassified', 'ac.approval' => 'Returned','ac.comment'=>$comment,'ac.attachment'=>$temp_url]);

			}else if($type == 'charged'){

				$data_query = clone $query ;
				$data = $data_query->whereIn('ac.id',$calls_ids)
					->update(['ac.approval' => 'Charged']);

			}else {
				$data_query = clone $query ;
				if($classifystatus == 'Business') {
					$data = $data_query->whereIn('ac.submitter', $agent_ids)
						->update(['ac.approval' => 'Closed']);
				}
				if($classifystatus == 'Personal') {
					$data = $data_query->whereIn('ac.submitter', $agent_ids)
						->update(['ac.approval' => 'Charged']);
				}
			}
		}

		for($i= 0 ; $i <count($calls_ids) ;$i++) {
			$approval = '';
			if( $type == 'child'  ) {
				$approval = 'Closed';
			}else if($type == 'child_return') {
				$approval = 'Returned';
			}else if($type == 'child_return_unclassified') {
				$approval = 'Returned';
			}else if($type == 'charged') {
				$approval = 'Charged';
			}else {
				$classifystatus = $request->get('classifystatus','Business');
				if($classifystatus == 'Business') {
					$approval = 'Closed';
				}
				if($classifystatus == 'Personal') {
					$approval='Charged';
				}
			}
			$base64_string = $request->get('image_src','') ;
			$image_url = $request->get('image_url','');
			$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/';
			$temp_url = '';
			if(!file_exists($output_file)) {
				mkdir($output_file, 0777);
			}
			if($image_url == '') {
				$output_file = '';
				$temp_url = '';
			}else {
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/' . $image_url;
				$temp_url = '/uploads/classify/' . $image_url;
			}
			if($base64_string !='') {
				$ifp = fopen($output_file, "wb");
				$data = explode(',', $base64_string);
				fwrite($ifp, base64_decode($data[1]));
				fclose($ifp);
			}
			$submitter_count = count($agent_ids);
			if( $submitter_count > 0) {
				for( $j=0; $j < count($agent_ids) ; $j++) {
					$call_comment = new CallComment();
					$call_comment->call_id = $calls_ids[$i];
					$call_comment->user_id = $agent_ids[$j];
					$call_comment->comment = $comment;
					$call_comment->approval = $approval;
					$call_comment->attachment = $temp_url;
					$call_comment->save();
				}
			}
		}
		return Response::json($depart_ids);
	}

		public  function updateFinanceMobDepartClose(Request $request){
		$property_id = $request->get('property_id', 0);
		$kind = $request->get('kind','');
		$type = $request->get('type','');
		$depart_ids = json_decode($request->get('depart_ids',[])) ;
		$agent_ids = json_decode($request->get('agent_ids',[]));
		$calls_ids = json_decode($request->get('calls_ids',[]));
		$comment = $request->get('comment','');

		if($kind == 'department') {
			//update approval colum to 'Closed' with same departmant, classify=Business, approval= Approved
			$query = DB::table('call_mobile_calls as ac')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->whereIn('cu.dept_id', $depart_ids)
				->where('ac.classify', 'Business')
				->where('ac.approval', 'Approved')
				->update(['ac.approval' => 'Closed']);
		}
		if($kind == 'agent') {
			$classifystatus = $request->get('classifystatus','Business');
			$approvalstatus = '';
			if($classifystatus == 'Business') $approvalstatus = 'Approved';
			if($classifystatus == 'Personal') $approvalstatus = 'Closed';
			$query = DB::table('call_mobile_calls as ac')
//				->where('ac.classify', 'Business')
//				->where('ac.approval', 'Approved');
				->where('ac.classify', $classifystatus )
				->where('ac.approval', $approvalstatus);
			if( $type == 'child'  ) {

				$data_query = clone $query ;
				$data = $data_query->whereIn('ac.id',$calls_ids)
					->update(['ac.approval' => 'Closed']);

			}else if($type == 'child_return'){
				$base64_string = $request->get('image_src','') ;
				$image_url = $request->get('image_url','');
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/';
				$temp_url = '';
				if(!file_exists($output_file)) {
					mkdir($output_file, 0777);
				}
				if($image_url == '') {
					$output_file = '';
					$temp_url = '';
				}else {
					$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/' . $image_url;
					$temp_url = '/uploads/classify/mobile/' . $image_url;
				}
				if($base64_string !='') {
					$ifp = fopen($output_file, "wb");
					$data = explode(',', $base64_string);
					fwrite($ifp, base64_decode($data[1]));
					fclose($ifp);
				}
				$data_query = clone $query ;
				$data = $data_query->whereIn('ac.id',$calls_ids)
					->update(['ac.approval' => 'Returned','ac.comment'=>$comment,'ac.attachment'=>$temp_url]);

			}else if( $type == 'child_return_unclassified') {
				$base64_string = $request->get('image_src','') ;
				$image_url = $request->get('image_url','');
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/';
				$temp_url = '';
				if(!file_exists($output_file)) {
					mkdir($output_file, 0777);
				}
				if($image_url == '') {
					$output_file = '';
					$temp_url = '';
				}else {
					$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/' . $image_url;
					$temp_url = '/uploads/classify/mobile/' . $image_url;
				}
				if($base64_string !='') {
					$ifp = fopen($output_file, "wb");
					$data = explode(',', $base64_string);
					fwrite($ifp, base64_decode($data[1]));
					fclose($ifp);
				}
				$data_query = clone $query ;
				$data = $data_query->whereIn('ac.id',$calls_ids)
					->update(['ac.classify' => 'Unclassified', 'ac.approval' => 'Returned','ac.comment'=>$comment,'ac.attachment'=>$temp_url]);

			}else if($type == 'charged'){

				$data_query = clone $query ;
				$data = $data_query->whereIn('ac.id',$calls_ids)
					->update(['ac.approval' => 'Charged']);

			}else {
				$data_query = clone $query ;
				if($classifystatus == 'Business') {
					$data = $data_query->whereIn('ac.submitter', $agent_ids)
						->update(['ac.approval' => 'Closed']);
				}
				if($classifystatus == 'Personal') {
					$data = $data_query->whereIn('ac.submitter', $agent_ids)
						->update(['ac.approval' => 'Charged']);
				}
			}
		}

		for($i= 0 ; $i <count($calls_ids) ;$i++) {
			$approval = '';
			if( $type == 'child'  ) {
				$approval = 'Closed';
			}else if($type == 'child_return') {
				$approval = 'Returned';
			}else if($type == 'child_return_unclassified') {
				$approval = 'Returned';
			}else if($type == 'charged') {
				$approval = 'Charged';
			}else {
				$classifystatus = $request->get('classifystatus','Business');
				if($classifystatus == 'Business') {
					$approval = 'Closed';
				}
				if($classifystatus == 'Personal') {
					$approval='Charged';
				}
			}
			$base64_string = $request->get('image_src','') ;
			$image_url = $request->get('image_url','');
			$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/';
			$temp_url = '';
			if(!file_exists($output_file)) {
				mkdir($output_file, 0777);
			}
			if($image_url == '') {
				$output_file = '';
				$temp_url = '';
			}else {
				$output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/classify/mobile/' . $image_url;
				$temp_url = '/uploads/classify/mobile/' . $image_url;
			}
			if($base64_string !='') {
				$ifp = fopen($output_file, "wb");
				$data = explode(',', $base64_string);
				fwrite($ifp, base64_decode($data[1]));
				fclose($ifp);
			}
			$submitter_count = count($agent_ids);
			if( $submitter_count > 0) {
				for( $j=0; $j < count($agent_ids) ; $j++) {
					$call_comment = new CallCommentMobile();
					$call_comment->call_id = $calls_ids[$i];
					$call_comment->user_id = $agent_ids[$j];
					$call_comment->comment = $comment;
					$call_comment->approval = $approval;
					$call_comment->attachment = $temp_url;
					$call_comment->save();
				}
			}
		}
		return Response::json($depart_ids);
	}

	public function getApprovalNotifyList(Request $request) {
		$agent_id = $request->get('agent_id', 0);

		$manager = CommonUser::find($agent_id);
		if( empty($manager) )
			return Response::json(array());

		$dept_id = $manager->dept_id;

		// get department
		$agent_list = DB::table('call_admin_calls as ac')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('cu.dept_id', $dept_id)
				->where('ac.approval', 'Waiting for Approval')
				->where('ac.classify', 'Business')
				->groupBy('ac.submitter')
				->select(DB::raw("cu.*, sum(carrier_charges) as cost, count(*) as cnt,  CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename, jr.job_role"))
				->get();

		return Response::json($agent_list);
	}



	public function getApprovalListForUser(Request $request) {
		$agent_id = $request->get('agent_id', 0);
		$approver_id = $request->get('approver_id', 0);
		$property_id = $request->get('property_id', 0);

		if( $agent_id > 0 )
		{
			$agent_list = DB::table('call_admin_calls as ac')
					->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
					->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
					->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
					->where('ac.submitter', $agent_id)
					->where('ac.approval', 'Waiting for Approval')
					->where('ac.classify', 'Business')
					->select(DB::raw("ac.*, se.extension"))
					->get();
		}
		else
		{
		$param = array();
		$param['user_id'] = $approver_id;
		$param['property_id'] = $property_id;
		$param['job_role_id'] = 0;
		$param['dept_id'] = 0;
		$param['usergroup_id'] = 0;
		//$param['date'] = $cur_date;
		$delegate_ids = [];
		$delegate_ids = ShiftGroupMember::getUserListUnderApprover($param);
		
		$delegate_ids[] = $approver_id;
			$agent_list = DB::table('call_admin_calls as ac')
					->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
					->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
					->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
					->whereIn('cu.id', $delegate_ids)
					->where('ac.approval', 'Waiting for Approval')
					->where('ac.classify', 'Business')
					->select(DB::raw("ac.*, se.extension"))
					->get();
		}

		return Response::json($agent_list);
	}
		public function getApprovalMobileListForUser(Request $request) {
		$agent_id = $request->get('agent_id', 0);
		$approver_id = $request->get('approver_id', 0);
		$property_id = $request->get('property_id', 0);
			
		if( $agent_id > 0 )
		{
			$agent_list = DB::table('call_mobile_calls as ac')
					->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
					->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
					->where('ac.submitter', $agent_id)
					->where('ac.approval', 'Waiting for Approval')
					->where('ac.classify', 'Business')
					->select(DB::raw("ac.*"))
					->get();
		}
		else
		{
		$param = array();
		$param['user_id'] = $approver_id;
		$param['property_id'] = $property_id;
		$param['job_role_id'] = 0;
		$param['dept_id'] = 0;
		$param['usergroup_id'] = 0;
		//$param['date'] = $cur_date;
		$delegate_ids = [];
		$delegate_ids = ShiftGroupMember::getUserListUnderApprover($param);
		
		$delegate_ids[] = $approver_id;
			$agent_list = DB::table('call_mobile_calls as ac')
					->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
					->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
					->whereIn('cu.id', $delegate_ids)
					->where('ac.approval', 'Waiting for Approval')
					->where('ac.classify', 'Business')
					->select(DB::raw("ac.*"))
					->get();
		}

		return Response::json($agent_list);
	}

	public function getFinanceListForUser(Request $request) {
		$agent_id = $request->get('agent_id', 0);
		$dept_id = $request->get('dept_id', 0);
		$property_id = $request->get('property_id', 0);
		$approvalstatusarr=array('Closed','Waiting For Approval');
		$settings = array();
		$settings['callaccounting_disable_approval'] = 0;
		
		$settings = PropertySetting::getPropertySettings($property_id, $settings);
		$classifystatus = $request->get('classifystatus','Business');
		$approvalstatus = '';
		if($classifystatus == 'Business') $approvalstatus = 'Approved';
		if($classifystatus == 'Personal') $approvalstatus = 'Closed';
		if( $agent_id > 0 )
		{
			$query = DB::table('call_admin_calls as ac')
				->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('ac.submitter', $agent_id);

//				->where('ac.approval', 'Approved')
//				->where('ac.classify', 'Business')
			if($settings['callaccounting_disable_approval'] == 0)
				$query->where('ac.approval', $approvalstatus);
			elseif($settings['callaccounting_disable_approval'] == 1)
				$query->whereIn('ac.approval', $approvalstatusarr);	

				$agent_list=$query->where('ac.classify', $classifystatus)
				->orderby('ac.submitter')
				->select(DB::raw("ac.*, se.extension, CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename"))
				->get();
		}
		else
		{

			$query = DB::table('call_admin_calls as ac')
				->join('call_staff_extn as se', 'ac.extension_id', '=', 'se.id')
				->leftJoin('common_users as cu', 'ac.submitter', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id');
//				->where('ac.approval', 'Approved')
//				->where('ac.classify', 'Business');
			if($settings['callaccounting_disable_approval'] == 0)
				$query->where('ac.approval', $approvalstatus);
			elseif($settings['callaccounting_disable_approval'] == 1)
				$query->whereIn('ac.approval', $approvalstatusarr);

				$query->where('ac.classify', $classifystatus);
			$data_query = clone $query;
			//$agent_list = $data_query->where('cu.dept_id', $dept_id)
			$agent_list = $data_query->where('ac.dept_id', $dept_id)
				->groupBy('cu.id')
				->select(DB::raw("ac.*, cu.id as agent_id, se.extension,CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename"))
				->get();
	
			$detail_list = array();
			$count =0 ;
			for( $i=0; $i<count($agent_list); $i++ ) {
				$detail_list[$count] = clone  $agent_list[$i];
				$agent_id = $detail_list[$count]->agent_id;
				$detail_query = clone $query;
				$detail_inform = $detail_query
					->where('ac.submitter', $agent_id)
					->orderby('ac.call_date')
					->select(DB::raw("ac.*, se.extension"))
					->get();
				if(!empty($detail_inform)){
					//$count++;
					//$detail_list[$count] = clone  $agent_list[$i];
					$detail_list[$count]->inform = $detail_inform;
					$count++;
				}

			}

		}
		return Response::json($detail_list);
	}

		public function getFinanceListMobForUser(Request $request) {
		$agent_id = $request->get('agent_id', 0);
		$dept_id = $request->get('dept_id', 0);
		$property_id = $request->get('property_id', 0);
		$classifystatus = $request->get('classifystatus','Business');
		$approvalstatus = '';
		$approvalstatusarr=array('Closed','Waiting For Approval');
		$settings = array();
		$settings['callaccounting_disable_approval'] = 0;
		
		$settings = PropertySetting::getPropertySettings($property_id, $settings);
		if($classifystatus == 'Business') $approvalstatus = 'Approved';
		if($classifystatus == 'Personal') $approvalstatus = 'Closed';
		if( $agent_id > 0 )
		{
			
			$query = DB::table('call_mobile_calls as ac')
				->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('ac.user_id', $agent_id);
				
//				->where('ac.approval', 'Approved')
//				->where('ac.classify', 'Business')
			if($settings['callaccounting_disable_approval'] == 0)
				$query->where('ac.approval', $approvalstatus);
			elseif($settings['callaccounting_disable_approval'] == 1)
				$query->whereIn('ac.approval', $approvalstatusarr);

				$agent_list=$query->where('ac.classify', $classifystatus)
				
				->orderby('ac.user_id')
				->select(DB::raw("ac.*, CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename"))
				->get();
		}
		else
		{
		
			$query = DB::table('call_mobile_calls as ac')
				->leftJoin('common_users as cu', 'ac.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id');
//				->where('ac.approval', 'Approved')
//				->where('ac.classify', 'Business');
				if($settings['callaccounting_disable_approval'] == 0)
				$query->where('ac.approval', $approvalstatus);
				elseif($settings['callaccounting_disable_approval'] == 1)
				$query->whereIn('ac.approval', $approvalstatusarr);

				$query->where('ac.classify', $classifystatus);
			$data_query = clone $query;
			//$agent_list = $data_query->where('cu.dept_id', $dept_id)
			$agent_list = $data_query->where('ac.dept_id', $dept_id)
				->groupBy('cu.id')
				->select(DB::raw("ac.*, cu.id as agent_id,CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename"))
				->get();
			$detail_list = array();
			$count =0 ;
			for( $i=0; $i<count($agent_list); $i++ ) {
				$detail_list[$count] = clone  $agent_list[$i];
				$agent_id = $detail_list[$count]->agent_id;
				$detail_query = clone $query;
				$detail_inform = $detail_query
					->where('ac.user_id', $agent_id)
					->orderby('ac.date')
					->select(DB::raw("ac.*"))
					->get();
				if(!empty($detail_inform)){
					//$count++;
					//$detail_list[$count] = clone  $agent_list[$i];
					$detail_list[$count]->inform = $detail_inform;
					$count++;
				}

			}

		}
		return Response::json($detail_list);
	}

	public function approveCall(Request $request) {
		$calls = $request->get('calls', array());

		for( $i = 0; $i < count($calls); $i++ )
		{
			$call = $calls[$i];

			AdminCall::where('id', $call['id'])
					->update($call);

			if( empty($call['comment']) )
				continue;

			$comment = new CallComment();

			$comment->call_id = $call['id'];
			$comment->user_id = $call['approver'];
			$comment->comment = $call['comment'];

			$comment->save();
		}

		return Response::json($calls);
	}
	public function approveMobileCall(Request $request) {
		$calls = $request->get('calls', array());

		for( $i = 0; $i < count($calls); $i++ )
		{
			$call = $calls[$i];

			MobileCalls::where('id', $call['id'])
					->update($call);

			if( empty($call['comment']) )
				continue;

			$comment = new CallComment();

			$comment->call_id = $call['id'];
			$comment->user_id = $call['approver'];
			$comment->comment = $call['comment'];

			$comment->save();
		}

		return Response::json($calls);
	}

	public function getDestinationList(Request $request) {
		$property_id = $request->get('property_id', 0);
		$datalist = DB::table('call_destination')->get();

		return Response::json($datalist);
	}

	public function fixReceivedCall(Request $request) {
		$id = 17;
		DB::select("INSERT INTO call_admin_received_calls (
			  SELECT se.id, se1.extension, bc.user_id, call_date, start_time, end_time, duration, 
			    called_no, trunk, transfer, 'Received_I' AS call_type, destination_id, carrier_charges, classify, approval, 
			    submitter, approver, COMMENT, guest_charge_rate_id 
			  FROM call_bc_calls AS bc 
			  JOIN call_staff_extn AS se ON (bc.called_no = se.extension AND bc_flag = '0')
			  JOIN call_staff_extn AS se1 ON bc.extension_id = se1.id
			  WHERE call_type = 'Internal' and bc.id = ? 
			  )", [$id]);

		$id = 18;
		DB::select("INSERT INTO call_guest_received_call (
				 SELECT 
					ge.room_id, '' AS from_room, 0 AS guest_id, ge.id, se1.extension, call_date, called_no, 'Received_I' AS call_type, trunk, transfer, start_time, end_time, duration, 
					0 AS pulse, destination_id, carrier_charges, 0 AS tax, 0 AS hotel_charges, 0 AS total_charges, guest_charge_rate_id 
					FROM call_bc_calls AS bc
				JOIN call_guest_extn AS ge ON bc.called_no = ge.extension
				JOIN call_staff_extn AS se1 ON bc.extension_id = se1.id  
				  WHERE call_type = 'Internal' and bc.id = ? 
              )", [$id]);

		$id = 19;
		DB::select("INSERT INTO call_bc_received_calls (
			  SELECT se.id, se1.extension, bc.user_id, call_date, start_time, end_time, duration, 
			    called_no, trunk, transfer, 'Received_I' AS call_type, destination_id, carrier_charges, tax, hotel_charges, total_charges, classify, approval, 
			    submitter, approver, COMMENT, guest_charge_rate_id 
			  FROM call_bc_calls AS bc 
			  JOIN call_staff_extn AS se ON (bc.called_no = se.extension AND se.bc_flag = '1')
			  JOIN call_staff_extn AS se1 ON bc.extension_id = se1.id
			  WHERE call_type = 'Internal' and bc.id = ? 
              )", [$id]);
	}

	public function getPhonebookList(Request $request) {
		
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$user_id = $request->get('user_id', '0');
		$searchtext = $request->get('searchtext', '');
		$type = $request->get('type', '');
		
		if($pageSize < 0 )
			$pageSize = 20;
		$ret = array();
		
		$query = DB::table('phonebook as pb')->whereIn('user_id',array($user_id,0));
								
		if($searchtext !='') {
			$where = sprintf(" (pb.contact_name like '%%%s%%' or								
								pb.contact_no like '%%%s%%' or
								pb.classify_comment like '%%%s%%' or
								pb.type like '%%%s%%')",
				$searchtext, $searchtext,$searchtext, $searchtext);
				$query->whereRaw($where);
		}
		if($type !='') {
			$query->where('pb.type','=',$type);
		}
		
		$data_query = clone $query;
		
		$data_list = $data_query
			->orderBy('pb.id', $sort)
			->select(DB::raw('pb.*'))
			->skip($skip)->take($pageSize)
			->get();
			
		
		
		
		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		
		return Response::json($ret);
		
	}

	public function addPhonebook(Request $request) {
		$contact_name = $request->get('contact_name', '');
		$contact_no = $request->get('contact_no', 0);
		//$id = $request->get('id',0);
		$type = $request->get('type','');
		$comment = $request->get('classify_comment','');
		$user_id = $request->get('user_id',0);
		$classify = $request->get('auto_classify',0);
		
						
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$phonebook_entry = Phonebook::where('contact_no',$contact_no)->where('user_id',$user_id)->get();
		
		if(count($phonebook_entry)>0)
		{
		//	echo 'Inside';
		$ret = array();

		$ret['message']="Phone number already exists in Phonebook.";
		return Response::json($ret);
		}


			DB::table('phonebook')->insert(['user_id' => $user_id,
				'contact_name' => $contact_name,
				'contact_no' => $contact_no,
				'auto_classify' => $classify,
				'classify_comment' => $comment,
				'type' => $type]);		
		
		MobileCalls::where('user_id',$user_id)->where('call_to',$contact_no)->update(['phonebk_flag' => 1]);
		AdminCall::where('user_id',$user_id)->where('called_no',$contact_no)->update(['phonebk_flag' => 1]);
		$ret = array();
		
		return Response::json($ret);
		}

		public function updatePhonebook(Request $request) {
			$id = $request->get('id', '');
			$input = $request->all();
			DB::table('phonebook')
				->where('id', $id)
				->update($input);	// read state
	
			return Response::json($input);
		}

	public function deletePhonebook(Request $request) {
		$id = $request->get('id', '');
		DB::table('phonebook')->where('id', $id)->delete();

		return Response::json($id);
	}

}


