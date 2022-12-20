<?php

namespace App\Models\Call;

use App\Models\Common\CommonUser;
use Illuminate\Database\Eloquent\Model;

class Section extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_section';
	public 		$timestamps = false;
	protected $moduleslist = [
        '1' => 'IVR',
        '2' => 'User',
		'3' => 'Dispatcher',
		'4' => 'Reports',
		'5' => 'Manager',
		'6' => 'Supervisor',		
    ];
	
	public function manager()
    {
		return $this->belongsTo(CommonUser::class, 'manager_id');
    }	
	
    public function getModuleList()
    {
        return $this->moduleslist;
    }
	
	public function getModules()
	{
		$moduledata = array();
		$data = explode(",", $this->modules);
		foreach($data as $value)
		{
			foreach($this->moduleslist as $key => $module)
			{
				if( $value == $module )
				{
					array_push($moduledata,$key);
					break;
				}				
			}
		}		
		
		return $moduledata;
	}
	
	public function setModules($modules)
	{
		$value = "";
		$i = 0;
		
		if( !empty($modules) )
		{
			foreach( $modules as $key => $id )
			{
				$value = $value . $this->moduleslist[$id];			
				$value = $value . ',';
				$i++;
			}
		}
		
		$this->modules = $value;
		$this->save();
	}
	
	
	
}