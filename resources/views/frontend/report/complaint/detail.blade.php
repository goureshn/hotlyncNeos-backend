<style>
.highlight {
    background: yellow;
}
</style>
<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
?>
@foreach($data['list'] as $row)
<div>
    
    <table style="width: 100%;" border="0">
    <tr style="border:0;">   
    <div>
        <td align="left" id="bloc1">
            <img src="<?php echo $logo_image_data?>" alt=""  width = 70>
        </td>
            <td style="border:0;text-align: right">
                <h3>
                    <strong> {{date('d', strtotime($row->created_at))}}<sup>th</sup> {{date('M Y', strtotime($row->created_at))}}</strong>
                </h3>
            </td>
    </div>
    </tr>
    </table>
</div>				
<div>
    <table style="width: 100%;" border="0">
        <tr style="border:0;">
            <td style="width: 30%;border:0; text-align:left">
                <h3 style="font-size: 10px;"> Feedback ID:
                    <strong class = "red"><?php echo sprintf('F%05d', $row->id)?></strong> 
                </h3> 
            </td>
            <td style="border:0;text-align: right">
                <h3> Status:
                @if($row->closed_flag == 1)
                    <strong class="closed">
                @elseif($row->status == 'Rejected')  
                    <strong class="rejected">
                @elseif($row->status == 'Resolved')  
                    <strong class="resolved">
                @elseif($row->status == 'Completed')  
                    <strong class="completed">
                @else    
                    <strong class="completed">
                @endif
                @if($row->closed_flag == 1)
                    Closed
                @else
                   
                    {{$row->status}}
                @endif
                </strong> 
                </h3>
				</td>
        </tr>
    </table>
</div>
<!---Guest Name------------>
<hr>
<div>
    <div class="pull-left">
        <p style="font-size: 10px; color:#ea4019"> Guest Information:
            
    </div>
	<table  style="width : 100%" border="0">
        <tbody >
            <tr class ="">
                <td>
                <div style="width: 50%; float:left">
                <li ><b>Guest Name: </b>{{$row->guest_name}}</li>
                <li><b>Nationality: </b>{{$row->nationality_name}}</li>
                <li><b>Gender: </b>{{$row->gender}}</li>
                <li ><b>Mobile: </b>{{$row->mobile}}</li>
                <li ><b>Email: </b>{{$row->email}}</li>
                <li><b>VIP: </b>{{$row->vip}}</li>                
                
                
        </div>
        <div style="width: 50%; float:left">
                <li ><b>Guest Type: </b>{{$row->guest_type}}</li>
                <li><b>Room Number: </b>{{$row->room}}</li>
                <li><b>Check-In: </b>{{$row->arrival}}</li>
                <li><b>Check-Out: </b>{{$row->departure}}</li>
                <li><b>Address: </b>{{$row->address}}</li>
        </div>
        </td>  
            </tr>
           
           
        </tbody>
		</table>
</div>
<hr>


