<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests;


use DateInterval;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;
use Curl;



class FaqController extends Controller
{
    public function getModuleList(Request $request) {
        
        // $model = Db::table('common_module')
        //     ->get();
        
        $permission_group_id  = $request->get('permission_group_id',0);

        $model = DB::table('common_permission_members as pm')
                ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
                ->join('common_module as cm', 'pr.module_id', '=', 'cm.id')
                ->where('pm.perm_group_id', $permission_group_id)
                ->select(DB::raw('DISTINCT(cm.id), cm.*'))
                ->get();    

        return Response::json($model);
    }

    public function getFaqList(Request $request) {
        $searchtext = $request->get('searchtext','');
        $module_id = $request->get('module_id',0);
        
        $permission_group_id  = $request->get('permission_group_id',0);
        $moules = DB::table('common_permission_members as pm')
                ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
                ->join('common_module as cm', 'pr.module_id', '=', 'cm.id')
                ->where('pm.perm_group_id', $permission_group_id)
                ->select(DB::raw('DISTINCT(cm.id), cm.*'))
                ->get();    
        $module_ids = array();
        for($i = 0 ; $i < count($moules) ; $i ++ ) {
            $mo_id = $moules[$i]->id;
            $module_ids[] = $mo_id;
        }


        $ret = array();

        $query = DB::table('common_faq as cf')
            ->leftJoin('common_module as cm', 'cf.module_id', '=', 'cm.id')
            ->leftJoin('common_users as cu', 'cf.user_id', '=', 'cu.id')
            ->leftJoin('common_category as cc', 'cf.category_id', '=', 'cc.id');

        if( $module_id != 0 )
        {
            $query->where('cf.module_id', $module_id);
        }else {          
            $query->whereIn('cf.module_id', $module_ids);
        }

        if($searchtext != '') {
            $where = sprintf(" (cc.name like '%%%s%%' or								
								cf.title like '%%%s%%' or								
								cf.content like '%%%s%%' or
								cu.first_name like '%%%s%%' or								
								cu.last_name like '%%%s%%')",
                $searchtext, $searchtext, $searchtext, $searchtext,$searchtext
            );
            $query->whereRaw($where);
        }

        $data_query = clone $query;

        $faq_list = $data_query
            ->select(DB::raw('cf.*, cm.name as module, cc.id as category_id, cc.name as category , CONCAT_WS(" ", cu.first_name, cu.last_name) as username '))
            ->get();

        for($i = 0 ; $i < count($faq_list) ; $i++) {
            $faq_id = $faq_list[$i]->id;
            if($faq_list[$i]->user_id == 0 ) $faq_list[$i]->username = 'Super Admin'; 
            $taglist = DB::table('common_faq_tag as cft')
                ->Join('common_tag as ct', 'cft.tag_id', '=', 'ct.id')
                ->where('cft.faq_id',$faq_id)
                ->select(['ct.name as text'])
                ->get();
            $faq_list[$i]->tags = $taglist;
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['faqlist'] = $faq_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);

    }


}