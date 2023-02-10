<?php
    
    $group_val = $data['group_by'];
    $repor_by = $data['report_by'];
    $rpeort_type = $data['report_type'];
?>
<br><br>

@if($rpeort_type == 'Frequency' )
    <div>
        <b>Summary</b>
    </div>    
    <div>
        <table  border="0" style="width : 100%;">
            <thead style="background-color:#ffffff">
                <tr class="plain">                    
                    <th align="center"><b>Name</b></th>
                    <th align="center"><b>Frequency</b></th>
                    <th align="center"><b>Cost</b></th>
                </tr>   
            </thead>
            <tbody>
            @foreach ($data['freq_list'] as $key => $row)
                <tr class="plain">                    
                    <td align="center">{{$row->name}}</td>
                    <td align="center">{{$row->count}}</td>
                    <td align="center">{{$row->cost}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <br/>
    <div>
        <b>Detail</b>
    </div>

    @foreach ($data['freq_list'] as $key => $row)  
    @if(count($row->comp_list) > 0) 
        <div>
            <div><b>{{$row->name}} - {{$row->count}}</b></div>            
            <table  border="0" style="width : 100%;">
                <thead style="background-color:#ffffff">
                    <tr class="plain">                    
                        <th align="center"><b>Complaint ID</b></th>
                        <th align="center"><b>Compensation</b></th>
                        <th align="center"><b>Location</b></th>
                        <th align="center"><b>Cost</b></th>
                    </tr>   
                </thead>
                <tbody>
                    @foreach ($row->comp_list as $key1 => $row1)
                        <tr class="plain">                    
                            <td align="center">{{sprintf('C%05d', $row1->complaint_id)}}</td>
                            <td align="center">{{$row1->compensation}}</td>
                            <td align="center">{{$row1->loc_name}}</td>
                            <td align="center">{{$row1->cost}}</td>
                        </tr>
                    @endforeach      
                    <tr class="plain">                    
                        <td align="center"></td>
                        <td align="center"></td>
                        <th align="center"></th>
                        <th align="center">{{$row->cost}}</th>
                    </tr>              
                </tbody>
            </table>    
        </div>
        <br/>    
    @endif    
    @endforeach

    
       
 @endif      

