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

    <div>
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c">
            <tr>
                <th><b>Date</b></th>
                  @foreach ($data['channel_names'] as $row)
                    <th><b>{{$row}}</b></th>
              @endforeach
            </tr>
              
            </thead>
             <tbody>
       
            @for($j=0;$j<count($data['channel_each']); $j++)
                <tr class="">
                    <td>{{$data['summary'][$j]->date}}</td>
                      @foreach ($data['channel_each'][$j] as $row )
                <td class="right">{{$row}}</td>
             @endforeach  
                  
                </tr>
            @endfor
             <tr>
                    <td colspan={{count($data['channel_total'])+1}}> &nbsp;</td>
                </tr>
             <tr class="">
                    <td>{{$data['total']}}</td>
                    @foreach ($data['channel_total'] as $row1)
                    <td class="right">{{$row1}}</td>
                    @endforeach
            </tr>
                
               
            </tbody>
           
</table>
</div>

@if( $data['report_type'] == 'Detailed')
@foreach ($data['detailed'] as $key => $row)
            <p style="margin-top: 5px; margin-bottom: 0px;" ><b>Channel : {{$row->channel}}</b></p>
            <table class="grid" border="1" style="width : 100%;">
                <thead style="background-color:#3c6f9c">
                <tr>
                    <th><b>Time</b></th>
                    <th><b>Agent</b></th>
                    <th><b>Caller ID</b></th>
                    <th><b>Origin</b></th>
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
                <?php
                if(!empty($row->userinform)) {
                ?>
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
                        <td>{{$row1->origin}}</td>
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