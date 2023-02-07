<?php

?>
<br><br>
<!-- Compensation Cost By Department -->
<div>
    <table style="width: 100%;" border="1">
        <tr>
            <th>Department</th>
            <th colspan="2">Total Compensation</th>            
        </tr>
        @foreach ($data['dept_comp_list'] as  $row)
            <tr>
                <td align="center">{{$row->department}}</td>
                <td align="right" colspan="2"><b>{{$data['currency']}}</b> {{number_format($row->dept_cost, 2)}} </td> 
            </tr>
        @endforeach     
        <tr>
            <td align="center"></td>
            <td align="left">Total</td>
            <td align="right"><b>{{$data['currency']}}</b> {{number_format($data['dept_total_cost'], 2)}} </td> 
        </tr>           
    </table>
</div>
<br><br>
<!-- Compensation Cost By Department -->
<div>
    <table style="width: 100%;" border="1">
        <tr>
            <th>Department</th>
            <th>Location Type</th>
            <th>Total Count</th>
            <th>Total</th>
            <th>Total Compensation</th>
        </tr>
        @foreach ($data['dept_comp_list'] as  $row)
            @foreach($row->loc_type_comp_list as $key => $row1)
                <tr>
                    @if( $key == 0 )
                        <td align="center" rowspan={{count($row->loc_type_comp_list)}}>{{$row->department}}</td>
                    @endif    
                    <td align="center">{{$row1->location_type}}</td>
                    <td align="center">{{$row1->count}}</td>
                    <td align="right"><b>{{$data['currency']}}</b> {{number_format($row1->loc_type_cost, 2)}} </td> 
                    @if( $key == 0 )
                        <td align="right" rowspan={{count($row->loc_type_comp_list)}}><b>{{$data['currency']}}</b> {{number_format($row->dept_cost, 2)}} </td> 
                    @endif                            
                </tr>
            @endforeach
        @endforeach          
    </table>
</div>
