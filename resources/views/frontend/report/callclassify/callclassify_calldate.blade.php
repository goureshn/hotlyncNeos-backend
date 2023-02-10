
<?php

function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}
$date_val = '';
?>
@if( $data['report_type'] == 'Summary')

@endif
@if( $data['report_type'] == 'Detailed')
    @foreach ($data['detailed'] as $key => $row)
    <div  style="margin-top: 10px">
        <p style="margin: 0px"><b> Call date : {{$row->date}} </b></p>
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>Extension</b></th>
                <th><b>User</b></th>
                <th><b>Call Time</b></th>
                <th><b>Duration</b></th>
                <th><b>Dialled Numbe</b></th>
                <th><b>Destination</b></th>
                <th><b>Call Cost ({{$data['currency']}})</b></th>
                <th><b>Status</b></th>
                <th><b>Comment</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($row->calldate as $key1 => $row1)
                <tr class="">
                        <td>{{$row1->extension}}</td>
                        <td>{{$row1->user}}</td>
                        <td>{{$row1->calltime}}</td>
                        <td>{{$row1->duration}}</td>
                        <td>{{$row1->dialednumber}}</td>
                        <td>{{$row1->destination}}</td>
                        <td class="right">{{number_format($row1->callcost,2)}}</td>
                        <td>{{$row1->status}}</td>
                        <td>{{$row1->comment}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p style="margin: 0px">Total Calls : {{$row->calltotal->calltotal}}  Call Cost ({{$data['currency']}}) : : {{number_format($row->calltotal->callcost,2)}} </p>
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>&nbsp;</b></th>
                <th><b>International</b></th>
                <th><b>Local</b></th>
                <th><b>National </b></th>
                <th><b>Total</b></th>
            </tr>
            </thead>
            <tbody>
                <tr class="">
                    <td class="right">{{$row->calls->calls}}</td>
                    <td class="right">{{$row->calls->international}}</td>
                    <td class="right">{{$row->calls->local}}</td>
                    <td class="right">{{$row->calls->national}}</td>
                    <td class="right">{{$row->calls->total}}</td>
                </tr>
                <tr class="">
                    <td class="right">{{$row->duration->duration}}</td>
                    <td class="right">{{$row->duration->international}}</td>
                    <td class="right">{{$row->duration->local}}</td>
                    <td class="right">{{$row->duration->national}}</td>
                    <td class="right">{{$row->duration->total}}</td>
                </tr>
                <tr class="">
                    <td class="right">{{$row->callcost->callcost}}</td>
                    <td class="right">{{number_format($row->callcost->international,2)}}</td>
                    <td class="right">{{number_format($row->callcost->local,2)}}</td>
                    <td class="right">{{number_format($row->callcost->national,2)}}</td>
                    <td class="right">{{number_format($row->callcost->total,2)}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
@endif
