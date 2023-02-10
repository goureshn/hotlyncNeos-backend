<br/><br/>
<?php

function getEmptyValue($value) {
    if( empty($value)|| $value == null )
        return 0;
    else
        return $value;
}

$call_count = 0;
$cost = 0;
$profit = 0;
$total = 0;
$total_cost = 0;
$total_profit = 0;
$total_price = 0;

$carrier_charges = 0;
$hotel_charges = 0;
$tax = 0;
$total_charges = 0;

$total_carrier_charges = 0;
$total_hotel_charges = 0;
$total_tax = 0;
$total_total_charges = 0;


$call_type_count = array();
$call_type_count['Internal'] = 0;
$call_type_count['Mobile'] = 0;
$call_type_count['International'] = 0;
$call_type_count['National'] = 0;
$call_type_count['Local'] = 0;
$call_type_count['Received'] = 0;
$call_type_count['Toll Free'] = 0;
$call_type_count['Total'] = 0;

$call_type_price = array();
$call_type_price['Internal'] = 0;
$call_type_price['Mobile'] = 0;
$call_type_price['International'] = 0;
$call_type_price['National'] = 0;
$call_type_price['Local'] = 0;
$call_type_price['Received'] = 0;
$call_type_price['Toll Free'] = 0;
$call_type_price['Total'] = 0;
$duration = 0;

?>
<div style="margin-top: 15px">
    <!--<p align="center"; style="font-size:16px;"> Guest Grand Total By Building </p>-->
    <b style="margin-bottom: 5px">Guest</b>
    <table class="" style="width : 100%">
        <thead>
        <tr>
            <th width=10%><b>Building</b></th>
            <th width=10%><b>International</b></th>
            <th width=10%><b>Local</b></th>
            <th width=10%><b>Mobile</b></th>
            <th width=10%><b>Carrier</b></th>
            <th width=10%><b>Hotel</b></th>
            <th width=10%><b>Total</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['guest_by_build_data'] as $row)
            <tr class="">
                <td>{{$row->name}}</td>
                <td class="right">{{number_format($row->International, 2)}}</td>
                <td class="right">{{number_format($row->Local, 2)}}</td>
                <td class="right">{{number_format($row->Mobile, 2)}}</td>
                <td class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                <td class="right">{{number_format($row->Total_Hotel, 2)}}</td>
                <td class="right">{{number_format($row->Total, 2)}}</td>
            </tr>
        @endforeach
        <tr class="total-amount">
            <td class="summary" align="right";><b>Total</b></td>
            <td class="summary" align="right";><b>{{$data['currency']}} {{number_format($data['guest_total_value']->International, 2)}}</b></td>
            <td class="summary" align="right";><b>{{$data['currency']}} {{number_format($data['guest_total_value']->Local, 2)}}</b></td>
            <td class="summary" align="right";><b>{{$data['currency']}} {{number_format($data['guest_total_value']->Mobile, 2)}}</b></td>
            <td class="summary" align="right";><b>{{$data['currency']}} {{number_format($data['guest_total_value']->Total_Carrier, 2)}}</b></td>
            <td class="summary" align="right";><b>{{$data['currency']}} {{number_format($data['guest_total_value']->Total_Hotel, 2)}}</b></td>
            <td class="summary" align="right";><b>{{$data['currency']}} {{number_format($data['guest_total_value']->Total, 2)}}</b></td>
        </tr>
        <tr>
            <td colspan="7"> &nbsp;</td>
        </tr>
        </tbody>
    </table>
</div>

