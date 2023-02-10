
<?php
function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}
?>

@if( $data['report_type'] == 'Summary')
@foreach ($data['mobile'] as $key => $row)
        @if (!empty($row['detail']))
        <div>
                    <p style="margin: 0px"><b> {{getEmptyValue($row['name'])}}</b> </p>
                    <table class="grid" style="width : 100%;">
                        <thead style="background-color:#3c6f9c">
                        <tr>
                        <th rowspan="2"><b>Mobile</b></th>
                        <th rowspan="2"><b>Unmarked(Call)/Charge</b></th>
                        <th rowspan="2"><b>Personal(Call)/Charge</b></th>  
                        <th colspan="3"><b>Business</b></th> 
                        <th rowspan="2"><b>Total Calls</b></th>
                        </tr>
                        <tr>
                            <th><b>Approved(Call)/Charge</b></th>
                            <th><b>Rejected(Call)/Charge</b></th>
                            <th><b>Awaiting(Call)/Charge</b></th> 
                        </tr>
                       
                        </thead>
                        <?php
                        $de_approvedcall = 0;
                        $de_approvedcharge = 0;
                        $de_unapprovedcount = 0;
                        $de_unapprovedcharge = 0;
                        $de_awaitingcount = 0;
                        $de_awaitingcharge = 0;
                        $de_unmarkedcount = 0;
                        $de_unmarkedcharge = 0;
                        $de_personalcount = 0;
                        $de_personalcharge = 0;
                        $de_totalcount = 0;
                        $de_totalcharge = 0;
                        ?>
                        <tbody>
                        @foreach ($row['detail'] as $row2)
                        
                        <?php
                        $row_total = $row2->unmarkedcount + $row2->personalcount + $row2->approvedcount + $row2->awaitingcount + $row2->unapprovedcount;
                        $row_totalcharge = $row2->personalcharge + $row2->approvedcharge + $row2->unapprovedcharge + $row2->awaitingcharge + $row2->unmarkedcharge;
                        ?>
                        
                            <tr class="">
                                <td>{{$row2->call_from}} - {{$row2->user}}</td>
                                <td class="right">{{$row2->unmarkedcount}}/{{number_format($row2->unmarkedcharge,2)}}</td>
                                <td class="right">{{$row2->personalcount}}/{{number_format($row2->personalcharge,2)}}</td>
                                <td class="right">{{$row2->approvedcount}}/{{number_format($row2->approvedcharge,2)}}</td>
                                <td class="right">{{$row2->unapprovedcount}}/{{number_format($row2->unapprovedcharge,2)}}</td>
                                <td class="right">{{$row2->awaitingcount}}/{{number_format($row2->awaitingcharge,2)}}</td>
                                <td class="right">{{$row_total}}/{{number_format($row_totalcharge,2)}}</td>
                            </tr>
                            <?php
                            $de_approvedcall += $row2->approvedcount;
                            $de_approvedcharge += $row2->approvedcharge;
                            $de_unapprovedcount += $row2->unapprovedcount;
                            $de_unapprovedcharge += $row2->unapprovedcharge;
                            $de_awaitingcount += $row2->awaitingcount;
                            $de_awaitingcharge += $row2->awaitingcharge;
                            $de_unmarkedcount += $row2->unmarkedcount;
                            $de_unmarkedcharge += $row2->unmarkedcharge;
                            $de_personalcount += $row2->personalcount;
                            $de_personalcharge += $row2->personalcharge;
                            $de_totalcount += $row_total;
                            $de_totalcharge += $row_totalcharge;
                            ?>
                    @endforeach
                            
                <tr class="">
                <td>Total</td>
                <td class="right">{{$de_unmarkedcount}}/{{number_format($de_unmarkedcharge,2)}}</td>
                <td class="right">{{$de_personalcount}}/{{number_format($de_personalcharge,2)}}</td>
                <td class="right">{{$de_approvedcall}}/{{number_format($de_approvedcharge,2)}}</td>
                <td class="right">{{$de_unapprovedcount}}/{{number_format($de_unapprovedcharge,2)}}</td>
                <td class="right">{{$de_awaitingcount}}/{{number_format($de_awaitingcharge,2)}}</td>
                <td class="right">{{$de_totalcount}}/{{number_format($de_totalcharge,2)}}</td>
                </tr>
        </tbody>
    </table>      
</div>  
@endif      
@endforeach
@endif

@if( $data['report_type'] == 'Detailed')
    <?php $totalcount = 0; $totalcost = 0; ?>
    @foreach ($data['extension'] as $key => $datagroup)
       
            <div style="margin-top: 0px;">
            
                <p style="margin: 0px"><b>{{$data['group_by_mobile_call']}} : {{$key}}</b></p>
           
                <table class="grid" style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        @if( $data['group_by_mobile_call'] != 'Date')
                            <th><b>Call Date</b></th>
                        @endif
                        <th><b>Call Time</b></th>
                        <th><b>Duration</b></th>
                        <th><b>Dialled Number</b></th>
                        @if( $data['group_by_mobile_call'] != 'Department')
                            <th><b>Department</b></th>
                        @endif 
                        @if( $data['group_by_mobile_call'] == 'Department')
                            <th><b>Mobile Number</b></th>
                        @endif 
                        @if( $data['group_by_mobile_call'] != 'User')
                            <th><b>User</b></th>
                        @endif 
                        <th><b>Destination</b></th>
                        <th><b>Call Type</b></th>
                        <th><b>Classification</b></th>
                        <th><b>Call Cost ({{$data['currency']}})</b></th>
                        <th><b>Status</b></th>
                        <th><b>Comments</b></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $totalcount = 0; $totalcost = 0; ?>
                    @foreach ($datagroup as $row2)
                        <tr class="">
                            @if( $data['group_by_mobile_call'] != 'Date')
                                <td>{{$row2->date}}</td>
                            @endif
                            <td>{{getEmptyValue($row2->calltime)}}</td>
                            <td>{{getEmptyValue($row2->duration)}}</td>
                            <td>{{getEmptyValue($row2->dialednumber)}}</td>
                            @if( $data['group_by_mobile_call'] != 'Department')
                                <td>{{getEmptyValue($row2->department)}}</td>
                            @endif
                            @if( $data['group_by_mobile_call'] == 'Department')
                                <td>{{getEmptyValue($row2->call_from)}}</td>
                            @endif
                            @if( $data['group_by_mobile_call'] != 'User')
                                <td>{{getEmptyValue($row2->user)}}</td>
                            @endif
                            <td>{{getEmptyValue($row2->dest_name)}}</td>
                            <td>{{getEmptyValue($row2->call_type)}}</td>
                            <td>{{getEmptyValue($row2->classify)}}</td>
                            <td class="right">{{getEmptyValue(number_format($row2->callcost,2))}}</td>
                            <td>{{getEmptyValue($row2->status)}}</td>
                            <td>{{getEmptyValue($row2->comment)}}</td>
                        </tr>
                        <?php
                        $totalcount++;
                        $totalcost += $row2->callcost;
                        ?>
                    @endforeach
                    <tr>
                        <td colspan="11">
                            Total Calls : {{$totalcount}}
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            Call Cost({{$data['currency']}}) :  {{number_format($totalcost,2)}}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        
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
