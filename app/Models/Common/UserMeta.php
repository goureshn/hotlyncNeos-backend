<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserMeta extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_users_meta';
	public 		$timestamps = true;

	static function saveMetaValue($user_id, $values) {

		foreach($values as $key => $row) {
			$model = UserMeta::where('user_id', $user_id)
				->where('meta_key', $key)
				->first();

			if( empty($model) )
			{
				$model = new UserMeta();
				$model->user_id = $user_id;
				$model->meta_key = $key;							
			}	

			$model->meta_value = $row;	
			$model->save();	
		}	
	}

	static function getMetaValue($user_id, $rules) {
		foreach($rules as $key => $value)
		{
			$data = UserMeta::where('user_id', $user_id)
					->where('meta_key', $key)
					->select(DB::raw('meta_value'))
					->first();

			if( empty($data) )
				continue;

			$rules[$key] = $data->meta_value;
		}

		return $rules;
	}

	static function getComplaintTicketFilter($user_id,$property_ids) {
		$rules = array();

		$rules['complaint_ticket'] = '{}';
		
		$rules = UserMeta::getMetaValue($user_id, $rules);

		$filter = json_decode($rules['complaint_ticket'], true);
		if(!is_array($filter)){
			$filter = array();
		}
		if( empty($filter) )
			$filter = array();
		if( empty($filter['all_flag']) )
			$filter['all_flag'] = 0;
		if( empty($filter['ticket']) )
			$filter['ticket'] = 'Last 24 Hours';
		if( empty($filter['service_recovery']) )
			$filter['service_recovery'] = '';

		if( empty($filter['status_filter']) )
		{
			$filter['status_filter'] = array(
												'Pending' => false,
												'Resolved' => false,
												'Acknowledge' => false,
												'Closed' => false,
												'Rejected' => false,
												'Flagged' => false,
												'Unresolved' => false,
												
											);	
		}

		if( empty($filter['severity_filter']) )
		{
			$severity_list = DB::table('services_complaint_type')
				->get();

			$filter['severity_filter'] = array();	
			foreach($severity_list as $row) {
				$filter['severity_filter'][$row->type] = true;	
			}	
		}

		if( empty($filter['property_filter']) )
		{
			$property_list = DB::table('common_property')
				->whereIn('id', $property_ids)
				->get();

			$filter['property_filter'] = array();	
			foreach($property_list as $row) {
				$filter['property_filter'][$row->name] = true;	
			}	
		}

		if( empty($filter['department_tags']) )
		{
			$filter['department_tags'] = array();
		}

		if( empty($filter['discussed']) )
		{
			$filter['discussed'] = false;
		}

		if( empty($filter['guest_type_filter']) )
		{
			$filter['guest_type_filter'] = array(
												'In-House' => false,
												'Checkout' => false,
												'Arrival' => false,
												'Walk-in' => false,
												'House Complaint' => false,
											);	
		}

		if( empty($filter['departure_flag']) )
		{
			$filter['departure_flag'] = false;
		}

		if( empty($filter['departure_date']) )
		{
			$filter['departure_date'] = date('Y-m-d');
		}
		
		return $filter;
	}

	static function saveComplaintTicketFilter($user_id, $filter) {
		$rules = array();

		$rules['complaint_ticket'] = json_encode($filter);
		
		$rules = UserMeta::saveMetaValue($user_id, $rules);
		
		return $rules;
	}

	static function getSubcomplaintTicketFilter($user_id,$property_ids) {
		$rules = array();

		$rules['subcomplaint_ticket'] = '{}';
		
		$rules = UserMeta::getMetaValue($user_id, $rules);

		$filter = json_decode($rules['subcomplaint_ticket'], true);

		if( empty($filter) )
			$filter = array();
		
		if( empty($filter['service_recovery']) )
			$filter['service_recovery'] = '';
		
		if( empty($filter['ticket']) )
			$filter['ticket'] = 'Last 24 Hours';

		if( empty($filter['status_filter']) )
		{
			$filter['status_filter'] = array(
												'Pending' => false,
												'Completed' => false,
												'Re-routing' => false,
												'Flagged' => false
											);	
		}

		if( empty($filter['severity_filter']) )
		{
			$severity_list = DB::table('services_complaint_type')
				->get();

			$filter['severity_filter'] = array();	
			foreach($severity_list as $row) {
				$filter['severity_filter'][$row->type] = true;	
			}	
		}

		if( empty($filter['category_tags']) )
		{
			$filter['category_tags'] = array();
		}

		if( empty($filter['subcategory_tags']) )
		{
			$filter['subcategory_tags'] = array();
		}
		
		if( empty($filter['location_tags']) )
		{
			$filter['location_tags'] = array();
		}

		if( empty($filter['location_type_tags']) )
		{
			$filter['location_type_tags'] = array();
		}

		return $filter;
	}

	static function saveSubcomplaintTicketFilter($user_id, $filter) {
		$rules = array();

		$rules['subcomplaint_ticket'] = json_encode($filter);
		
		$rules = UserMeta::saveMetaValue($user_id, $rules);
		
		return $rules;
	}

	static function getComplaintSetting($user_id) {
		$rules = array();

		$rules['complaint_setting'] = '{}';
		
		$rules = UserMeta::getMetaValue($user_id, $rules);


		$filter = json_decode($rules['complaint_setting'], true);

		if( empty($filter) )
			$filter = array();

		if( !isset($filter['complaint_notify']) )
			$filter['complaint_notify'] = true;

		if( !isset($filter['complaint_create']) )
			$filter['complaint_create'] = true;

		if( !isset($filter['subcomplaint_create']) )
			$filter['subcomplaint_create'] = true;

		if( !isset($filter['subcomplaint_complete']) )
			$filter['subcomplaint_complete'] = true;
		if( !isset($filter['compensation_change']) )
			$filter['compensation_change'] = true;

		if( !isset($filter['severity_filter']) )
		{
			$severity_list = DB::table('services_complaint_type')
				->get();

			$filter['severity_filter'] = array();	
			foreach($severity_list as $row) {
				$filter['severity_filter'][$row->type] = true;	
			}	
		}

		return $filter;
	}

	static function saveComplaintSetting($user_id, $filter) {
		$rules = array();

		$rules['complaint_setting'] = json_encode($filter);
		
		$rules = UserMeta::saveMetaValue($user_id, $rules);
		
		return $rules;
	}

	static function getCallcenterSetting($user_id) {
		$rules = array();

		$rules['callcenter_setting'] = '{}';
		
		$rules = UserMeta::getMetaValue($user_id, $rules);


		$filter = json_decode($rules['callcenter_setting'], true);

		if( empty($filter) )
			$filter = array();

		if( !isset($filter['callcenter_notify']) )
			$filter['callcenter_notify'] = true;

		return $filter;
	}

	static function saveCallcenterSetting($user_id, $filter) {
		$rules = array();

		$rules['callcenter_setting'] = json_encode($filter);
		
		$rules = UserMeta::saveMetaValue($user_id, $rules);
		
		return $rules;
	}


	static function getLostFoundFilter($user_id){
		$rules = array();

		$rules['lost_found'] = '{}';

		$rules = UserMeta::getMetaValue($user_id, $rules);

		$filter = json_decode($rules['lost_found'], true);

		if(empty($filter)){
			$filter = array();
		}

        if(empty($filter['all_flag'])){
            $filter['all_flag'] = 0;
        }

        if( empty($filter['status_filter']) )
        {
            $status_list = DB::table('services_lnf_status')
                ->get();

            $filter['status_filter'] = array();   
            foreach($status_list as $row) {
                $filter['status_filter'][$row->status_name] = true;  
            }   
        }

		if( empty($filter['location_type_filter']) )
		{
			$filter['location_type_filter'] = array(
												'Property' => true,
												'Building' => true,
												'Floor' => true,
												'Room' => true,
												'Common Area' => true,
												'Admin Area' => true,
												'Outdoor' => true,
											);	
		}

        return $filter;
	}

	static function saveLostFoundFilter($user_id, $filter)
	{
		$rules = array();

		$rules['lost_found'] = json_encode($filter);

		$rules = UserMeta::saveMetaValue($user_id, $rules);

		return $rules;
	}

}