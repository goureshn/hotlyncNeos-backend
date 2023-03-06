<?php

namespace App\Http\Controllers;

use App\Models\Common\Department;
use Illuminate\Http\Request;
use Response;

class DepartmentController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getList()
    {
        $model = Department::all();

        return Response::json($model);
    }

}
