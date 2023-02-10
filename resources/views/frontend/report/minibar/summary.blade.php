<?php
$omit_num = $data['omit_num'];
function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}
$grand_total = 0;
$grand_count = 0;
$grand_lost_count = 0;
$grand_lost_total = 0;
?>

@if ($data['report_type'] == 'Summary' && $data['report_by'] == 'Service Item')
<div style="margin-top: 15px">
    <table class="grid"  style="width : 100%;">
    <thead style="background-color:#3c6f9c" >
          
                            <th><b>Item</b></th>
                            <th><b>Charge</b></th>
                            <th><b>Total Quantity</b></th>
                            <th><b>Total Amount</b></th>
                            <th><b>Total Lost Quantity</b></th>
                            <th><b>Total Lost Amount</b></th>
                        
         
            </thead>
            <tbody>
            @foreach ($data['minibar_list'] as  $key => $data_group)
            <?php
                 
                 $total_count = 0;
                 $price = 0;
                 $total_count_lost = 0;
                 $price_lost = 0;

                ?>
            @foreach($data_group as $row)
            <?php
                
                 
                $data_row = array();
         
                $data_row[] = $row->item_name;
           
             if($row->guest_id != 0){
                $total_count +=  $row->item_quantity ;
                $price += $row->item_total;
            }
            if($row->guest_id == 0){
                $total_count_lost +=  $row->item_quantity;
                $price_lost += $row->item_total;
            }
               
                ?>
          
              
            @endforeach
            <tr class="">
                        @foreach($data_row as $key1=>$value)
                          
                                <td class="left">{{$data_row[$key1]}}</td>  
                        @endforeach
                        <td class="right"><b>{{$data['currency']}} {{number_format($row->item_price,2)}}</b></td>
                        <td align="center"><b>{{$total_count}}</b></td>
                  <td class="right"><b>{{$data['currency']}} {{getEmptyValue(number_format($price, 2))}}</b></td>
                  <td align="center"><b>{{$total_count_lost}}</b></td>
                  <td class="right"><b>{{$data['currency']}} {{getEmptyValue(number_format($price_lost, 2))}}</b></td> 
                       
                    </tr>
                    <?php

            $grand_total += $price;
            $grand_count += $total_count;
            $grand_lost_total += $price_lost;
            $grand_lost_count += $total_count_lost;

            ?>
               
            @endforeach

            </tbody>

    </table>

    </div>
@endif



<br><br>
<table   style="width : 100%;background-color:#DCDCDC;">
        <tr class="">
                <td  class="right" colspan="7"><b>Grand Total {{getEmptyValue($grand_count)}} items</b></td>
                <td class="right"><b>{{$data['currency']}} {{getEmptyValue(number_format($grand_total, 2))}}</b></td>
            </tr>
            <tr class="">
                <td  class="right" colspan="7"><b>Grand Total Lost Posting {{getEmptyValue($grand_lost_count)}} items</b></td>
                <td class="right"><b>Grand Total Lost Amount {{$data['currency']}} {{getEmptyValue(number_format($grand_lost_total, 2))}}</b></td>
            </tr>
</table>
