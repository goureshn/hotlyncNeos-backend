<?php
    $prev_category_name = '';
    $id = 1;
?>

<div style="text-align: center">
    <b>{{$data['name']}}</b>
</div>    

@foreach ($data['checklist_category'] as $row2)
<div>   
   
    
        <div align="left" id="bloc1">
        <p style="margin: -5px 0px 0px 0px">{{$row2['category']}}</p>

        </div>
        <div align="right" id="bloc2">
        <p >Yes - {{$row2['yes']}}  No - {{$row2['no']}}</p>
        </div>
    


    <table  border="0" style="width : 100%;">
        <thead style="background-color:#ffffff">
            <tr class="plain">                                    
                <th align="center"><b>Task</b></th>
                <th align="center"><b>Yes/No</b></th>                     
                <th align="center"><b>Comments</b></th>
                <th align="center"><b>Images</b></th>
                <th align="center"><b>User</b></th>
            </tr>   
        </thead>
        <tbody>
        <?php
        $id = 1;
        ?>
        @foreach ($row2['sublist'] as $row)
            <tr class="plain">                    
            <td align="left">{{$id}} - {{$row->item_name}}</td>
                <td align="center">
                    @if($row->item_type == "Yes/No")
                        @if($row->yes_no == 1 && $row->check_flag == 1)
                            <img style="width:12px;height:12px;" src="{{$data['tick_icon_base64']}}"/>
                        @else
                            <img style="width:12px;height:12px;" src="{{$data['cancel_icon_base64']}}"/>
                        @endif
                    @endif    
                </td>    
                <td align="center">
                    {{$row->comment}}                    
                </td>              
                <td width="100">                    
                    @foreach($row->base64_image_list as $row1)                                                          
                        <a href="{{$row1['url']}}"><img style="width:40px;height:40px;" src="{{$row1['base64']}}"/></a>                                
                    @endforeach                    
                </td>    
                <td align="center" width="60">{{$row->wholename}}</td>
            </tr>
            <?php
            $id += 1;
            ?>
        @endforeach
        </tbody>
    </table>
</div>
@endforeach