@if( $data['report_type'] == 'guest')
    <div>
        <table  border="0" style="width : 100%;">
            <?php  $i = 0;?>
            @foreach ($data['guestcall'] as $key => $row)
                @if($call_count == 0)
                    <thead>
                    <tr>
                        <td class="summary"><b>Room : {{$row->room}} </b></td>
                    </tr>
                    <tr class="plain">
                        <th><b>Date</b></th>
                        <th><b>Time</b></th>
                        <th><b>Extension</b></th>
                        <th><b>Guest Name</b></th>
                        <th><b>Called No</b></th>
                        <th><b>Duration</b></th>
                        <th><b>Call Type</b></th>
                        <th><b>Destination</b></th>
                        <th><b>Carrier</b></th>
                        <th><b>Hotel</b></th>
                        <th><b>Tax</b></th>
                        <th><b>Total</b></th>
                        {{--<th><b>Room</b></th>--}}
                        {{--<th><b>Extension</b></th>--}}
                        {{--<th><b>Date & Time</b></th>--}}
                        {{--<th><b>Called #</b></th>--}}
                        {{--<th><b>Destination</b></th>--}}
                        {{--<th><b>Duration</b></th>--}}
                        {{--<th><b>Cost</b></th>--}}
                        {{--<th><b>Profit</b></th>--}}
                        {{--<th><b>Total</b></th>--}}
                    </tr>
                    </thead>
                    <tbody>
                @endif
                <tr class="">
                        <td >{{getEmptyValue(date_format(new DateTime($row->call_date),'d-M-Y'))}}</td>
                        <td >{{getEmptyValue($row->start_time)}}</td>
                        <td >{{getEmptyValue($row->extension)}}</td>
                        <td >{{getEmptyValue($row->guest_name)}}</td>
                        <td >{{getEmptyValue($row->called_no)}}</td>
                        <td >{{gmdate("H:i:s", $row->duration)}}</td>
                        <td >{{getEmptyValue($row->call_type)}}</td>
                        <td class="right">{{getEmptyValue($row->country)}}</td>
                        <td class="right">{{getEmptyValue($row->carrier_charges)}}</td>
                        <td class="right">{{getEmptyValue($row->hotel_charges)}}</td>
                        <td class="right">{{getEmptyValue($row->tax)}}</td>
                        <td class="right">{{getEmptyValue($row->total_charges)}}</td>
                </tr>
            <?php

            $call_count++;
            $cost += $row->carrier_charges;
            $profit += $row->tax + $row->hotel_charges;
            $total += $row->total_charges;

            $total_cost += $row->carrier_charges;
            $total_profit += $row->tax + $row->hotel_charges;
            $total_price += $row->total_charges;

            $carrier_charges +=$row->carrier_charges; ;
            $hotel_charges  +=$row->hotel_charges;
            $tax += $row->tax ;
            $total_charges +=$row->total_charges;

            $total_carrier_charges +=$row->carrier_charges; ;
            $total_hotel_charges  +=$row->hotel_charges;
            $total_tax += $row->tax ;
            $total_total_charges +=$row->total_charges;

            $duration += $row->duration;

            //last row   and // diff row
            if($i == (count($data['guestcall']) - 1) ||  $row->room != $data['guestcall'][$i + 1]->room)
            {
            ?>
                <tr class="">
	                	<td style="background-color:#fff; border:0;"></td>
		                <td style="background-color:#fff; border:0;"></td>
			              <td style="background-color:#fff; border:0;"></td>
				            <td style="background-color:#fff; border:0;"></td>
					          <td style="background-color:#fff; border:0;"></td>
						        <td style="background-color:#fff; border:0;"></td>
							      <td style="background-color:#fff; border:0;"></td>
                    <td class="right summary"><b>Total Call(s) {{getEmptyValue($call_count)}}</b></td>
                    <td class="right summary"><b>{{$data['currency']}} {{getEmptyValue(number_format($carrier_charges, 2))}}</b></td>
                    <td class="right summary"><b>{{$data['currency']}} {{getEmptyValue(number_format($hotel_charges, 2))}}</b></td>
                    <td class="right summary"><b>{{$data['currency']}} {{getEmptyValue(number_format($tax, 2))}}</b></td>
                    <td class="right summary"><b>{{$data['currency']}} {{getEmptyValue(number_format($total_charges, 2))}}</b></td>
                </tr>
            <?php
                $call_count = 0;
                $cost = 0;
                $profit = 0;
                $total = 0;

                $carrier_charges = 0;
                $hotel_charges  = 0;
                $tax = 0 ;
                $total_charges = 0 ;
            }
            if( !array_key_exists($row->call_type, $call_type_count ) )
                $call_type_count[$row->call_type] = 0;
            $call_type_count[$row->call_type]++;

            if( !array_key_exists($row->call_type, $call_type_price ) )
                $call_type_price[$row->call_type] = 0;
            $call_type_price[$row->call_type] += $row->total_charges;

            $call_type_count['Total']++;
            $call_type_price['Total']+= $row->total_charges;

            $i++;
            ?>
            @endforeach
            {{--<tr class="">--}}
                {{--<td class="right">{{'Mobile: ' . $call_type_count['Mobile']}}</td>--}}
                {{--<td class="right">{{'International: ' . $call_type_count['International']}}</td>--}}
                {{--<td class="right">{{'National: ' . $call_type_count['National']}}</td>--}}
                {{--<td class="right">{{'Local: ' . $call_type_count['Local']}}</td>--}}
                {{--<td class="right">{{'Toll Free: ' . $call_type_count['Toll Free']}}</td>--}}
                {{--<td class="right">{{$call_type_count['Total'] . ' Calls'}}</td>--}}
                {{--<td class="right"> &nbsp;</td>--}}
                {{--<td class="right">{{'AED ' . number_format($total_carrier_charges, 2)}}</td>--}}
                {{--<td class="right">{{'AED ' . number_format($total_hotel_charges, 2)}}</td>--}}
                {{--<td class="right">{{'AED ' . number_format($total_tax, 2)}}</td>--}}
                {{--<td class="right">{{'AED ' . number_format($total_total_charges, 2)}}</td>--}}
            {{--</tr>--}}
            {{--<tr class="">--}}
                {{--<td class="right">{{$data['currency']}} {{number_format($call_type_price['Mobile'], 2)}}</td>--}}
                {{--<td class="right">{{$data['currency']}} {{number_format($call_type_price['International'], 2)}}</td>--}}
                {{--<td class="right">{{$data['currency']}} {{number_format($call_type_price['National'], 2)}}</td>--}}
                {{--<td class="right">{{$data['currency']}} {{number_format($call_type_price['Local'], 2)}}</td>--}}
                {{--<td class="right">{{$data['currency']}} {{number_format($call_type_price['Toll Free'], 2)}}</td>--}}
                {{--<td class="right">{{$data['currency']}} {{number_format($call_type_price['Total'], 2)}}</td>--}}
            {{--</tr>--}}
            </tbody>
      </table>
    </div>
    <?php
    $price = 0;
    $call_count = 0;
    $price_lost = 0 ;
    $call_count_lost = 0;
    $total_price = 0;
    $total_call_count = 0;
    $total_price_lost = 0;
    $total_call_count_lost = 0;
    ?>
    @if($data['minibaroption'] == true)
        <div>
            <h5> Minibar</h5>
            <table  border="0" style="width : 100%;">
                <?php  $i = 0;?>
                @foreach ($data['minibarlist'] as $key => $row)
                    @if($call_count == 0)
                        <thead>
                            <tr>
                                <td colspan="12"><b>Room :{{$row->room}} </b></td>
                            </tr>
                            <tr class="plain">
                                <th><b>Date</b></th>
                                <th><b>Time</b></th>
                                <th><b>Guest</b></th>
                                <th><b>Minibar Item</b></th>
                                <th><b>Price</b></th>
                                <th><b>Quantity</b></th>
                                <th><b>Posted By</b></th>
                                <th><b>Total</b></th>
                            </tr>
                        </thead>
                        <tbody>
                    @endif
                            <tr class="">
                                <td >{{getEmptyValue(date_format(new DateTime($row->date),'d-M-Y'))}}</td>
                                <td >{{getEmptyValue($row->time)}}</td>
                                <td >@if($row->guest_id == 0)
                                         Lost Posting
                                    @else
                                        {{getEmptyValue($row->guest_name)}}
                                    @endif
                                </td>
                                <td >{{getEmptyValue($row->item_name)}}</td>
                                <td class="right">{{getEmptyValue($row->item_price)}}</td>
                                <td class="right">{{getEmptyValue($row->item_quantity)}}</td>
                                <td >{{getEmptyValue($row->wholename)}}</td>
                                <td class="right" >{{getEmptyValue($row->item_total)}}</td>
                            </tr>
                            <?php
                             if($row->guest_id == 0) {
                                 $call_count_lost++;
                                 $price_lost += $row->item_total;
                             }else {
                                 $call_count++;
                                 $price += $row->item_total;
                             }

                            //last row   and // diff row
                            if($i == (count($data['minibarlist']) - 1) ||  $row->room != $data['minibarlist'][$i + 1]->room)
                            { ?>
                                <tr class="">
                                    <td  class="right" colspan="7">Total {{getEmptyValue($call_count)}} items</td>
                                    <td class="right">{{$data['currency']}} {{getEmptyValue(number_format($price, 2))}}</td>
                                </tr>
                                <tr class="">
                                    <td  class="right" colspan="7"> Total Lost Posting {{getEmptyValue($call_count_lost)}} items</td>
                                    <td class="right">Total Lost Amount {{$data['currency']}} {{getEmptyValue(number_format($price_lost, 2))}}</td>
                                </tr>
                                <?php
                                $call_count = 0;
                                $price = 0;
                                $call_count_lost = 0;
                                $price_lost = 0;
                               }
                             $i++;
                            ?>
                @endforeach
                        </tbody>
            </table>
            <br><br>

            <table  border="0" style="width : 100%;">
                <thead>
                <tr class="plain">
                    <th><b>Item</b></th>
                    <th><b>Quantity</b></th>
                    <th><b>Price</b></th>
                    <th><b>Total</b></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($data['minibartotal'] as $key => $row)
                        <tr class="">
                            <td >{{getEmptyValue($row->item_name)}}</td>
                            <td class="right">{{getEmptyValue($row->item_quantity)}}</td>
                            <td class="right">{{getEmptyValue($row->item_price)}}</td>
                            <td class="right" >{{getEmptyValue($row->item_total)}}</td>
                        </tr>
                    <?php
                    $total_call_count++ ;
                    $total_price += $row->item_total;
                    ?>
                @endforeach
                        <tr class="">
                            <td  class="right" colspan="3"><b>Total {{getEmptyValue($total_call_count)}} items</b></td>
                            <td class="right"><b>{{$data['currency']}} {{getEmptyValue(number_format($total_price, 2))}}</b></td>
                        </tr>
                </tbody>
            </table>
            <table  border="0" style="width : 100%;">
                <thead>
                <tr class="plain">
                    <th><b>Lost Item</b></th>
                    <th><b>Lost Quantity</b></th>
                    <th><b>Lost Price</b></th>
                    <th><b>Lost Total</b></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($data['minibartotal_lost'] as $key => $row)
                    <tr class="">
                        <td >{{getEmptyValue($row->item_name)}}</td>
                        <td class="right">{{getEmptyValue($row->item_quantity)}}</td>
                        <td class="right">{{getEmptyValue($row->item_price)}}</td>
                        <td class="right" >{{getEmptyValue($row->item_total)}}</td>
                    </tr>
                    <?php
                    $total_call_count_lost++ ;
                    $total_price_lost += $row->item_total;
                    ?>
                @endforeach
                <tr class="">
                    <td  class="right" colspan="3"><b>Total Lost Posting {{getEmptyValue($total_call_count_lost)}} items</b></td>
                    <td class="right"><b>Total Lost Amount {{$data['currency']}} {{getEmptyValue(number_format($total_price_lost, 2))}}</b></td>
                </tr>
                </tbody>
            </table>
        </div>
    @endif
@endif
