
@if(!empty($data['business_centre_call_list']))
    <p align="center" style="font-size:8px;"> Business Centre Call </p>
@endif

@foreach ($data['business_centre_call_list'] as  $building => $data_key)
    @foreach ($data_key['business'] as  $key => $data_group)
        <div style="margin-top: 10px">
            <p style="margin: 0px"><b>Building :</b> {{$building}} <b>{{$data['report_by_business_centre_call']}} :</b> {{$key}}</p>
            <table class="grid" style="width : 100%">
                <thead >
                    <tr style="background-color: #c2dbec;">
                        @if( $data['report_by'] != 'Call Date')
                            <th width=10%><b>Date</b></th>
                        @endif
                        <th width=8.75%><b>Time</b></th>
                        <th width=8.75%><b>Extension</b></th>
                        <th width=8.75%><b>User Name</b></th>
                        <th width=4%><b>Called No</b></th>
                        @if($data['transfer']=='true')
                            <th width=8.75%><b>Transfer</b></th>
                         @endif
                        <th width=8.75%><b>Duration</b></th>
                        <th width=8.75%><b>Call Type</b></th>
                        <th width=8.75%><b>Destination</b></th>
                        <th width=8.75% style="text-align:right"><b>Carrier</b></th>
                        <th width=8.75% style="text-align:right"><b>Hotel</b></th>
                        <th width=8.75% style="text-align:right"><b>Total</b></th>
                    </tr>
                </thead>
            </table>
                <?php
                $total_carrier = 0;
                $total_carrier_hotal = 0;
                $total_carrier_total = 0;
                ?>
                @foreach ($data_group['detail'] as $row)
                    <table class="grid" style="width : 100%">
                        <tbody>
                        <tr class="">
                            @if( $data['report_by'] != 'Call Date')
                                <td width=8.75%>{{date("d-M-Y",  strtotime($row->call_date))}}</td>
                            @endif
                            <td width=8.75%>{{$row->start_time}}</td>
                            <td width=8.75%>{{$row->extension}}</td>
                            <td width=8.75%>{{$row->wholename}}</td>
                            <td width=4%>{{$row->called_no}}</td>
                            @if($data['transfer']=='true')
                                @if(($row->transfer)==($row->called_no))
                                    <td width=8.75%></td>
                                @else
                                    <td width=8.75%>{{$row->transfer}}</td>
                                @endif
                            @endif
                            <td width=8.75%>{{gmdate("H:i:s", $row->duration)}}</td>
                            <td width=8.75%>{{$row->call_type}}</td>
                            <td width=8.75%>{{$row->country}}</td>
                            <td width=8.75% class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $row->carrier_charges)}}</b></td>
                            <td width=8.75% class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $row->hotel_charges)}}</b></td>
                            <td width=8.75% class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $row->total_charges)}}</b></td>
                        </tr>
                        <?php
                        $total_carrier += $row->carrier_charges;
                        $total_carrier_hotal += $row->hotel_charges;
                        $total_carrier_total += $row->total_charges;
                        ?>
                        </tbody>
                    </table>
                @endforeach
            <table class="grid" style="width : 100%">
                <tbody>
                <tr class="">
                    @if( $data['report_by'] != 'Call Date')
                        <td width=8.75%></td>
                    @endif
                    <td width=8.75% style="background-color:#fff; border:0;"></td>
                    <td width=8.75% style="background-color:#fff; border:0;"></td>
                    <td width=8.75% style="background-color:#fff; border:0;"></td>
                    <td width=4% style="background-color:#fff; border:0;"></td>
                    @if($data['transfer']=='true')<td width=8.75% style="background-color:#fff; border:0;"></td>@endif
                    <td width=8.75% style="background-color:#fff; border:0;"></td>
                    <td width=8.75% style="background-color:#fff; border:0;"></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC"><b>Total</b></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC"  class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $total_carrier)}}</b></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC"  class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $total_carrier_hotal)}}</b></td>
                    <td width=8.75% style="text-align:right; background-color:#CFD8DC"  class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $total_carrier_total)}}</b></td>
                </tr>
                </tbody>
            </table>
        </div>
    @endforeach
@endforeach