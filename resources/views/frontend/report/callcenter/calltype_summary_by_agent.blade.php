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
            <th><b>Date</b></th>
            <th><b>Agent</b></th>
            <th><b>Calls for Reservation</b></th>
            <th><b>Calls for Enquiry</b></th>
            <th><b>Calls for Followup</b></th>
            <th><b>Calls for Others</b></th>
            <th><b>Total</b></th>
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
                <td>{{$row->booking}}</td>
                <td>{{$row->inquiry}}</td>
                <td>{{$row->followup}}</td>
                <td>{{$row->other}}</td>
                <td>{{$row->total_calls}}</td>
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
                <td>{{$row->booking}}</td>
                <td>{{$row->inquiry}}</td>
                <td>{{$row->followup}}</td>
                <td>{{$row->other}}</td>
                <td>{{$row->total_calls}}</td>
            </tr>
        @endforeach

        </tbody>
    </table>
</div>