
<?php

function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}
$date_val = '';
$origin_department = '';
$count=0;
$de_approvedcall = 0;
$de_approvedcharge = 0;
$de_unapprovedcount = 0;
$de_unapprovedcharge = 0;
$de_awaitingcount = 0;
$de_awaitingcharge = 0;
$de_unmarkedcount = 0;
$de_unmarkedcharge = 0;
$de_totalcount = 0;
$de_totalcharge = 0;

$all_approvedcall = 0;
$all_approvedcharge = 0;
$all_unapprovedcount = 0;
$all_unapprovedcharge = 0;
$all_awaitingcount = 0;
$all_awaitingcharge = 0;
$all_unmarkedcount = 0;
$all_unmarkedcharge = 0;
$all_totalcount = 0;
$all_totalcharge = 0;
if(!empty($data['summary']))
$summay_maxcount = count($data['summary']);

?>

@if( $data['report_type'] == 'Summary')
    @foreach ($data['summary'] as $key => $row)
        <div>
            @if($origin_department != $row->depart_name)
            @if($count!=0)
         </tbody>
                    </table>    

                    <table  style="width : 100%;margin-top: 10px;">
                        <thead>
                        <tr>
                            <th class="subth"><b>Extension</b></th>
                            <th class="subth"><b>Approved(Call)/Charge</b></th>
                            <th class="subth"><b>Rejected(Call)/Charge</b></th>
                            <th class="subth"><b>Awaiting(Call)/Charge</b></th>
                            <th class="subth"><b>Unmarked(Call)/Charge</b></th>
                            <th class="subth"><b>Total Calls</b></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr class="">
                            <td>Total</td>
                            <td class="right">{{$de_approvedcall}}/{{number_format($de_approvedcharge,2)}}</td>
                            <td class="right">{{$de_unapprovedcount}}/{{number_format($de_unapprovedcharge,2)}}</td>
                            <td class="right">{{$de_awaitingcount}}/{{number_format($de_awaitingcharge,2)}}</td>
                            <td class="right">{{$de_unmarkedcount}}/{{number_format($de_unmarkedcharge,2)}}</td>
                            <td class="right">{{$de_totalcount}}/{{number_format($de_totalcharge,2)}}</td>
                        </tr>
                        </tbody>
                    </table>
                <?php
                    $all_approvedcall += $de_approvedcall;
                    $all_approvedcharge += $de_approvedcharge;
                    $all_unapprovedcount += $de_unapprovedcount;
                    $all_unapprovedcharge += $de_unapprovedcharge;
                    $all_awaitingcount += $de_awaitingcount;
                    $all_awaitingcharge += $de_awaitingcharge;
                    $all_unmarkedcount += $de_unmarkedcount;
                    $all_unmarkedcharge += $de_unmarkedcharge;
                    $all_totalcount += $de_totalcount;
                    $all_totalcharge +=  $de_totalcharge;

                    $de_approvedcall = 0;
                    $de_approvedcharge = 0;
                    $de_unapprovedcount = 0;
                    $de_unapprovedcharge = 0;
                    $de_awaitingcount = 0;
                    $de_awaitingcharge = 0;
                    $de_unmarkedcount = 0;
                    $de_unmarkedcharge = 0;
                    $de_totalcount = 0;
                    $de_totalcharge = 0;
                ?>
                @endif
               
                 <p style="margin: 0px"><b> Department : {{$row->depart_name}}</b> </p>
                    <table class="grid"  style="width : 100%;">
                        <thead style="background-color:#3c6f9c">
                        <tr>
                            <th><b>Extension</b></th>
                            <th><b>Approved(Call)/Charge</b></th>
                            <th><b>Rejected(Call)/Charge</b></th>
                            <th><b>Awaiting(Call)/Charge</b></th>
                            <th><b>Unmarked(Call)/Charge</b></th>
                            <th><b>Total Calls</b></th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr class="">
                                <td>{{$row->extension}} - {{$row->user}}</td>
                                <td class="right">{{$row->approvedcount}}/{{number_format($row->approvedcharge,2)}}</td>
                                <td class="right">{{$row->unapprovedcount}}/{{number_format($row->unapprovedcharge,2)}}</td>
                                <td class="right">{{$row->awaitingcount}}/{{number_format($row->awaitingcharge,2)}}</td>
                                <td class="right">{{$row->unmarkedcount}}/{{number_format($row->unmarkedcharge,2)}}</td>
                                <td class="right">{{$row->totalcount}}/{{number_format($row->totalcharge,2)}}</td>
                            </tr>
                            <?php
                            $de_approvedcall += $row->approvedcount;
                            $de_approvedcharge += $row->approvedcharge;
                            $de_unapprovedcount += $row->unapprovedcount;
                            $de_unapprovedcharge += $row->unapprovedcharge;
                            $de_awaitingcount += $row->awaitingcount;
                            $de_awaitingcharge += $row->awaitingcharge;
                            $de_unmarkedcount += $row->unmarkedcount;
                            $de_unmarkedcharge += $row->unmarkedcharge;
                            $de_totalcount += $row->totalcount;
                            $de_totalcharge += $row->totalcharge;
                            ?>

            @else
                            <tr class="">
                                <td>{{$row->extension}} - {{$row->user}}</td>
                                <td class="right">{{$row->approvedcount}}/{{number_format($row->approvedcharge,2)}}</td>
                                <td class="right">{{$row->unapprovedcount}}/{{number_format($row->unapprovedcharge,2)}}</td>
                                <td class="right">{{$row->awaitingcount}}/{{number_format($row->awaitingcharge,2)}}</td>
                                <td class="right">{{$row->unmarkedcount}}/{{number_format($row->unmarkedcharge,2)}}</td>
                                <td class="right">{{$row->totalcount}}/{{number_format($row->totalcharge,2)}}</td>
                            </tr>
                            <?php
                            $de_approvedcall += $row->approvedcount;
                            $de_approvedcharge += $row->approvedcharge;
                            $de_unapprovedcount += $row->unapprovedcount;
                            $de_unapprovedcharge += $row->unapprovedcharge;
                            $de_awaitingcount += $row->awaitingcount;
                            $de_awaitingcharge += $row->awaitingcharge;
                            $de_unmarkedcount += $row->unmarkedcount;
                            $de_unmarkedcharge += $row->unmarkedcharge;
                            $de_totalcount += $row->totalcount;
                            $de_totalcharge += $row->totalcharge;
                            ?>    
                    
                    @if(($summay_maxcount-1) == $count)
                    </tbody>
                            </table>
                        <table  style="width : 100%;margin-top: 10px;">
                            <thead>
                            <tr>
                                <th class="subth"><b>Extension</b></th>
                                <th class="subth"><b>Approved(Call)/Charge</b></th>
                                <th class="subth"><b>Rejected(Call)/Charge</b></th>
                                <th class="subth"><b>Awaiting(Call)/Charge</b></th>
                                <th class="subth"><b>Unmarked(Call)/Charge</b></th>
                                <th class="subth"><b>Total Calls</b></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="">
                                <td>Total</td>
                                <td class="right">{{$de_approvedcall}}/{{number_format($de_approvedcharge,2)}}</td>
                                <td class="right">{{$de_unapprovedcount}}/{{number_format($de_unapprovedcharge,2)}}</td>
                                <td class="right">{{$de_awaitingcount}}/{{number_format($de_awaitingcharge,2)}}</td>
                                <td class="right">{{$de_unmarkedcount}}/{{number_format($de_unmarkedcharge,2)}}</td>
                                <td class="right">{{$de_totalcount}}/{{number_format($de_totalcharge,2)}}</td>
                            </tr>
                            </tbody>
                        </table>
                    @endif
                    
            @endif
        </div>
        <?php
        $origin_department =  $row->depart_name;
        $count++;
        ?>
        
    @endforeach
    <table   style="width : 100%;margin-top: 10px;">
        <thead>
        <tr>
            <th class="subth"><b>Extension</b></th>
            <th class="subth"><b>Approved(Call)/Charge</b></th>
            <th class="subth"><b>Rejected(Call)/Charge</b></th>
            <th class="subth"><b>Awaiting(Call)/Charge</b></th>
            <th class="subth"><b>Unmarked(Call)/Charge</b></th>
            <th class="subth"><b>Total Calls</b></th>
        </tr>
        </thead>
        <tbody>
        <tr class="">
            <td>Grand Total</td>
            <td class="right">{{$all_approvedcall}}/{{number_format($all_approvedcharge,2)}}</td>
            <td class="right">{{$all_unapprovedcount}}/{{number_format($all_unapprovedcharge,2)}}</td>
            <td class="right">{{$all_awaitingcount}}/{{number_format($all_awaitingcharge,2)}}</td>
            <td class="right">{{$all_unmarkedcount}}/{{number_format($all_unmarkedcharge,2)}}</td>
            <td class="right">{{$all_totalcount}}/{{number_format($all_totalcharge,2)}}</td>
        </tr>
        </tbody>
    </table>
