<br>

@foreach ($data['summary'] as  $dept => $data_key)

    <div style="margin-top: 5px">
        <p style="margin: 0px"> <b>Department :</b> {{$dept}}</p>
        <table class="grid" style="width : 100%">
            <thead>
                <tr style="background-color: #c2dbec;">
                   

                    <th><b>Extension</b></th>
                    <th ><b>Total Calls</b></th>
                    <th ><b>Ans Total</b></th>
                    <th ><b>UnAns Total</b></th>
                    <th ><b>Incmg Int Ans Calls / %</b></th>
                    <th ><b>Outg Int Ans Calls / %</b></th>
                    <th ><b>Incmg Ext Ans Calls / %</b></th>
                    <th ><b>Outg Ext Ans Calls / %</b></th>
                    <th ><b>Incmg Int UnAns Calls / %</b></th>
                    <th ><b>Outg Int UnAns Calls / %</b></th>
                    <th ><b>Incmg Ext UnAns Calls / %</b></th>
                    <th ><b>Outg Ext UnAns Calls / %</b></th>
                    <th ><b>00:00 07:00</b></th>
                    <th ><b>07:00 08:00</b></th>
                    <th ><b>08:00 09:00</b></th>
                    <th ><b>09:00 10:00</b></th>
                    <th ><b>10:00 11:00</b></th>
                    <th ><b>11:00 12:00</b></th>
                    <th ><b>12:00 13:00</b></th>
                    <th ><b>13:00 14:00</b></th>
                    <th ><b>14:00 15:00</b></th>
                    <th ><b>15:00 16:00</b></th>
                    <th ><b>16:00 17:00</b></th>
                    <th ><b>17:00 18:00</b></th>
                    <th ><b>18:00 19:00</b></th>
                    <th ><b>19:00 20:00</b></th>
                    <th ><b>20:00 21:00</b></th> 
                    <th ><b>21:00 22:00</b></th>
                    <th ><b>22:00 23:00</b></th>
                    <th ><b>23:00 24:00</b></th>
                </tr>
            </thead>

            <?php
            $total_total = 0;
            $total_answered = 0;
            $total_unanswered = 0;
            $total_ans_int_incoming = 0;
            $total_ans_int_outgoing = 0;
            $total_ans_ext_incoming = 0;
            $total_ans_ext_outgoing = 0;
            $total_unans_int_incoming = 0;
            $total_unans_int_outgoing = 0;
            $total_unans_ext_incoming = 0;
            $total_unans_ext_outgoing = 0;
            $total_unans_int_inc_per = 0;
            $total_unans_int_out_per = 0;
            $total_unans_ext_inc_per = 0;
            $total_unans_ext_out_per = 0;
            $total_ans_int_inc_per = 0;
            $total_ans_int_out_per = 0;
            $total_ans_ext_inc_per = 0;
            $total_ans_ext_out_per = 0;
            $total_seven = 0;
            $total_eight = 0;
            $total_nine = 0;
            $total_ten = 0;
            $total_eleven = 0;
            $total_twelve= 0;
            $total_thirteen= 0;
            $total_fourteen = 0;
            $total_fifteen = 0;
            $total_sixteen = 0;
            $total_seventeen = 0;
            $total_eighteen = 0;
            $total_ninteen = 0;
            $total_twenty = 0;
            $total_twentyone = 0;
            $total_twentytwo = 0;
            $total_twentythree = 0;
            $total_twentyfour = 0;
            $unans_int_per = 0;
            $unans_ext_per = 0;
            ?>
       
            
            @foreach ($data_key as $row1)

            <?php
                if ($row1->tot_unans != 0)
                {
                   $unans_int_inc_per = ($row1->unanswered_int_incoming / $row1->tot_unans ) * 100;
                   $unans_int_out_per = (($row1->unanswered_int1_outgoing + $row1->unanswered_int2_outgoing) / $row1->tot_unans ) * 100;
                   $unans_ext_inc_per = ($row1->unanswered_ext_incoming / $row1->tot_unans  ) * 100;
                   $unans_ext_out_per = (($row1->unanswered_ext1_outgoing + $row1->unanswered_ext2_outgoing) / $row1->tot_unans  ) * 100;
                }else{
                    $unans_int_inc_per = 0;
                    $unans_int_out_per = 0;
                    $unans_ext_inc_per = 0;
                    $unans_ext_out_per = 0;
                }

                if ($row1->tot_ans != 0)
                {
                   $ans_int_inc_per = ($row1->answered_int_incoming / $row1->tot_ans ) * 100;
                   $ans_int_out_per = ($row1->answered_int_outgoing  / $row1->tot_ans ) * 100;
                   $ans_ext_inc_per = ($row1->answered_ext_incoming / $row1->tot_ans  ) * 100;
                   $ans_ext_out_per = ($row1->answered_ext_outgoing / $row1->tot_ans  ) * 100;
                }else{
                    $ans_int_inc_per = 0;
                    $ans_int_out_per = 0;
                    $ans_ext_inc_per = 0;
                    $ans_ext_out_per = 0;
                }

            ?>
               
                    <tbody>
                   
                        <tr class="">
                            <td class="right">{{$row1->extension}}</td>
                            <td class="right">{{$row1->total_calls}}</td>
                            <td class="right">{{$row1->tot_ans}}</td>
                            <td class="right">{{$row1->tot_unans}}</td>
                            <td class="right">{{$row1->answered_int_incoming}} / {{number_format($ans_int_inc_per,2)}} </td>
                            <td class="right">{{$row1->answered_int_outgoing}} / {{number_format($ans_int_out_per,2)}}</td>
                            <td class="right">{{$row1->answered_ext_incoming}} / {{number_format($ans_ext_inc_per,2)}}</td>
                            <td class="right">{{$row1->answered_ext_outgoing}} / {{number_format($ans_ext_out_per,2)}}</td>
                            <td class="right">{{$row1->unanswered_int_incoming}} / {{number_format($unans_int_inc_per,2)}} </td>
                            <td class="right">{{$row1->unanswered_int1_outgoing + $row1->unanswered_int2_outgoing}} / {{number_format($unans_int_out_per,2)}} </td>
                            <td class="right">{{$row1->unanswered_ext_incoming}} / {{number_format($unans_ext_inc_per,2)}}</td>
                            <td class="right">{{$row1->unanswered_ext1_outgoing + $row1->unanswered_ext2_outgoing}} / {{number_format($unans_ext_out_per,2)}}</td>
                            <td class="right">{{$row1->seven}}</td>
                            <td class="right">{{$row1->eight}}</td>
                            <td class="right">{{$row1->nine}}</td>
                            <td class="right">{{$row1->ten}}</td>
                            <td class="right">{{$row1->eleven}}</td>
                            <td class="right">{{$row1->twelve}}</td>
                            <td class="right">{{$row1->thirteen}}</td>
                            <td class="right">{{$row1->fourteen}}</td>
                            <td class="right">{{$row1->fifteen}}</td>
                            <td class="right">{{$row1->sixteen}}</td>
                            <td class="right">{{$row1->seventeen}}</td>
                            <td class="right">{{$row1->eighteen}}</td>
                            <td class="right">{{$row1->ninteen}}</td>
                            <td class="right">{{$row1->twenty}}</td>
                            <td class="right">{{$row1->twentyone}}</td>
                            <td class="right">{{$row1->twentytwo}}</td>
                            <td class="right">{{$row1->twentythree}}</td>
                            <td class="right">{{$row1->twentyfour}}</td>
                        </tr>
                   
                    
                    </tbody>
                    <?php
                    $total_total += $row1->total_calls;
                    $total_answered += $row1->tot_ans;
                    $total_unanswered += $row1->tot_unans;
                    $total_ans_int_incoming += $row1->answered_int_incoming;
                    $total_ans_int_outgoing += $row1->answered_int_outgoing;
                    $total_ans_ext_incoming += $row1->answered_ext_incoming;
                    $total_ans_ext_outgoing += $row1->answered_ext_outgoing;
                    $total_unans_int_incoming += $row1->unanswered_int_incoming;
                    $total_unans_int_outgoing += $row1->unanswered_int1_outgoing + $row1->unanswered_int2_outgoing;
                    $total_unans_ext_incoming += $row1->unanswered_ext_incoming;
                    $total_unans_ext_outgoing += $row1->unanswered_ext1_outgoing + $row1->unanswered_ext2_outgoing;
                   
                    $total_seven += $row1->seven;
                    $total_eight += $row1->eight;
                    $total_nine += $row1->nine;
                    $total_ten += $row1->ten;
                    $total_eleven += $row1->eleven;
                    $total_twelve += $row1->twelve;
                    $total_thirteen += $row1->thirteen;
                    $total_fourteen += $row1->fourteen;
                    $total_fifteen += $row1->fifteen;
                    $total_sixteen += $row1->sixteen;
                    $total_seventeen += $row1->seventeen;
                    $total_eighteen += $row1->eighteen;
                    $total_ninteen += $row1->ninteen;
                    $total_twenty += $row1->twenty;
                    $total_twentyone += $row1->twentyone;
                    $total_twentytwo += $row1->twentytwo;
                    $total_twentythree += $row1->twentythree;
                    $total_twentyfour += $row1->twentyfour;
                    ?>
              
            @endforeach

           
                <tbody>

                <?php
                    if ($total_unanswered != 0){
                        $total_unans_int_inc_per = ($total_unans_int_incoming / $total_unanswered) * 100;
                        $total_unans_int_out_per = ($total_unans_int_outgoing / $total_unanswered) * 100;
                        $total_unans_ext_inc_per = ($total_unans_ext_incoming / $total_unanswered) * 100;
                        $total_unans_ext_out_per = ($total_unans_ext_outgoing / $total_unanswered) * 100;
                    }else{
                        $total_unans_int_inc_per = 0;
                        $total_unans_int_out_per = 0;
                        $total_unans_ext_inc_per = 0;
                        $total_unans_ext_out_per = 0;
                    }
                    if ($total_answered != 0){
                        $total_ans_int_inc_per = ($total_ans_int_incoming / $total_answered) * 100;
                        $total_ans_int_out_per = ($total_ans_int_outgoing / $total_answered) * 100;
                        $total_ans_ext_inc_per = ($total_ans_ext_incoming / $total_answered) * 100;
                        $total_ans_ext_out_per = ($total_ans_ext_outgoing / $total_answered) * 100;
                    }else{
                        $total_ans_int_inc_per = 0;
                        $total_ans_int_out_per = 0;
                        $total_ans_ext_inc_per = 0;
                        $total_ans_ext_out_per = 0;
                    }
                ?>
             
                <tr class="">
                   
                    <td class="right" style="background-color:#CFD8DC;"><b>TOTAL</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_total}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_answered}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_unanswered}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_ans_int_incoming}} / {{number_format($total_ans_int_inc_per,2)}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_ans_int_outgoing}} / {{number_format($total_ans_int_out_per,2)}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_ans_ext_incoming}} / {{number_format($total_ans_ext_inc_per,2)}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_ans_ext_outgoing}} / {{number_format($total_ans_ext_out_per,2)}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_unans_int_incoming}} / {{number_format($total_unans_int_inc_per,2)}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_unans_int_outgoing}} / {{number_format($total_unans_int_out_per,2)}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_unans_ext_incoming}} / {{number_format($total_unans_ext_inc_per,2)}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_unans_ext_outgoing}} / {{number_format($total_unans_ext_out_per,2)}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_seven}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_eight}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_nine}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_ten}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_eleven}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_twelve}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_thirteen}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_fourteen}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_fifteen}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_sixteen}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_seventeen}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_eighteen}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_ninteen}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_twenty}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_twentyone}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_twentytwo}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_twentythree}}</b></td>
                    <td class="right" style="background-color:#CFD8DC" ><b>{{$total_twentyfour}}</b></td>

                </tr>
                </tbody>
            </table>   
    </div>
   
@endforeach