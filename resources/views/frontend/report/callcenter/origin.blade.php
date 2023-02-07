<br/><br/>
<?php
function getEmptyValue($value) {
    if( empty($value) )
        return 0;
    else
        return $value;
}
$date_val = '';
?>
@if( $data['report_type'] == 'Summary')
    <div>
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>Date</b></th>
                <th><b>Origin</b></th>
                <th><b>Answered</b></th>
                <th><b>Abandoned</b></th>
                <th><b>Callback</b></th>
                <th><b>Missed</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data['summary'] as $key => $row)
                @foreach ($row->dateform1 as $key => $row1)
                <tr class="">
                    <td>{{$row->date}}</td>
                    <td>
                        @if($row1->origin == '')
                            Internal
                        @else
                            {{$row1->origin}}
                        @endif</td>
                    <td class="right">{{$row1->answered}}</td>
                    <td class="right">{{$row1->abandoned}}</td>
                    <td class="right">{{$row1->callback}}</td>
                    <td class="right">{{$row1->missed}}</td>
                </tr>
                @endforeach
            @endforeach

            <tr class="">
                <td>{{$data['total']}}</td>
                <td>&nbsp;</td>
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
                <th><b>Origin</b></th>
                <th><b>Answered</b></th>
                <th><b>Abandoned</b></th>
                <th><b>Callback</b></th>
                <th><b>Missed</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data['summary'] as $key => $row)
                @foreach ($row->dateform1 as $key => $row1)
                    <tr class="">
                        <td>{{$row->date}}</td>
                        <td>
                            @if($row1->origin == '')
                                Internal
                            @else
                            {{$row1->origin}}
                            @endif
                        </td>
                        <td class="right">{{$row1->answered}}</td>
                        <td class="right">{{$row1->abandoned}}</td>
                        <td class="right">{{$row1->callback}}</td>
                        <td class="right">{{$row1->missed}}</td>
                    </tr>
                @endforeach
            @endforeach

            <tr class="">
                <td>{{$data['total']}}</td>
                <td>&nbsp;</td>
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
            <?php
            if(!empty($row->userinform)) {
            ?>
            <p style="margin-top: 5px; margin-bottom:0px;" >
                <?php
                foreach($row->userinform as $key => $row1) {
                  if($row1->origin == '') echo 'Internal' ;
                  else echo $row1->origin;
                  break;
                }
                ?>
            </p>
            <table class="grid"  style="width : 100%;">
                <thead style="background-color:#3c6f9c">
                <tr>
                    <th><b>Time</b></th>
                    <th><b>Agent</b></th>
                    <th><b>Caller ID</b></th>
                    <th><b>Status</b></th>
                    <th><b>Call Type</b></th>
                    <th><b>Origin</b></th>
                    <th><b>Type</b></th>
                    <th><b>Channel</b></th>
                    <th><b>Taken By</b></th>
                    <th><b>Duration</b></th>
                    <th><b>TTA</b></th>
                    <th><b>Time on queue</b></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($row->userinform as $key => $row1)
                    <tr class="">
                        @if($date_val != $row1->date )
                            <td>{{$row1->date}} &nbsp; {{$row1->time}}</td>
                            <?php
                            $date_val = $row1->date;
                            ?>
                        @else
                            <td style="padding-left: 70px;">{{$row1->time}}</td>
                        @endif
                        <td>{{$row1->agent}}</td>
                        <td>{{$row1->callerid}}</td>
                        <td>{{$row1->status}}</td>
                        <td>{{$row1->calltype}}</td>
                        <td>{{$row1->origin}}</td>
                        <td>{{$row1->type}}</td>
                        <td>{{$row1->channel}}</td>
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
                <tr>
                    <td colspan="11"> &nbsp;</td>
                </tr>

                </tbody>
            </table>
                <?php
                }
                ?>
        @endforeach

    </div>
@endif
