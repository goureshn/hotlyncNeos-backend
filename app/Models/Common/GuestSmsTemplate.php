<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Functions;

class GuestSmsTemplate extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_guest_sms_template';
    public 		$timestamps = false;

    public static function getTemplateElementList() {
        return array(
            array('key' => '', 'value' => '-- Select Item Name'),
            //array('key' => 'first_name', 'value' => 'First Name'),
            //array('key' => 'last_name', 'value' => 'Last Name'),
            array('key' => 'guest_name', 'value' => 'Guest Name'),
            array('key' => 'property_name', 'value' => 'Property Name'),
            array('key' => 'mobile', 'value' => 'Mobile Number'),
        );
    }

    public static function generateTemplate($param) {
      //  $style = '<style>' . file_get_contents($_SERVER["DOCUMENT_ROOT"] . '/frontpage/bower_components/quill/quill_export.snow.css') . '</style>';

        $model = GuestSmsTemplate::where('property_id', $param['property_id'])
            ->first();

        if( empty($model) )
            $content = '';
        else
            $content = $model->template;

       // $content = str_replace("{{first_name}}", $param['guest']->first_name, $content);
        $content = str_replace("{{guest_name}}", $param['guest']->guest_name, $content);
        $content = str_replace("{{property_name}}", $param['guest']->property_name, $content);
        $content = str_replace("{{mobile}}", $param['guest']->mobile, $content);

        //$content = '<html><head>' . $style . '</head><body><div class="ql-container ql-snow"><div class="ql-editor" contenteditable="true" data-placeholder="override default placeholder">' . $content . '</div></div></body>';

        return $content;
    }
}