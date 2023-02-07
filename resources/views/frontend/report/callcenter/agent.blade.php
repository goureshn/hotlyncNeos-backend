<br/><br/>
<?php

function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}

function getEmptyTimeValue($value) {
    if( empty($value)|| $value == null )
        return '00:00:00';
    else
        return $value;
}

$date_val = '';
$count = 0 ;
?>
@if( $data['report_type'] == 'Summary')
<div><br/>
    <table class="grid" style="width : 100%">
        <thead style="background-color:#3c6f9c">
        <tr style="font-size: 8px">
            <!--<td style="width:150px;"><b>&nbsp;</b></td>-->
            <th align="center"><b>Answered</b></th>
            <th align="center"><b>Abandoned</b></th>
            <th align="center"><b>Missed</b></th>
            <th align="center"><b>Outgoing</b></th>
            <th align="center"><b>Dropped</b></th>
            <th align="center"><b>Callback</b></th>
            <th align="center"><b>Total Calls</b></th>
            <th align="center"><b>Peak Hour</b></th>
            <th align="center"><b>Time to Answer</b></th>
            <th align="center"><b>Avg Talk Time</b></th>
            <th align="center"><b>Agent Utilization %</b></th>
            <th align="center"><b>Call Answered %</b></th>
            <!--<td style="width:150px;"><b>&nbsp;</b></td>-->
        </tr>
        </thead>
        <tbody>
        <tr class="">
            <!--<td><b>&nbsp;</b></td>-->
            <td class="right">{{getEmptyValue($data['answered'])}}</td>
            <td class="right">{{getEmptyValue($data['abandoned'])}}</td>
            <td class="right">{{getEmptyValue($data['missed'])}}</td>
            <td class="right">{{getEmptyValue($data['outgoing'])}}</td>
            <td class="right">{{getEmptyValue($data['dropped'])}}</td>
            <td class="right">{{getEmptyValue($data['callback'])}}</td>
            <td class="right">{{getEmptyValue($data['totalcall'])}}</td>
            <td class="right">{{getEmptyValue($data['peak_hour'])}}</td>
            <td class="right">{{getEmptyValue($data['total_tta'])}}</td>
            <td class="right">{{getEmptyValue($data['total_att'])}}</td>
            <td class="right">{{getEmptyValue($data['total_au'])}} %</td>
            <td class="right">{{getEmptyValue($data['total_ans_per'])}} %</td>
            <!--<td class="right"><b>&nbsp;</b></td>-->
        </tr>
      </tbody>
    </table>
<br/>
    <table class="grid"  style="width : 100%;">
        <thead style="background-color:#3c6f9c">
            <tr>
                <th rowspan="2"><b>Agent</b></th>
                <th colspan="5"><b>Number of Calls</b></th>
                <th colspan="8"><b>Duration</b></th>
            </tr>
            <tr>
                <th><b>Answered</b></th>
                <th rowspan="4"><b>Abandoned</b></th>
                <th rowspan="2"><b>Callback</b></th>
                <th><b>Outgoing</b></th>
                <th><b>Dropped</b></th>
                <th><b>Available</b></th>
                <th><b>Idle</b></th>
                <th><b>Busy</b></th>
                <th><b>Wrap Up</b></th>
                <th><b>On Break</b></th>
                <th><b>Online</b></th>
                <!--<th><b>Total on time on call</b></th>-->
                <th><b>Ave TTA</b></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($data['summary'] as $key => $row)
            <tr class="">
            @if(!empty($row->userinform1->tta))
                <td>{{$row->agent}}</td>
                
                <td class="right">{{getEmptyValue($row->userinform1->answered)}}</td>
                <td class="right">{{getEmptyValue($row->userinform1->abandoned)}}</td>
                <td class="right">{{getEmptyValue($row->userinform1->callback)}}</td>
                <td class="right">{{getEmptyValue($row->userinform1->outgoing)}}</td>
                <td class="right">{{getEmptyValue($row->userinform1->dropped)}}</td>
                <td class="right">{{getEmptyTimeValue($row->historyinform->available)}}</td>
                <td class="right">{{getEmptyTimeValue($row->historyinform->idle)}}</td>
                <td class="right">{{getEmptyTimeValue($row->historyinform->busy)}}</td>
                <td class="right">{{getEmptyTimeValue($row->historyinform->wrapup)}}</td>
                <td class="right">{{getEmptyTimeValue($row->historyinform->onbreak)}}</td>
                <td class="right">{{getEmptyTimeValue($row->historyinform->online)}}</td>
                <!--<td class="right">{{getEmptyValue($row->userinform1->totaltime)}}</td>-->
                <td class="right">{{getEmptyValue($row->userinform1->tta)}}</td>
                @else
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                @endif
            </tr>
        @endforeach

        <tr class="">
            <td class="right">{{$data['total']}}</td>
            <td class="right">{{$data['answered']}}</td>
            <td class="right">{{$data['abandoned']}}</td>
            <td class="right">{{$data['callback']}}</td>
            <td class="right">{{$data['outgoing']}}</td>
            <td class="right">{{$data['dropped']}}</td>
            <td class="right">{{$data['available']}}</td>
            <td class="right">{{$data['idle']}}</td>
            <td class="right">{{$data['busy']}}</td>
            <td class="right">{{$data['wrapup']}}</td>
            <td class="right">{{$data['onbreak']}}</td>
            <td class="right">{{$data['online']}}</td>
            <!--<td class="right">{{$data['totaltime']}}</td>-->
            <td class="right">{{$data['tta']}}</td>
        </tr>
        </tbody>
    </table>
