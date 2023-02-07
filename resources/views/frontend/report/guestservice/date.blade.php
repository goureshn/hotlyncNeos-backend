<br/>
<?php

function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}

function getEmptyTimeValue($value) {
    if( empty($value)|| $value == null )
        return '00:00:00';
    else
        return $value;
}

function getCompare($origin, $new, $param) {
    if($origin == $new) return $param = '';
    else return $param;
}
$date_val = '';

?>
<!-- {{ $data['time'] }} -->
{{--<div>--}}
    {{--<img src="frontpage/img/hotlync.png" alt="" style="width: 200px; height: auto">--}}
    {{--<span style="margin-left: 400px;vertical-align: middle">{{$data['report_type']}} report by {{$data['report_by']}}</span>--}}
{{--</div>--}}
@if($data['report_by'] != 'Service Category')
@if($data['report_type'] == 'Detailed' )
    @if( !empty($data['ticket_type']) )
        <div style="margin-top: 30px">
            <table>
                <tr>
                    <td>Filters:</td>
                    <td style="padding-left: 50px">
                         {{$data['ticket_type']}}
                    </td>
                </tr>
            </table>
        </div>
    @endif
    <?php
    $ticket_type = array('', 'Guest Request', 'Department Request', 'Complaint', 'Managed Task');
    $status_name = array('Completed', 'Open', 'Escalated', 'Timeout', 'Canceled', 'Scheduled');
    $ticket_type_prefix = array('', 'G', 'D', 'C', 'M');
    $omit_num = $data['omit_num'];
    $count_number = array();
    $m = 0;
    ?>
    
        @foreach ($data['ticket_list'] as  $key => $data_group)
            <?php
            $title_all = 0;
            $before_ticket_id = '0';
            ?>
            @foreach ($data_group as $row)
                <?php
                $ticket_id = sprintf('%05d', $row->id);
                if($before_ticket_id != $ticket_id)
                    $title_all++;
                $before_ticket_id = $ticket_id;
                ?>
            @endforeach
            <?php
              $count_number[] = $title_all;
            ?>
        @endforeach
    

    @foreach ($data['ticket_list'] as  $key => $data_group)
        <div>
            <p style="margin: 0px">
                @if($data['report_by'] == 'Date')
                    {{date("d-M-Y",  strtotime($key))}}
                @elseif($data['report_by'] == 'Ticket Type')
                    {{$ticket_type[$key]}}
                @elseif($data['report_by'] == 'Status')
                    {{$key}} <b style="color: red"> ({{$count_number[$m]}} Tickets) </b>
                @elseif($data['report_by'] == 'Staff')
                    {{$key}}<b style="color: red"> ({{$count_number[$m]}} Tickets) </b>
                @else
                    {{$key}}
                @endif
            </p>
            <table class="grid print-friendly" border="0" style="width : 100%;" >
                <thead style="background-color:#3c6f9c" >
                @if ($data['report_type'] == 'Detailed')
                    <tr >
                        @foreach($data['fields'] as $key1=>$value)
                            @if( $key1 != $omit_num )
                                <th style="width:{{$data['widths'][$key1]}}"><b>{{$data['fields'][$key1]}}</b></th>
                            @endif
                        @endforeach
                    </tr>
                @endif
                </thead>
                <tbody>
                <?php
                $before_ticket_id = '0';
                ?>
                @foreach ($data_group as $row)
                    <?php
                    $ticket_id = sprintf('%05d', $row->id);
                    $data_row = array();
                    $ticket_number = getCompare($before_ticket_id,$ticket_id,$ticket_id);
                    if( !empty($ticket_number) )
                        $ticket_number = $ticket_type_prefix[$row->type] . $ticket_number;

                    $data_row[] = $ticket_number;
               

                    // $data_row[] = getCompare($before_ticket_id,$ticket_id,$ticket_type[$row->type]);
                    if( !empty($row->lgm_name) ) {
                        if($ticket_type[$row->type] == 'Guest Request')
                        $data_row[] = getCompare($before_ticket_id,$ticket_id,$row->lgm_name.' - '.$row->guest_name);
                        else
                        $data_row[] = getCompare($before_ticket_id,$ticket_id,$row->lgm_name);
                    }
                    else
                        $data_row[] = '';

                    if( !empty($row->task_name) )
                        $data_row[] = getCompare($before_ticket_id,$ticket_id,$row->task_name);
                    else
                        $data_row[] = '';

                    if ($row->sub_status == 'Modify(Closed)')
                        $row->sub_status = 'Closed';
                    else if ($row->sub_status == 'Modify(Hold)')
                        $row->sub_status = 'Hold';
                    else if ($row->sub_status == 'Modify(Resume)')
                        $row->sub_status = 'Resume';

                    if ($row->sub_status == 'Resume'){

                        $hold_time = strtotime($row->log_time) - strtotime($hold_log_time); 
                    }

                    if( $row->proc_time < 0 )
                        $proc_time = '00:00';
                    else
                        $proc_time = sprintf('%02d:%02d', ($row->proc_time/60), $row->proc_time%60);
                    if ($row->sub_status == 'Completed'){
                        $data_row[] = $row->sub_status . '('.  gmdate("H:i:s", $row->proc_time) . ')';
                    }
                    else  if ($row->sub_status == 'Resume'){
                        $data_row[] = $row->sub_status . '('.  gmdate("H:i:s", $hold_time) . ')';
                    }else{
                        $data_row[] = $row->sub_status;
                    }
                    
                    if( $data['report_by'] == 'Date' )
                        $data_row[] = date("H:i:s",  strtotime($row->log_time));
                    else
                        $data_row[] = date("d-M-y",  strtotime($row->log_time));

                    $data_row[] = date("H:i:s",  strtotime($row->log_time));                    
                    $data_row[] = $row->comment;                    
                    $data_row[] = $row->sub_wholename;

                    if ($row->sub_status == 'Hold')
                            $hold_log_time = $row->log_time; 
                    $before_ticket_id = $ticket_id;// store to compare the previously displayed values.
                    ?>
                    @if ($data['report_type'] == 'Detailed')
                        <tr class="">
                            @foreach($data_row as $key1=>$value)
                                @if( $key1 != $omit_num )
                                    <td class="left">{{$data_row[$key1]}}</td>
                                @endif
                            @endforeach
                        </tr>
                    @else
                        <tr style="display: none">
                            <td colspan="7"></td>
                        </tr>
                    @endif
                @endforeach
          
                @if(!(($data['report_type'] == 'Detailed')&&($data['report_by'] == 'Status')))
                <tr class="">
                    <td colspan="7">Summary - Total: {{$data['summary_data'][$key]->total}}</td>
                </tr>
                <tr class="">                                  
                    <td><b>Completed</b></td>
                    <td><b>Opened</b></td>
                    <td><b>Escalated</b></td>
                    <td><b>Timeout</b></td>
                    <td><b>Canceled</b></td>
                    <td><b>Scheduled</b></td>
                    <td><b>Duration</b></td>
                </tr>
                <tr class="">                                
                    <td class="right">{{$data['summary_data'][$key]->completed}}</td>
                    <td class="right">{{$data['summary_data'][$key]->opened}}</td>
                    <td class="right">{{$data['summary_data'][$key]->escalated}}</td>
                    <td class="right">{{$data['summary_data'][$key]->timeout}}</td>
                    <td class="right">{{$data['summary_data'][$key]->canceled}}</td>
                    <td class="right">{{$data['summary_data'][$key]->scheduled}}</td>
                    <td class="right">{{$data['summary_data'][$key]->completed_time}}</td>
                </tr>
                @endif
                </tbody>
            </table>
        </div>
        <?php $m++;?>
    @endforeach
