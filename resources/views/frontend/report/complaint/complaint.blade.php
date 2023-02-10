<?php
    function converDate($val) {
        $date_val = date_format(new DateTime($val),'d-M-Y');
        return  $date_val;
    }

    function convertDateTime($val) {
        $date_val = date_format(new DateTime($val),'d-M-Y H:i:s');
        return  $date_val;   
    }

    function  getTicketNumberComp ($ticket){
        if(!$ticket)
            return 'F00000';
        return sprintf('F%05d', $ticket->id);
    }

    function compensationStatus($val) {
        $convert_val = '';
        switch($val) {
            case 0:
                $convert_val = 'Complete Approved' ;
                break;
            case 1:
                $convert_val ='On-route';
                break;
            case 2:
                $convert_val ='Rejected';
                break;
            case 3:
                $convert_val ='Returned';
                break;
            case 4:
                $convert_val = 'Pending';
                break;
            default:
                $convert_val = 'Complete Approved' ;
                break;                             
        }
        return $convert_val;
    }

    function isCheckNull($val) {
        if($val == null) {
            return "";
        }else {
            return $val;
        }
    }

    function  getTicketNumber ($ticket){
        if(!$ticket)
            return 'C00000';
        return sprintf('C%05d', $ticket->parent_id);
    }

    function seconds2human($ss) {
        $s = $ss%60;
        $m = floor(($ss%3600)/60);
        $h = floor(($ss%86400)/3600);
        $d = floor(($ss%2592000)/86400);
        $M = floor($ss/2592000);
        if ($d > 0)
            if ($d == 1)
                return "$d day";
            else
                return "$d days";
        else if ($h > 0)
            if ($h == 1)
                return "$h hour";
            else
                return "$h hours";
        else if ($m > 0)
            if ($m == 1)
                return    "$m minute, $s seconds";
            else
                return    "$m minutes, $s seconds";
        else if ($m == 0)
                return " $s seconds";
        else  
                return " $s seconds";   
        }

    $group_val = $data['group_by'];
    $repor_by = $data['report_by'];
    $rpeort_type = $data['report_type'];
