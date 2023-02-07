

    
    <div style= "margin-top: 5px">
        <table  border="0" style="width : 100%;">
            <thead style="background-color:#ffffff">
            <tr class="plain">
                <th><b>Device</b></th>
                <th><b>Rooms Allocated</b></th>
                <th><b>Total Rooms</b></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $total = 0;
            ?>
            @foreach ($data['hskp_list'] as $row)
                    <tr class="plain">
                        <td align="center">{{$row->name}}</td>
                        <td >{{$row->room_list}} </td>
                        <td align="center">{{$row->count}}</td>
                       
                    </tr>
                    <?php
                    $total += $row->count;
                    ?>
            @endforeach

            <tr class="plain">
                        <td ></td>
                        <td align="right"><b>Total </b></td>
                        <td align="center"><b>{{$total}}</b></td>
                       
                    </tr>
           
            </tbody>
        </table>


    </div>
    
   

   