
<?php
function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}
$count=0;
if(!empty($data['summary']))
    $summay_maxcount = count($data['summary']);
?>

@if( $data['report_type'] == 'Summary')


@endif

@if( $data['report_type'] == 'Detailed')
    <?php $count = 0 ?>
    @foreach ($data['user'] as $key => $row)
        @if (!empty($row['detail']))
            <div style="margin-top: 0px;">
                <p style="margin: 0px"><b>{{getEmptyValue($row['name'])}}</b></p>
                <table class="grid" style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th ><b>Extension</b></th>
                        <th width="20%"><b>Call Date</b></th>
                        <th><b>Call Time</b></th>
                        <th><b>Duration</b></th>
                        <th><b>Dialled Number</b></th>
                        <th><b>Destination</b></th>
                        <th><b>Call Cost ({{$data['currency']}})</b></th>
                        <th><b>Status</b></th>
                        <th><b>Comments</b></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($row['detail'] as $row2)
                        <tr class="">
                            <td>{{getEmptyValue($row2->extension)}}</td>
                            <td>{{getEmptyValue($row2->call_date)}}</td>
                            <td>{{getEmptyValue($row2->calltime)}}</td>
                            <td>{{getEmptyValue($row2->duration)}}</td>
                            <td>{{getEmptyValue($row2->dialednumber)}}</td>
                            <td>{{getEmptyValue($row2->dest_name)}}</td>
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
    <table class="grid"  style="width : 100%;margin-top: 20px;">
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
