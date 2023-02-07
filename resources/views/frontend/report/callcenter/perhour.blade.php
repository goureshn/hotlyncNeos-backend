<br/><br/>
<?php
//echo json_encode($data['summary']);
function getEmptyValue($value) {
    if( empty($value) )
        return '';
    else {
        return $value;
        }
}
function viewOriginValue($value) {
    if($value == 0) return 'Internal';
    else return $value;
}
$date_val = '';
$count = 0 ;
?>
@if( $data['report_type'] == 'Summary')
    <div>
        @foreach ($data['summary'] as $key => $row)
            @if($date_val != $row->date)
                <?php
                 $date_val = $row->date;
                 if($count != 0 && $count < count($data['summary'])) { ?>
                        </tbody>
                    </table>
                <?php
                    }
                 ?>
                <p style="margin-top: 5px; margin-bottom: 0px;"><b>Date: {{$row->date}}</b></p>
                <table class="grid"  style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th><b>Per Hour</b></th>
                        <th><b>Answered</b></th>
                        <th><b>Abandoned</b></th>
                        <th><b>Callback</b></th>
                        <th><b>Missed</b></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($row->dateform1 as $key => $row1)
                        <tr class="">
                            <td align="center">{{$row->hour}}</td>
                            <td align="center">{{getEmptyValue($row1->answered)}}</td>
                            <td align="center">{{getEmptyValue($row1->abandoned)}}</td>
                            <td align="center">{{getEmptyValue($row1->callback)}}</td>
                            <td align="center">{{getEmptyValue($row1->missed)}}</td>
                        </tr>
                    @endforeach
                <?php $count++; ?>
            @else
                    @foreach ($row->dateform1 as $key => $row1)
                        <tr class="">
                            <td align="center">{{$row->hour}}</td>
                            <td align="center">{{getEmptyValue($row1->answered)}}</td>
                            <td align="center">{{getEmptyValue($row1->abandoned)}}</td>
                            <td align="center">{{getEmptyValue($row1->callback)}}</td>
                            <td align="center">{{getEmptyValue($row1->missed)}}</td>
                        </tr>
                    @endforeach
            @endif
        @endforeach

                <!--<h4>Date: Total</h4>-->
                <table class="grid"  style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th><b>Per Hour</b></th>
                        <th><b>Answered</b></th>
                        <th><b>Abandoned</b></th>
                        <th><b>Callback</b></th>
                        <th><b>Missed</b></th>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="">
                            <td align="center">{{$data['total']}}</td>
                            <td align="center">{{$data['answered']}}</td>
                            <td align="center">{{$data['abandoned']}}</td>
                            <td align="center">{{$data['callback']}}</td>
                            <td align="center">{{$data['missed']}}</td>
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
    @foreach ($data['summary'] as $key => $row)
    @if($date_val != $row->date)
    <?php
    $date_val = $row->date;
    if($count != 0) { ?>
    </tbody>
    </table>
    <?php
    }
    ?>
    <p style="margin-top: 5px; margin-bottom: 0px;"><b>Date: {{$row->date}}</b></p>
    <table class="grid"  style="width : 100%;">
        <thead style="background-color:#3c6f9c">
        <tr>
            <th><b>Per Hour</b></th>
            <th><b>Answered</b></th>
            <th><b>Abandoned</b></th>
            <th><b>Callback</b></th>
            <th><b>Missed</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($row->dateform1 as $key => $row1)
            <tr class="">
                <td align="center">{{$row->hour}}</td>
                <td align="center">{{getEmptyValue($row1->answered)}}</td>
                <td align="center">{{getEmptyValue($row1->abandoned)}}</td>
                <td align="center">{{getEmptyValue($row1->callback)}}</td>
                <td align="center">{{getEmptyValue($row1->missed)}}</td>
            </tr>
        @endforeach
        <?php $count++; ?>
        @else
            @foreach ($row->dateform1 as $key => $row1)
                <tr class="">
                    <td align="center">{{$row->hour}}</td>
                    <td align="center">{{getEmptyValue($row1->answered)}}</td>
                    <td align="center">{{getEmptyValue($row1->abandoned)}}</td>
                    <td align="center">{{getEmptyValue($row1->callback)}}</td>
                    <td align="center">{{getEmptyValue($row1->missed)}}</td>
                </tr>
            @endforeach
        @endif
        @endforeach
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>Per Hour</b></th>
                <th><b>Answered</b></th>
                <th><b>Abandoned</b></th>
                <th><b>Callback</b></th>
                <th><b>Missed</b></th>
            </tr>
            </thead>
            <tbody>
            <tr class="">
                <td align="center">{{$data['total']}}</td>
                <td align="center">{{$data['answered']}}</td>
                <td align="center">{{$data['abandoned']}}</td>
                <td align="center">{{$data['callback']}}</td>
                <td align="center">{{$data['missed']}}</td>
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
            <p style=" margin-top: 5px;margin-bottom: 0px;">
               <b> Date: {{$row->date}}&nbsp;&nbsp;Hour: {{$row->hour}}</b>
            </p>
            <table class="grid"  style="width : 100%;">
                <thead style="background-color:#3c6f9c">
                <tr>
                    <th><b>Time</b></th>
                    <th><b>Agent</b></th>
                    <th><b>Caller ID</b></th>
                    <th><b>Origin</b></th>
                    <th><b>Call Type</b></th>
                    <th><b>Status</b></th>
                    <th><b>Channel</b></th>
                    <th><b>Type</b></th>
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
                            <td>{{$row1->time}}</td>
                            <?php
                            $date_val = $row1->date;
                            ?>
                        @else
                            <td style="padding-left: 70px;">{{$row1->time}}</td>
                        @endif
                        <td>{{$row1->agent}}</td>
                        <td>{{$row1->callerid}}</td>
                        <td>{{viewOriginValue($row1->origin)}}</td>
                        <td>{{$row1->calltype}}</td>
                        <td>{{$row1->status}}</td>
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
