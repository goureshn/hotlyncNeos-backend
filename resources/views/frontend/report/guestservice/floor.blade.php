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



@if ($data['report_type'] == 'Detailed')
@if(!empty($data['completed_floor']))
@foreach ($data['completed_floor'] as $key => $datagroup)
<div>
@if(!empty($datagroup['floor']))
<p style="margin : 0px; font-size:9px;"><b style="font-size:9px;">{{$key}}</b></p>
@foreach ($datagroup['floor'] as  $dept_key => $dept_group)

    <p style="margin: 0px;font-size:9px;"><b style="margin : 0px; font-size:9px;">Location :</b> {{$dept_key}}</p>
   
   
    
         
    <table class="grid print-friendly" border="0" style="width : 100%;" >
        <thead style="background-color:#3c6f9c" >
        
        <tr>
            <th><b>ID</b></th>
            <th><b>Request</b></th>
            <th><b>Status</b></th>
            <th><b>Created</b></th>
            <th><b>Assignee</b></th>
            <th><b>Assign Dur</b></th>
            <th><b>Actual Dur</b></th>
            <th><b>Completed By</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($dept_group['location'] as $row)
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
                    $state = 'On Time';
                else if ($row->status == 1)
                    $state = 'Open';
                else if ($row->status == 2)
                    $state = 'Escalated';
                else if ($row->status == 3)
                    $state = 'Timeout';
                else if  ($row->status == 4)
                    $state = 'Canceled';
                else if  ($row->status == 6)
                    $state = 'Escalated';
                else if  ($row->status == 7)
                    $state = 'Closed';
                else
                    $state = 'Hold'; 
   
                ?>
                <td >{{$id}}</td>      
                <td >{{$row->task}}</td>
                <td >{{$state}}</td>
                <td >{{$row->start_date_time}}</td>
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
@endif
</div>
@endforeach
@endif
@endif

<br>
@if(!empty($data['summary_data']))

<div>
  
         
    <table class="grid print-friendly" border="0" style="width : 100%;" >
        <thead style="background-color:#3c6f9c" >
        
        <tr>
            <th><b>Floor</b></th>
            <th><b>Building</b></th>
            <th><b>On Time</b></th>
            <th><b>Open</b></th>
            <th><b>Escalated</b></th>
            <th><b>Timeout</b></th>
            <th><b>Closed</b></th>
            <th><b>Canceled</b></th>
            <th><b>Total</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['summary_data'] as $row)
            <tr class="">      
           
                <td >Floor {{$row->group_key1}}</td>     
                <td >{{$row->building}}</td>  
                <td >{{$row->completed}}</td>
                <td >{{$row->opened}}</td>
                <td >{{$row->escalated}}</td>
                <td >{{$row->timeout}}</td>
                <td >{{$row->closed}}</td>
                <td >{{$row->canceled}}</td>
                <td >{{$row->total}}</td>
            </tr>
        @endforeach
        </tbody>    
  </table>
  <br>
  

</div>

@endif



