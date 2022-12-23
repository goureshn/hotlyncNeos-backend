<?php

namespace App\Models\Intface;

use Illuminate\Database\Eloquent\Model;
use DB;

class Protocol extends Model {
    protected $connection = 'interface';
	protected $table = 'protocol';
    protected 	$guarded = [];
    public 		$timestamps = false;

    // public function getTypeList()
    // {
    //     $result = DB::connection('interface')->select("SHOW COLUMNS FROM protocol WHERE FIELD = 'type'");	
	// 	$result = str_replace(array("enum('", "')", "''"), array('', '', "'"), $result[0]->Type);
    //     $typelist = explode("','", $result);
        
    //     return $typelist;
    // }

    protected $typelist = [
        '1' => 'PBX',
        '2' => 'PMS',
        '3' => 'HOTLYNC',
        '4' => 'REPLICATE',
        '5' => 'TV',
    ];

    public function getTypeList()
    {
        return $this->typelist;
    }
}