?>
<br><br>
@if($rpeort_type == 'Detailed'&& !empty($data['complaint_list']))
    @foreach ($data['complaint_list'] as  $key => $obj)
        @if(!empty($obj))
        <div>
            <table >
                <tr>
                    <td class="text-lg"><b>{{$group_val}} : 
                        @if( $group_val=="Date" ) 
                            </b> {{converDate($key)}}
                        @else
                            </b> {{$key}}
                        @endif 
                    </td>
                </tr>
            </table>
        </div>
        @foreach ($obj as $data1)
        <hr>   
        <div>
            <table style="width:95%;">
                <tr>
                    <td style="width: 50%">
                        <table style="width: 100%;" border="0">
                            <tr><td class="items">Ticket#:</td>          <td> {{getTicketNumberComp($data1)}}</td></tr>
                            <tr><td class="items">Type:</td>          <td> {{$data1->feedback_type}}</td></tr>
                            <tr><td class="items">Source:</td>          <td> {{$data1->feedback_source}}</td></tr>
                            <tr><td class="items">Raised by:</td>          <td> {{$data1->wholename}}</td></tr>
                            <tr><td class="items">Guest:</td>         <td> {{$data1->guest_fullname}}</td></tr>
                        @if($data1->guest_type=="In-House" || $data1->guest_type=="Checkout") 
                            <tr><td class="items">Room :</td>         <td> {{$data1->room}}</td></tr>  
                        @endif
                            <tr><td class="items" >Severity :</td>          <td> {{$data1->severity_name}}</td></tr>
                            <tr><td class="items" >Status :</td>              <td> {{$data1->status}} {{convertDateTime($data1->updated_at)}}</td></tr>
                        </table>
                    </td>
                    <td style="width: 50%">
                        <table style="width: 100%;" border="0">
                            <tr><td class="items" >Status:</td>              <td> {{$data1->status}}</td></tr>
                            <tr><td class="items" >Guest Type:</td>          <td> {{$data1->guest_type}}</td></tr>
                            <tr><td class="items" >Incident Time:</td>    <td> {{convertDateTime($data1->incident_time)}}</td></tr>
                            @if( !empty($data1->category_name) )
                                <tr><td class="items">Category:</td>            <td> {{$data1->category_name}}</td></tr>
                            @endif    
                            @if($data1->guest_type=="In-House" || $data1->guest_type=="Checkout")<tr><td class="items">Stay :</td>               <td> {{converDate($data1->arrival) }} to {{converDate($data1->departure)}}</td></tr>@endif
                            <tr><td class="items">Property :</td> <td> {{$data1->property_name}}</td></tr>
                            <tr><td class="items">Location :</td> <td> {{$data1->location}}</td></tr>
                        </table>
                    </td>
                </tr>           
                <tr>
                    <td colspan="2">
                        <table style="width: 100%;" border="0">
                            <tr><td class="items" colspan="2" >&nbsp;</td>
                            <tr><td class="items" style="vertical-align: top">Feedback:</td>          <td> {!! nl2br(e($data1->comment_highlighted)) !!}</td></tr>
                            <tr><td class="items" colspan="2" >&nbsp;</td>
                            <tr><td class="items" style="vertical-align: top" >Initial Response :</td>   <td> {!!nl2br(e( $data1->initial_response_highlighted)) !!}</td></tr>
                            <tr><td class="items" style="vertical-align: middle" >Resolution:</td>        <td> {!! $data1->solution !!}</td></tr>
                        </table>
                    </td>                   
                </tr>
                <tr>
                    <td colspan="2">
                        <table style="width: 100%;" border="0">                        
                            <tr>
                                <td colspan="2">
                                    @if(!empty($data1->compensation))
                                        <table style="width: 100%;" class="table1">
                                            <thead>
                                            <tr>
                                                <th class="subtitle"><b>Compensation Type</b></th>
                                                <th class="subtitle"><b>Department</b></th>
                                                <th class="subtitle"><b>Cost({{$data['currency']}})</b></th>
                                                <th class="subtitle"><b>Comment</b></th>
                                                <th class="subtitle"><b>Provider</b></th>
                                                <th class="subtitle"><b>Approval</b></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($data1->compensation as $row1 )
                                                <tr>
                                                    <td class="subtitle"><span>{{isCheckNull($row1->item_name)}}</span></td>
                                                    <td class="subtitle"><span>{{isCheckNull($row1->department)}}</span></td>
                                                    <td class="subtitle"><span>{{isCheckNull($row1->cost)}}</span></td>
                                                    <td class="subtitle"><span>{{isCheckNull($row1->comment)}}</span></td>
                                                    <td class="subtitle"><span>{{isCheckNull($row1->provider)}}</span></td>
                                                    <td class="subtitle">
                                                        <span>
                                                            {{isCheckNull(compensationStatus($row1->status))}}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                            
                            <tr><td colspan="2" style="height: 20px;"> &nbsp;</td> </tr>
                            <tr style="border:0;">
                                <td style="width: 90%" colspan="2">
                                    @if($data1->subcomplaintcount > 0)
                                    <table style="width: 100%;">
                                        <tr><td class="items"><b>Sub Complaint Count:</b></td>          <td>{{$data1->subcomplaintcount}}</td></tr>
                                        <tr>
                                            <td colspan="2">
                                            <!--sub complaint list loop-->                              
                                                <table style="width:100%;margin-left: 40px;">
                                                @foreach ($data1->subcomplaint as $sub)
                                                        <tr>
                                                            <td colspan="2" style="font-weight: bold"> {{getTicketNumber($sub)}}{{$sub->sub_label}} - {{$sub->department}} - {{$sub->status}}</td>
                                                        </tr>
                                                       
                                                        <tr>
                                                            <td class="items" >Created at:</td> <td>{{convertDateTime($sub->created_at)}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="items">Severity Type:</td> <td>{{$sub->type}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="items">Location:</td> <td>{{$sub->location_name}} - {{$sub->location_type}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="items">Created By:</td> <td>{{$sub->created_by}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="items">Assignee:</td> <td>{{$sub->assignee_name}}</td>
                                                        </tr>
                                                    @if( !empty($sub->resolution) )  
                                                        <tr>
                                                            <td class="items">Resoultion:</td> <td>{{$sub->resolution}}</td>
                                                        </tr>
                                                    @endif    

                                                    @if( !empty($sub->category_name) )  
                                                        <tr><td class="items">Category:</td>        <td> {{$sub->category_name}}</td></tr>
                                                    @endif
                                                    @if( !empty($sub->subcategory_name) )      
                                                        <tr><td class="items">Sub Category:</td>    <td> {{$sub->subcategory_name}}</td></tr>
                                                    @endif    
                                                    @if($sub->status=='Completed')
                                                        <tr><td class="items">Completed By:</td>     <td> {{$sub->completed_by_name}}</td></tr>
                                                        <tr><td class="items">Closure Date:</td>     <td> {{convertDateTime($sub->completed_at)}}</td></tr>
                                                        <tr><td class="items">Closure Days:</td>     <td> {{seconds2human($sub->closure_days)}}</td></tr>
                                                    @endif
                                                    @if( !empty($sub->comment_list) && count($sub->comment_list) > 0 )          
                                                        @foreach($sub->comment_list as $key => $com)
                                                            @if( $key == 0 )                                            
                                                                <tr style="margin-top: 10px;">
                                                                    <td class="items"><b>Comment:</b></td>      
                                                                    <td>
                                                                        <label style="margin-left: 20px;"> {{$com->comment}} </label>
                                                                    </td>
                                                                </tr>
                                                            @else 
                                                                <tr>
                                                                    <td class="items">&nbsp;</td>
                                                                    <td>
                                                                        <label style="margin-left: 20px;"> {{$com->comment}} </label>
                                                                    </td>
                                                                </tr>
                                                            @endif

                                                        @endforeach
                                                    @endif    
                                                    @if( !empty($sub->comp_list) && count($sub->comp_list) > 0 )      
                                                        <tr>
                                                            <td colspan="2">
                                                                <div>
                                                                    <div><b>Compensation List</b></div>            
                                                                    <table  border="0" style="width : 100%;">
                                                                        <thead style="background-color:#ffffff">
                                                                            <tr class="plain">                    
                                                                                <th align="center"><b>No</b></th>
                                                                                <th align="center"><b>Compensation</b></th>                                                                                
                                                                                <th align="center"><b>Cost</b></th>
                                                                                <th align="center"><b>Provided By</b></th>
                                                                                <th align="center"><b>Date & Time</b></th>
                                                                            </tr>   
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($sub->comp_list as $key1 => $row1)
                                                                                <tr class="plain">                    
                                                                                    <td align="center">{{$key1 + 1}}</td>
                                                                                    <td align="center">{{$row1->compensation}}</td>                                                                                    
                                                                                    <td align="center">{{$row1->cost}}</td>
                                                                                    <td align="center">{{$row1->wholename}} {{$row1->department}}</td>
                                                                                    <td align="center">{{$row1->created_at}}</td>
                                                                                </tr>
                                                                            @endforeach                                                                                 
                                                                        </tbody>
                                                                    </table>    
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif    
                                                @endforeach
                                                </table>
                                            <!--sub complaint list end-->
                                            </td>
                                        </tr>                            
                                    </table>
                                    @endif
                                </td>
                            </tr>
                        </table>  
                    </td> 
                    <td style="width: 50%">
                    </td>    
                </tr>  
            </table>               
        </div>  
        
        @endforeach 
        @endif
    @endforeach
    @if($repor_by == 'Complaint' && $group_val == 'Category')
        @if(!empty($data['complaint_list']))    
            <table style="width: 100%;">
                <tr>
                <th> {{$data['group_by']}}</th>
                    @foreach ($data['summary_header'] as  $header)
                        <th> {{$header}}</th>
                    @endforeach    
                </tr>
                <?php
                $total_info = 0;
                $total_minor = 0;
                $total_major = 0;
                $total_moderate = 0;
                $total_serious = 0;
                $total_tot = 0;
                ?>
                @foreach ($data['complaint_summary'] as  $key =>$obj)
                <tr>
                    <td align="center">{{$key}}</td>       
                    <td align="center"> {{$obj[$data['summary_header'][0]]}} </td> 
                    <td align="center"> {{$obj[$data['summary_header'][1]]}} </td>  
                    <td align="center"> {{$obj[$data['summary_header'][2]]}} </td>             
                    <td align="center"> {{$obj[$data['summary_header'][3]]}} </td>  
                    <td align="center"> {{$obj[$data['summary_header'][4]]}} </td>
                    <td align="center"> {{$obj[$data['summary_header'][5]]}} </td>                      
                </tr>   
                <?php
                        $total_info += $obj[$data['summary_header'][0]];
                        $total_minor += $obj[$data['summary_header'][1]];
                        $total_moderate += $obj[$data['summary_header'][2]];
                        $total_major += $obj[$data['summary_header'][3]];
                        $total_serious += $obj[$data['summary_header'][4]];
                        $total_tot += $obj[$data['summary_header'][5]];
                    ?>    
                @endforeach
                <tr class="">
                    <td style="text-align:center; background-color:#CFD8DC" class="right"><b>Total</b></td>
                    <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_info}}</b></td>
                    <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_minor}}</b></td>
                    <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_moderate}}</b></td>
                    <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_major}}</b></td>
                    <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_serious}}</b></td>
                    <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_tot}}</b></td>
                </tr>        
            </table>        
        @endif
    @endif
     
