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
    return sprintf('F%05d', $ticket->complaint_id);
}

$group_val = $data['group_by'];
$rpeort_type = $data['report_type'];
$currency = $data['currency'];
?>
<br><br>
@if($rpeort_type == 'Detailed')
    @foreach ($data['complaint_list'] as  $key => $obj)
        @foreach ($obj as $data)
            @if(!empty($data->compensation))
            <hr>
            <div>
                <p><strong>{{$group_val}} : {{$key}}</strong></p>
            </div>
            <div>
                <table style="width: 100%;">
                     <tr>
                        <td>                      
                         @foreach ($data->compensation as $comp) 
                            <table style="width:100%">                           
                             <tr><td colspan="2"><p><b>Ticket#:&nbsp;&nbsp;</b>{{getTicketNumberComp($comp)}}</p></td></tr>
                             <tr>
                                <td style="width: 50%;">
                                     <table style="width: 100%;" border="0">
                                        <tr><td class="items">Compensation:</td> <td> {{$comp->item_name}}</td></tr>
                                        <tr><td class="items">Comment:</td>      <td> {{$comp->comment}}</td></tr>
                                        <tr><td class="items">Provided By:</td>  <td> {{$comp->provider}}</td></tr>
                                    </table>
                                </td>
                                <td style="width: 50%">
                                    <table style="width: 100%;" border="0">                               
                                        <tr><td class="items">Cost:</td>    <td> {{$comp->cost}}<b>{{$currency}}</b></td></tr>
                                        <tr><td class="items">Date:</td>    <td> {{converDate($comp->created_at)}}</td></tr>
                                        <tr><td class="items">Status:</td>  <td> {{compensationStatus($comp->status)}}</td></tr>
                                    </table>
                                </td>
                            </tr>
                            </table>
                          @endforeach  
                          </td>
                    </tr>
                    <tr>                        
                        <td style="width: 50%;">
                            <table style="width: 100%;" border="0">                                
                                <tr><td class="items">Created by:</td>          <td> {{$data->wholename}}</td></tr>
                                <tr><td class="items"style="vertical-align: middle">Complaint:</td>          <td> {{$data->comment}}</td></tr>
                                <tr><td class="items" >Severity:</td>          <td> {{$data->serverity_name}}</td></tr>
                                <tr><td class="items">Cateogry:</td>            <td> {{$data->category_name}}</td></tr>
                            </table>
                        </td>                       
                    </tr>
                    <tr><td colspan="2" style="height: 10px;"> &nbsp;</td> </tr>
                    <tr style="border:0;">
                        <td colspan="2">
                             <!--sub complaint list loop-->
                             @if(!empty($data->subcomplaint))
                            <p><b>Sub-complaint</b></p>
                            <table style="width:100%;">
                                <tr>
                                    <th>Date Created</th>
                                    <th>Sub-complaint</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Severity</th>
                                    <th>Category</th>
                                    <th>Sub-category</th>
                                </tr>
                                @foreach ($data->subcomplaint as $sub)
                                    <tr>
                                        <td align = "center">{{convertDateTime($sub->created_at)}}</td>
                                        <td align = "center">{{$sub->complaint_name}}</td>
                                        <td align = "center">{{$sub->department}}</td>
                                        <td align = "center">{{$sub->status}}</td>
                                        <td align = "center">{{$sub->type}}</td> 
                                        <td align = "center"> {{$sub->category_name}}</td>
                                        <td align = "center">{{$sub->subcategory_name}}</td>
                                    </tr>
                                @endforeach
                            </table>
                            @endif
                            <!--sub complaint list end-->
                        </td>
                    </tr>
                </table>
            </div>
            @endif
        @endforeach
    @endforeach
@endif


@if($rpeort_type == 'Summary')
 @foreach ($data['complaint_list'] as  $key => $obj)
        @foreach ($obj as $data)
            @if(!empty($data->compensation))
                <div>
                    <b>{{$group_val}} : {{$key}}</b>
                </div>
                <table style="width: 100%;">
                <tr>
                    <th>Compensation</th>
                    <th>Cost</th>
                    <th>Provided by</th>
                </tr>
           
                @foreach ($data->compensation as  $comp)
                    <tr>
                        <td align="center">{{$comp->item_name}}</td>
                        <td align="center"> {{$comp->cost}}<b>{{$currency}}</b> </td>
                        <td align="center"> {{$comp->provider}} </td>
                    </tr>
                @endforeach
            </table>
           @endif 
        @endforeach
    @endforeach        
@endif
