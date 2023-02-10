<br/><br/>
<?php
function getEmptyValue($value) {
    if( empty($value) )
        return 0;
    else
        return $value;
}
?>
<div>
    <table class="grid" border="1" style="width : 100%;">
        <thead style="background-color:#3c6f9c">
        <tr>
            <th rowspan="2"><b>Time</b></th>
            <th rowspan="2"><b>Total Calls</b></th>
            <th colspan="3"><b>Answered Calls</b></th>
            <th colspan="4"><b>Abandoned Calls</b></th>
            <th colspan="2"><b>Outgoing Calls</b></th>
        </tr>
        <tr>
            <th><b>Total Calls</b></th>
            <th rowspan="4"><b>Average Duration</b></th>
            <th rowspan="2"><b>Averate Time to Answer</b></th>
            <th><b>Total Calls</b></th>
            <th><b>Queued Calls</b></th>
            <th><b>%Missed</b></th>
            <th><b>Average Wait Time</b></th>
            <th><b>Total Calls</b></th>
            <th><b>Average Duration</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['detail'] as $key => $row)
            <tr class="">
                <td>{{$row['time_name']}}</td>
                <td>{{$row['call_trafic']->total_calls}}</td>
                <td>{{getEmptyValue($row['call_trafic']->answered)}}</td>
                <td>{{getEmptyValue($row['call_trafic']->avg_duration)}}</td>
                <td>{{getEmptyValue($row['call_trafic']->avg_tta)}}</td>
                <td>{{getEmptyValue($row['call_trafic']->abandoned)}}</td>
                <td></td>
                <td></td>
                <td>{{getEmptyValue($row['call_trafic']->avg_waiting)}}</td>
                <td>{{getEmptyValue($row['call_trafic']->outgoing)}}</td>
                <td>{{getEmptyValue($row['call_trafic']->avg_outgoing_duration)}}</td>
            </tr>
        @endforeach
            <tr>
                <td colspan="11"> &nbsp;</td>
            </tr>
            <tr class="tr_summary">
                <td>Total</td>
                <td>{{getEmptyValue($data['summary']->total_calls)}}</td>
                <td>{{getEmptyValue($data['summary']->answered)}}</td>
                <td>{{getEmptyValue($data['summary']->avg_duration)}}</td>
                <td>{{getEmptyValue($data['summary']->avg_tta)}}</td>
                <td>{{getEmptyValue($data['summary']->abandoned)}}</td>
                <td></td>
                <td></td>
                <td>{{getEmptyValue($data['summary']->avg_waiting)}}</td>
                <td>{{getEmptyValue($data['summary']->outgoing)}}</td>
                <td>{{getEmptyValue($data['summary']->avg_outgoing_duration)}}</td>
            </tr>
        </tbody>
    </table>
</div>