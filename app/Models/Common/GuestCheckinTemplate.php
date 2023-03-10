<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Functions;

class GuestCheckinTemplate extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_guest_checkin_template';
    public 		$timestamps = false;

   public static function getTemplateElementList() {
		return array(
					array('key' => '', 'value' => '-- Select Item Name'),
					array('key' => 'guest_name', 'value' => 'Guest Name'),
					array('key' => 'property_name', 'value' => 'Property Name'),
					array('key' => 'room_number', 'value' => 'Room Number'),
					array('key' => 'checkin_date', 'value' => 'Checkin Date'), 
					array('key' => 'checkout_date', 'value' => 'Checkout Date'),
					array('key' => 'current_date', 'value' => 'Current Date'), 
					array('key' => 'current_time', 'value' => 'Current Time'), 
				);
	}

    public static function generateTemplate($param) {
        $style = '<style>' . file_get_contents($_SERVER["DOCUMENT_ROOT"] . '/frontpage/bower_components/quill/quill_export.snow.css') . '</style>';

        $model = GuestCheckinTemplate::where('property_id', $param['property_id'])
            ->first();

        if( empty($model) )
            $content = '';
        else
            $content = $model->template;

       // $content = str_replace("{{first_name}}", $param['guest']->first_name, $content);
        $content = str_replace("{{guest_name}}", $param['guest']->guest_name, $content);
        $content = str_replace("{{property_name}}", $param['guest']->property_name, $content);
        $content = str_replace("{{checkin_date}}", $param['guest']->checkin_date, $content);
        $content = str_replace("{{checkout_date}}", $param['guest']->checkout_date, $content);
        $content = str_replace("{{current_date}}", $param['guest']->current_date, $content);
        $content = str_replace("{{current_time}}", $param['guest']->current_time, $content);
        $content = str_replace("{{room_number}}", $param['guest']->room_number, $content);

       $content = '<html><head>' . $style . '</head><body><div class="ql-container ql-snow"><div class="ql-editor" contenteditable="true" data-placeholder="override default placeholder">' . $content . '</div></div></body>';

        return $content;
    }
}