</div>
@endif
@if( $data['report_type'] == 'Detailed')

<div>
        @foreach ($data['summary_hour'] as $key => $row)
            @if($date_val != $row->date)
                <?php
                 $date_val = $row->date;
                 if($count != 0 && $count < count($data['summary_hour'])) { ?>
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
                            <td align="center">{{$data['total_hour']}}</td>
                            <td align="center">{{$data['answered_hour']}}</td>
                            <td align="center">{{$data['abandoned_hour']}}</td>
                            <td align="center">{{$data['callback_hour']}}</td>
                            <td align="center">{{$data['missed_hour']}}</td>
                        </tr>
                        <tr>
                            <td colspan="11"> &nbsp;</td>
                        </tr>
                    </tbody>
            </table>
    </div>










    <div>
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th rowspan="2"><b>Agent</b></th>
                <th colspan="5"><b>Number of Calls</b></th>
                <th colspan="8"><b>Duration</b></th>
            </tr>
            <tr>
                <th><b>Answered</b></th>
                <th rowspan="4"><b>Abandoned</b></th>
                <th rowspan="2"><b>Callback</b></th>
                <th><b>Outgoing</b></th>
                <th><b>Dropped</b></th>
                <th><b>Available</b></th>
                <th><b>Idle</b></th>
                <th><b>Busy</b></th>
                <th><b>Wrap Up</b></th>
                <th><b>On Break</b></th>
                <th><b>Online</b></th>
                <!--<th><b>Total on time on call</b></th>-->
                <th><b>Ave TTA</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data['summary'] as $key => $row)
                <tr class="">
                @if(!empty($row->userinform1->tta))
                    <td >{{$row->agent}}</td>
                   
                    <td class="right">{{$row->userinform1->answered}}</td>
                    <td class="right">{{$row->userinform1->abandoned}}</td>
                    <td class="right">{{$row->userinform1->callback}}</td>
                    <td class="right">{{$row->userinform1->outgoing}}</td>
                    <td class="right">{{$row->userinform1->dropped}}</td>
                    <td class="right">{{$row->historyinform->available}}</td>
                    <td class="right">{{$row->historyinform->idle}}</td>
                    <td class="right">{{$row->historyinform->busy}}</td>
                    <td class="right">{{$row->historyinform->wrapup}}</td>
                    <td class="right">{{$row->historyinform->onbreak}}</td>
                    <td class="right">{{$row->historyinform->online}}</td>
                    <!--<td class="right">{{$row->userinform1->totaltime}}</td>-->
                    <td class="right">{{$row->userinform1->tta}}</td>
                    @else
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    @endif
                </tr>
            @endforeach

            <tr class="">
                <td class="right">{{$data['total']}}</td>
                <td class="right">{{$data['answered']}}</td>
                <td class="right">{{$data['abandoned']}}</td>
                <td class="right">{{$data['callback']}}</td>
                <td class="right">{{$data['outgoing']}}</td>
                <td class="right">{{$data['dropped']}}</td>
                <td class="right">{{$data['available']}}</td>
                <td class="right">{{$data['idle']}}</td>
                <td class="right">{{$data['busy']}}</td>
                <td class="right">{{$data['wrapup']}}</td>
                <td class="right">{{$data['onbreak']}}</td>
                <td class="right">{{$data['online']}}</td>
                <!--<td class="right">{{$data['totaltime']}}</td>-->
                <td class="right">{{$data['tta']}}</td>
            </tr>
            </tbody>
        </table>

        @foreach ($data['detailed'] as $key => $row)
        @if(!empty($row->userinform))
            <?php $date_val = '' ;?>
        <p style="margin-left:5px;margin-top: 15px; margin: 0px;" ><b>{{$row->agent}}</b></p>
        <table class="grid" style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>Time</b></th>
                <th><b>Caller ID</b></th>
                <th><b>Origin</b></th>
                <th><b>Call Type</b></th>
                <th><b>Channel</b></th>
                <th><b>Type</b></th>
                <th><b>Taken By</b></th>
                <th><b>Status</b></th>
                <th><b>Duration</b></th>
                <th><b>TTA</b></th>
                <th><b>Time on queue</b></th>
            </tr>
            </thead>
            <tbody>

                @foreach ($row->userinform as $key => $row1)
                <tr class="">
                    @if($date_val != $row1->date )
                    <td >{{$row1->date}}{{"  "}} {{$row1->time}}</td>
                        <?php
                        $date_val = $row1->date;
                        ?>
                    @else
                        <td  style="padding-left: 45px;">{{$row1->time}}</td>
                    @endif
                    <td align="center">{{$row1->callerid}}</td>
                    <td align="center">{{$row1->origin}}</td>
                    <td align="center">{{$row1->calltype}}</td>
                    <td align="center">{{$row1->channel}}</td>
                    <td align="center">{{$row1->type}}</td>
                    @if($row1->callback_flag==2 || $row1->missed_flag==2 || $row1->abandon_flag==2)
                    <td align="center">{{$row1->agent_taken}}</td>
                     @else
                    <td align="center"></td>
                    @endif
                    <td align="center">{{$row1->status}}</td>
                    <td align="center">{{$row1->duration}}</td>
                    <td align="center">{{$row1->tta}}</td>
                    <td align="center">{{$row1->queue}}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="11"> &nbsp;</td>
                </tr>

            </tbody>
        </table>
        @endif
        @endforeach

      
        <?php $date_val = '' ;?>
        @if(!empty($data['noagent']))
        <table class="grid" style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>Time</b></th>
                <th><b>Caller ID</b></th>
                <th><b>Origin</b></th>
                <th><b>Call Type</b></th>
                <th><b>Channel</b></th>
                <th><b>Type</b></th>
                <th><b>Taken By</b></th>
                <th><b>Status</b></th>
                <th><b>Duration</b></th>
                <th><b>TTA</b></th>
                <th><b>Time on queue</b></th>
            </tr>
            </thead>
            <tbody>

                @foreach ($data['noagent'] as $key => $row1)
                <tr class="">
                    @if($date_val != $row1->date )
                    <td >{{$row1->date}}  {{$row1->time}}</td>
                        <?php
                        $date_val = $row1->date;
                        ?>
                    @else
                        <td  style="padding-left: 45px;">{{$row1->time}}</td>
                    @endif
                    <td align="center">{{$row1->callerid}}</td>
                    <td align="center">{{$row1->origin}}</td>
                    <td align="center">{{$row1->calltype}}</td>
                    <td align="center">{{$row1->channel}}</td>
                    <td align="center">{{$row1->type}}</td>
                    @if($row1->callback_flag==2 || $row1->missed_flag==2 || $row1->abandon_flag==2)
                    <td align="center">{{$row1->agent_taken}}</td>
                     @else
                    <td align="center"></td>
                    @endif
                    <td align="center">{{$row1->status}}</td>
                    <td align="center">{{$row1->duration}}</td>
                    <td align="center">{{$row1->tta}}</td>
                    <td align="center">{{$row1->queue}}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="11"> &nbsp;</td>
                </tr>

            </tbody>
        </table>
      @endif

    </div>
@endif
