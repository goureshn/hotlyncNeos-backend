<?php
$date_val = '';
$i = 0;
$j = 0;
?>
<br/><br/>
<div>
    <table class="grid" style="width : 100%;">
        <thead>
        <tr>
            <th><b>Agent</b></th>
            <th><b>Call Start Time</b></th>
            <th><b>Call End Time</b></th>
            <th><b>Call Type</b></th>
            <th><b>Caller ID</b></th>
            <th><b>Wait Time in Queue</b></th>
            <th><b>Time To Answer</b></th>
            <th><b>Talk Time</b></th>
            <th><b>Hold Time</b></th>
            <th><b>Inquiry</b></th>
            <th><b>Modify</b></th>
            <th><b>Reservation</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['detail'] as $key => $row)

            @if( $row->call_start_date != $date_val )
                <tr class="">
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="">
                    <td><strong>{{$row->call_start_date}}</strong></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="">
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endif
            <tr class="">
                <td>{{$row->wholename}}</td>
                <td>{{$row->call_start_time}}</td>
                <td>{{$row->call_end_time}}</td>
                <td>{{$row->call_type}}</td>
                <td>{{$row->caller_id}}</td>
                <td>{{$row->waiting}}</td>
                <td>{{$row->time_to_answer}}</td>
                <td>{{$row->talk_time}}</td>
                <td>{{--$row->hold_time--}}</td>
                @if( $row->type == 'Inquiry')
                    <td>Y</td>
                @else
                    <td>N</td>
                @endif
                @if( $row->type == 'Modify')
                     <td>Y</td>
                @else
                     <td>N</td>
                @endif
                @if( $row->type == 'Booking')
                    <td>Y</td>
                @else
                    <td>N</td>
                @endif
            </tr>
            <?php
            $date_val = $row->call_start_date;
            $i++;
            ?>
        @endforeach
        <tr class="">
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="">
            <td>&nbsp;</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="13"><b>Report Summary</b></td>
        </tr>
        <tr class="tr_summary">
            <td>Agent</td>
            <td>Avarege Time to Answer</td>
            <td>Avarage Talk Time</td>
            <td>Inquiry</td>
            <td>Modify</td>
            <td>Reservatiion</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        @foreach ($data['summary'] as $key => $row)
            <tr class="">
                <td>{{$row->wholename}}</td>
                <td>{{$row->avg_time_answer}} sec</td>
                <td>{{$row->avg_talk_time}} min</td>
                <td>{{$row->inquiry}}</td>
                <td>{{$row->follow}}</td>
                <td>{{$row->reservation}}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>