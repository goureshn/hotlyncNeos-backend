<br/><br/>
<?php
function getEmptyValue($value) {
    if( empty($value) )
        return 0;
    else
        return $value;
}
$date_val = '';
function convertDate($val) {
        $date_val1 = date_format(new DateTime($val),'d-M-Y');
        return  $date_val1;
    }
?>
@if( $data['report_type'] == 'Summary')
    <div>
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>Date</b></th>
                <th><b>Answered</b></th>
                <th><b>Abandoned</b></th>
                <th><b>Callback</b></th>
                <th><b>Missed</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data['summary'] as $key => $row)
                <tr class="">
                    <td>{{convertDate($row->date)}}</td>
                    @if(!empty($row->dateform1))
                    <td class="right">{{$row->dateform1->answered}}</td>
                    <td class="right">{{$row->dateform1->abandoned}}</td>
                    <td class="right">{{$row->dateform1->callback}}</td>
                    <td class="right">{{$row->dateform1->missed}}</td>
                    @else
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    @endif
                </tr>
            @endforeach

            <tr class="">
                <td>{{$data['total']}}</td>
                <td class="right">{{$data['answered']}}</td>
                <td class="right">{{$data['abandoned']}}</td>
                <td class="right">{{$data['callback']}}</td>
                <td class="right">{{$data['missed']}}</td>
            </tr>
            <tr>
                <td colspan="11"> &nbsp;</td>
            </tr>

            </tbody>
        </table>
    </div>
@endif
@if( $data['report_type'] == 'Detailed')
    <div>
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>Date</b></th>
                <th><b>Answered</b></th>
                <th><b>Abandoned</b></th>
                <th><b>Callback</b></th>
                <th><b>Missed</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data['summary'] as $key => $row)
                <tr class="">
                    <td>{{convertDate($row->date)}}</td>
                    @if(!empty($row->dateform1))
                    <td class="right" >{{$row->dateform1->answered}}</td>
                    <td class="right">{{$row->dateform1->abandoned}}</td>
                    <td class="right">{{$row->dateform1->callback}}</td>
                    <td class="right">{{$row->dateform1->missed}}</td>
                    @else
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    @endif
                </tr>
            @endforeach

            <tr class="">
                <td>{{$data['total']}}</td>
                <td class="right">{{$data['answered']}}</td>
                <td class="right">{{$data['abandoned']}}</td>
                <td class="right">{{$data['callback']}}</td>
                <td class="right">{{$data['missed']}}</td>
            </tr>
            <tr>
                <td colspan="11"> &nbsp;</td>
            </tr>

            </tbody>
        </table>

        @foreach ($data['detailed'] as $key => $row)
            <p style="margin-top: 5px; margin-bottom:0px;" ><b>{{$row->date}}</b></p>
            <table class="grid"  style="width : 100%;">
                <thead style="background-color:#3c6f9c">
                <tr>
                    <th><b>Time</b></th>
                    <th><b>Agent</b></th>
                    <th><b>Caller ID</b></th>
                    <th><b>Status</b></th>
                    <th><b>Origin</b></th>
                    <th><b>Call Type</b></th>
                    <th><b>Channel</b></th>
                    <th><b>Type</b></th>
                    <th><b>Taken By</b></th>
                    <th><b>Duration</b></th>
                    <th><b>TTA</b></th>
                    <th><b>Time on queue</b></th>
                </tr>
                </thead>
                <tbody>
                <?php
                if(!empty($row->userinform)) {
                ?>
                @foreach ($row->userinform as $key => $row1)
                    <tr class="">
                        @if($date_val != $row1->date )
                            <td>{{convertDate($row1->date)}} &nbsp; {{$row1->time}}</td>
                            <?php
                            $date_val = $row1->date;
                            ?>
                        @else
                            <td style="padding-left: 70px;">{{$row1->time}}</td>
                        @endif
                        <td>{{$row1->agent}}</td>
                        <td>{{$row1->callerid}}</td>
                        <td>{{$row1->status}}</td>
                        <td>{{$row1->origin}}</td>
                        <td>{{$row1->calltype}}</td>
                        <td>{{$row1->channel}}</td>
                        <td>{{$row1->type}}</td>
                        @if($row1->callback_flag==2 || $row1->missed_flag==2 || $row1->abandon_flag==2)
                    <td align="center">{{$row1->agent_taken}}</td>
                     @else
                    <td align="center"></td>
                    @endif
                        <td>{{$row1->duration}}</td>
                        <td>{{$row1->tta}}</td>
                        <td>{{$row1->queue}}</td>
                    </tr>
                @endforeach
                <?php
                }
                ?>
                <tr>
                    <td colspan="11"> &nbsp;</td>
                </tr>

                </tbody>
            </table>
        @endforeach

    </div>
@endif
