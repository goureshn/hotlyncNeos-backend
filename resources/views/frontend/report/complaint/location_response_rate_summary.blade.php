<?php

?>
<style>
    p {
        margin: 0px;
    }

    table.border {
       border-collapse: collapse;
    }

    .border th {
        border: 1px solid black;        
        padding: 2px 0px 2px 0px;
    }
 
</style>
<br><br><br>

<div>
    <table class="border" border="1" style="width : 100%;">
        <thead style="background-color:#ffffff">
            <tr class="plain">                    
                <th align="center" rowspan=3><b>Location</b></th>
                <th align="center" rowspan=2><b>Open</b></th>
                <th align="center" rowspan=2><b>Re-open</b></th>
                <th align="center" rowspan=2><b>Closed</b></th>
                <th align="center" colspan=10><b>Closed</b></th>
            </tr>   
            <tr class="plain">
                <th align="center" colspan=3><b>Major</b></th>
                <th align="center" colspan=3><b>Minor</b></th>
                <th align="center" colspan=4><b>Overall Total</b></th>
            </tr>    
            <tr class="plain">
                <th align="center">Total</th>
                <th align="center">Total</th>
                <th align="center">Total</th>
                <th align="center">Total</th>
                <th align="center"><=1 day</th>
                <th align="center">% Response Rate</th>
                <th align="center">Total</th>
                <th align="center"><=7 days</th>
                <th align="center">% Response Rate</th>
                <th align="center"><=1 day</th>
                <th align="center">% Response Rate</th>
                <th align="center"><=7 day</th>
                <th align="center">% Response Rate</th>
            </tr>            
        </thead>
        <tbody>  
            @foreach($data['response_rate_data'] as $row)
            <tr>
                <td align="center">{{$row->location_name}}</td>
                <td align="center">{{$row->open_cnt}}</td>
                <td align="center">{{$row->reopen_cnt}}</td>
                <td align="center">{{$row->closed_cnt}}</td>
                <td align="center">{{$row->major_total_cnt}}</td>
                <td align="center">{{$row->major_one_day_cnt}}</td>
                <td align="center">{{$row->major_one_day_percent}}%</td>
                <td align="center">{{$row->minor_total_cnt}}</td>
                <td align="center">{{$row->minor_seven_day_cnt}}</td>
                <td align="center">{{$row->minor_seven_day_percent}}%</td>
                <td align="center">{{$row->closed_one_day_cnt}}</td>
                <td align="center">{{$row->closed_one_day_percent}}%</td>
                <td align="center">{{$row->closed_seven_day_cnt}}</td>
                <td align="center">{{$row->closed_seven_day_percent}}%</td>
            </tr>    
            @endforeach       
        </tbody>
    </table>    
</div>
