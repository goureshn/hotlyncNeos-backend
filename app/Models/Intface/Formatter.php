<?php

namespace App\Models\Intface;

use Illuminate\Database\Eloquent\Model;

class Formatter extends Model {

    protected $connection = 'interface';
	protected 	$guarded = [];
	protected 	$table = 'formatter';
	public 		$timestamps = false;

	protected $typelist = [		
		'0' => 'None',
		'1' => 'BCC',
		'2' => 'CRC-32',
	];

	public function getTypeList()
	{
		return $this->typelist;
	}
}