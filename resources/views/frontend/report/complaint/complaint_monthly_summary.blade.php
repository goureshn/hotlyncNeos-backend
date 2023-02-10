<?php
    $key_list = ['inhouse', 'walkin', 'checkout', 'housecomplaint', 'arrival', 'total'];
    $header_list = ['IN_HOUSE', 'WALK-IN', 'CHECKOUT', 'HOUSE COMPLAINT', 'ARRIVAL', 'TOTAL'];
?>
<style>
    p {
        margin: 0px;    
    }

    td p {
        padding: 0px 0px 0px 5px;
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
                <th align="center" colspan=9><b>Summary</b></th>                
            </tr>    
            <tr class="plain">
                <th align="center">Month</th>
                <th align="center">Average no. of in-house guest complaint</th>
                <th align="center">Average no. of visitor complaints</th>
                <th align="center">Total no. of complaints</th>
                <th align="center">Open Complaints</th>
                <th align="center">Closed Complaints</th>
                <th align="center">Total Compensation Cost</th>
                <th align="center">Average compensation cost per complaint</th>
                <th align="center">Overal Average closure time</th>                
            </tr>            
        </thead>
        <tbody>  
            @foreach($data['monthly_data_list'] as $row)
                <tr class="plain">                    
                    <td align="center">{{$row->year_month1}}</td>
                    <td align="center">{{$row->avg_inhouse_percent}} %</td>
                    <td align="center">{{$row->avg_walkin_percent}} %</td>
                    <td align="center">{{$row->total_cnt}}</td>
                    <td align="center">{{$row->open_cnt}}</td>
                    <td align="center">{{$row->total_closed_cnt}}</td>
                    <td>
                        <p>
                            <span style="float:right">AED</span>
                            <span style="float:right">{{number_format($row->total_cost,2)}}</span>
                        </p>
                    </td>
                    <td>                        
                        <p>
                            <span style="float:right">AED</span>
                            <span style="float:right">{{number_format($row->avg_comp,2)}}</span>
                        </p>
                    </td>
                    <td align="center">{{$row->avg_closure_time}}</td>
                </tr>    
            @endforeach       
        </tbody>
    </table>    
</div>

<br>

@foreach($key_list as $num => $row1)
<?php 
    $closed_cnt_key = $row1 . "_closed_cnt";
    $within_closed_cnt_key = $row1 . "_within_closed_cnt";
    $above_closed_cnt_key = $row1 . "_above_closed_cnt";
    $within_closed_percent_key = $row1 . "_within_closed_percent";
    $above_closed_percent_key = $row1 . "_above_closed_percent";
?>
<div>
    <table class="border" border="1" style="width : 100%;">
        <thead style="background-color:#ffffff">         
            <tr class="plain">
                <th align="center" colspan=6><b>{{$header_list[$num]}}</b></th>                
            </tr>    
            <tr class="plain">
                <th align="center">Month</th>
                <th align="center">Total</th>
                <th align="center">Total within 24hrs</th>
                <th align="center">% within 24hrs</th>
                <th align="center">Total above 24hrs</th>
                <th align="center">% above 24hrs</th>                
            </tr>            
        </thead>
        <tbody>  
            @foreach($data['monthly_data_list'] as $row)
                <tr class="plain">                    
                    <td align="center">{{$row->year_month1}}</td>
                    <td align="center">{{$row->$closed_cnt_key}}</td>
                    <td align="center">{{$row->$within_closed_cnt_key}}</td>
                    <td align="center">{{$row->$within_closed_percent_key}}%</td>
                    <td align="center">{{$row->$above_closed_cnt_key}}</td>
                    <td align="center">{{$row->$above_closed_percent_key}}%</td>
                </tr>    
            @endforeach       
        </tbody>
    </table>    
</div>
@endforeach