@endif

@if($rpeort_type == 'Summary')
<table style="width: 100%;">
    <tr>
        <!--<th>&nbsp;</th>-->
        <th> {{$data['group_by']}}</th>
    @foreach ($data['summary_header'] as  $header)
        <th> {{$header}}</th>
    @endforeach    
    </tr>
    <?php
    $total_info = 0;
    $total_minor = 0;
    $total_major = 0;
    $total_moderate = 0;
    $total_serious = 0;
    $total_tot = 0;
    ?>
    @foreach ($data['complaint_summary'] as  $key =>$obj)
        <tr>
            <td align="center">{{$key}}</td>       
            <td align="center"> {{$obj[$data['summary_header'][0]]}} </td> 
            <td align="center"> {{$obj[$data['summary_header'][1]]}} </td>  
            <td align="center"> {{$obj[$data['summary_header'][2]]}} </td>             
            <td align="center"> {{$obj[$data['summary_header'][3]]}} </td>  
            <td align="center"> {{$obj[$data['summary_header'][4]]}} </td>
            <td align="center"> {{$obj[$data['summary_header'][5]]}} </td>  
                            
        </tr> 
        <?php
        $total_info += $obj[$data['summary_header'][0]];
        $total_minor += $obj[$data['summary_header'][1]];
        $total_moderate += $obj[$data['summary_header'][2]];
        $total_major += $obj[$data['summary_header'][3]];
        $total_serious += $obj[$data['summary_header'][4]];
        $total_tot += $obj[$data['summary_header'][5]];
        ?>   
    @endforeach
    <tr class="">
            
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>Total</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_info}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_minor}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_moderate}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_major}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_serious}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_tot}}</b></td>
    </tr>          
</table>
@endif



