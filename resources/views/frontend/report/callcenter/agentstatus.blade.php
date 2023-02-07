<br/><br/>
<?php
//echo json_encode($data['summary']);
function getEmptyValue($value) {
    if( empty($value) )
        return '&nbsp;';
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
                <p style="margin-top: 5px; margin-bottom: 0px;"><b>{{$row->date}}</b></p>
                <table class="grid" border="1" style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th><b>Agent</b></th>
                        <th><b>Online</b></th>
                        <th><b>Available</b></th>
                        <th><b>Busy</b></th>
                        <th><b>Hold</b></th>
                        <th><b>Idle</b></th>
                        <th><b>On Break</b></th>
                        <th><b>Wrap up</b></th>
                        <th><b>Away</b></th>
                    </tr>
                    </thead>
                    @foreach ($row->status as $key1 => $row1)
                        <tbody>
                        @foreach ($row1->dateform1 as $key2 => $row2)
                                <tr class="">
                                <td>{{getEmptyValue($row1->agent)}}</td>
                                <td class="right">{{getEmptyValue($row2->online)}}</td>
                                <td class="right">{{getEmptyValue($row2->available)}}</td>
                                <td class="right">{{getEmptyValue($row2->busy)}}</td>
                                <td class="right">{{getEmptyValue($row2->hold)}}</td>
                                <td class="right">{{getEmptyValue($row2->idle)}}</td>
                                <td class="right">{{getEmptyValue($row2->onbreak)}}</td>
                                <td class="right">{{getEmptyValue($row2->wrapup)}}</td>
                                <td class="right">{{getEmptyValue($row2->away)}}</td>
                                </tr>
                        @endforeach
                        </tbody>
                    <?php $count++; ?>
                    @endforeach
                </table>
        @endforeach
    </div>
@endif
@if( $data['report_type'] == 'Detailed')
    <div>
       
        @foreach ($data['detailed'] as $key => $row)
            <?php
            if(!empty($row->userinform)) {
            ?>
            <p style="margin-top: 5px; margin-bottom:0px;" ><b>
                <?php
                foreach($row->userinform as $key => $row1) {
                  echo $row1->agent;
                 break;
                }
                ?>
                </b>
            </p>
            <table class="grid"  style="width : 100%;">
                <thead style="background-color:#3c6f9c">
                <tr>
                    <th><b>DateTime</b></th>
                   
                    <th><b>Time</b></th>
                   
                    <th><b>Extension</b></th>
                 
                    <th><b>Status</b></th>
                   
                    <th><b>Duration</b></th>
                   
                </tr>
                </thead>
                <tbody>
                @foreach ($row->userinform as $key => $row1)
                    <tr class="">
                        @if($date_val != $row1->date )
                            <td>{{$row1->date}}</td>
                            <?php
                            $date_val = $row1->date;
                            ?>
                        @else
                            <td> </td>
                        @endif

                        <td>{{$row1->time}}</td>
                      
                        <td>{{$row1->extension}}</td>
                        <td>{{$row1->status}}</td>
                       
                        <td>{{gmdate('H:i:s', $row1->duration)}}</td>
                       
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4"> &nbsp;</td>
                </tr>

                </tbody>
            </table>
                <?php
                }
                ?>
        @endforeach
    </div>
@endif
