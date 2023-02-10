<?php
    $date_val = '';
    $i = 0;
    $j = 0;
?>
<br/>
<div>
    <table class="grid" style="width : 100%;">
        <thead>
        <tr>
            <th><b>Date</b></th>
            <th><b>Agent</b></th>
            <th><b>Call Abandoned</b></th>
            <th><b>%Call Abandoned</b></th>
            <th><b>Call Abandoned in 20 secs</b></th>
            <th><b>%Call Abandoned in 20 secs</b></th>
            <th><b>Lognest Wait Abandoned</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['detail'] as $key => $row)
            @if( $key == 0 )
                <tr class="">
                    <td colspan="4"></td>
                </tr>
            @endif
            @if( $row->call_date != $date_val && $i != 0)
                <tr class="">
                    <td>&nbsp;</td>
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
                </tr>
            @endif
            <tr class="">
                <td>
                    @if ($row->call_date != $date_val){{$row->call_date}} @endif
                </td>
                <td>{{$row->wholename}}</td>
                <td>{{$row->abandoned}}</td>
                @if( $row->total_calls > 0 )
                    <td>{{round($row->abandoned * 100 / $row->total_calls, 2)}}%</td>
                @else
                    <td>0%</td>
                @endif
                <td>{{$row->abandoned_20}}</td>
                @if( $row->total_calls > 0 )
                    <td>{{round($row->abandoned_20 * 100 / $row->total_calls, 2)}}%</td>
                @else
                    <td>0%</td>
                @endif
                <td>{{$row->abandoned_max}}</td>
            </tr>
            <?php
                $date_val = $row->call_date;
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
            </tr>
        @foreach ($data['summary'] as $key => $row)
                 @if( $key == 0 )
                <tr class="tr_summary">
                    <td>Report Summary</td>
                @else
                <tr>
                    <td></td>
                @endif
                <td>{{$row->wholename}}</td>
                <td>{{$row->abandoned}}</td>
                @if( $row->total_calls > 0 )
                    <td>{{round($row->abandoned * 100 / $row->total_calls, 2)}}%</td>
                @else
                    <td>0%</td>
                @endif
                <td>{{$row->abandoned_20}}</td>
                @if( $row->total_calls > 0 )
                    <td>{{round($row->abandoned_20 * 100 / $row->total_calls, 2)}}%</td>
                @else
                    <td>0%</td>
                @endif
                <td>{{$row->abandoned_max}}</td>
            </tr>
        @endforeach

        </tbody>
    </table>
</div>
