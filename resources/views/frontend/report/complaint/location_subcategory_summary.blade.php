<?php

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
 
</style>
<br><br><br>

@foreach($data['dept_loctype_subcategory'] as $row)
    <div>        
        <p align="center" style="color : red;"><b>{{$row->department}}</b></p> 
     {{--   <p>Location Type: {{$row->loc_type_name_list}}</p>         --}}
    </div>    
    @foreach($row->loc_type_list as $row1)
        @if(count($row1->subcategory_count) > 0)
            <div>        
                <br>
                <p><b>{{$row->department}} - {{$row1->type}}</b></p>                  
            </div>    
            <div>
                <table  border="1" style="width : 100%;">
                    <thead style="background-color:#ffffff">
                        <tr class="plain">                    
                            <th align="center" style="width : 60%;"><b>Sub Category</b></th>
                            <th align="center" style="width : 20%;"><b>Percent</b></th>
                            <th align="center" style="width : 20%;"><b>Count</b></th>                        
                        </tr>   
                    </thead>
                    <tbody>        
                        @foreach($row1->subcategory_count as $row2)
                            <tr class="plain">                    
                                <td align="center">{{$row2->subcategory_name}}</td>
                                <td align="center">{{$row2->percent}}%</td>
                                <td align="center">{{$row2->cnt}}</td>                        
                            </tr>
                        @endforeach
                        <tr class="plain">                    
                            <td align="right" colspan = 2><b>Total</b></td>
                            <td align="center"><b>{{$row1->subcategory_total_count}}</b></td>                        
                        </tr>
                    </tbody>
                </table>    
            </div>    
        @endif    
    @endforeach 
    <hr>   
@endforeach
