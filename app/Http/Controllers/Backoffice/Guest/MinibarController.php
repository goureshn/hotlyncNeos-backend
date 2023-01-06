<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Common\PropertySetting;
use App\Models\Service\MinibarLogs;
use Illuminate\Http\Request;
use App\Http\Controllers\UploadController;
use App\Models\Common\Building;
use App\Models\Service\RoomServiceGroup;
use App\Models\Service\RoomServiceItem;
use App\Modules\Functions;

use Excel;
use Response;
use DB;
use Datatables;
use DateTime;
use DateInterval;
use Redis;

class MinibarController extends UploadController
{
   	public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('services_rm_srv_grp as rsg')			
						->leftJoin('common_building as cb', 'rsg.building_id', '=', 'cb.id')
						->leftJoin('common_room_type as rt', 'rsg.room_type_id', '=', 'rt.id')
						->select(DB::raw('rsg.*, cb.name as cbname, rt.type as room_type'));
						
		return Datatables::of($datalist)		
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('edit', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
					})
					->addColumn('delete', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="job_role!=\'SuperAdmin\'" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
					})				
					->make(true);
		}
		else
		{
			$rsglist = RoomServiceGroup::lists('name', 'id');
			$buildlist = Building::lists('name', 'id');
			
			$rsg_id = 1;
			$rsg = RoomServiceGroup::first();
			if( !empty($rsg) )
				$rsg_id = $rsg->id;			
			
			$step = '4';
			
			return view('backoffice.wizard.guestservice.minibar', compact('rsglist', 'buildlist', 'rsg_id', 'step'));					
		}
		
    }

    public function create(Request $request)
    {
		$input = $request->all();
			
		$model = RoomServiceGroup::create($input);
		
		return Response::json($model);				
    }
		
	public function getGroupList(Request $request)
    {
		$rsg_id = $request->get('rsg_id', '1');
		
		$rsi = RoomServiceItem::where('room_service_group', $rsg_id)->get();
			
		return Response::json($rsi);		
    }

	public function createRSIList(Request $request)
    {
		$input = $request->all();
		
		$model = RoomServiceItem::create($input);
		
		return Response::json($model);				
    }
	
    public function store(Request $request)
    {
    	$input = $request->except('id');
			
		$model = RoomServiceGroup::create($input);
		
		return Response::json($model);			
    }

    public function show($id)
    {
        $model = RoomServiceGroup::find($id);	
		
		return Response::json($model);
    }

    public function edit(Request $request, $id)
    {
        
    }

    public function update(Request $request, $id)
    {
		$model = RoomServiceGroup::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		return Response::json($model);	
    }

    public function destroy(Request $request, $id)
    {
        $model = RoomServiceGroup::find($id);
		$model->delete();
		return Response::json($model);
    }

	public function getRoomTypeList(Request $request) {
		$build_id = $request->get('build_id', 0);

		$datalist = DB::table('common_room_type')
			->where('bldg_id', $build_id)
			->get();

		return Response::json($datalist);
	}

	public function parseExcelFile($path)
	{
		Excel::selectSheets('room_service_group')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;					
					RoomServiceGroup::create($data);
				}
			}							
		});
	}

	public function getStatisticInfo(Request $request)
	{
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$during = $request->get('during', '');
		$user_id = $request->get('user_id', '');
		$building_ids = $request->get('building_ids', '');

		$ret = array();
		switch($period)
		{
			case 'Today';
				$ret = $this->getStaticsticsByToday($request, $user_id, $building_ids);
				break;
			case 'Weekly';
				$ret = $this->getStaticsticsByDate($request, $end_date, 7, $user_id, $building_ids);
				break;
			case 'Monthly';
				$ret = $this->getStaticsticsByDate($request, $end_date, 30, $user_id, $building_ids);
				break;
			case 'Custom Days';
				$ret = $this->getStaticsticsByDate($request, $end_date, $during, $user_id, $building_ids);
				break;
			case 'Yearly';
				$ret = $this->getStaticsticsByDate($request, $end_date, 365, $user_id, $building_ids);
				break;
		}

		return Response::json($ret);
	}

	public function getStaticsticsByToday(Request $request, $user_id, $building_ids)
	{

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$yesterday_time = date('Y-m-d H:i:s',strtotime("-1 days")); // last 24

		$building_list = DB::table('services_minibar_profile')
				->where('user_id', $user_id)
				->select(DB::raw('buildings'))
				->first();

		if(!empty($building_list)) {
					$building_id = $building_list->buildings;
		}else {
					$building_id = $building_ids;
		}

		$query = DB::table('services_minibar_log as ml')
				->join('common_room as cr', 'ml.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('common_users as cu', 'ml.user_id', '=', 'cu.id')
				->whereRaw(" ml.created_at >= '".$yesterday_time."' ");

		// get building ids
	//	$user_id = $request->get('user_id', 0);
	//	$building_ids = CommonUser::getBuildingIds($user_id);
/*
		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$query->whereIn('cf.bldg_id', $building_ids);
		}	
	*/	
		$building_ids = [];
		if( !empty($building_id) )		
		{
			$building_ids = explode(',', $building_id);
			$query->whereIn('cf.bldg_id', $building_ids);
		}

		$item_list = DB::table('services_rm_srv_itm')
				->get();

		$item_list_key = array();
		for($i = 0; $i < count($item_list); $i++ )
			$item_list_key[$item_list[$i]->id] = $item_list[$i];

		$ret = array();

		// By revenue
		$today_query = clone $query;
		$minibar_list = array();
		$data_list = $today_query->get();
		$total_posted = 0;
		$total_sale = 0;
		$total_checkout = 0;
		$total_posting = 0;

		foreach ($data_list as $row) {
			$ids = json_decode($row->item_ids);
			$quantitys = json_decode($row->quantity);
			$room_id = $row->room_id;
			$created_at =  $row->created_at;

			for($i = 0; $i < count($ids); $i++)
			{
				$group_key = $item_list_key[$ids[$i]]->item_name;
				$unit_charge = $item_list_key[$ids[$i]]->charge;
				$quantity = $quantitys[$i];
				$charge = round($quantity* $unit_charge,2);

				if(empty($minibar_list[$group_key]['posted'])) $minibar_list[$group_key]['posted'] = 0 ;
				if(empty($minibar_list[$group_key]['sale'])) $minibar_list[$group_key]['sale'] = 0;
				if(empty($minibar_list[$group_key]['checkout'])) $minibar_list[$group_key]['checkout'] = 0;
				if(empty($minibar_list[$group_key]['posting'])) $minibar_list[$group_key]['posting'] = 0;

				//if (isset($minibar_list[$group_key])) {
					$minibar_list[$group_key]['posted'] = $minibar_list[$group_key]['posted']+$quantity;
					$minibar_list[$group_key]['sale'] = $minibar_list[$group_key]['sale'] + $charge;
					$total_posted = $total_posted + $quantity;
					$total_sale = $total_sale + $charge;

					/*  if guest_id == 0 , this user will check out.
					*/

					if($row->guest_id == 0) {
						$minibar_list[$group_key]['checkout'] = $minibar_list[$group_key]['checkout']+$quantity;
						 $minibar_list[$group_key]['posting'] = $minibar_list[$group_key]['posting'] + $charge;
						$total_checkout = $total_checkout + $quantity;
						$total_posting = $total_posting + $charge;
					}

				//}
			}
		}
		$ret['by_revenue_count'] = $minibar_list;
		$ret['total_posted'] = $total_posted;
		$ret['total_sale'] = $total_sale;
		$ret['total_checkout'] = $total_checkout;
		$ret['total_posting'] = $total_posting;

		// By Posted by User
		$user_minibar_list = array();
		$today_query = clone $query;
		$by_user_count = $today_query
				->orderBy('ml.user_id')
				->select(DB::raw('*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();

		foreach ($by_user_count as $row) {
			$ids = json_decode($row->item_ids);
			$quantitys = json_decode($row->quantity);
			$room_id = $row->room_id;
			$user_name = $row->wholename;
			$created_at =  $row->created_at;

			for($i = 0; $i < count($ids); $i++)
			{
				$group_key = $user_name;
				$unit_charge = $item_list_key[$ids[$i]]->charge;
				$quantity = $quantitys[$i];
				$charge = round($quantity* $unit_charge,2);

				if(empty($user_minibar_list[$group_key]['posted'])) $user_minibar_list[$group_key]['posted'] = 0 ;
				if(empty($user_minibar_list[$group_key]['sale'])) $user_minibar_list[$group_key]['sale'] = 0;
				if(empty($user_minibar_list[$group_key]['checkout'])) $user_minibar_list[$group_key]['checkout'] = 0;
				if(empty($user_minibar_list[$group_key]['posting'])) $user_minibar_list[$group_key]['posting'] = 0;

				//if (isset($minibar_list[$group_key])) {
					$user_minibar_list[$group_key]['posted'] = $user_minibar_list[$group_key]['posted']+$quantity;
					$user_minibar_list[$group_key]['sale'] = $user_minibar_list[$group_key]['sale'] + $charge;

					/* created_at of common_guest is  small than created_at of services_minibar_log checkout
					 and  latest value countnumber  that checkout_flag of common_guest is checkout
					*/
					$guest_checkout = DB::table('common_guest')
						->where('checkout_flag','checkout')
						->whereRaw('guest_id > 0')
						->where('room_id', $room_id)
						->whereRaw(" created_at < '" . $created_at . "'")
						->first();

				//	if(!empty($guest_checkout)) {
					if($row->guest_id == 0){
						$user_minibar_list[$group_key]['checkout'] = $user_minibar_list[$group_key]['checkout']+$quantity;
						$user_minibar_list[$group_key]['posting'] = $user_minibar_list[$group_key]['posting'] + $charge;
					}
				//}
			}
		}

		$ret['by_user_count'] = $user_minibar_list;

		return $ret;
	}

	public function getStaticsticsByDate(Request $request, $end_date, $during, $user_id, $building_ids)
	{

		$building_list = DB::table('services_minibar_profile')
				->where('user_id', $user_id)
				->select(DB::raw('buildings'))
				->first();

		if(!empty($building_list)) {
					$building_id = $building_list->buildings;
		}else {
					$building_id = $building_ids;
		}

		$query = DB::table('services_minibar_log as ml')
				->join('common_room as cr', 'ml.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('common_users as cu', 'ml.user_id', '=', 'cu.id');

		// get building ids
		/*
		$user_id = $request->get('user_id', 0);
		$building_ids = CommonUser::getBuildingIds($user_id);

		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$query->whereIn('cf.bldg_id', $building_ids);
		}		
		*/
		
		$building_ids = [];
		if( !empty($building_id) )		
		{
			$building_ids = explode(',', $building_id);
			$query->whereIn('cf.bldg_id', $building_ids);
		}
		
		$ret = array();

		// By task
		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P' . $during . 'D'));
		$start_date = $datetime->format('Y-m-d');

		$time_range = sprintf("'%s' < DATE(ml.created_at) AND DATE(ml.created_at) <= '%s'", $start_date, $end_date);

		$ret['time_range'] = $time_range;

		$item_list = DB::table('services_rm_srv_itm')
				->get();

		$item_list_key = array();
		for($i = 0; $i < count($item_list); $i++ )
			$item_list_key[$item_list[$i]->id] = $item_list[$i];

		// By revenue
		$today_query = clone $query;
		$minibar_list = array();
		$data_list = $today_query
				->whereRaw($time_range)
			        ->select(DB::raw('ml.* , CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();
		$total_posted = 0;
		$total_sale = 0;
		$total_checkout = 0;
		$total_posting = 0;

		foreach ($data_list as $row) {
			$ids = json_decode($row->item_ids);
			$quantitys = json_decode($row->quantity);
			$room_id = $row->room_id;
			$created_at =  $row->created_at;

			for($i = 0; $i < count($ids); $i++)
			{
				$group_key = $item_list_key[$ids[$i]]->item_name;
				$unit_charge = $item_list_key[$ids[$i]]->charge;
				$quantity = $quantitys[$i];
				$charge = round($quantity* $unit_charge,2);

				if(empty($minibar_list[$group_key]['posted'])) $minibar_list[$group_key]['posted'] = 0 ;
				if(empty($minibar_list[$group_key]['sale'])) $minibar_list[$group_key]['sale'] = 0;
				if(empty($minibar_list[$group_key]['checkout'])) $minibar_list[$group_key]['checkout'] = 0;
				if(empty($minibar_list[$group_key]['posting'])) $minibar_list[$group_key]['posting'] = 0;


				//if (isset($minibar_list[$group_key])) {
					$minibar_list[$group_key]['posted'] = $minibar_list[$group_key]['posted']+$quantity;
					$minibar_list[$group_key]['sale'] = $minibar_list[$group_key]['sale'] + $charge;
					$total_posted = $total_posted + $quantity;
					$total_sale = $total_sale + $charge;

					/* if guest_id == 0, this user is checkout
					*/

					if($row->guest_id === 0 ) {
						$minibar_list[$group_key]['checkout'] = $minibar_list[$group_key]['checkout']+$quantity;
						$minibar_list[$group_key]['posting'] = $minibar_list[$group_key]['posting'] + $charge;
						$total_checkout = $total_checkout + $quantity;
						$total_posting = $total_posting + $charge;
					}

				//}
			}
		}
		$ret['by_revenue_count'] = $minibar_list;
		$ret['total_posted'] = $total_posted;
		$ret['total_sale'] = $total_sale;
		$ret['total_checkout'] = $total_checkout;
		$ret['total_posting'] = $total_posting;

		// By Department
		$user_minibar_list = array();
		$today_query = clone $query;
		$by_user_count = $today_query
				->whereRaw($time_range)
				->orderBy('ml.user_id')
				->select(DB::raw('ml.* , CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();
		foreach ($by_user_count as $row) {
			$ids = json_decode($row->item_ids);
			$quantitys = json_decode($row->quantity);
			$room_id = $row->room_id;
			$user_name = $row->wholename;
			$created_at =  $row->created_at;

			for($i = 0; $i < count($ids); $i++)
			{
				$group_key = $user_name;
				$unit_charge = $item_list_key[$ids[$i]]->charge;
				$quantity = $quantitys[$i];
				$charge = round($quantity* $unit_charge,2);

				if(empty($user_minibar_list[$group_key]['posted'])) $user_minibar_list[$group_key]['posted'] = 0 ;
				if(empty($user_minibar_list[$group_key]['sale'])) $user_minibar_list[$group_key]['sale'] = 0;
				if(empty($user_minibar_list[$group_key]['checkout'])) $user_minibar_list[$group_key]['checkout'] = 0;
				if(empty($user_minibar_list[$group_key]['posting'])) $user_minibar_list[$group_key]['posting'] = 0;

				//if (isset($minibar_list[$group_key])) {
					$user_minibar_list[$group_key]['posted'] = $user_minibar_list[$group_key]['posted']+$quantity;
					$user_minibar_list[$group_key]['sale'] = $user_minibar_list[$group_key]['sale'] + $charge;

					/* created_at of common_guest is  small than created_at of services_minibar_log checkout
					 and  latest value countnumber  that checkout_flag of common_guest is checkout
					*/
					$guest_checkout = DB::table('common_guest')
						->where('checkout_flag','checkout')
						->whereRaw('guest_id > 0')
						->where('room_id', $room_id)
						->whereRaw(" created_at < '" . $created_at . "'")
						->first();

				//	if(!empty($guest_checkout)) {
					if($row->guest_id == 0){
						$user_minibar_list[$group_key]['checkout'] = $user_minibar_list[$group_key]['checkout']+$quantity;
						$user_minibar_list[$group_key]['posting'] = $user_minibar_list[$group_key]['posting'] + $charge;
					}
				//}
			}
		}
		$ret['by_user_count'] = $user_minibar_list;

		return $ret;
	}

	public function getServiceGroupList(Request $request)
	{
		$group_id = $request->get('group_id', '1');

		// get selected ids
		$idlist = DB::table('services_srv_grp_mbr')
			->where('grp_id', $group_id)
			->select(DB::raw('item_id'))
			->get();

		$selected_ids = array();
		for($i = 0; $i < count($idlist); $i++){
			$selected_ids[$i] = $idlist[$i]->item_id;
		}

		// get ids with same room type
		$room_type_info = DB::table('services_rm_srv_grp')
			->where('id', $group_id)
			->select('room_type_id')
			->first();

		if( !empty($room_type_info) )
		{
			$same_ids_with_sameroomtype = DB::table('services_srv_grp_mbr as sgm')
					->join('services_rm_srv_grp as rsg', 'sgm.grp_id', '=', 'rsg.id')
					->where('rsg.room_type_id', $room_type_info->room_type_id)
					->select(DB::raw('sgm.item_id'))
					->get();

			$same_ids = array();
			for($i = 0; $i < count($same_ids_with_sameroomtype); $i++){
				$same_ids[$i] = $same_ids_with_sameroomtype[$i]->item_id;
			}
		}

		$unselected_member = DB::table('services_rm_srv_itm')
				->whereNotIn('id', $same_ids)
				->select(DB::raw('*'))
				->get();
		$selected_member = DB::table('services_rm_srv_itm')
				->whereIn('id', $selected_ids)
				->select(DB::raw('*'))
				->get();

		$model = array();
		$model[] = $unselected_member;
		$model[] = $selected_member;

		return Response::json($model);
	}

	public function postGroup(Request $request)
	{
		$group_id = $request->get('group_id', '1');

		DB::table('services_srv_grp_mbr')->where('grp_id', $group_id)->delete();

		$select_id = $request->get('select_id');

		for( $i = 0; $i < count($select_id); $i++ )
		{
			$item_id = $select_id[$i];

			DB::table('services_srv_grp_mbr')->insert([
					['grp_id' => $group_id, 'item_id' => $item_id, 'max_qty' => 2],
			]);
		}

		echo "Minibar Item Group Member has beed updated successfully";
	}
	public function getMinibarItemLists(Request $request) {
		
		$property_id = $request->get('property_id', 1);
		$sync = $request->get('sync_minibar', 0);
		$user_id = $request->get('user_id', 0);

		$ret = array();
		
		$datalist = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->join('services_rm_srv_grp as rsg', 'rsg.room_type_id', '=', 'cr.type_id')
			->where('cb.property_id', $property_id)	
			->select(DB::raw('cr.id as room_id, cr.room,  rsg.id as group_id'))
			->orderBy('cr.id','asc')
			->get();

		$sgm= DB::table('services_srv_grp_mbr as sgm')->get();
		
		foreach ($datalist as $key => $value) {
			foreach ($sgm as $key1 => $value1) {
				if($value1->grp_id==$value->group_id)
				{
					$value->item_ids[]=$value1->item_id;
				}					
			}
		}

		$items = DB::table('services_rm_srv_itm as rsi')->where('rsi.active_status', 1)->get();
		$content = array();
		$currencyitem = DB::table('property_setting')->where('settings_key', 'currency')->first();
		if(!empty($currencyitem)){
			$currency = $currencyitem->value;
		}else{
			$currency = 'AED';
		}
		$content['currency']=$currency;
		$content['minibaritemlist'] = $items;
		$content['itemlistrooms'] = $datalist;
		

		$ret['code'] = 200;
		$ret['content'] = $content;
		$ret['sync_minibar'] = 0;
		$ret['message'] = '';
		


		return Response::json($ret);
	}
	
	public function getMinibarTitle(Request $request) {
		$user_id = $request->get('user_id', 1);
		$property_id = $request->get('property_id', 1);
		$room_number = $request->get('room', 0);

		// $room_info = DB::table('common_room as cr')
		// 		->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
		// 		->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
		// 		->where('cr.room', $room_number)			
		// 		->where('cb.property_id', $property_id)	
		// 		->select(DB::raw('cr.*, cr.id as room_id'))
		// 		->first();

		$ret = array();

		// if( empty($room_info) )
		// {
		// 	$ret['code'] = 401;
		// 	$ret['message'] = 'You have input invalid room number';
		// }
		// else
		// {
			$minibartitle = 'Vacant';
			$guest_info = DB::table('common_guest as cg')
			->join('common_room as cr', 'cr.id', '=', 'cg.room_id')
			->where('cr.room', $room_number)
			->orderBy('cg.id', 'desc')
			->orderBy('cg.arrival', 'desc')
			->where('cg.checkout_flag', 'checkin')
			->select(['cg.*'])
			->first();

			
			// $guest_info = DB::table('common_guest')
			// 		->where('room_id', $room_info->id)
			// 		->orderBy('departure', 'desc')
			// 		->orderBy('arrival', 'desc')
			// 		->first();
			
			if(!empty($guest_info) && $guest_info->checkout_flag == 'checkin'){
				$minibartitle = $guest_info->guest_name;
				if($guest_info->no_post=='Y')
				$minibartitle=$minibartitle.' (No Post)';
			}else if(!empty($guest_info) && $guest_info->checkout_flag == 'checkout'){
				$minibartitle = 'Vacant';
			}
			$content = array();

			//$content['room_info'] = $room_info;
                        $content['minibartitle'] = $minibartitle;
			
			$ret['code'] = 200;
			$ret['content'] = $content;
			$ret['message'] = '';
	//	}


		return Response::json($ret);
	}

	public function getMinibarItemList(Request $request) {
		$user_id = $request->get('user_id', 1);
		$property_id = $request->get('property_id', 1);
		$room_number = $request->get('room', 0);

		$room_info = DB::table('common_room as cr')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->where('cr.room', $room_number)			
				->where('cb.property_id', $property_id)	
				->select(DB::raw('cr.*, cr.id as room_id'))
				->first();

		$ret = array();

		if( empty($room_info) )
		{
			$ret['code'] = 401;
			$ret['message'] = 'You have input invalid room number';
		}
		else
		{
			$datalist = DB::table('services_srv_grp_mbr as sgm')
					->join('services_rm_srv_grp as rsg', 'sgm.grp_id', '=', 'rsg.id')
					->join('services_rm_srv_itm as rsi', 'sgm.item_id', '=', 'rsi.id')
					->where('rsg.room_type_id', $room_info->type_id)
					->where('rsi.active_status', 1)
					->select(DB::raw('sgm.*, rsi.*'))
					->get();
			$guest_info = DB::table('common_guest')
					->where('room_id', $room_info->id)
					->orderBy('departure', 'desc')
					->orderBy('arrival', 'desc')
					->first();
			$minibartitle = 'Vacant';
			if(!empty($guest_info) && $guest_info->checkout_flag == 'checkin'){
				$minibartitle = $guest_info->guest_name;
			}else if(!empty($guest_info) && $guest_info->checkout_flag == 'checkout'){
				$minibartitle = 'Vacant';
			}
			$content = array();

			$content['room_info'] = $room_info;
			$content['itemlist'] = $datalist;
                        $content['minibartitle'] = $minibartitle;
			
			$ret['code'] = 200;
			$ret['content'] = $content;
			$ret['message'] = '';
		}


		return Response::json($ret);
	}

	public function postMinibarItemList(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		
		$user_id = $request->get('user_id', 1);
        $posted_by = $request->get('posted_by', 0);
		$property_id = $request->get('property_id', 0);
		$room_id = $request->get('room_id', 0);

		$item_ids = $request->get('item_ids', '[]');
		$quantity = $request->get('quantity', '[]');

		$total_amount = $request->get('total_amount', 0);

        $minibarpost_list_ids =  $request->get('minibarpost_list_ids', []);

		$ret = array();
		
		
		
		$guest_info = DB::table('common_guest')
			->where('room_id', $room_id)
			->where('checkout_flag', 'checkin')
			->orderBy('departure', 'desc')
			->orderBy('arrival', 'desc')
			->first();
			
		if( !empty($guest_info) ) {
			$guest_id = $guest_info->guest_id;
			$guest_nopost = '';
			$guest_nopost = $guest_info->no_post;
		}
		else
		{
			$minibar_setting = PropertySetting::getMinibarSetting($property_id);
			$flag = $minibar_setting['allow_minibar_post'];

			if( $flag != 1 )		// check allow minibar post
			{
				$ret['code'] = 201;
				$ret['message'] = 'Posting Failed! Guest Not Checked In!';

				return Response::json($ret);
			}

			$guest_id = 0;
			$guest_id = 0;
			$guest_nopost = '';
		}
		
		$end_time = new DateTime($cur_time);
		$end_time->sub(new DateInterval('PT1M'));
		$end_time = $end_time->format('Y-m-d H:i:s');
		
		$data = DB::table('services_minibar_log as ml')
					->where('ml.user_id', $user_id)
					->where('ml.guest_id', $guest_id)
					->where('ml.room_id', $room_id)
					->where('ml.total_amount', $total_amount)
					->where('ml.quantity',$quantity)
					->where('ml.item_ids',$item_ids)
					->whereBetween('ml.created_at', array($end_time,$cur_time))
					->get();
		
		if(!empty($data->toArray()))
		{
			$ret['code'] = 201;
		    $ret['message'] = 'Duplicate posting! Please wait 1 minute before posting same items again.';

			return Response::json($ret);
		}
		
		if($guest_nopost == 'Y')
				{

					$minibar_no_setting = PropertySetting::getMinibarSetting($property_id);
					$flag = $minibar_no_setting['disable_minibar_nopost'];
		
					if( $flag == 'Y' )		
					{
						$ret['code'] = 201;
						$ret['message'] = 'Posting Failed! Room on No Post!';
						return Response::json($ret);
					}
				}

		if(count($minibarpost_list_ids) > 0)
        {
            DB::table('services_minibar_log')
                ->where('guest_id', $guest_id)
                ->where('room_id', $room_id)
                ->whereIn('id', $minibarpost_list_ids)
                ->delete();
        }

		$item_id_array = json_decode($item_ids);
		$quantity_array = json_decode($quantity);

		$ret = $this->postMinibarItemListProc($user_id, $property_id, $room_id, $item_id_array, $quantity_array , $posted_by);

		if( $ret['code'] == 200 )
		{
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->changeMinibarTaskToComplete($room_id, $user_id, "Mobile");
			$ret['content'] = $info;
		}

		$ret['message'] = 'Minibar Item is posted successfully';	
		$ret['property_id'] = $property_id;	
		
		return Response::json($ret);
	}

	public function isMinibarPostAllowed(Request $request) {
		$extension = $request->get('extension', '');

		$extension = DB::table('call_guest_extn as ge')
				->join('common_room as cr', 'ge.room_id', '=', 'cr.id')
				->join('common_building as cb', 'ge.bldg_id', '=', 'cb.id')
				->where('ge.extension', $extension)
				->select(DB::raw('ge.*, cr.room, cb.property_id'))
				->first();

		$ret = array();
		$ret['code'] = '0';

		if( empty($extension) )
		{
			$ret['code'] = '0';
			$ret['message'] = 'Invalid extension number';

			return Response::json($ret);
		}

		$guest_info = DB::table('common_guest')
				->where('room_id', $extension->room_id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

		if( !empty($guest_info) ) {
			$ret['code'] = '1';

			return Response::json($ret);
		}

		$minibar_setting = PropertySetting::getMinibarSetting($extension->property_id);
		$ret['code'] = $minibar_setting['allow_minibar_post'];

		return Response::json($ret);
	}

	private function getItemIDQuantity($item_array, &$item_id_array, &$quantity_array)
	{
		foreach($item_array as $row)
		{
			$service_item = DB::table('services_rm_srv_itm as item')
					->leftJoin('services_rm_srv_grp as grp', 'item.room_service_group', '=', 'grp.id')
					->where('ivr_code', $row->ivr_code)
					->select(DB::raw('item.*, grp.sales_outlet'))
					->first();	

			if( empty($service_item) )
				continue;		

			$item_id_array[] = $service_item->id;
			$quantity_array[] = $row->qty;
		}
	}

	public function postMinibarItemListFromIVR(Request $request) {
		$data = $request->get('data', '');
		$param = json_decode($data);

		$user_id = $param->user_id;
		$extension = $param->extension;
		$item_array = $param->item;

		$extension = DB::table('call_guest_extn as ge')
				->join('common_room as cr', 'ge.room_id', '=', 'cr.id')
				->join('common_building as cb', 'ge.bldg_id', '=', 'cb.id')
				->where('ge.extension', $extension)
				->select(DB::raw('ge.*, cr.room, cb.property_id'))
				->first();

		if( empty($extension) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Extension';

			return Response::json($ret);
		}

		$item_id_array = [];
		$quantity_array = [];
		$this->getItemIDQuantity($item_array, $item_id_array, $quantity_array);
		
		$ret = $this->postMinibarItemListProc($user_id, $extension->property_id, $extension->room_id, $item_id_array, $quantity_array);

		if( $ret['code'] == 200 )
		{
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->changeMinibarTaskToComplete($extension->room_id, $user_id, "IVR");
			$ret['info'] = $info;
		}

		return Response::json($ret);		
	}

	public function postMinibarItemListFromIVRWithRoom(Request $request) {
		$data = $request->get('data', '');
		$param = json_decode($data);

		$user_id = $param->user_id;
		$property_id = $param->property_id;
		$room = $param->room;
		$item_array = $param->item;

		$extension = DB::table('call_guest_extn as ge')
				->join('common_room as cr', 'ge.room_id', '=', 'cr.id')
				->join('common_building as cb', 'ge.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id)
				->where('cr.room', $room)
				->where('primary_extn', 'Y')
				->select(DB::raw('ge.*, cr.room, cb.property_id'))
				->first();

		if(empty($extension) )
		{
			$extension = DB::table('call_guest_extn as ge')
				->join('common_room as cr', 'ge.room_id', '=', 'cr.id')
				->join('common_building as cb', 'ge.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id)
				->where('cr.room', $room)
				->select(DB::raw('ge.*, cr.room, cb.property_id'))
				->first();			
		}		

		if( empty($extension) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Extension';

			return Response::json($ret);
		}

		$item_id_array = [];
		$quantity_array = [];
		$this->getItemIDQuantity($item_array, $item_id_array, $quantity_array);
		
		$ret = $this->postMinibarItemListProc($user_id, $extension->property_id, $extension->room_id, $item_id_array, $quantity_array);

		if( $ret['code'] == 200 )
		{
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->changeMinibarTaskToComplete($extension->room_id, $user_id, "IVR");
			$ret['info'] = $info;
		}

		return Response::json($ret);		
	}

    public function postMinibarItemStatusChange(Request $request)
    {

        $posted_by = $request->get('posted_by', 0);
        $status_id = $request->get('posting_status_id', 0);
        $item_id = $request->get('item_id', 0);

        $minibar_logs = MinibarLogs::find($item_id);

        $minibar_logs->posting_status = $status_id; // Pending 1  , Posted 4
        $minibar_logs->posted_by = $posted_by;
        $minibar_logs->save();


        DB::table('services_room_status')
            ->where('id', $minibar_logs->room_id)
            ->update([
                'minibar_post_status' => $minibar_logs->posting_status,
                'minibar_posted_by' => $posted_by,
                'minibar_post_id' =>  $minibar_logs->id

            ]);


        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);

	}
	public function postMinibarItemStatusChangeMobile(Request $request)
    {

        $posted_by = $request->get('posted_by', 0);
        $status = $request->get('posting_status', '');
        $item_id = $request->get('item_id', 0);

        $minibar_logs = MinibarLogs::find($item_id);
		$post_status = DB::table('services_minibar_posting_status')->where('posting_status',$status)->first();
		if(!empty($minibar_logs))
		{
        $minibar_logs->posting_status = $post_status->id; // Pending 1  , Posted 4
        $minibar_logs->posted_by = $posted_by;
        $minibar_logs->save();


        DB::table('services_room_status')
            ->where('id', $minibar_logs->room_id)
            ->update([
                'minibar_post_status' => $minibar_logs->posting_status,
                'minibar_posted_by' => $posted_by,
                'minibar_post_id' =>  $minibar_logs->id

            ]);


        $ret = array();
        $ret['code'] = 200;
		$ret['message'] = 'Status Posted Successfully!';
		return Response::json($ret);
		}
		else
		{
		$ret = array();
        $ret['code'] = 401;
		$ret['message'] = 'Minibar posting status not available!';
		return Response::json($ret);
		}

    }

	private function postMinibarItemListProc($user_id, $property_id, $room_id, $item_id_array, $quantity_array , $posted_by = 0)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");


		$ret = array();
		$ret['code'] = 200;

		$guest_info = DB::table('common_guest')
				->where('room_id', $room_id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

		$room_info = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->where('cr.id', $room_id)
			->select(DB::raw('cr.*, cf.bldg_id'))
			->first();		

		if( empty($room_info))
		{
			$ret['code'] = 201;
			$ret['message'] = 'Room is not valid';

			return $ret;
		}	

		$room = $room_info->room;
		$bldg_id = $room_info->bldg_id;

		$minibar_setting = PropertySetting::getMinibarSetting($property_id);
        $guest_id = 0;
        $guest_nopost = '';
		if( !empty($guest_info) && $guest_info->checkout_flag == 'checkin' ) {
			$guest_id = $guest_info->guest_id;
			$guest_nopost = $guest_info->no_post;
		}
		else
		{
			$flag = $minibar_setting['allow_minibar_post'];

			if( $flag != 1 )		// check allow minibar post
			{
				$ret['code'] = 201;
				$ret['message'] = 'Guest does not checkin and setting is not allowed';

				return $ret;
			}

			$guest_id = 0;
		}

		$total_amount = 0;
		$sub_total_amount = array();
		$sales_outlet_array = [];
		$pms_code_array = [];
		foreach( $item_id_array as $key => $row )
		{
			$service_item = DB::table('services_rm_srv_itm as item')
					->leftJoin('services_rm_srv_grp as grp', 'item.room_service_group', '=', 'grp.id')
					->where('item.id', $row)
					->select(DB::raw('item.*, grp.sales_outlet'))
					->first();

			if( empty($service_item) )
			{
				$sales_outlet_array[] = 0; 
				$pms_code_array[] = 0;
				continue;
			}

			$total_amount += $service_item->charge * $quantity_array[$key];

			$sales_outlet = $service_item->sales_outlet;

			$sales_outlet_array[] = $sales_outlet;
			$pms_code_array[] = $service_item->pms_code;

			if( !isset($sub_total_amount[$sales_outlet]))
				$sub_total_amount[$sales_outlet] = 0;
			$sub_total_amount[$sales_outlet] += $service_item->charge * $quantity_array[$key];
		}



		$minibar_logs = new MinibarLogs();

		$minibar_logs->guest_id = $guest_id;
		$minibar_logs->room_id = $room_id;
		$minibar_logs->item_ids = json_encode($item_id_array);
		$minibar_logs->quantity = json_encode($quantity_array);
		$minibar_logs->total_amount = $total_amount;
		$minibar_logs->user_id = $user_id;
		if($guest_nopost == 'Y')
            $minibar_logs->posting_status = 2; // Pending 1  , Posted 4 , no post 2
        else
            $minibar_logs->posting_status = 4; // Pending 1  , Posted 4 , no post 2

        $minibar_logs->created_at = $cur_time;
        $minibar_logs->posted_by = $posted_by;
		$minibar_logs->save();

        DB::table('services_room_status')
            ->where('id', $room_id)
            ->update([
                'minibar_post_status' => $minibar_logs->posting_status,
                'minibar_posted_by' => $posted_by,
                'minibar_post_id' =>  $minibar_logs->id

            ]);


		$opera = '';


        try {

            $opera = '';
			$data = array();
			$data['property_id'] = $property_id;

			$src_config = array();
			$src_config['src_property_id'] = $property_id;
			$src_config['src_build_id'] = $bldg_id;
			$src_config['accept_build_id'] = array();

			$data['src_config'] = $src_config;

			$date_msg = [];

			$post_no = Redis::get('minibar_post_no');

			if( $minibar_setting['minibar_posting_type'] == 'total' )
			{
				foreach($sub_total_amount as $key => $row )
				{
					$post_no++;
					$date_msg[] = sprintf("PS|RN%d|PTC|P#%d|DA%06s|TI%06s|TA%d|SO%d",
						$room, $post_no, date('ymd'), date('His'), $row * 100, $key);
				}
			}

			if( $minibar_setting['minibar_posting_type'] == 'Item' )
			{
				foreach ($item_id_array as $key => $row) {
					$post_no++;
					$date_msg[] = sprintf("PS|RN%d|PTM|P#%d|M#%d|MA%d|DA%06s|TI%06s",
						$room_info->room, $post_no, $pms_code_array[$key], $quantity_array[$key], date('ymd'), date('His'));
				}
			}

			Redis::set('minibar_post_no', $post_no);

			foreach ($date_msg as $row) {

				$data['msg'] = $row;
				Functions::sendMessageToInterface('interface_hotlync', $data);
			}
            

        } catch (\Exception $e) {
            //die("Could not open connection to database server.  Please check your configuration.");
        }

		$ret['content'] = $opera;
		$ret['message'] = 'Minibar Item is posted successfully';
		$ret['opera'] = $opera;

		return $ret;
	}

	// http://192.168.1.253/minibar/migratelog
	public function migrateMinibarLogs(Request $request) {
		set_time_limit(0);

		$list = DB::table('services_minibar_log')
		    ->where('guest_id', '>', 0)
		    ->get();

		$log_ids = [];
		foreach($list as $row) {
			$guest_info = DB::table('common_guest')
				->where('room_id', $row->room_id)
				->where('created_at', '<=', $row->created_at)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

			if( empty($guest_info) || $guest_info->checkout_flag == 'checkout' )	// checkout room
				$log_ids[] = $row->id;				

			// $guest_info = DB::table('common_guest_log')
			// 	->where('room_id', $row->room_id)
			// 	->whereIn('action', array('checkin', 'checkout'))
			// 	->where('created_at', '<=', $row->created_at)
			// 	->orderBy('created_at', 'desc')
			// 	->first();

			// if( empty($guest_info) || $guest_info->action == 'checkout' )	// checkout room
			// 	$log_ids[] = $row->id;	
		}

		DB::table('services_minibar_log')
			->whereIn('id', $log_ids)
			->update(array('guest_id' => 0));

		echo json_encode($log_ids);	
	}

	// public function 

	//store callcenter profile for dashboard of every user
	public function storeMinibarProfile(Request $request) {

		$user_id = $request->get('user_id', 0);
	
		$building_ids = $request->get('building_ids','[]');
		

		
			$profile = DB::table('services_minibar_profile')
				->where('user_id', $user_id)
				->get();
			if (!empty($profile)) {
				DB::table('services_minibar_profile')
					->where('user_id', $user_id)
					->update(['user_id' => $user_id,
						'buildings' => $building_ids]);

			} else {
				DB::table('services_minibar_profile')
					->insert(['user_id' => $user_id,
						'buildings' => $building_ids]);

			}
		
		//after save reload
		$profile = DB::table('services_minibar_profile')
			->where('user_id', $user_id)
			->get();
		return Response::json($profile);
		
	}

	public function getBuildingListUser(Request $request)
	{
		
		$user_id = $request->get('user_id', 0);
		$profile = DB::table('services_minibar_profile')
			->where('user_id', $user_id)
			->select(DB::raw('buildings'))
			->first();
		$buildings = array();

		if (!empty($profile)){

		$buildings = explode(",", $profile->buildings);

		}
		
		$building_name = '';

		for($i = 0; $i < count($buildings); $i++){

			$list = DB::table('common_building as cb')
				->where('cb.id', $buildings[$i])
				->select(DB::raw('cb.name'))
				->first();
			if (!empty($list))
			{
				$building_name .= $list->name . ',';   
			}

		}
            

		
		$ret = $building_name;
		
		return Response::json($ret);
	}

}