<div>
  <div class="pull-left">
        <p style="font-size: 10px; color:#ea4019"> Feedback Information:
            
    </div>
    <table  style="width : 100%">
        <tbody>
            <tr class="">
                <td>
                <div style="width: 50%; float:left">
                    <li><b>Type: </b>{{$row->feedback_type}}</li>
                    <li><b>Raised by: </b>{{$row->job_role}} <strong>{{$row->wholename}}</strong></li>
                    <li><b>Incident Time: </b>{{date('d-M-Y H:i', strtotime($row->incident_time))}}</li>                              
                </div>    

                <div style="width: 50%; float:left">
                    <li><b>Source: </b>{{$row->feedback_source}}</li>  
                    <li><b>Location: </b>{{$row->lgm_name}} - {{$row->lgm_type}}</li>
                    @if( ($row->category_id) != 0 )
                        <li><b>Category: </b>{{$row->maincategory}}</li>
                    @endif    	                	                
                </div>    
                
                <div style="width: 100%; float:left">
                    <br>
                    <li><b>Feedback: </b>{!!$row->comment_highlighted!!}</li><br>
                    <li><b>Initial Response: </b>{!!$row->initial_response_highlighted!!}</li><br>

                    @if($row->status == 'Rejected')
                    <li>
                        <li><b>{{$row->status}}</b> [{{date('d-M-Y H:i', strtotime($row->updated_at))}}] </li>
                        <li><b>Resolution:</b> {{$row->solution}} &emsp;
                        <b>Resolution time:</b> {{$row->resolution_time}}</li>
                    </li>   <br> 
                    @endif
    
                    @if($row->status == 'Resolved')
                    <li>
                        <li><b>{{$row->status}}</b>[{{date('d-M-Y H:i', strtotime($row->updated_at))}}]</li>
                        <li><b>Resolution:</b> {{$row->solution}} &emsp;
                        <b>Resolution time:</b> {{$row->resolution_time}}</li>
                    </li>    <br>
                    @endif
                    @if($row->closed_flag == 1)
                        <li><b>Feedback Closed</b>[{{date('d-M-Y H:i', strtotime($row->closed_time))}}]</li>
                        <li><b>Comment: </b>{{$row->closed_comment}}</li>
                        <li style="text-align: right">Total Resolution Time: <b>{{$row->total_resolution_time}}</b></li><br>
                    @endif
                <tr >
                <td>
                    @foreach($row->sublist as $sub)     
                        <div style="width: 100%; font-size: 10px; float:left">
                        <li><strong>{{sprintf('F%05d%s', $row->id, $sub->sub_label)}}</strong> [<b>{{$sub->department}}</b>]: <b>{{$sub->update_status}} 
                            {{$sub->status == 1 ? 'Pending' : ''}}
                            {{$sub->status == 2 ? 'Completed' : ''}}
                            {{$sub->status == 3 ? 'Escalated' : ''}}
                            {{$sub->status == 4 ? 'Re-routing' : ''}}
                            {{$sub->status == 5 ? 'Canceled' : ''}}
                            {{$sub->status == 6 ? 'Timeout' : ''}}
                            {{$sub->status == 7 ? 'Re-opened' : ''}}
                        </b></li>
                        </div>
                        <div style="width: 50%; float:left">
                            <li><b>Category: </b>{{$sub->category_name}}</li>
                            <li><b>Sub-Category: </b>{{$sub->subcategory_name}}</li>
                            <li><b>Severity: </b>{{$sub->severity_name}}</li>
                            <li ><b>Created By: </b>{{$sub->created_by}}</li>
                        </div>
                        <div style="width: 50%; float:left">
                               
                                <li ><b>Location: </b>{{$sub->location_name}}</li>
                                <li ><b>Location Type: </b>{{$sub->location_type}}</li>
                                <li ><b>Assignee: </b>{{$sub->assignee_name}}</li>
                                <li ><b>Completed By: </b>{{$sub->completed_by_name}}</li>
                        </div>
                        <div style="width: 100%; float:left">
                        <br>
                        <li ><b>Comment: </b>{{$sub->comment}}</li><br>
                        <li ><b>Initial Response: </b>{{$sub->init_response}}</li><br>
                        @if($sub->status == 2)
                        Resolution Time:
                            <b>{{$sub->elaspse_time}}</b> 
                           
                        Resolution: <b>{{$sub->resolution}}</b>
                        <br>
                        @endif    
                        @foreach($sub->subcomp_list as $subcomp_list)
                        <li>[<b>{{$subcomp_list->provided_by}}</b>] {{$subcomp_list->name}}: {{$data['currency']}} {{$subcomp_list->cost}}</li>
                        @endforeach
                        <li style="text-align: right">Total Sub-Complaint Compensation: <b>{{$data['currency']}} {{$sub->sub_comp_total}}</b></li>
                        </div>
                   
                   
                   
                    @endforeach
                    </td>
                    </tr>
                    <br>
                
                </div>    
            </td>   
            </tr>
        </tbody>
    </table>
</div>

<!---Guest Information---->


<!---Additional Comment---->
<hr>
<div>
    <div class="pull-left">
       <p style="font-size: 10px;color:#ea4019">Comments: </p>
    </div>
</div>

<div>
    <table  style="width : 100%" border="0">
        <tbody >
        <tr class ="">
            <td>
                <div style="width: 100%; float:left">
                @foreach($row->main_comment_list as $comment)
                        <li><b>{{$comment->wholename}}</b>[{{$comment->created_at}}] : {{$comment->comment}}</li>
                    @endforeach
                   </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<hr>



<!---Compensation---->
<hr>
<div>
    <div class="pull-left">
       <p style="font-size: 10px;color:#ea4019">Compensation: </p>
    </div>
</div>

<div>
    <table  style="width : 100%" border="0">
        <tbody >
        <tr class ="">
            <td>
                <div style="width: 100%; float:left">   
                @foreach($row->comp_list as $comp)  
                    <li>[<b>{{$comp->provided_by}}</b>] {{$comp->name}}: {{$data['currency']}} {{$comp->cost}} - {{$comp->comment}}</li>
                @endforeach
                <li style="text-align: right">Total Compensation: <b>{{$data['currency']}} {{$row->total_cost}}</b></li>
            
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<hr>
@endforeach
