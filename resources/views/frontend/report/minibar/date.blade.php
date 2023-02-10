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

@foreach ($data['minibar_list'] as  $key => $data_group)
    <div style="margin-top: 5px">
        <p style="margin: 0px"><b>{{$data['group_key']}} : {{$key}}</b></p>
        <table class="grid"  style="width : 100%;">
            <thead style="background-color:#3c6f9c" >
            @if ($data['report_type'] == 'Detailed')
                <tr>
                    @foreach($data['fields'] as $key1=>$value)
                        @if( $key1 != $omit_num )
                            <th><b>{{$data['fields'][$key1]}}</b></th>
                        @endif
                    @endforeach
                </tr>
            @endif
            </thead>
            <tbody>
            <?php
            $total_count = 0;
            $price = 0;
            $total_count_lost = 0;
            $price_lost = 0;
            ?>
            @foreach($data_group as $row)
                <?php
                
                $data_row = array();
                $name = $row->guest_name;
                if($row->guest_id == 0 ) $name = 'Lost Posting';
                $data_row[] = $name;
                $data_row[] = substr($row->created_at, 0, 10);
                $data_row[] = $row->time;
                $data_row[] = $row->room;
                $data_row[] = $row->wholename;
                $data_row[] = $row->item_name;
                $data_row[] = $row->item_quantity;
                $data_row[] = $row->item_price;
                $data_row[] = $row->item_total;

                if($row->guest_id != 0){
                    $total_count +=  $row->item_quantity ;
                    $price += $row->item_total;
                }
                if($row->guest_id == 0){
                    $total_count_lost +=  $row->item_quantity;
                    $price_lost += $row->item_total;
                }
                ?>
                @if ($data['report_type'] == 'Detailed')
                    <tr class="">
                        @foreach($data_row as $key1=>$value)
                            @if( $key1 != $omit_num )
                                <td class="right">{{$data_row[$key1]}}</td>
                            @endif
                        @endforeach
                    </tr>
                @else
                    <tr style="display: none"><td colspan="3"></td></tr>
                @endif
            @endforeach
            <tr class="">
                <td colspan="1">Summary</td>
                <td  class="right" colspan="6">Total {{getEmptyValue($total_count)}} items</td>
                <td class="right">{{$data['currency']}} {{getEmptyValue(number_format($price, 2))}}</td>
            </tr>
            <tr class="">
                <td  class="right" colspan="7"> Total Lost Posting {{getEmptyValue($total_count_lost)}} items</td>
                <td class="right">Total Lost Amount {{$data['currency']}} {{getEmptyValue(number_format($price_lost, 2))}}</td>
            </tr>
            <?php

            $grand_total += $price;
            $grand_count += $total_count;
            $grand_lost_total += $price_lost;
            $grand_lost_count += $total_count_lost;

            ?>
            </tbody>
        </table>

    </div>
@endforeach

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

@if($data['report_type'] == 'Stock' )
	<div style="margin-top: 10px">
        <table class="grid print-friendly" border="0" style="width : 100%;" >
		<thead style="background-color:#3c6f9c">
			@if ($data['report_type'] == 'Stock')
                <tr>
                    @foreach($data['fields1'] as $key1=>$value)
                        @if( $key1 != $omit_num )
                            <th><b>{{$data['fields1'][$key1]}}</b></th>
                        @endif
                    @endforeach
                </tr>
            @endif
		</thead>
        <tbody>
			@foreach ($data['data_list1'] as $row)
				<?php
				$data_row = array();
				$data_row[] = $row->ivr_code;
				$data_row[] = $row->item_name;
				$data_row[] = $row->item_stock;
				$data_row[] = $row->charge;
				$data_row[] = $row->alarm_count;
				?>
				 <tr class="">
					@foreach($data_row as $key1=>$value)                        
                        <td class="right">{{$data_row[$key1]}}</td>
                    @endforeach
				</tr>
			@endforeach			
		</tbody>
        </table>                        
    </div>    
@endif
