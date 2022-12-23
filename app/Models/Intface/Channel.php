<?php

namespace App\Models\Intface;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model {

    protected $connection = 'interface';
	protected 	$guarded = [];
	protected 	$table = 'channel';
	public 		$timestamps = false;
	
	protected $commodelist = [
        '1' => 'TCP Client',
        '2' => 'TCP Server',
		'3' => 'Web Service',
        '4' => 'Serial Port',
        '5' => 'Restful_XML',
        '6' => 'SOAP',
    ];
	
	public function getComModeList()
    {
        return $this->commodelist;
    }
	
}