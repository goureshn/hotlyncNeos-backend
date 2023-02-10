<?php

namespace App\Http\Controllers\Backoffice\Admin;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Common\Property;
use App\Models\Common\Faq;
use App\Models\Common\Category;
use App\Models\Common\Tag;

use Excel;
use DB;
use Datatables;
use Response;

class FaqWizardController extends UploadController
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('common_faq')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = Faq::where('id', '>', '0');
        /*
        $search = $request->input('search');
        if( !empty($search) )
        {
            $query->where(function($searchquery)
                {
                    $search = '%' . $request->input('search') . '%';
                    $searchquery->where('name', 'like', $search)
                     ->orWhere('description', 'like', $search);
                });
        }
        */
        $pagesize = $request->input('pagesize');
        if( empty($pagesize) )
            $pagesize = 10;

        $request->flashOnly('search');

        $property = Property::lists('name', 'id');
        $datalist = $query->orderby('name')->paginate($pagesize);

        //$mode = "read";
        $step = '3';
        return view('backoffice.wizard.admin.faq', compact('datalist', 'model', 'pagesize', 'property', 'step'));
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('common_faq as cf')
                ->leftJoin('common_module as cm', 'cf.module_id', '=', 'cm.id')
                ->leftJoin('common_users as cu', 'cf.user_id', '=', 'cu.id')                
                ->leftJoin('common_category as cc', 'cf.category_id', '=', 'cc.id')
                ->select(['cf.*', 'cm.name as module' ,'cc.id as category_id', 'cc.name as category', 'cu.username as username' ]);

            return Datatables::of($datalist)
                 ->editColumn('username', function($data) {
                    if($data->user_id == 0) {
                        return 'Super Admin';
                    }else {
                        return $data->username;
                    }
                })
                ->addColumn('checkbox', function ($data) {
                    return '<input type="checkbox" class="checkthis" />';
                })
                ->addColumn('tags', function ($data) {
                    $faq_id = $data->id;
                    $taglist = DB::table('common_faq_tag as cft')
                        ->Join('common_tag as ct', 'cft.tag_id', '=', 'ct.id')
                        ->where('cft.faq_id',$faq_id)
                        ->select(['ct.name as text'])
                        ->get();

                    return $taglist;
                })
                ->addColumn('edit', function ($data) {
                    return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
                })
                ->addColumn('delete', function ($data) {
                    return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="job_role!=\'SuperAdmin\'" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
                })
                ->rawColumns(['username', 'checkbox', 'tags', 'edit', 'delete'])
                ->make(true);
        }
        else
        {
            $model = new Faq();
            return $this->showIndexPage($request, $model);
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $input = $request->except(['id','category','tags','module','username']);
        
        $model = Faq::create($input);

        $message = 'SUCCESS';

        if( empty($model) )
            $message = 'Internal Server error';

        if(!empty($model)) {
            $faq_id = $model->id;
            $tags = $request->tags ?? [];
            for($i = 0 ; $i < count($tags) ; $i++ ) {
                $tag = $tags[$i]['text'];
                $data = Db::table('common_tag')                
                    ->whereRaw("name like '%".$tag."%'")
                    ->select(DB::raw('*'))
                    ->first();
                if(!empty($data)) {
                    $tag_id = $data->id;                    
                }else {                    
                    $tag_id = DB::table('common_tag')->insertGetId(['name' =>$tag]);
                } 
                DB::table('common_faq_tag')->where('faq_id', $faq_id)->where('tag_id', $tag_id)->delete();
                $faqs = DB::table('common_faq_tag')->insert(['faq_id'=>$faq_id, 'tag_id' => $tag_id]);   
            }
        }

        if ($request->ajax())
            return Response::json($model);
        else
            return back()->with('error', $message)->withInput();
    }

    public function show($id)
    {
        //
    }

    public function edit(Request $request, $id)
    {
        $model = Faq::find($id);
        if( empty($model) )
            $model = new Faq();

        return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
        $model = Faq::find($id);
        $input = $request->except(['id','category','tags','module','username']);    
        //$input = $request->all();
        $model->update($input);

         if(!empty($model)) {
            $faq_id = $model->id;
            $tags = $request->get('tags',[]);
            if(!is_array($tags)) $tags = json_decode($tags);

            for($i = 0 ; $i < count($tags) ; $i++ ) {
                $tag = is_array($tags[$i]) ? $tags[$i]['text'] : $tags[$i]->text;
                $data = Db::table('common_tag')                
                    ->whereRaw("name like '%".$tag."%'")
                    ->select(DB::raw('*'))
                    ->first();
                if(!empty($data)) {
                    $tag_id = $data->id;                    
                }else {                    
                    $tag_id = DB::table('common_tag')->insertGetId(['name' =>$tag]);
                } 
                DB::table('common_faq_tag')->where('faq_id', $faq_id)->where('tag_id', $tag_id)->delete();
                $faqs = DB::table('common_faq_tag')->insert(['faq_id'=>$faq_id, 'tag_id' => $tag_id]);   
            }
        }    

        if ($request->ajax())
            return Response::json($model);
        else
            return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = Faq::find($id);
        $model->delete();

        return $this->index($request);
    }

    public function getCategories(Request $request) {        
        $category = $request->get('category','');

        $model = Db::table('common_category')                
                ->whereRaw("name like '%".$category."%'")
                ->select(DB::raw('*'))
                ->get();

        return Response::json($model);
    }
    public function addCategory(Request $request) {
        $category = $request->get('category', '');

        $data = new Category();
        $data->name = $category;  
        $data->save(); 

        $ret = array();
        $ret['list'] = $data;

        return Response::json($ret);
    }

    public function getTagList(Request $request) {
        
        $filter = $request->get('filter', '');      
        $ret = array();

        $datalist = DB::table('common_tag')
                ->whereRaw("name like '%".$filter."%'")
                ->select(DB::raw('*'))
                ->get();

            for($i = 0; $i < count($datalist); $i++)
                $ret[] = $datalist[$i]->name;

        return Response::json($ret);
    }

}
