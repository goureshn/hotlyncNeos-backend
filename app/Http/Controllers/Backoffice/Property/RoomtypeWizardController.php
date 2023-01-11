<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;

use DB;
use Response;


class RoomtypeWizardController extends UploadController
{
    public function getRoomTypeList(Request $request)
	{
		$property_id = $request->get('property_id', '0');

		$model = DB::table('common_room_type as rt')									
			->leftJoin('common_building as cb', 'rt.bldg_id', '=', 'cb.id')		
			->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id')
			->where('cp.id', $property_id)
			->select('rt.*')
			->get();
	
		return Response::json($model);
	}
}
