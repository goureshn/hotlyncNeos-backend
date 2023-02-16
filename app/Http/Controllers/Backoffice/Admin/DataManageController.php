<?php

namespace App\Http\Controllers\Backoffice\Admin;


use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Call\GuestExtension;
use App\Models\Common\Property;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;
use App\Models\Common\RoomType;
use App\Models\Common\Room;
use App\Models\Common\Department;
use App\Models\Common\CommonUser;
use App\Models\Common\CommonJobrole;
use App\Models\Call\Section;
use App\Models\Call\StaffExternal;
use App\Models\Service\TaskList;
use App\Models\Service\TaskCategory;
use App\Models\Service\Location;
use App\Models\Service\LocationType;
use App\Imports\CommonImportExcel;

use Excel;
use DB;
use Datatables;
use Response;

class DataManageController extends UploadController
{
	function upload(Request $request)
	{
		$output_dir = "uploads/template/";
		
		$ret = array();
		
		$filekey = 'myfile';

		if($request->hasFile($filekey) === false )
		{
			$ret['code'] = 201;
			$ret['message'] = "No input file";
			$ret['content'] = array();
			return Response::json($ret);
		}

		//You need to handle  both cases
		//If Any browser does not support serializing of multiple files using FormData() 
		if (!is_array($_FILES[$filekey]["name"])) //single file
		{
			if ($request->file($filekey)->isValid() === false )
			{
				$ret['code'] = 202;
				$ret['message'] = "No valid file";
				return Response::json($ret);
			}

			$fileName = 'upload_' . $_FILES[$filekey]["name"];			
			
			$dest_path = $output_dir . $fileName;
			
			move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);

			$ret = $this->parseExcelFile($dest_path);

			// save setting value
			$ret['code'] = 200;			
			$ret['content'] = $dest_path;
			return Response::json($ret);
		}
	}
	
	private function addFloorRoomData($data, $step)
	{
		// check property
		$property_name = $data['property'];
		$property = Property::where('name', $property_name)->first();
		if( empty($property) )
		{
			$property = new Property();

			$property->name = $property_name;
			$property->save();
		}

		// check building
		$bldg_name = $data['building'];
		if( $bldg_name != '' )
		{
			$building = Building::where('property_id', $property->id)->where('name', $bldg_name)->first();
			if( empty($building) )
			{
				$building = new Building();

				$building->property_id = $property->id;
				$building->name = $bldg_name;
				$building->save();
			}
		}
		else
		{
			$building = new Building();
			$building->id = 0;
		}
		
		if( empty($building) )
		{
			$building = new Building();

			$building->property_id = $property->id;
			$building->name = $bldg_name;
			$building->save();
		}

		unset($data['property']);	
		unset($data['building']);	

		if( $step == 2 )	// Floor
		{
			$data['bldg_id'] = $building->id;
			if( CommonFloor::where('bldg_id', $data['bldg_id'])->where('floor', $data['floor'])->exists() )
				return;				
			
			CommonFloor::create($data);

			return;
		}

		// Floor				
		$floor = CommonFloor::where('bldg_id', $building->id)->where('floor', $data['floor'])->first();
		if(empty($floor))
		{
			$floor = new CommonFloor();

			$floor->bldg_id = $building->id;
			$floor->floor = $data['floor'];

			$floor->save();
		}

		$data['flr_id'] = $floor->id;
		unset($data['floor']);	

		// Room type
		$room_type = RoomType::where('bldg_id', $building->id)->where('type', $data['room_type'])->first();
		if(empty($room_type))
		{
			$room_type = new RoomType();

			$room_type->bldg_id = $building->id;
			$room_type->type = $data['room_type'];

			$room_type->save();
		}

		$data['type_id'] = $room_type->id;
		unset($data['room_type']);	
		
		if( Room::where('flr_id', $data['flr_id'])->where('room', $data['room'])->exists() )
			return;					
		Room::create($data);

		return $data;
	}

	private function addUserData($data)
	{
		// check property
		$property_name = $data['property'];
		$property = Property::where('name', $property_name)->first();
		if( empty($property) )
		{
			$property = new Property();

			$property->name = $property_name;
			$property->save();
		}
		unset($data['property']);	

		// check building
		$bldg_name = $data['building'];
		if( $bldg_name != '' )
		{
			$building = Building::where('property_id', $property->id)->where('name', $bldg_name)->first();
			if( empty($building) )
			{
				$building = new Building();

				$building->property_id = $property->id;
				$building->name = $bldg_name;
				$building->save();
			}
		}
		else
		{
			$building = new Building();
			$building->id = 0;
		}
		
		unset($data['building']);	
		
		// Department
		$department = Department::where('property_id', $property->id)
						->where('building_id', $building->id)
						->where('department', $data['department'])
						->first();
		if(empty($department))
		{
			$department = new Department();

			$department->property_id = $property->id;
			$department->building_id = $building->id;
			$department->department = $data['department'];

			$department->save();
		}

		unset($data['department']);	

		// Job role
		$job_role = CommonJobrole::where('property_id', $property->id)
							->where('dept_id', $department->id)
							->where('job_role', $data['job_role'])							
							->first();
		if(empty($job_role))
		{
			$job_role = new Section();

			$job_role->id = 0;
		}

		unset($data['job_role']);	


		// User		
		$data['dept_id'] = $department->id;
		$data['job_role_id'] = $job_role->id;
		
		if( CommonUser::where('username', $data['username'])			
			->exists() 
			)
			return;				

		CommonUser::create($data);	
		
		return $data;
	}

	private function addAdminExtensionData($data)
	{
		// check property
		$property_name = $data['property'];
		$property = Property::where('name', $property_name)->first();
		if( empty($property) )
		{
			$property = new Property();

			$property->name = $property_name;
			$property->save();
		}
		unset($data['property']);	

		// check building
		$bldg_name = $data['building'];
		if( $bldg_name != '' )
		{
			$building = Building::where('property_id', $property->id)->where('name', $bldg_name)->first();
			if( empty($building) )
			{
				$building = new Building();

				$building->property_id = $property->id;
				$building->name = $bldg_name;
				$building->save();
			}
		}
		else
		{
			$building = new Building();
			$building->id = 0;
		}
		
		unset($data['building']);	
		
		// Department
		$department = Department::where('property_id', $property->id)
						->where('building_id', $building->id)
						->where('department', $data['department'])
						->first();
		if(empty($department))
		{
			$department = new Department();

			$department->property_id = $property->id;
			$department->building_id = $building->id;
			$department->department = $data['department'];

			$department->save();
		}

		unset($data['department']);	

		// Call Section
		$call_section = Section::where('building_id', $building->id)
							->where('dept_id', $department->id)
							->where('section', $data['section'])
							->first();
		if(empty($call_section))
		{
			$call_section = new Section();

			$call_section->building_id = $building->id;
			$call_section->dept_id = $department->id;
			$call_section->section = $data['section'];

			$call_section->save();
		}

		unset($data['section']);	


		// User
		$user = CommonUser::where('dept_id', $department->id)
						->whereRaw('CONCAT_WS(" ", first_name, last_name) = "'. $data['user'] .'"')
						->first();		
		unset($data['user']);	

		$data['building_id'] = $building->id;
		$data['section_id'] = $call_section->id;
		if( !empty($user) )
			$data['user_id'] = $user->id;
		else	
			$data['user_id'] = 0;

		if( StaffExternal::where('building_id', $data['building_id'])
			->where('section_id', $data['section_id'])
			->exists() 
			)
			return;				

		StaffExternal::create($data);	
		
		return $data;
	}

	private function addGuestExtensionData($data)
	{
		// check property
		$property_name = $data['property'];
		$property = Property::where('name', $property_name)->first();
		if( empty($property) )
		{
			$property = new Property();

			$property->name = $property_name;
			$property->save();
		}
		unset($data['property']);	

		// check building
		$bldg_name = $data['building'];
		if( $bldg_name != '' )
		{
			$building = Building::where('property_id', $property->id)->where('name', $bldg_name)->first();
			if( empty($building) )
			{
				$building = new Building();

				$building->property_id = $property->id;
				$building->name = $bldg_name;
				$building->save();
			}
		}
		else
		{
			$building = new Building();
			$building->id = 0;
		}
		
		unset($data['building']);	
		
		// Room
		$room = DB::table('common_room as cr')
						->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
						->where('cf.bldg_id', $building->id)
						->where('cr.room', $data['room'])
						->first();
		
		unset($data['room']);	

		$data['bldg_id'] = $building->id;
		if(empty($room))
			$data['room_id'] = 0;
		else
			$data['room_id'] = $room->id;

		if( $data['room_id'] > 0 )
		{
			if( GuestExtension::where('bldg_id', $data['bldg_id'])
						->where('room_id', $data['room_id'])
						->exists() 
				)
			return;				

			GuestExtension::create($data);	
		}	
		
		return $data;
	}

	private function addTaskListData($data)
	{
		// check category		
		$category = TaskCategory::where('name', $data['category'])->first();
		if( empty($category) )
		{
			$category = new TaskCategory();

			$category->name = $data['category'];
			$category->save();
		}
		unset($data['category']);	

		$data['category_id'] = $category->id;

		if( TaskList::where('category_id', $data['category_id'])
								->where('task', $data['task'])
								->exists() 
						)
					return;				

		TaskList::create($data);			

		return $data;
	}

	private function addLocation($data)
	{
		// check property
		$property_name = $data['property'];
		$property = Property::where('name', $property_name)->first();
		if( empty($property) )
		{
			$property = new Property();

			$property->name = $property_name;
			$property->save();
		}
		unset($data['property']);	

		// check location type
		$type_name = $data['type'];
		$loc_type = LocationType::createOrFind($type_name);
		
		unset($data['type']);	
		
		// location
		$location = Location::where('property_id', $property->id)
					->where('type_id', $loc_type->id)
					->first();

		if( empty($location) )
		{
			$location = new Location();
			$location->property_id = $property->id;
			$location->type_id = $loc_type->id;
		}		

		$location->name = $data['name'];
		$location->desc = $data['description'];		
		$location->save();

		
		return $data;
	}


	public function parseExcelFile($path)
	{
		$ret = array();
		
		// Property		
		$ret['Property'] = Property::createLocation();
	
		// Building
		$ret['Building'] = Building::createLocation();

		$rows = Excel::toArray(new CommonImportExcel, $path);
		$rows = [$rows[0]];
		for($i = 0; $i < count($rows); $i++ )
		{
			foreach( $rows[$i] as $data )
			{
				//echo json_encode($data);				
				$data = $this->addUserData($data);
			}
		}

		$rows = Excel::toArray(new CommonImportExcel, $path);
		$rows = [$rows[1]];
		for($i = 0; $i < count($rows); $i++ )
		{
			foreach( $rows[$i] as $data )
			{
				//echo json_encode($data);				
				$data = $this->addFloorRoomData($data, 2);
			}
		}
		$ret['Floor'] = CommonFloor::createLocation();

		$rows = Excel::toArray(new CommonImportExcel, $path);
		$rows = [$rows[2]];
		for($i = 0; $i < count($rows); $i++ )
		{
			foreach( $rows[$i] as $data )
			{
				$data = $this->addFloorRoomData($data, 3);
			}
		}
		$ret['Room'] = Room::createLocation();

		$rows = Excel::toArray(new CommonImportExcel, $path);
		$rows = [$rows[3]];
		for($i = 0; $i < count($rows); $i++ )
		{
			foreach( $rows[$i] as $data )
			{
				$data = $this->addAdminExtensionData($data);
			}
		}							

		$rows = Excel::toArray(new CommonImportExcel, $path);
		$rows = [$rows[4]];
		for($i = 0; $i < count($rows); $i++ )
		{
			foreach( $rows[$i] as $data )
			{
				$data = $this->addGuestExtensionData($data);
			}
		}							

		$rows = Excel::toArray(new CommonImportExcel, $path);
		$rows = [$rows[5]];
		for($i = 0; $i < count($rows); $i++ )
		{
			foreach( $rows[$i] as $data )
			{
				$data = $this->addTaskListData($data);
			}
		}							

		$rows = Excel::toArray(new CommonImportExcel, $path);
		$rows = [$rows[6]];
		for($i = 0; $i < count($rows); $i++ )
		{
			foreach( $rows[$i] as $data )
			{
				$data = $this->addLocation($data);
			}
		}
	}

	public function eraseTables(Request $request)
	{
		$table_list = $request->get('table_list', '');

		$table_list = explode(',', $table_list);

		$ret = array();

		
		foreach($table_list as $row)
		{
			if( $row == 'common_floor' )
				$ret['floor'] = CommonFloor::deleteLocation();

			if( $row == 'common_room' )
				$ret['room'] = Room::deleteLocation();

			DB::select("TRUNCATE TABLE " . $row);
		}

		$ret['code'] = 200;
		$ret['table_list'] = $table_list;

		return Response::json($ret);
	}	
}
