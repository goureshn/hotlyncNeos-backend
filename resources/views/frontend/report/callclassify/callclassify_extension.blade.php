
<?php
function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}
$date_val = '';
$origin_extension = '';

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
$count=0;
if(!empty($data['summary']))
    $summay_maxcount = count($data['summary']);
?>

@if( $data['report_type'] == 'Summary')
@foreach ($data['summary'] as $key => $row)
        <div>
            @if($origin_extension != $row->extension)
                @if($count!=0)
                        </tbody>
                    </table>

                    <table  style="width : 100%;margin-top: 10px;">
                        <thead>
                        <tr>
                            <th class="subth"><b></b></th>
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
                 <p style="margin: 0px"><b> Extension : {{$row->extension}}</b> </p>
                    <table class="grid" style="width : 100%;">
                        <thead style="background-color:#3c6f9c">
                        <tr>
                            <th><b>Call Date</b></th>
                            <th><b>Approved(Call)/Charge</b></th>
                            <th><b>Rejected(Call)/Charge</b></th>
                            <th><b>Awaiting(Call)/Charge</b></th>
                            <th><b>Unmarked(Call)/Charge</b></th>
                            <th><b>Total Calls</b></th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr class="">
                                <td>{{$row->call_date}}</td>
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
                                <td>{{$row->call_date}}</td>
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
        $origin_extension =  $row->extension;
        $count++;
        ?>
    @endforeach
    <table  style="width : 100%;margin-top: 10px;">
        <thead>
        <tr>
            <th class="subth"><b></b></th>
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
    <?php $count = 0 ?>
    @foreach ($data['extension'] as $key => $row)
        @if (!empty($row['detail']))
            <div style="margin-top: 0px;">
                <b style="margin: 0px">{{getEmptyValue($row['name'])}}</b>
                <table class="grid" style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th width="20%"><b>Call Date</b></th>
                        <th><b>Call Time</b></th>
                        <th><b>Duration</b></th>
                        <th><b>Dialled Number</b></th>
                        <th><b>Destination</b></th>
                        <th><b>Call Type</b></th>
                        <th><b>Call Cost ({{$data['currency']}})</b></th>
                        <th><b>Status</b></th>
                        <th><b>Comments</b></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($row['detail'] as $row2)
                        <tr class="">
                            <td>{{getEmptyValue($row2->call_date)}}</td>
                            <td>{{getEmptyValue($row2->calltime)}}</td>
                            <td>{{getEmptyValue($row2->duration)}}</td>
                            <td>{{getEmptyValue($row2->dialednumber)}}</td>
                            <td>{{getEmptyValue($row2->dest_name)}}</td>
                            <td>{{getEmptyValue($row2->call_type)}}</td>
                            <td class="right">{{getEmptyValue(number_format($row2->callcost,2))}}</td>
                            <td>{{getEmptyValue($row2->status)}}</td>
                            <td>{{getEmptyValue($row2->comment)}}</td>
                        </tr>
                        <?php
                        $count++;
                        ?>
                    @endforeach
                    <tr>
                        <td colspan="11">
                            Total Calls : {{$count}}
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            Call Cost({{$data['currency']}}) :  {{number_format($row['total']->totalcharge,2)}}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        @endif
        <?php $count = 0; ?>
    @endforeach
    <table class="grid" style="width : 100%;margin-top: 20px;">
        <thead style="background-color:#3c6f9c">
        <tr>
            <th><b>&nbsp;</b></th>
            <th><b>International</b></th>
            <th><b>Mobile</b></th>
            <th><b>Local</b></th>
            <th><b>National </b></th>
            <th><b>Internal </b></th>
            <th><b>Total</b></th>
        </tr>
        </thead>
        <tbody>
        <tr class="">
            <td class="right">{{$data['extension_all_calls']->calls}}</td>
            <td class="right">{{$data['extension_all_calls']->international}}</td>
            <td class="right">{{$data['extension_all_calls']->mobile}}</td>
            <td class="right">{{$data['extension_all_calls']->local}}</td>
            <td class="right">{{$data['extension_all_calls']->national}}</td>
            <td class="right">{{$data['extension_all_calls']->internal}}</td>
            <td class="right">{{$data['extension_all_calls']->total}}</td>
        </tr>
        <tr class="">
            <td class="right">{{$data['extension_all_duration']->duration}}</td>
            <td class="right">{{$data['extension_all_duration']->international}}</td>
            <td class="right">{{$data['extension_all_duration']->mobile}}</td>
            <td class="right">{{$data['extension_all_duration']->local}}</td>
            <td class="right">{{$data['extension_all_duration']->national}}</td>
            <td class="right">{{$data['extension_all_duration']->internal}}</td>
            <td class="right">{{$data['extension_all_duration']->total}}</td>
        </tr>
        <tr class="">
            <td class="right">{{$data['extension_all_callcost']->callcost}}</td>
            <td class="right">{{number_format($data['extension_all_callcost']->international,2)}}</td>
            <td class="right">{{number_format($data['extension_all_callcost']->mobile,2)}}</td>
            <td class="right">{{number_format($data['extension_all_callcost']->local,2)}}</td>
            <td class="right">{{number_format($data['extension_all_callcost']->national,2)}}</td>
            <td class="right">{{number_format($data['extension_all_callcost']->internal,2)}}</td>
            <td class="right">{{number_format($data['extension_all_callcost']->total,2)}}</td>
        </tr>
        </tbody>
    </table>
@endif
