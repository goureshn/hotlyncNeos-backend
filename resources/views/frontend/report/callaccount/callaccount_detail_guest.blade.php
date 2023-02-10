@if(!empty($data['guest_call_list']))
<p align="center"; style="font-size:8px;"> Guest Call </p>
@endif
@foreach ($data['guest_call_list'] as  $building => $data_key)
@foreach ($data_key['guest'] as  $key => $data_group)
    <div style="margin-top: 5px">
        <p style="margin: 0px"> <b>Building :</b> {{$building}} <b>{{$data['report_by_guest_call']}} :</b> {{$key}}</p>
        <table class="grid" style="width : 100%">
            <thead>
                <tr style="background-color: #c2dbec;">
                    <th width=8.75%><b>DateTime</b></th>
                    <th width=4%><b>Extension</b></th>
                    @if($data['report_by'] != 'Called Number')
                        @if($data['report_by'] != 'Room' )
                            <th width=4%><b>Room</b></th>
                        @endif
                    @endif
                    <th width=8.75%><b>Guest Name</b></th>
                    @if($data['report_by'] != 'Called Number')
                        <th width=10%><b>Called No</b></th>
                    @endif
                    @if($data['transfer']=='true')
                        <th width=8.75%><b>Transfer</b></th>
                    @endif
                    <th width=8.75%><b>Duration</b></th>
                    @if($data['report_by'] != 'Called Number')
                        <th width=10%><b>Call Type</b></th>
                        <th width=10%><b>Destination</b></th>
                    @endif
                    <th width=8.75%><b>Carrier</b></th>
                    <th width=8.75%><b>Hotel</b></th>
                    <th width=8.75%><b>Tax</b></th>
                    <th width=8.75%><b>Total charges</b></th>
                </tr>
            </thead>
        </table>
            <?php
            $total_carrier = 0;
            $total_hotel = 0;
            $total_tax = 0;
            $total_total = 0;
            ?>
            @foreach ($data_group['detail'] as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                    @if ($data['report_type'] == 'Detailed')
                        <tr class="">
                            <td width=8.75%>{{date("d-M-Y",  strtotime($row->call_date))}} {{$row->start_time}}</td>
                            <td width=4%>{{$row->extension}}</td>
                            @if($data['report_by'] != 'Called Number')
                                @if($data['report_by'] != 'Room' )
                                    <td width=4%>{{$row->room}}</td>
                                @endif
                            @endif
                            <td width=8.75%>{{$row->guest_name}}</td>
                            @if($data['report_by'] != 'Called Number' )
                                <td width=10%>{{$row->called_no}}</td>
                            @endif
                            @if($data['transfer']=='true')
                                @if(($row->transfer)==($row->called_no))
                                    <td width=8.75%></td>
                                @else
                                    <td width=8.75%>{{$row->transfer}}</td>
                                @endif
                            @endif
                            <td width=8.75%>{{gmdate("H:i:s", $row->duration)}}</td>
                            @if($data['report_by'] != 'Called Number')
                                <td width=10%>{{$row->call_type}}</td>
                                <td width=10%>{{$row->country}}</td>
                            @endif
                            <td width=8.75% class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $row->carrier_charges)}}</b></td>
                            <td width=8.75% class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $row->hotel_charges)}}</b></td>
                            <td width=8.75% class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $row->tax)}}</b></td>
                            <td width=8.75% class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $row->total_charges)}}</b></td>
                        </tr>
                    @else
                        <tr style="display: none"><td colspan="11"></td></tr>
                    @endif
                    <?php
                    $total_carrier += $row->carrier_charges;
                    $total_hotel += $row->hotel_charges;
                    $total_tax += $row->tax;
                    $total_total += $row->total_charges;
                    ?>
                    </tbody>
                </table>
            @endforeach
            <table class="grid" style="width : 100%">
                <tbody>
                <tr class="">
                    <td width=8.75% style="background-color:#fff; border:0px;"></td>
                    @if($data['report_by'] != 'Called Number')
                        <td width=4% style="background-color:#fff; border:0px;"></td>
                        <td width=4% style="background-color:#fff; border:0px;"></td>

                        <td width=4% style="background-color:#fff; border:0px;"></td>

                        <td width=4% style="background-color:#fff; border:0px;"></td>
                    @endif
                    @if($data['transfer']=='true')
                        <td width=8.75% style="background-color:#fff; border:0;"></td>
                    @endif
                    @if($data['report_by'] != 'Room' )
                        <td width=8.75% style="background-color:#fff; border:0px;"></td>
                    @endif
                    <td width=8.75% style="background-color:#fff; border:0px;"></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC" class="right"><b>Total</b></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $total_carrier)}}</b></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $total_hotel)}}</b></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $total_tax)}}</b></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $total_total)}}</b></td>
                </tr>
                </tbody>
            </table>
    </div>
    @endforeach
@endforeach