<?php    
   $rpeort_type = $data['report_type'];
?>
<br><br>

@if($rpeort_type == 'Category' )
    <div>
        <table  border="0" style="width : 100%;">
            <thead style="background-color:#ffffff">
                <tr class="plain">                    
                    <th align="center"><b>Category Name</b></th>
                    <th align="center"><b>Compensation</b></th>
                    <th align="center"><b>Cost</b></th>
                    <th align="center"><b>Location</b></th>
                </tr>   
            </thead>
            <tbody>
            @foreach ($data['category_list'] as $key => $row)
                <tr class="plain">                    
                    <td align="center">{{$row->name}}</td>
                    <td align="center">{{$row->compensation}}</td>
                    <td align="center">{{$row->cost}}</td>
                    <td align="center">{{$row->loc_name}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    
       
 @endif      

