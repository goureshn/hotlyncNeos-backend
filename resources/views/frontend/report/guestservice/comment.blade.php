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
@if(!empty($data['completed_comment']))
@foreach ($data['completed_comment'] as $key => $datagroup)
<div>
@if(!empty($datagroup['date']))
<p style="margin : 0px; font-size:9px;"><b style="font-size:9px;">{{$key}}</b></p>
@foreach ($datagroup['date'] as  $dept_key => $dept_group)

    <p style="margin: 0px;font-size:9px;"><b style="margin : 0px; font-size:9px;">Department :</b> {{$dept_key}}</p>
   
   
    
         
    <table class="grid print-friendly" border="0" style="width : 100%;" >
        <thead style="background-color:#3c6f9c" >
        
        <tr>
            <th><b>ID</b></th>
            <th><b>Request</b></th>
            <th><b>Location</b></th>
            <th><b>On Time</b></th>
            <th><b>Created Time</b></th>
            <th><b>Assignee</b></th>
            <th><b>Assign Dur</b></th>
            <th><b>Actual Dur</b></th>
            <th><b>Completed By</b></th>
            <th><b>Completed Comment</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($dept_group['department'] as $row)
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

                   
                if ( $row->status_id == 0)
                    $state = 'On Time';
                else
                    $state = 'Escalated'; 
   
                ?>
                <td style="text-align:center" >{{$id}}</td>      
                <td style="text-align:center" >{{$row->task}}</td>
                <td style="text-align:center" >{{$row->lgm_type}}  {{$row->lgm_name}}</td>
                @if ($state == 'Escalated')
                <td style="text-align:center" >No</td>
                @else
                <td style="text-align:center" >Yes</td>
                @endif
                <td style="text-align:center" >{{$row->time}}</td>
                <td style="text-align:center" >{{$row->attendant_wholename}}</td>
                <td style="text-align:center" >{{gmdate("H:i:s", $row->max_time)}}</td>
                <td style="text-align:center" >{{gmdate("H:i:s", $row->duration)}}</td>
                <td style="text-align:center" >{{$row->finisher_wholename}}</td>
                <td style="text-align:center" >{{$row->log_comment}}</td>
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




