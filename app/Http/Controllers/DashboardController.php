<?php

namespace App\Http\Controllers;

use Auth;
use Hash;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
	
	function index()
	{
		return view('dashboard.home');
	}
	
}
