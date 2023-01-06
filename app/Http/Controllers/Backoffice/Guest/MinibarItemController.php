<?php

namespace App\Http\Controllers\Backoffice\Guest;

//use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Common\PropertySetting;

use App\Models\Service\RoomServiceItem;
use App\Models\Common\Property;
use App\Models\Common\CommonUser;

use Excel;
use Response;
use DB;
use Datatables;
use DateTime;

class MinibarItemController extends UploadController
{
	public function index(Request $request)
	{
		$datalist = DB::table('services_rm_srv_itm as rsi')
				->select(DB::raw('rsi.*'));

		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('thumbnail', function ($data) {
					return '<img src="' .  $data->img_path .'" alt="Thumbnail" height="42" width="42">';
				})
				->addColumn('itemstatus', function ($data) {
					if($data->active_status == 1) return 'Active';
					else return 'Inactive';
				})
				->addColumn('edit', function ($data) {
					/*return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';*/
					return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-click="onShowEditRow('.$data->id.')">
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

	public function create(Request $request)
	{
		$input = $request->all();

		$model = RoomServiceItem::create($input);

		return Response::json($model);
	}


	public function store(Request $request)
	{
		$input = $request->except(['id','user_id']);
		//$input  = $request->except('user_id');

		$model = RoomServiceItem::create($input);
		$itm_id = $model->id;
		$user_id = $request->get('user_id',0);
		$this->insertLog($itm_id, $user_id, 'Create');

		return Response::json($model);
	}

	public function show($id)
	{
		$model = RoomServiceItem::find($id);

		return Response::json($model);
	}

	public function edit(Request $request, $id)
	{

	}


	public function update(Request $request, $id)
	{
		$model = RoomServiceItem::find($id);
		$original = clone $model;

		$input = $request->except(['user_id']);
		$model->update($input);

		$itm_id = $id;
		$user_id = $request->get('user_id', 0 );

		$changed_val = $this->getTransaction($original, $input);
		if($changed_val != '') {
			$this->insertLog($itm_id, $user_id, $changed_val);
		}

		return Response::json($model);
	}

	public function getTransaction($original, $new) {
		$changed_val = '';
		if($original->item_name != $new['item_name']) {
			$changed_val.=" Edited Item:".$original->item_name." to ".$new['item_name'];
		}
		if($original->desc != $new['desc']) {
			$changed_val.=" Edited description";
		}
		if($original->charge != $new['charge']) {
			$changed_val.=" Edited Charge:".$original->charge." to ".$new['charge'];
		}
		if($original->pms_code != $new['pms_code']) {
			$changed_val.=" Edited PMS Code:".$original->pms_code." to ".$new['pms_code'];
		}
		if($original->ivr_code != $new['ivr_code']) {
			$changed_val.=" Edited IVR Code:".$original->ivr_code." to ".$new['ivr_code'];
		}
		if($original->max_qty != $new['max_qty']) {
			$changed_val.=" Edited Max Qty:".$original->max_qty." to ".$new['max_qty'];
		}
		if($original->active_status != $new['active_status']) {
			$origin_val = '';
			if($original->active_status == 1) $origin_val = 'Active';
			else $origin_val = 'Inactive';

			$new_val = '';
			if($new['active_status'] == 1) $new_val = 'Active';
			else $new_val = 'Inactive';

			$changed_val.="Edited Status:".$origin_val." to ".$new_val;
		}
		return $changed_val;
	}

	public function destroy(Request $request, $id)
	{
		$model = RoomServiceItem::find($id);
		$model->delete();
		return Response::json($model);
	}

	public function parseExcelFile($path)
	{
		Excel::selectSheets('room_service_item')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
					// continue;
					RoomServiceItem::create($data);
				}
			}
		});
	}

	public function getMinibarLogs(Request $request) {
		$start_date = $request->get('start_date', '2016-01-01');
		$end_date = $request->get('end_date','2016-01-01');
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$searchtext = $request->get('searchtext', '');

		if($pageSize < 0 )
			$pageSize = 20;

		$mini_range = sprintf("DATE(ml.created_at) >= '%s' AND DATE(ml.created_at) <= '%s'", $start_date, $end_date);

		$ret = array();

		$query = DB::table('services_minibar_log as ml')
				->join('common_room as cr', 'ml.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_users as cu', 'ml.user_id', '=', 'cu.id')
//				->leftJoin('common_guest as cg','ml.guest_id','=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('ml.guest_id', '=', 'cg.guest_id');
					$join->on('cb.property_id', '=', 'cg.property_id');
				})
				->where('cb.property_id', $property_id)
				->whereRaw($mini_range);


		// get building ids
		$user_id = $request->get('user_id', 0);
		$building_ids = CommonUser::getBuildingIds($user_id);

		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$query->whereIn('cf.bldg_id', $building_ids);
		}

		if($searchtext !='') {
			$where = sprintf(" (cg.guest_name like '%%%s%%' or
								cu.first_name like '%%%s%%' or
								cu.last_name like '%%%s%%' or			
								cr.room like '%%%s%%')",
				$searchtext, $searchtext,$searchtext,$searchtext);
			$query->whereRaw($where);
		}

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('ml.*, cr.room, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cg.guest_name'))
				->skip($skip)->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$minibar_item_list = DB::table('services_rm_srv_itm')->get();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['minibar_item_list'] = $minibar_item_list;

		return Response::json($ret);
	}

	public function getRoomList(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date('Y-m-d');

		$room_list = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->leftJoin('services_minibar_log as ml', function($join) use ($cur_date) {
				$join->on('cr.id', '=', 'ml.room_id');
				$join->on(DB::raw("DATE(ml.created_at) = '$cur_date'"), DB::raw(''), DB::raw(''));

			})
            ->join('common_guest as cg', 'cg.room_id', '=', 'cr.id')
			->where('cb.property_id', $property_id)
        //    ->where('cg.checkout_flag', 'checkin')
			->groupBy('cr.id')
			->orderBy('cr.id')
			->select(DB::raw('cr.*, ml.id as flag, cg.no_post, cg.checkout_flag'))
			->get();

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $room_list;

		return Response::json($ret);
	}

	public function getMinibarLogsForMobile(Request $request) {
		$pageSize = $request->get('pagesize', 20);
		$last_id = $request->get('last_id', -1);
		$property_id = $request->get('property_id', '0');
		$searchtext = $request->get('searchtext', '');

		if($pageSize < 0 )
			$pageSize = 20;

		$query = DB::table('services_minibar_log as ml')
				->join('common_room as cr', 'ml.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->leftJoin('common_users as cu', 'ml.user_id', '=', 'cu.id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('ml.guest_id', '=', 'cg.guest_id');
					$join->on('cb.property_id', '=', 'cg.property_id');
				});

		if( $property_id > 0 )
			$query->where('cb.property_id', $property_id);

		// get building ids
		$user_id = $request->get('user_id', 0);
		$building_ids = CommonUser::getBuildingIds($user_id);

		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$query->whereIn('cf.bldg_id', $building_ids);
		}

		if($searchtext !='') {
			$where = sprintf(" (cg.guest_name like '%%%s%%' or
								cu.first_name like '%%%s%%' or
								cu.last_name like '%%%s%%' or			
								cr.room like '%%%s%%')",
				$searchtext, $searchtext,$searchtext,$searchtext);
			$query->whereRaw($where);
		}

		if( $last_id > 0 )
			$query->where('ml.id', '<', $last_id);

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy('ml.id', 'desc')
				->select(DB::raw('ml.*, cr.room, CONCAT_WS(" ", cu.first_name, cu.last_name) as posted_by, cg.guest_name'))
				->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret = array();

		$ret['code'] = 200;

		$data = array();

		$data['datalist'] = $data_list;
		$data['totalcount'] = $totalcount;

		$ret['content'] = $data;

		return Response::json($ret);
	}


	public function getMinibarGuest(Request $request) {
		date_default_timezone_set(config('app.timezone'));

		$start_date = $request->get('start_date', '2016-01-01');
		$end_date = $request->get('end_date','2016-01-01');
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$searchtext = $request->get('searchtext', '');

		if($pageSize < 0 )
			$pageSize = 20;

		$mini_range = sprintf("DATE(ml.created_at) >= '%s' AND DATE(ml.created_at) <= '%s'", $start_date, $end_date);


		$ret = array();

		$query = DB::table('services_minibar_log as ml')
			->join('common_room as cr', 'ml.room_id', '=', 'cr.id')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
//			->join('common_guest as cg','ml.guest_id','=', 'cg.guest_id')
			->join('common_guest as cg', function($join) {
				$join->on('ml.guest_id', '=', 'cg.guest_id');
				$join->on('cb.property_id', '=', 'cg.property_id');
			})
			->where('cb.property_id', $property_id)
		    ->whereRaw($mini_range);

		if($searchtext !='') {
			$where = sprintf(" (cg.guest_name like '%%%s%%' or								
								cr.room like '%%%s%%')",
				$searchtext, $searchtext);
			$query->whereRaw($where);
		}



		$data_query = clone $query;

		$data_list = $data_query
			->groupBy('ml.guest_id')
			->groupBy('ml.room_id')
			->orderBy($orderby, $sort)
			->select(DB::raw("ml.*, cr.room, cg.arrival as checkin, cg.departure as checkout, sum(ml.total_amount) as total_amount,cg.guest_name, false as view "))
			->skip($skip)->take($pageSize)
			->get();

		$count_query = clone $query;
		$totalcount = count($count_query->groupBy('ml.guest_id')->groupBy('ml.room_id')->get());


		$minibar_item_list = DB::table('services_rm_srv_itm')->get();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['minibar_item_list'] = $minibar_item_list;

		return Response::json($ret);
	}

	public function mininbarDetail($request){
		date_default_timezone_set(config('app.timezone'));

		$start_date = $request->get('start_date', '2016-01-01');
		$end_date = $request->get('end_date','2016-01-01');
		$property_id = $request->get('property_id', '0');
		$guest_id = $request->get('guest_id',0);
		$room_id = $request->get('room_id',0);
		$checkin = $request->get('checkin','');
		$checkout = $request->get('checkout','');
		//$set_key = "muncip_fee";

		$minibar_item = DB::table('services_rm_srv_itm')->get();
		$minibar_item_list = array();
		foreach($minibar_item as $mini) {
			$minibar_item_list[$mini->id] = $mini;
		}

		$mini_range = sprintf("DATE(ml.created_at) >= '%s' AND DATE(ml.created_at) <= '%s'", $start_date, $end_date);

		$ret = array();
		$data = DB::table('services_minibar_log as ml')
			->join('common_room as cr', 'ml.room_id', '=', 'cr.id')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->join('common_users as cu', 'ml.user_id', '=', 'cu.id')
			//->join('common_guest as cg', 'ml.guest_id', '=', 'cg.guest_id')
			->join('common_guest as cg', function($join) {
				$join->on('ml.guest_id', '=', 'cg.guest_id');
				$join->on('cb.property_id', '=', 'cg.property_id');

			})
			->where('cb.property_id', $property_id)
			->where('ml.guest_id', $guest_id)
			->where('ml.room_id', $room_id)
			->whereRaw($mini_range)
			->orderBy('ml.guest_id')
			->orderBy('ml.room_id')
			->orderBy('ml.created_at')
			->select(DB::raw('ml.*,  cg.guest_name, cr.room,  CONCAT_WS(" ", cu.first_name, cu.last_name) as username'))
			->get();
		$data_list = array();
		$total_mount = 0;
		$guest_name = '';
		$room = '';
		$key = '';
		$settings = array();
		$settings['muncip_fee'] = 0;
		$settings['ser_chrg'] = 0;
		$settings['vat'] = 0;
		$settings['vat_no'] = 0;
		$settings = PropertySetting::getPropertySettings($property_id, $settings);

		foreach( $data as $row) {
			$guest_name = $row->guest_name;
			$room = $row->room;
			$key = $row->id;
			//if(empty($data_list[$key])) $data_list[$key] = array();
			$item_ids = json_decode($row->item_ids);
			$quantitys = json_decode($row->quantity);

			//$list = array();
			for($i =0 ; $i<count($item_ids) ;$i++) {
				$data = (object)array();
				$item = $item_ids[$i];

				$key_name = date_format(new DateTime($row->created_at),'d-M-Y');
				$item_name = $minibar_item_list[$item]->item_name;
				$price = $minibar_item_list[$item]->charge;
				$mount = $quantitys[$i]*$price;
				$data->quantity = $quantitys[$i];
				$data->item_name = $item_name;
				$data->price = $price;
				$data->mount = round($mount,2);
				$total_mount += round($mount,2);
				$data->username = $row->username;
				if($i != 0) $data->key_name = '';
				else  $data->key_name = $key_name;
				$data_list[] = clone $data;
			}
			$data = (object)array();
			$data->quantity = '';
			$data->item_name = '';
			$data->price = 'Net Amount';
			$data->mount = $total_mount;

			$total_mount = 0;
			$data->username = '';
			$data->date = '';
			$data->key_name = '';
			$data_list[] = clone $data;
			//array_merge($data_list, $list);
		}

		$currency = DB::table('property_setting as ps')
			    ->select(DB::raw('ps.value'))
			    ->where('ps.settings_key', 'currency')
				->first();

		$ret['currency'] = $currency->value;
		$ret['datalist'] = $data_list;
		$ret['report_by'] = 'Minibar_By_Guest';
		$ret['report_type'] = 'Guest Name: '.$guest_name.' &nbsp;&nbsp;Room: '.$room.'&nbsp;&nbsp; Check in:'.$checkin.' &nbsp;&nbsp; Check out:'.$checkout;
		$ret['guest_name'] = $guest_name;
		$ret['room'] = $room;
		$ret['id'] = $row->id;
		$ret['property'] = Property::find($property_id);
		$ret['checkin'] = $checkin;
		$ret['checkout'] = $checkout;
		$ret['muncip_fee'] = $settings['muncip_fee'];
		$ret['ser_chrg'] = $settings['ser_chrg'];
		$ret['vat'] = $settings['vat'];
		$ret['vat_no'] = $settings['vat_no'];

		return $ret;
	}

	public function getMinibarStock(Request $request) {

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$searchtext = $request->get('searchtext', '');


		if($pageSize < 0 )
			$pageSize = 20;
		$ret = array();

		$query = DB::table('services_rm_srv_itm as ml');

		if($searchtext !='') {
			$where = sprintf(" (ml.item_name like '%%%s%%' or								
								ml.id like '%%%s%%')",
				$searchtext, $searchtext);
				$query->whereRaw($where);
		}

		$data_query = clone $query;

		$data_list = $data_query
			->orderBy('ml.id', $sort)
			->select(DB::raw('ml.id,ml.item_name,ml.item_stock,ml.alarm_count'))
			->skip($skip)->take($pageSize)
			->get();




		$count_query = clone $query;
		$totalcount = $count_query->count();


		$minibar_item_list = DB::table('services_rm_srv_itm')->get();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['minibar_item_list'] = $minibar_item_list;

		return Response::json($ret);

	}

	public function addMinibarStock(Request $request) {
		$change = $request->get('quantity_1', 0);
		$alarm = $request->get('quantity_2', 0);
		$item_id = $request->get('id',0);
		$item_stock = $request->get('item_stock',0);
		$alarm_count = $request->get('alarm_count',0);
		$user_id = $request->get('user_id',0);


		$query = DB::table('services_rm_srv_itm as ml');

		$action = 'Added '.$change.' Item';
		$action_alarm = 'Alarm reset to '.$alarm;
		$action_both = 'Added '.$change.' Item and Alarm reset to '.$alarm;
		$data_query = clone $query;


		$upd_list = $data_query
					->where('ml.id', $item_id)
					->update(['ml.item_stock' => $item_stock + $change]);
		if($alarm != ''	){
		$upd_list1 = $data_query
					->where('ml.id', $item_id)
					->update(['ml.alarm_count' =>$alarm]);
		}

		$user_query = DB::table('common_users as cu')
					->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->where('cu.id', $user_id)
					->first();

		$user_name = $user_query->wholename;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		if (($alarm != $alarm_count)&&($change == ''))
		{
			DB::table('services_minibar_itm_log')->insert(['user_id' => $user_id,
				'item_id' => $item_id,
				'user_name' => $user_name,
				'changed_at' => $cur_time,
				'action_taken' => $action_alarm]);
		}
		else if (($alarm == '') &&($change != '' ))
		{
			DB::table('services_minibar_itm_log')->insert(['user_id' => $user_id,
				'item_id' => $item_id,
				'user_name' => $user_name,
				'changed_at' => $cur_time,
				'action_taken' => $action]);
		}
		else
		{
			DB::table('services_minibar_itm_log')->insert(['user_id' => $user_id,
				'item_id' => $item_id,
				'user_name' => $user_name,
				'changed_at' => $cur_time,
				'action_taken' => $action_both]);
		}

		$ret = array();

		return Response::json($ret);
		}

	public function rmvMinibarStock(Request $request) {
		$change = $request->get('quantity_1', 0);
		$alarm = $request->get('quantity_2', 0);
		$item_id = $request->get('id',0);
		$item_stock = $request->get('item_stock',0);
		$alarm_count = $request->get('alarm_count',0);
		$user_id = $request->get('user_id',0);

		$ret = array();
		$rem_stock = $item_stock - $change;

		if($rem_stock <=0 )
		{

			$ret['quantity_1'] = $change;
			$ret['item_stock'] = $item_stock;

		}
		else
		{

		$query = DB::table('services_rm_srv_itm as ml');
		$action = 'Removed '.$change.' Item';
		$action_alarm = 'Alarm reset to '.$alarm;
		$action_both = 'Removed '.$change.' Item and Alarm reset to '.$alarm;

		$data_query = clone $query;


		$upd_list = $data_query
					->where('ml.id', $item_id)
					->update(['ml.item_stock' => $item_stock - $change]);

		}
		if($alarm != ''){
		$upd_list1 = $data_query
					->where('ml.id', $item_id)
					->update(['ml.alarm_count' => $alarm]);
		}

		$user_query = DB::table('common_users as cu')
					->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->where('cu.id', $user_id)
					->first();

		$user_name = $user_query->wholename;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d  H:i:s");
		if (($alarm != $alarm_count)&&($change == ''))
		{
			DB::table('services_minibar_itm_log')->insert(['user_id' => $user_id,
				'item_id' => $item_id,
				'user_name' => $user_name,
				'changed_at' => $cur_time,
				'action_taken' => $action_alarm]);
		}
		else if (($alarm == '') &&($change != '' ))
		{
			DB::table('services_minibar_itm_log')->insert(['user_id' => $user_id,
				'item_id' => $item_id,
				'user_name' => $user_name,
				'changed_at' => $cur_time,
				'action_taken' => $action]);
		}
		else
		{
			DB::table('services_minibar_itm_log')->insert(['user_id' => $user_id,
				'item_id' => $item_id,
				'user_name' => $user_name,
				'changed_at' => $cur_time,
				'action_taken' => $action_both]);
		}


		return Response::json($ret);

	}

	public function getMinibarItemHistory(Request $request) {

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 10);
		$skip = $page;
		//$orderby = $request->get('field', 'changed_at');
		$sort = $request->get('sort', 'desc');
		$property_id = $request->get('property_id', '0');
		$item_id = $request->get('id',0);

		if($pageSize < 0 )
			$pageSize = 10;

		$ret = array();

		$query = DB::table('services_minibar_itm_log as ml');

		$data_query = clone $query;

		$data_list = $data_query
					->orderBy('ml.changed_at', $sort)
					->select(DB::raw('ml.user_name,ml.changed_at,ml.action_taken'))
					->where('ml.item_id', $item_id)
					->skip($skip)->take($pageSize)
					->get();

		$count_list = DB::table('services_minibar_itm_log as ml')
					  ->where('ml.item_id', $item_id);

		$count_query = clone $count_list;
		//$count_query = clone $query;

		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function getMininbarDetail(Request $request) {
		$data = $this->mininbarDetail($request);
		return Response::json($data);

	}
	public function insertLog($itm_id, $user_id ,$action) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		DB::table('services_rm_srv_itm_log')->insert(['itm_id' => $itm_id,
			'user_id' => $user_id,
			'action' => $action,
			'created_at' => $cur_time]);
	}
	public function getHistory($id) {

		$data = DB::table('services_rm_srv_itm_log as sl')
			->leftJoin('common_users as cu', 'sl.user_id', '=', 'cu.id')
			->where('sl.itm_id', $id)
		    ->select(DB::raw('sl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as username'))
			->get();
		for($i = 0 ; $i < count($data) ; $i++) {
			if($data[$i]->user_id == 0) $data[$i]->username = 'Super Admin';
			$data[$i]->created_at  = date('d-M-Y h:i:s' ,strtotime($data[$i]->created_at));
		}
		$ret = array();
		$ret['datalist'] = $data;

		return Response::json($ret);
	}

}
