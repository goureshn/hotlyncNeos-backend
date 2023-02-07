<?php
function converDate($val) {
    $date_val = date_format(new DateTime($val),'d-M-Y');
    return  $date_val;
}

function convertDateTime($val) {
    $date_val = date_format(new DateTime($val),'d-M-Y H:i:s');
    return  $date_val;
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
        return 'F00000';
    return sprintf('F%05d', $ticket->parent_id);
}

function  getTicketNumberComp ($ticket){
    if(!$ticket)
        return 'F00000';
    return sprintf('F%05d', $ticket->id);
}

$group_val = $data['group_by'];
$rpeort_type = $data['report_type'];
$currency = $data['currency'];
?>
<br><br>
@if (!empty($data['comptotal_list']))
<table style="width: 100%;" border = 1;>
                <tr>
                    <th>Department</th>
                    <th>Total Compensation Cost</th>
                   
                </tr>
                @foreach ($data['comptotal_list'] as  $key => $obj)
             
                    <tr>
                        <td align="center">{{$key}}</td>
                        <td align="right"><b>{{$data['currency']}}</b> {{number_format($obj['Total'],2)}} </td> 
                      
                    </tr>
                @endforeach
                
</table>
@endif
@if($rpeort_type == 'Detailed')
@if(!empty($data['comp_list']))
    <div >
        <p align="center"><strong>Detailed Compensation Report by Department</strong></p>
    </div>
    @foreach ($data['comp_list'] as  $key => $obj)
    <div >
        <p style="margin-bottom: 2px"><b>{{$group_val}} : {{$key}}</b></p>
    </div>

        <table  style="width: 100%;" border = 1;>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Provided by</th>
                    <th>Location</th>
                    <th>Cost({{$data['currency']}})</th>
                </tr>
                <?php
                $total = 0;
                ?>
           
                @foreach ($obj as $sub)
                @if($sub->sub_cost > 0)
                    <tr>
                        <td align="center" style="width: 10%;">{{getTicketNumber($sub)}}{{$sub->sub_label}}</td>
                        <td align="center" style="width: 20%;"> {{$sub->status1}}</td>
                        <td align="center" style="width: 20%;"> {{$sub->sub_provider}} </td>
                        <td align="center" style="width: 40%;"> {{$sub->location_name}} {{$sub->location_type}} </td>
                        <td align="right" style="width: 10%;"> <b>{{$data['currency']}}</b>{{number_format($sub->sub_cost,2)}} </td>
                    </tr>
                @endif
                    <?php
                $total += $sub->sub_cost;
                ?>
                @endforeach
                <tr style = "background-color:#d3d3d3">
                        <td align="right" colspan="4">Total</td>
                        
                        <td align="right"> <b>{{$data['currency']}}{{number_format($total,2)}} </b></td>
                    </tr>
            </table>    
    @endforeach
@endif
@endif 



               
              
          
      
      

