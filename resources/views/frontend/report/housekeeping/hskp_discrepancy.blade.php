    
    <div style= "margin-top: 20px">
    @foreach ($data['hskp_list'] as  $key => $data_group)
    <p style="margin: 0px"><b>Room : {{$key}}</b></p>
        <table  border="0" style="width : 100%;">
            <thead style="background-color:#ffffff">
            <tr class="plain">
                <th><b>Room</b></th>
                <th><b>Guest Name</b></th>
                <th><b>Posted By</b></th>
                <th><b>Adult</b></th>
                <th><b>Kids</b></th>
                <th><b>Created Time</b></th>
            </tr>
            </thead>
            <tbody>
           
            @foreach ($data_group as $row)
                    <tr class="plain">
                        <td align="center">{{$row->room}}</td>
                        <td align="center">{{$row->guest_name}}</td>
                        <td align="center">{{$row->wholename}}</td>
                        <td align="center">{{$row->adult}}</td>
                        <td align="center">{{$row->child}}</td>
                        <td align="center">{{$row->created_at}}</td>
                       
                    </tr>
                   
            @endforeach
           
            </tbody>
        </table>
    @endforeach

    </div>
    
   

   