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
@if( $data['report_type'] == 'Detailed')
    <div>
       

        @foreach ($data['detailed'] as $key => $row)
            <p style="margin-top: 5px; margin-bottom:0px;" ><b>{{$row->date}}</b></p>
            <table class="grid"  style="width : 100%;">
                <thead style="background-color:#3c6f9c">
                <tr>
                    <th><b>Time</b></th>
                    <th><b>Extension</b></th>
                    <th><b>Description</b></th>
                    <th><b>Caller ID</b></th>
                   
                    <th><b>Call Type</b></th>
                 
                    <th><b>Status</b></th>
                 
                  
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
                        <td>{{$row1->ext}}</td>
                        <td>{{$row1->description}}</td>
                        <td>{{$row1->callerid}}</td>
                       
                        <td>{{$row1->calltype}}</td>
                     
                        <td>{{$row1->status}}</td>
                       
                     
                    
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

@if ($data['report_type'] == 'Summary')
@if(!empty($data['summary_list']))
@foreach ($data['summary_list'] as $key => $datagroup)
<div>    
<p style="margin : 0px"><b>Date : {{$key}}</b></p>
    <table class="grid print-friendly" border="0" style="width : 100%;" >
        <thead style="background-color:#3c6f9c" >
        
        <tr>
        
                    <th><b>Department</b></th>
                    <th><b>Answered</b></th>
                    <th><b>Answered %</b></th>
                    <th><b>No Input</b></th>
                    <th><b>No Input %</b></th>
                    <th><b>Cancel</b></th>
                    <th><b>Cancel %</b></th>
                    <th><b>Total</b></th>
                   
                  
                  
        </tr>
        <?php
                 $tot = 0;
                 $completed = 0;
                 $opened=0;
                 $escalated=0;
                 $timeout=0;
                 $canceled=0;
                 ?>
        </thead>
        <tbody>
        @foreach ($datagroup as $row)
            <tr class="">            
                <td align = 'center'>{{$row->group_key}}</td>
                <td align = 'center'>{{$row->answered}}</td> 
                <td align = 'center'>{{number_format($row->ans_per,2)}}%</td>
                <td align = 'center'>{{$row->noinput}}</td>
                <td align = 'center'>{{number_format($row->inp_per,2)}}%</td>
                <td align = 'center'>{{$row->cancel}}</td>
                <td align = 'center'>{{number_format($row->can_per,2)}}%</td>
                <td align = 'center'>{{$row->total}}</td>
            </tr>
            <?php 
                $tot += $row->total;
                $completed += $row->answered;
                $opened += $row->noinput;
                $escalated += $row->cancel;
               
            ?>
            @endforeach
            <tr class="">
                    <td style=" background-color:#CFD8DC" align = 'center' ><b>Total</b></td>
                    <td style=" background-color:#CFD8DC"  align = 'center'><b>{{$completed}}</b></td>
                    <td style=" background-color:#CFD8DC"  align = 'center'><b></b></td>
                    <td style=" background-color:#CFD8DC" align = 'center'><b>{{$opened}}</b></td>
                    <td style=" background-color:#CFD8DC" align = 'center'><b></b></td>
                    <td style=" background-color:#CFD8DC" align = 'center'><b>{{$escalated}}</b></td>
                    <td style=" background-color:#CFD8DC" align = 'center'><b></b></td>
                    <td style=" background-color:#CFD8DC" align = 'center'><b>{{$tot}}</b></td>
            </tr>  
        </tbody>    
  </table>
  <br>

</div>
@endforeach
@endif
@endif