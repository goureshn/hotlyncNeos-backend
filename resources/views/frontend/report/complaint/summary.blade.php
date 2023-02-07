<br/><br/>
<style>
.highlight {
    background: yellow;
}

.first-col li {
    text-align: center;
}

</style>

<div>
    <table  style="width : 100%" border="0">
        <tbody >
        <tr style="border:0">
            <td  style="width:100%;border: 0; text-align: center; font-size: 13px;">
                <strong>Summary Feedback Report</strong>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<div>
    <table style="width : 100%">
        <tbody>
        @foreach($data['list'] as $row)
        <tr class="">
             
            <td class="first-col" style="width: 15%">                         
                @if($row->guest_path != NULL)  
                <?php
                    $path = $_SERVER['DOCUMENT_ROOT'] .'/'. $row->guest_path;                    
                    $type1 = pathinfo($path, PATHINFO_EXTENSION);
                    if( file_exists($path) == 1 )
                    {
                        $image_data = file_get_contents($path);
                        $logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
                    }
                    else
                    {
                        $logo_image_data = "";
                    }
                ?>
                    <li >
                        <div>
                            <img src="<?php echo $logo_image_data?>" alt=""  height = 60 width = 60>
                        </div>
                    </li>
                @endif

                <li class="red"><b>{{sprintf('F%05d', $row->id)}}</b></li>
                <li>{{date('d M Y', strtotime($row->created_at))}}</li>
                
                @if($row->closed_flag == 1)
                    <li class="closed"><b>
                @elseif($row->status == 'Rejected')  
                    <li class="rejected"><b>
                @elseif($row->status == 'Resolved')  
                    <li class="resolved"><b>
                @elseif($row->status == 'Completed')  
                    <li class="completed"><b>  
                @else    
                    <li class="completed"><b>
                @endif
                @if($row->closed_flag == 1)
                    Closed
                @else
                    {{$row->status}}
                @endif
                    </b>
                </li>   
            </td>
            <td>
                <div style="width: 50%; float:left">
                    <li><b>Type: </b>{{$row->feedback_type}}</li>
                    <li><b>Raised by: </b>{{$row->wholename}}[{{$row->job_role}}]</li>
                    <li><b>Incident Time: </b>{{date('d-M-Y H:i', strtotime($row->incident_time))}}</li>
                    @if( !empty($row->room) )
                        <li><b>Room: </b>{{$row->room}} - {{$row->guest_name}}</li>
                    @else  
                        @if( ($row->guest_type) != 'House Complaint' )
                            <li><b>Guest: </b>{{$row->guest_name}}</li>
                        @endif  
                    @endif 
                    @if( !empty($row->arrival) && !empty($row->departure) )
                        <li><b>Arrival: </b>{{date('d-M-Y', strtotime($row->arrival))}}</li>
                    @endif
                    @if( !empty($row->vip) )
                        <li><b>VIP: </b>{{$row->vip}}</li>                
                    @endif  
                    @if( !empty($row->booking_rate) )
                        <li><b>Booking Rate: </b>{{$row->booking_rate}}</li>            
                    @endif                                  
                </div>    

                <div style="width: 50%; float:left">
                    <li><b>Source: </b>{{$row->feedback_source}}</li>  
                    @if( ($row->category_id) != 0 )
                        <li><b>Category: </b>{{$row->maincategory}}</li>
                    @endif
                    <li><b>Location: </b>{{$row->lgm_name}} - {{$row->lgm_type}}</li>
                    <li><b>Type: </b>{{$row->guest_type}}</li>		                	                
                    @if( !empty($row->departure) )
                        <li><b>Departure: </b>{{date('d-M-Y', strtotime($row->departure))}}</li>
                    @endif
                    @if( !empty($row->company) )
                        <li><b>Company: </b>{{$row->company}}</li>                
                    @endif  
                    @if( !empty($row->booking_src) )
                        <li><b>Booking Source: </b>{{$row->booking_src}}</li>                
                    @endif 
                </div>    
                
                <div style="width: 100%; float:left">
                <br>
                    <li><b>Feedback: </b>{!!$row->comment_highlighted!!}</li><br>
                    <li><b>Initial Response: </b>{!!$row->initial_response_highlighted!!}</li>  <br>

                    @if($row->status == 'Rejected')
                    <li>
                        [{{date('d-M-Y H:i', strtotime($row->updated_at))}}] <strong>[Duty Manager] </strong>{{$row->solution}}
                    </li>    <br>
                    @endif
    
                    @if($row->status == 'Resolved')
                    <li>
                        <li>[{{date('d-M-Y H:i', strtotime($row->updated_at))}}] Completed</li>
                        <b>Resolution:</b> {{$row->solution}}
                    </li>    <br>
                    @endif
    
                    @foreach($row->sublist as $sub)     
                    <li>{{sprintf('F%05d%s', $row->id, $sub->sub_label)}} [<b>{{$sub->department}}</b>]: {{$sub->update_status}} 
                            {{$sub->status == 1 ? 'Pending' : ''}}
                            {{$sub->status == 2 ? 'Completed' : ''}}
                            {{$sub->status == 3 ? 'Escalated' : ''}}
                            {{$sub->status == 4 ? 'Re-routing' : ''}}
                            {{$sub->status == 5 ? 'Canceled' : ''}}
                            {{$sub->status == 6 ? 'Timeout' : ''}}
                            {{$sub->status == 7 ? 'Re-opened' : ''}}
                        @if($sub->status == 2)
                        Resolution Time:
                            <b>{{$sub->elaspse_time}}</b> 
                        @endif    
                    </li>
                    @endforeach
                    <br>
                    @if($row->closed_flag == 1)
                        <li>[{{date('d-M-Y H:i', strtotime($row->closed_time))}}] <strong>[Duty Manager] </strong>Feedback Closed</li>
                        <li>Comment: {{$row->closed_comment}}</li>
                        <li style="text-align: right">Total Resolution Time: <b>{{$row->total_resolution_time}}</b></li><br>
                    @endif
                    @if($row->total_cost != 0)
                        <li style="text-align: right">Compenstion: <b>{{$data['currency']}} {{$row->total_cost}}</b></li>
                    @endif
                </div>    
            </td>    
        </tr>
        @endforeach

        <tr class="">
            <td style="width: 15%">
                <li>Total</li>
                <li>Feedback:  {{count($data['list'])}}</li>
                <li>Comp: {{$data['compensation']['total_count']}}</li>
            </td>            
            <td>
                @foreach($data['compensation']['list'] as $key => $row)
                <li><label class="red">&#9899;</label><b>{{$row->name}}:</b> {{$data['currency']}} {{$row->cost}} X {{$row->count}}</li>
                @endforeach                
                <li style="text-align: right"><b>Total: {{$data['currency']}} {{$data['compensation']['total_cost']}}</b></li>
            </td>
        </tr>
        </tbody>
    </table>
</div>