@endif

<!-- summary -->
<br>
@if($data['report_type'] == 'Summary' )
    @if( !empty($data['ticket_type']) )
        <div style="margin-top: 30px">
            <table>
                <tr>
                    <td>Filters:</td>
                    <td style="padding-left: 50px">
                        {{$data['ticket_type']}}
                    </td>
                </tr>
            </table>
        </div>
    @endif
    
    <?php
        $ticket_type = array('', 'Guest Request', 'Department Request', 'Complaint', 'Managed Task');
        $status_name = array('Completed', 'Open', 'Escalated', 'Timeout', 'Canceled', 'Scheduled');
        $omit_num = $data['omit_num'];
        $count_number = array();
        $m = 0;
    ?>
            
    @foreach ($data['ticket_list'] as  $key => $data_group)
    <?php
    $title_all = 0;
    $before_ticket_id = '0';
    ?>
    @foreach ($data_group as $row)
    <?php
    $ticket_id = sprintf('%05d', $row->id);
    if($before_ticket_id != $ticket_id)
        $title_all++;
    $before_ticket_id = $ticket_id;
    ?>
    @endforeach
    <?php
    $count_number[] = $title_all;
    ?>
    @endforeach
            
    <?php
    $all_count_number = count($data['ticket_list']);
    ?>
   <div>
        <table class="grid print-friendly" border = "0" style="width : 100%;" >
            <thead>
                <tr >
                    <th style="width: 100px;"><b>{{$data['report_by']}}</b></th>
                    @if($data['report_by'] == 'Staff')
                        <th><b>Department</b></th>
                        <th><b>Job Role</b></th>
                    @endif
                    <th><b>On Time %</b></th>
                    <th><b>Avg Time</b></th>
                    <th><b>Open</b></th>
                    <th><b>On Time</b></th>
                    <th><b>Escalated</b></th>
                    <th><b>Timeout</b></th>
                    <th><b>Closed</b></th>
                    <th><b>Canceled</b></th>
                    @if($data['report_by'] != 'Staff')
                        <th><b>Scheduled</b></th>
                       
                    @endif
                    <th><b>Total</b></th>
                </tr>
                <?php
                 $tot = 0;
                 $completed = 0;
                 $opened=0;
                 $escalated=0;
                 $timeout=0;
                 $closed=0;
                 $canceled=0;
                 $per = 0;
                 $percent = 0;
                 $avg = 0;
                 ?>
                 </thead>
            @foreach ($data['ticket_list'] as  $key => $data_group)

                <?php
                    $total = 0;
                    $total_complete_time = 0;
                    $complete_count = 0;
                    $ticket_count = array(0, 0, 0, 0, 0, 0, 0, 0, 0);
                    $before_ticket_id = '0';
                   

                ?>
                @foreach ($data_group as $row)
                    <?php
                        $ticket_id = sprintf('%05d', $row->id);

                        if($before_ticket_id != $ticket_id) {
                            $total++;
                         
                            if( $row->status_id == 0 && $row->proc_time >=0 )
                            {
                                $complete_count++;
                                $total_complete_time = $total_complete_time + $row->proc_time;
                            }
                        }
                        $before_ticket_id = $ticket_id;// store to compare the previously displayed values.
                    ?>
                @endforeach
                <?php
                    if( $complete_count > 0 )
                    {
                        $average_time = $total_complete_time / $complete_count;
                        $average_time = sprintf('%02d:%02d', ($average_time/60), $average_time%60);
                    }
                    else
                        $average_time = '--:--';
                ?>
                 <?php
                   $per  = $data['summary_data'][$key]->completed + $data['summary_data'][$key]->escalated + $data['summary_data'][$key]->timeout + $data['summary_data'][$key]->closed;

                   if ($per != 0){
                       $percent = ($data['summary_data'][$key]->completed /$per ) * 100;
                       $avg = $data['summary_data'][$key]->completed_time1/$per;

                   }
                   else{
                       $percent = 0;
                       $avg = 0;
                   }
                ?>
                <tbody>
                <tr class="">
                    <td class="left">
                        @if($data['report_by'] == 'Date')
                            {{date("d-M-Y",  strtotime($key))}}
                        @elseif($data['report_by'] == 'Ticket Type')
                            {{$ticket_type[$key]}}
                        @elseif($data['report_by'] == 'Status')
                            {{$key}} <b style="color: red"> ({{$count_number[$m]}} Tickets) </b>
                        @else
                            {{$key}}
                        @endif
                    </td>
                    @if($data['report_by'] == 'Staff')
                        <td class="right">{{$data['summary_data'][$key]->department}}</td>
                        <td class="right">{{$data['summary_data'][$key]->job_role}}</td>
                    @endif
                    
                    <td class="right">{{number_format($percent , 2)}}</td>
                    <td class="right">{{gmdate("H:i:s", $avg)}}</td>
                    <td class="right">{{$data['summary_data'][$key]->opened}}</td>
                    <td class="right">{{$data['summary_data'][$key]->completed}}</td>
                    <td class="right">{{$data['summary_data'][$key]->escalated}}</td>
                    <td class="right">{{$data['summary_data'][$key]->timeout}}</td>
                    <td class="right">{{$data['summary_data'][$key]->closed}}</td>
                    <td class="right">{{$data['summary_data'][$key]->canceled}}</td>
                    @if($data['report_by'] != 'Staff')
                    <td class="right">{{$data['summary_data'][$key]->scheduled}}</td>
                    @endif
                    <td class="right">{{$data['summary_data'][$key]->total}}</td>
                </tr>                            
                <?php $m++;
                $tot += $data['summary_data'][$key]->total;
                $completed += $data['summary_data'][$key]->completed;
                $opened += $data['summary_data'][$key]->opened;
                $escalated += $data['summary_data'][$key]->escalated;
                $timeout += $data['summary_data'][$key]->timeout;
                $closed += $data['summary_data'][$key]->closed;
                $canceled += $data['summary_data'][$key]->canceled;
                ?>
            @endforeach
            @if($data['report_by'] == 'Staff')
            <tr class="">
                    <td style="text-align:left; background-color:#CFD8DC" class="right"></td>
                    <td style="text-align:left; background-color:#CFD8DC" class="right"></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"><b>Total</b></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$opened}}</b></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$completed}}</b></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$escalated}}</b></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$timeout}}</b></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$closed}}</b></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$canceled}}</b></td>
                    <td style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$tot}}</b></td>
                    
            </tr>  
            @endif          
            </tbody>
        </table>                        
    </div>    
