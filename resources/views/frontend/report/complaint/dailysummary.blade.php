<?php
 $open_percent = 0;
 $closed_percent = 0;

 function seconds2human($ss) {
    $s = $ss%60;
    $m = floor(($ss%3600)/60);
    $h = floor(($ss%86400)/3600);
    $d = floor(($ss%2592000)/86400);
    $M = floor($ss/2592000);
    if ($d > 0)
        if ($d == 1)
            return "$d day";
        else
            return "$d days";
    else if ($h > 0)
        if ($h == 1)
            return "$h hour";
        else
            return "$h hours";
    else if ($m > 0)
        if ($m == 1)
            return    "$m minute, $s seconds";
        else
            return    "$m minutes, $s seconds";
    else if ($m == 0)
            return " $s seconds";
    else  
            return " $s seconds";   
    }
?>
<style>
    p {
        margin: 0px;
    }

    table.border {
       border-collapse: collapse;
    }

    .border th, .border td {
        border: 1px solid black;
        height: 30px;
        padding: 0px 0px 0px 10px;
    }

    .green {
        background-color: #00b050;
    }

    .red {
        background-color: #FF0000;
    }

    .gray td {
        background-color: #bfbfbf;
    }
</style>
<br><br><br>
<div style="text-align: center">
  
        <b>Complaint Breakdown For {{date('d M Y', strtotime($data['start_time']))}}</b>
  

  
       
</div>    

<div>
    <table  border="1" style="width : 100%;">
        <thead style="background-color:#ffffff">
            <tr class="plain">                    
                <th align="center"><b>Department</b></th>
                <th align="center"><b>Location Type</b></th>
                <th align="center"><b># Complaints</b></th>
                <th align="center"><b># Open Complaints</b></th>
                <th align="center"><b>% Open Complaints</b></th>
                <th align="center"><b># Closed Complaints</b></th>
                <th align="center"><b>% Closed Complaints</b></th>
            </tr>   
        </thead>
        <?php
 $open_percent = 0;
 $closed_percent = 0;
?> 
        <tbody
                                  
        @foreach ($data['dept_wise'] as $key => $row)
            <?php
            if ($row->total != 0){
                $open_percent = ($row->open * 100)/$row->total;
                $closed_percent = ($row->closed * 100) / $row->total;
            }
            ?>
            <tr class="plain">                    
                <td align="center">{{$row->department}}</td>
                <td align="center">{{$row->type}}</td>
                <td align="center">{{$row->total}}</td>
                <td align="center">{{$row->open}}</td>
                @if ($row->open > 0) 
                <td align="center" class="red">{{round($open_percent, 1)}}%</td>
                @else
                <td align="center">{{round($open_percent, 1)}}%</td>
                @endif
                <td align="center">{{$row->closed}}</td>
                <td align="center">{{round($closed_percent, 1)}}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<br/>

<div style="text-align: center">
{{--    @if($data['report_by'] == 'Daily') --}}
    <b>Periodical CFS Reporting</b>
{{--    @endif --}}


    <p>Based on reported date of feedback</p> 
</div>    
<div>
    <table class="border" style="width : 100%;">
        <tbody>        
            <tr class="plain">                                    
                <td>
                 {{--   @if($data['report_by'] == 'Daily') --}}
                        Report Date
                {{--    @endif --}}

                  
                </td>
                <td>
                {{--    @if($data['report_by'] == 'Daily') --}}
                        {{date('d M Y', strtotime($data['start_time']))}} - {{date('d M Y', strtotime($data['end_time']))}}
                {{--    @endif --}}
                
                                  
                </td>        
            </tr>
            <tr class="plain">                    
                <td>Time</td>
                <td>{{date('H:i', strtotime($data['start_time']))}} - {{date('H:i', strtotime($data['end_time']))}}</td>        
            </tr>
            <tr class="plain">                    
                <td>Occupancy</td>
                <td></td>        
            </tr>
           
            <tr class="plain">                    
                <td>
                    <p>Total no. of Sub-complaints</p>
                   
                </td>
                <td>{{$data['total_subcomplaint_count']}}</td>        
            </tr>
            <tr class="plain">                    
                <td>
                    <p>Total no. of  Open Sub-Complaints</p>
                   
                </td>
                <td class="green">{{$data['total_subcomplaint_non_closure_completed_count']}}</td>        
            </tr>

            <tr class="plain">                    
                <td>Total amount of compensation cost (AED)</td>
                <td>AED&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($data['total_subcomplaint_compensation_cost'],2)}}</td>        
            </tr>
            <tr class="plain">                    
                <td>Average amount of compensation cost (AED)</td>
                <td>AED&nbsp;&nbsp;&nbsp;&nbsp;{{number_format($data['average_subcomplaint_compensation_cost'],2)}}</td>        
            </tr>
            <tr class="plain">                    
                <td>
                    <p>Average closure time per closed complaint</p>
                    <p>(Based on reported date of feedback)</p>
                </td>
                @if ( $data['closed_avg_days'] <= 86400)
                    <td class="green">{{seconds2human($data['closed_avg_days'])}}</td> 
                @else
                    <td class="red">{{seconds2human($data['closed_avg_days'])}}</td> 
                @endif  
            </tr>
            <tr class="plain gray">                    
                <td>Total no. of Sub-Area (complaints)</td>
                <td>{{$data['total_subcomplaint_count']}}</td>        
            </tr>
            
            <!-- Category Name & Count -->
        @foreach($data['sub_area_list'] as $row)
        @if($row->count != 0)
            <tr class="plain">                    
                <td>{{$row->name}}</td>
                <td>{{$row->count}}</td>
            </tr>
        @endif
        @endforeach    

            <!-- Sub Category Name & Count -->
            <tr class="plain gray">                    
                <td>Attributes (complaints)</td>
                <td>{{$data['total_subcomplaint_count']}}</td>        
            </tr>
        @foreach($data['attrib_list'] as $row)
        @if($row->count != 0)
            <tr class="plain">                    
                <td>{{$row->name}}</td>
                <td>{{$row->count}}</td>
            </tr>
        @endif
        @endforeach

        </tbody>
    </table>
</div>