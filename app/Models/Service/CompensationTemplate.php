<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class CompensationTemplate extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_compensation_template';
	public 		$timestamps = false;

	public static function getTemplateElementList() {
		return array(
					array('key' => '', 'value' => '-- Select Item Name'),
					array('key' => 'guest_name', 'value' => 'Guest Name'),
					array('key' => 'compensations', 'value' => 'Compensation List'),
					array('key' => 'guest_address', 'value' => 'Guest Address'),
					array('key' => 'property_name', 'value' => 'Property Name'),
					array('key' => 'username', 'value' => 'User Name'),
					array('key' => 'job_role', 'value' => 'Job Role'),
					array('key' => 'room_number', 'value' => 'Room Number'),
					array('key' => 'checkin_date', 'value' => 'Checkin Date'), 
					array('key' => 'checkout_date', 'value' => 'Checkout Date'),
					array('key' => 'current_date', 'value' => 'Current Date'), 
					array('key' => 'current_time', 'value' => 'Current Time'), 
				);
	}
 
	public static function generateTemplate($param) {
		$style = '<style>' . file_get_contents($_SERVER["DOCUMENT_ROOT"] . '/frontpage/bower_components/quill/quill_export.snow.css') . '</style>';

		$model = CompensationTemplate::where('property_id', $param['property_id'])
				->first();

		if( empty($model) )	
			$content = '';
		else
			$content = $model->template;

		$content = str_replace("{{guest_address}}", $param['complaint']->address, $content);
		$content = str_replace("{{salutation}}", $param['complaint']->salutation, $content);
		$content = str_replace("{{guest_name}}", $param['complaint']->guest_name, $content);
		$content = str_replace("{{property_name}}", $param['complaint']->property_name, $content);
		$content = str_replace("{{username}}", $param['user']->wholename, $content);
		$content = str_replace("{{job_role}}", $param['user']->job_role, $content);
		$content = str_replace("{{room_number}}", $param['complaint']->room, $content);
		$content = str_replace("{{checkin_date}}", $param['complaint']->arrival, $content); 
		$content = str_replace("{{checkout_date}}", $param['complaint']->departure, $content);
		$content = str_replace("{{current_date}}", date("l, j F Y"), $content); 
		$content = str_replace("{{current_time}}", date("g:i a"), $content); 
		 

		$comp_list = '<ul>';
		foreach($param['comp_list'] as $row)
		{
			$comp_list .= '<li>' . $row->item_name . '</li>';
		}
		$comp_list .= '</ul>';

		$content = str_replace("{{compensations}}", $comp_list, $content);

		$content = '<html><head>' . $style . '</head><body><div class="ql-container ql-snow"><div class="ql-editor" contenteditable="true" data-placeholder="override default placeholder">' . $content . '</div></div></body>';

		return $content;
	}
}