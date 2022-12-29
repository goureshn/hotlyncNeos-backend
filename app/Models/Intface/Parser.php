<?php

namespace App\Models\Intface;

use Illuminate\Database\Eloquent\Model;

class Parser extends Model {

    protected $connection = 'interface';
	protected 	$guarded = [];
	protected 	$table = 'parser';
	public 		$timestamps = false;
}