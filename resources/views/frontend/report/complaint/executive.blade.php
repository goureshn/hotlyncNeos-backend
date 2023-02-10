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
$group_val = $data['group_by'];
$repor_by = $data['report_by'];
$rpeort_type = $data['report_type'];
$no = 1;
$total = 0;

function  getTicketNumberComp ($ticket){
    if(!$ticket)
        return 'F00000';
    return sprintf('F%05d', $ticket->id);
}

function converDate($val) {
    $date_val = date_format(new DateTime($val),'d-M-Y');
    return  $date_val;
}

function convertDateTime($val) {
    $date_val = date_format(new DateTime($val),'d-M-Y H:i:s');
    return  $date_val;   
}

function  getTicketNumber ($ticket){
    if(!$ticket)
        return 'F00000';
    return sprintf('F%05d', $ticket->parent_id);
}

function isCheckNull($val) {
    if($val == null) {
        return "";
    }else {
        return $val;
    }
}
?>

@foreach ($data['complaint_list'] as  $key => $obj)
    @if(!empty($obj))
        <br>
        <div>
            <table >
                <tr>
                    <td class="text-md" ><b>Date : </b> {{converDate($key)}}</td>
                </tr>
            </table>
            <br>
        </div>
        @foreach ($obj as $row)

            <div>
                <table  style="width : 100%" border="0">
                    <tbody>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Complaint ID :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{getTicketNumberComp($row)}}</td>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Status :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{$row->status}}</td>             
                        </tr>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Type :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{$row->feedback_type}}</td>              
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Source :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{$row->feedback_source}}</td>
                        </tr>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Incident Location :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{$row->location}}</td>              
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Incident Time :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{convertDateTime($row->incident_time)}}</td>
                        </tr>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Guest Name :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{$row->guest_fullname}}</td>              
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Guest Type :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{$row->guest_type}}</td>
                        </tr>
                        @if($row->guest_type=="In-House" || $row->guest_type=="Checkout") 
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Room :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{$row->room}}</td>              
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Stay :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{converDate($row->arrival) }} to {{converDate($row->departure)}}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Category :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{$row->category_name}}</td>              
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Sub-Category :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{$row->subcategory_name}}</td>
                        </tr>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Severity :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{$row->severity_name}}</td>              
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Raised By :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{$row->wholename}}</td>
                        </tr>
                    </tbody>
                </table>
                <table style="width : 100%" border="0">
                    <tbody>
                        @if(!empty($row->department_tags))
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Tagged Department :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="80%">{{$row->department_tags}}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="border-style : hidden!important; vertical-align:top" align="right" width="20%"><span style="font-weight:bold;">Guest Feedback :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important" width="80%">{!! nl2br(e($row->comment_highlighted)) !!}</td>
                        </tr>
                        <tr>
                            <td style="border-style : hidden!important; vertical-align:top" align="right" width="20%"><span style="font-weight:bold;">Initial Response :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important" width="80%">{!!nl2br(e( $row->initial_response_highlighted)) !!}</td>
                        </tr>
                        @if(!empty($row->solution))
                        <tr>
                            <td style="border-style : hidden!important; vertical-align:top" align="right" width="20%"><span style="font-weight:bold;">Resolution :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="80%">{!! $row->solution !!}</td>
                        </tr>
                        @endif
                        @if(!empty($row->closed_comment))
                        <tr> 
                            <td style="border-style : hidden!important; vertical-align:top" align="right" width="20%"><span style="font-weight:bold;">Investigation :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="80%">{!! nl2br(e($row->closed_comment)) !!}</td>
                        </tr>
                        @endif

                      
                        @if(!empty($row->base64_image_list))
                        <tr> 
                            <td style="border-style : hidden!important; vertical-align:top" align="right" width="20%"><span style="font-weight:bold;">Attachment(s) :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="80%">
                                @foreach($row->base64_image_list as $row1)                                                          
                                    <a href="{{$row1['url']}}" target="_blank"><img style="width:20px;height:20px;" src="{{$row1['base64']}}"/></a>                                
                                @endforeach   
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                @if(!empty($row->comment_list))
                        
                        <br><span style="font-size: 12px"><b>COMMENTS</b></span><br>
                        <table border="1%" style="width: 100%;">
                            <thead style="background-color:#ffffff">
                                <tr class = "plain">
                                    <th class="subtitle"><b>No</b></th> 
                                    <th class="subtitle"><b>Date</b></th>                           
                                    <th class="subtitle"><b>Comments</b></th>                            
                                    <th class="subtitle"><b>User</b></th> 
                                   
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $no = 1;
                                ?>
                                @foreach ($row->comment_list as $row1 )
                                <tr>
                                    <td align="center"><span></span>{{$no}}</td>
                                    <td align="center"><span>{{isCheckNull($row1->created_at)}}</span></td>
                                    <td align="center"><span>{{isCheckNull($row1->comment)}}</span></td>                            
                               
                                    <td align="center"><span>{{isCheckNull($row1->commented_by)}}</span></td>
                                    
                                </tr>
                                <?php
                                    $no++;  
                                ?>
                                @endforeach
                            </tbody>
                        </table>
                        @endif

                @if(!empty($row->compensation) || !empty($row->subcompensation))
                <br><span style="font-size: 12px"><b>SERVICE RECOVERY</b></span><br>
                <table border="1%" style="width: 100%;">
                    <thead style="background-color:#ffffff">
                        <tr class = "plain">
                            <th class="subtitle"><b>No</b></th> 
                            <th class="subtitle"><b>Date</b></th>                           
                            <th class="subtitle"><b>Compensation</b></th>                            
                        @if(count($row->subcompensation) > 0)
                            <th class="subtitle"><b>Location - Department</b></th> 
                        @endif
                            <th class="subtitle"><b>Provided By</b></th> 
                            <th class="subtitle"><b>Amount({{$data['currency']}})</b></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $no = 1;
                            $total = 0;
                        ?>
                        @foreach ($row->compensation as $row1 )
                        <tr>
                            <td align="center"><span></span>{{$no}}</td>
                            <td align="center"><span>{{isCheckNull($row1->created_at)}}</span></td>
                            <td align="center"><span>{{isCheckNull($row1->item_name)}}</span></td>                            
                        @if(count($row->subcompensation) > 0)
                            <td align="center"></td>                            
                        @endif
                            <td align="center"><span>{{isCheckNull($row1->provider)}}</span></td>
                            <td align="right"><span>{{isCheckNull($row1->cost)}}</span></td>
                        </tr>
                        <?php
                            $no++;
                            $total = $total + $row1->cost;
                        ?>
                        @endforeach

                        @foreach ($row->subcompensation as $row1 )
                        <tr>
                            <td align="center"><span></span>{{$no}}</td>
                            <td align="center"><span>{{isCheckNull($row1->created_at)}}</span></td>
                            <td align="center"><span>{{isCheckNull($row1->item_name)}}</span></td>
                            <td align="center"><span></span>{{isCheckNull($row1->location)}}-{{isCheckNull($row1->department)}}</td>
                            <td align="center"><span>{{isCheckNull($row1->provider)}}</span></td>
                            <td align="right"><span>{{isCheckNull($row1->cost)}}</span></td>
                        </tr>
                        <?php
                            $no++;
                            $total = $total + $row1->cost;
                        ?>
                        @endforeach
                        <tr>
                        @if(count($row->subcompensation) > 0)
                            <td align="right" colspan = '5'><span><b>Total</span></b></td>
                        @else
                            <td align="right" colspan = '4'><span><b>Total</span></b></td>
                        @endif
                            <td align="right"><span><b>{{$data['currency']}} {{isCheckNull($total)}}</b></span></td>
                        </tr>
                    </tbody>
                </table>
                @endif
                
                @if($row->subcomplaintcount > 0)
                <br><span style="font-size: 12px"><b>SUB COMPLAINT COUNT :{{$row->subcomplaintcount}}</b></span><br>
                @foreach ($row->subcomplaint as $sub)
                <table  style="width : 100%" border="0">
                    <tbody>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Sub-complaint ID :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{getTicketNumber($sub)}}{{$sub->sub_label}} - {{$sub->department}}</td>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Status :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{$sub->status}}</td>             
                        </tr>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Category :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{$sub->category_name}}</td>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Sub-Category :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{$sub->subcategory_name}}</td>             
                        </tr>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Location/Department :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="30%">{{$sub->location_name}}&nbsp;{{$sub->department}}</td>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Created By :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="30%">{{$sub->created_by}}</td>             
                        </tr>
                    </tbody>
                </table>
                <table style="width : 100%" border="0">
                    <tbody>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Investigation :&nbsp;&nbsp;</span></td>                
                            <td style="border-style : hidden!important;" width="80%">{{$sub->comment}}</td>
                        </tr>
                        <tr>
                            <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Internal Action :&nbsp;&nbsp;</span></td>
                            <td style="border-style : hidden!important;" width="80%">{{$sub->resolution}}</td>             
                        </tr>
                    </tbody>
                </table>

                @endforeach
                @endif

            </div>
            <hr>
        @endforeach
    @endif
    
@endforeach
