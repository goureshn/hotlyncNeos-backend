
<?php

function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}
$date_val = '';
function convertDateTime($val) {
     $date_val1 = date_format(new DateTime($val),'d-M-Y H:i:s');
        return  $date_val1;   
    }
?>
     @if($data['report_type'] == 'Summary' )
    <div style= "margin-top: 10px">
        <table  border="0" style="width : 100%;">
            <thead style="background-color:#ffffff">
            <tr class="plain">
                <th><b>Building</b></th>
                <th><b>Pending</b></th>
                <th><b>Success</b></th>
                <th><b>Failed</b></th>
                <th><b>Canceled</b></th>
                <th><b>Total</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data['building'] as $row)
                    <tr class="plain">
                        <td align="center">{{getEmptyValue($row->name)}}</td>
                        <td align="center">{{getEmptyValue($row->pending)}}</td>
                        <td  align="center">{{getEmptyValue($row->success)}}</td>
                        <td  align="center">{{getEmptyValue($row->failed)}}</td>
                        <td  align="center">{{getEmptyValue($row->canceled)}}</td>
                        <td  align="center">{{getEmptyValue($row->total)}}</td>
                    </tr>
            @endforeach
            <tr>
                <td colspan="7"> &nbsp;</td>
            </tr>
            <tr>
                <td colspan="7"> &nbsp;</td>
            </tr>
            </tbody>
        </table>


    </div>
    
    @endif


    @if($data['report_type'] == 'Detailed' )
    @foreach ($data['wakeup_list'] as  $key => $data_group)
     <div style="margin-top: 5px">
        <p style="margin: 0px">
        @if( $data['report_by'] != 'Date')
            <b>{{$data['report_by']}} :</b> {{$key}}
        @else
            <b>{{$data['report_by']}} :</b> {{date("d-M-Y", strtotime($data_group[0]->time))}}
        @endif
        </p>
        <table  border="0" style="width : 100%;">
            <thead style="background-color:#ffffff">
            <tr class="plain">
                <th><b>ID</b></th>
                @if( $data['report_by'] != 'Date') <th><b>DateTime</b></th> @endif
                <th><b>Guest</b></th>
                @if( $data['report_by'] != 'Room')<th><b>Room</b></th> @endif
                <th><b>Set Time</b></th>
                <th><b>Set-by</b></th>
                <th><b>Attempts</b></th>
                @if( $data['report_by'] != 'Status')<th><b>Status</b></th> @endif
            </tr>
            </thead>
            <tbody>
            @foreach ($data_group as $row)
                    <tr class="plain">
                        <td align="center">{{$row->id}}</td>
                        @if( $data['report_by'] != 'Date')<td align="center">{{$row->time}}</td> @endif
                        <td align="center">{{$row->guest_name}}</td>
                        @if( $data['report_by'] != 'Room')<td align="center">{{$row->room}}</td> @endif
                        <td align="center">{{$row->date}} {{$row->set_time}}</td>
                        <td align="center">{{$row->set_by}}</td>
                        <td align="center">{{$row->attempts}}</td>
                        @if( $data['report_by'] != 'Status')<td align="center">{{$row->status}}</td> @endif
                    </tr>
            @endforeach
           
            </tbody>
        </table>


    </div>
    @endforeach
    @endif