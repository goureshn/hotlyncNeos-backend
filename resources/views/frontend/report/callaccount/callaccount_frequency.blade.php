<br>

@foreach ($data['summary'] as  $ext => $data_key)

    <div style="margin-top: 5px">
        <p style="margin: 0px"> <b>Extension :</b> {{$ext}}</p>
        <table class="grid" style="width : 100%">
            <thead>
                <tr style="background-color: #c2dbec;">
                   

                    <th><b>Called No</b></th>
                    <th ><b>Total Calls</b></th>
                    <th ><b>Total Duration</b></th>
                    <th ><b>Total Charge</b></th>
                   
                </tr>
            </thead>

            <?php
            $total_total = 0;
            $total_dur = 0;
            $total_chrg = 0;
          
            ?>
       
            
            @foreach ($data_key as $row1)
                    <tbody>
                   
                        <tr class="">
                            <td align="center">{{$row1->called_no}}</td>
                            <td class="right">{{$row1->total_calls}}</td>
                            <td class="right">{{$row1->tot_dur}}</td>
                            <td class="right">{{$row1->tot_chrg}}</td>
                           
                        </tr>
                   
                    
                    </tbody>
                    <?php
                    $total_total += $row1->total_calls;
                    $total_dur += $row1->tot_dur;
                    $total_chrg += $row1->tot_chrg;
                   
                    ?>
              
            @endforeach

           
                <tbody>

               
                <tr class="">
                   
                    <td class="right" style="background-color:#CFD8DC;"><b>TOTAL</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_total}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_dur}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_chrg}}</b></td>
                   

                </tr>
                </tbody>
            </table>   
    </div>
   
@endforeach