@endif
@if( $data['report_type'] == 'Detailed')
    @foreach ($data['department_dept_section_extension'] as $key => $row)
        @if (!empty($row['summary']))
                <div style="margin-top: 20px;">
                    <table class="grid"  style="width : 100%;">
                        <thead style="background-color:#3c6f9c">
                        <tr>
                            <th width="20%"><b>Department</b></th>
                            <th><b>International</b></th>
                            <th><b>Mobile</b></th>
                            <th><b>Local</b></th>
                            <th><b>National </b></th>
                            <th><b>Total</b></th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr class="">
                                <td>{{getEmptyValue($row['summary']->depart_name)}}</td>
                                <td class="right">{{getEmptyValue(number_format($row['summary']->international,2))}}</td>
                                <td class="right">{{getEmptyValue(number_format($row['summary']->mobile,2))}}</td>
                                <td class="right">{{getEmptyValue(number_format($row['summary']->local,2))}}</td>
                                <td class="right">{{getEmptyValue(number_format($row['summary']->national,2))}}</td>
                                <td class="right">{{getEmptyValue(number_format($row['summary']->total,2))}}</td>
                            </tr>
                        </tbody>
                    </table>
                    @if (!empty($row['detail']))
                        <table class="grid" style="width : 100%;margin-top:10px; ">
                            <thead>
                            <tr>
                                <th class="subth" width="20%"><b>Section</b></th>
                                <th class="subth"><b>International</b></th>
                                <th class="subth"><b>Mobile</b></th>
                                <th class="subth"><b>Local</b></th>
                                <th class="subth"><b>National </b></th>
                                <th class="subth"><b>Total</b></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($row['detail'] as $key1 => $row1)
                                @if (!empty($row1['summary']))

                                            <tr class="">
                                                <td>{{getEmptyValue($row1['summary']->section)}}</td>
                                                <td class="right">{{getEmptyValue(number_format($row1['summary']->international,2))}}</td>
                                                <td class="right">{{getEmptyValue(number_format($row1['summary']->mobile,2))}}</td>
                                                <td class="right">{{getEmptyValue(number_format($row1['summary']->local,2))}}</td>
                                                <td class="right">{{getEmptyValue(number_format($row1['summary']->national,2))}}</td>
                                                <td class="right">{{getEmptyValue(number_format($row1['summary']->totalcharge,2))}}</td>
                                            </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
        @endif
    @endforeach
    @foreach ($data['department_dept_section_extension'] as $key => $row)
        @if (!empty($row['detail']))
            <div style="margin-top: 0px;">
                <b style="margin: 0px">Department: {{getEmptyValue($row['summary']->depart_name)}}</b>
                @foreach ($row['detail'] as $key1 => $row1)
                    <b style="margin: 0px">Section: {{getEmptyValue($row1['summary']->section)}}</b>
                    <table class="grid"  style="width : 100%;">
                        <thead style="background-color:#3c6f9c">
                        <tr>
                            <th width="20%"><b>Extension</b></th>
                            <th><b>User</b></th>
                            <th><b>Call Date</b></th>
                            <th><b>Call Time</b></th>
                            <th><b>Duration </b></th>
                            <th><b>Dialled Number</b></th>
                            <th><b>Destination</b></th>
                            <th><b>Call Cost ({{$data['currency']}})</b></th>
                            <th><b>Classification</b></th>
                            <th><b>Status</b></th>
                            <th><b>Comments</b></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                          $count = 0 ;
                        ?>
                        @foreach ($row1['detail'] as $key2 => $row2)
                            <tr class="">
                                <td>{{getEmptyValue($row2['detail']->extension)}}</td>
                                <td>{{getEmptyValue($row2['detail']->user)}}</td>
                                <td>{{getEmptyValue($row2['detail']->call_date)}}</td>
                                <td>{{getEmptyValue($row2['detail']->calltime)}}</td>
                                <td>{{getEmptyValue($row2['detail']->duration)}}</td>
                                <td>{{getEmptyValue($row2['detail']->dialednumber)}}</td>
                                <td>{{getEmptyValue($row2['detail']->destination)}}</td>
                                <td class="right">{{getEmptyValue(number_format($row2['detail']->callcost,2))}}</td>
                                <td>{{getEmptyValue($row2['detail']->classify)}}</td>
                                <td>{{getEmptyValue($row2['detail']->status)}}</td>
                                <td>{{getEmptyValue($row2['detail']->comment)}}</td>
                            </tr>
                         <?php $count++ ;?>
                         @endforeach
                        </tbody>
                    </table>
                    <b style="margin: 0px"> Total Calls : <?php echo $count; ?>  &nbsp;&nbsp;&nbsp;&nbsp;Call Cost({{$data['currency']}}) :  {{number_format($row1['summary']->totalcharge,2)}}  </b><br>

                @endforeach
            </div>
        @endif
    @endforeach
    <table class="grid"  style="width : 100%;margin-top: 10px;">
        <thead style="background-color:#3c6f9c">
        <tr>
            <th><b>&nbsp;</b></th>
            <th><b>International</b></th>
            <th><b>Mobile</b></th>
            <th><b>Local</b></th>
            <th><b>National </b></th>
            <th><b>Total</b></th>
        </tr>
        </thead>
        <tbody>
        <tr class="">
            <td class="right">{{$data['department_dept_section_extension_all_calls']->calls}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_calls']->international}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_calls']->mobile}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_calls']->local}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_calls']->national}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_calls']->total}}</td>
        </tr>
        <tr class="">
            <td class="right">{{$data['department_dept_section_extension_all_duration']->duration}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_duration']->international}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_duration']->mobile}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_duration']->local}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_duration']->national}}</td>
            <td class="right">{{$data['department_dept_section_extension_all_duration']->total}}</td>
        </tr>
        <tr class="">
            <td class="right">{{$data['department_dept_section_extension_all_callcost']->callcost}}</td>
            <td class="right">{{number_format($data['department_dept_section_extension_all_callcost']->international,2)}}</td>
            <td class="right">{{number_format($data['department_dept_section_extension_all_callcost']->mobile,2)}}</td>
            <td class="right">{{number_format($data['department_dept_section_extension_all_callcost']->local,2)}}</td>
            <td class="right">{{number_format($data['department_dept_section_extension_all_callcost']->national,2)}}</td>
            <td class="right">{{number_format($data['department_dept_section_extension_all_callcost']->total,2)}}</td>
        </tr>
        </tbody>
    </table>
@endif
