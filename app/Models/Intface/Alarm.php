<?php

namespace App\Models\Intface;

use Illuminate\Database\Eloquent\Model;

class Alarm extends Model {

    protected $connection = 'interface';
	protected 	$guarded = [];
	protected 	$table = 'alarm';
	public 		$timestamps = false;
}