@endif
<br>
@if ($data['report_type'] == 'Summary' && $data['report_by'] == 'Department')
@if(!empty($data['completed']))
@foreach ($data['completed'] as $key => $datagroup)
<div>
@if(!empty($datagroup['date']))
<p style="margin : 0px; font-size:9px;"><b style="font-size:9px;">Date : {{date("d-M-y",  strtotime($key))}}</b></p>
@foreach ($datagroup['date'] as  $dept_key => $dept_group)

    <p style="margin: 0px;font-size:9px;"><b style="margin : 0px; font-size:9px;">Department :</b> {{$dept_key}}</p>
    @foreach ($dept_group['department'] as  $status_key => $status_group)
    <?php
                if ( $status_key == 0)
                    $state = 'On Time';
                else if ($status_key == 1)
                    $state = 'Open';
                else if ($status_key == 2)
                    $state = 'Escalated';
                else if ($status_key == 3)
                    $state = 'Timeout';
                else if  ($status_key == 4)
                    $state = 'Canceled';
                else if  ($status_key == 6)
                    $state = 'Escalated';
                else if  ($status_key == 7)
                    $state = 'Closed';
                else
                    $state = 'Hold'; 
    ?>
    
           @if ($state == 'On Time')
           <p style="font-size:8px;color:Green;margin: 0px"><b>{{$state}}</b> - (Requests that have been completed within the allotted duration) </p> 
            @elseif($state == 'Open')
            <p style="font-size:8px;color:Blue;margin: 0px"><b>{{$state}}</b></p> 
            @elseif($state == 'Escalated')
            <p style="font-size:8px;color:Orange;margin: 0px"><b>{{$state}}</b></p> 
            @elseif($state == 'Timeout')
            <p style="font-size:8px;color:Red;margin: 0px"><b>{{$state}}</b> - (Requests that haven't been completed after the allotted duration and escalation)</p>  
            @elseif($state == 'Canceled')
            <p style="font-size:8px;color:Grey;margin: 0px"><b>{{$state}}</b></p> 
            @elseif($state == 'Closed')
            <p style="font-size:8px;color:Grey;margin: 0px"><b>{{$state}}</b> - (Requests that have been completed after getting timed-out)</p> 
            @else
            <p style="font-size:8px;color:Orange;margin: 0px"><b>{{$state}}</b></p>  
            @endif
    <table class="grid print-friendly" border="0" style="width : 100%;" >
        <thead style="background-color:#3c6f9c" >
        
        <tr>
            <th><b>ID</b></th>
            <th><b>Request</b></th>
            <th><b>Location</b></th>
            <th><b>Created</b></th>
            <th><b>Assignee</b></th>
            <th><b>Assign Dur</b></th>
            <th><b>Actual Dur</b></th>
            <th><b>Completed By</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($status_group['status'] as $row)
            <tr class="">            
                <?php
                if ($row->type == 1)
                    $id = "G".$row->id;
                else if ($row->type == 2)
                    $id = "D".$row->id;
                else if ($row->type == 3)
                    $id = "C".$row->id;
                else
                    $id = "M".$row->id;
                ?>
                <td >{{$id}}</td>
                <td >{{$row->task}}</td>
                <td >{{$row->lgm_type}}  {{$row->lgm_name}}</td>
                <td >{{$row->time}}</td>
                <td >{{$row->attendant_wholename}}</td>
                <td >{{gmdate("H:i:s", $row->max_time)}}</td>
                <td >{{gmdate("H:i:s", $row->duration)}}</td>
                <td >{{$row->finisher_wholename}}</td>
            </tr>
            @endforeach
        </tbody>    
  </table>
  <br>
  @endforeach
@endforeach
@endif
</div>
@endforeach
@endif
@endif

@endif

<br>
@if ($data['report_by'] == 'Service Category')
@if(!empty($data['summary_list']))
@foreach ($data['summary_list'] as $key => $datagroup)
<div>    
<p style="margin : 0px"><b>Category : {{$key}}</b></p>
    <table class="grid print-friendly" border="0" style="width : 100%;" >
        <thead style="background-color:#3c6f9c" >
        
        <tr>
        
                    <th><b>Task</b></th>
                    <th><b>Total</b></th>
                    <th><b>On Time</b></th>
                    <th><b>Open</b></th>
                    <th><b>Escalated</b></th>
                    <th><b>Timeout</b></th>
                    <th><b>Canceled</b></th>
                  
                  
        </tr>
        <?php
                 $tot = 0;
                 $completed = 0;
                 $opened=0;
                 $escalated=0;
                 $timeout=0;
                 $canceled=0;
                 ?>
        </thead>
        <tbody>
        @foreach ($datagroup as $row)
            <tr class="">            
                <td >{{$row->group_key}}</td>
                <td >{{$row->total}}</td>
                <td >{{$row->completed}}</td>
                <td >{{$row->opened}}</td>
                <td >{{$row->escalated}}</td>
                <td >{{$row->timeout}}</td>
                <td >{{$row->canceled}}</td>
            </tr>
            <?php 
                $tot += $row->total;
                $completed += $row->completed;
                $opened += $row->opened;
                $escalated += $row->escalated;
                $timeout += $row->timeout;
                $canceled += $row->canceled;
            ?>
            @endforeach
            <tr class="">
                    <td style=" background-color:#CFD8DC" ><b>Total</b></td>
                    <td style=" background-color:#CFD8DC" ><b>{{$tot}}</b></td>
                    <td style=" background-color:#CFD8DC" ><b>{{$completed}}</b></td>
                    <td style=" background-color:#CFD8DC" ><b>{{$opened}}</b></td>
                    <td style=" background-color:#CFD8DC" ><b>{{$escalated}}</b></td>
                    <td style=" background-color:#CFD8DC" ><b>{{$timeout}}</b></td>
                    <td style=" background-color:#CFD8DC" ><b>{{$canceled}}</b></td>
                    
            </tr>  
        </tbody>    
  </table>
  <br>

</div>
@endforeach
@endif
@if ($data['report_type'] == 'Detailed')
@if(!empty($data['tasklist']))
<p align="center" style="font-size:8px;margin : 0px"><b>Task List by Category</b></p>
@foreach ($data['tasklist'] as $key => $datagroup)
<div>    
<p style="margin : 0px"><b>Category : {{$key}}</b></p>
    <table class="grid print-friendly" border="0" style="width : 100%;" >
        <thead style="background-color:#3c6f9c" >
        
        <tr>
            <th><b>Ticket</b></th>
            <th><b>Reported On</b></th>
            <th><b>Reported By</b></th>
            <th><b>Request</b></th>
            <th><b>Quantity</b></th>
            <th><b>Location</b></th>
            <th><b>Guest Affected</b></th>
            <th><b>VIP</b></th>
            <th><b>Status</b></th>
            <th><b>Completed By</b></th>
            <th><b>Completed On</b></th>
            <th><b>Assigned To</b></th>
            <th><b>Resolution</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($datagroup as $row)
            <tr class="">            
                <?php
                if ($row->type == 1)
                    $id = "G".$row->id;
                else if ($row->type == 2)
                    $id = "D".$row->id;
                else if ($row->type == 3)
                    $id = "C".$row->id;
                else
                    $id = "M".$row->id;
                if ( $row->status == 0)
                    $state = 'Completed';
                else if ($row->status == 1)
                    $state = 'Open';
                else if ($row->status == 2)
                    $state = 'Escalated';
                else if ($row->status == 3)
                    $state = 'Timeout';
                else if  ($row->status == 4)
                    $state = 'Canceled';
                else
                    $state = 'Hold'; 
                ?>
                <td >{{$id}}</td>
                <td >{{$row->start_date_time}}</td>
                <td >{{$row->requester_name}}</td>
                <td >{{$row->task}}</td>
                <td >{{$row->quantity}}</td>
                <td >{{$row->lgm_type}}  {{$row->lgm_name}}</td>
                <td >{{$row->guest_name}}</td>
                <td >{{$row->vip}}</td>
                <td >{{$state}}</td>
                <td >{{$row->finished_wholename}}</td>
                <td >{{$row->end_date_time}}</td>
                <td >{{$row->assigned_wholename}}</td>
                <td >{{$row->duration_time}}</td>
            </tr>
            @endforeach
        </tbody>    
  </table>
  <br>

</div>
@endforeach
@endif
@endif
@endif
