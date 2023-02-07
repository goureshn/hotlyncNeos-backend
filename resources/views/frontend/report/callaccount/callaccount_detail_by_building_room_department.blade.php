@if(!empty($data['by_building_room_department_data']))
<p align="center"; style="font-size:8px;"> Detailed by Building, Room and Department </p>
@endif
@foreach ($data['by_building_room_department_data'] as  $key => $building_group)
        <!---------   Guest detail By Building Room --------------------->
<div style="margin-top: 5px">
    @if(!empty($building_group['guest']))
        <p style="margin: 0px"><b>Guest - Building :</b> {{$key}}</p>
        @foreach ($building_group['guest'] as  $room_key => $room_group)
            <p style="margin: 0px"><b>Room :</b> {{$room_key}}</p>
            <table class="grid" style="width : 100%">
                <thead>
                    <tr style="background-color: #c2dbec;">
                        <th width=8.75%><b>Call Date</b></th>
                        <th width=8.75%><b>Guest</b></th>
                        <th width=8.75%><b>Called No</b></th>
                        <th width=8.75%><b>Call Type</b></th>
                        @if($data['transfer']=='true')
                            <th width=8.75%><b>Transfer</b></th>
                        @endif
                        <th width=8.75%><b>Time</b></th>
                        <th width=10%><b>Duration</b></th>
                        <th width=8.75%><b>Destination</b></th>
                        <th width=8.75%><b>Carrier</b></th>
                        <th width=8.75%><b>Hotel</b></th>
                        <th width=8.75%><b>Total</b></th>
                    </tr>
                </thead>
            </table>
            @foreach ($room_group['detail'] as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                        <tr class="">
                            <td width=8.75%>{{$row->call_date}}</td>
                            <td width=8.75%>{{$row->guest_name}}</td>
                            <td width=8.75%>{{$row->called_no}}</td>
                            <td width=8.75%>{{$row->call_type}}</td>
                            @if($data['transfer']=='true')
                                @if(($row->transfer)==($row->called_no))
                                    <td width=8.75%></td>
                                @else
                                    <td width=8.75%>{{$row->transfer}}</td>
                                @endif
                            @endif
                            <td width=8.75%>{{$row->start_time}}</td>
                            <td width=10%>{{gmdate("H:i:s", $row->duration)}}</td>
                            <td width=8.75%>{{$row->country}}</td>
                            <td width=8.75% class="right">{{sprintf('%.2f', $row->carrier_charges)}}</td>
                            <td width=8.75% class="right">{{sprintf('%.2f', $row->hotel_charges)}}</td>
                            <td width=8.75% class="right">{{sprintf('%.2f', $row->total_charges)}}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        @if($data['transfer']=='true')
                            <td width=10%></td>
                        @endif
                        <td width=8.75% class="right"><b>Total</b></td>
                        <td width=8.75% class="right"><b>{{$data['currency']}} {{number_format($room_group['summary']->carrier_charges, 2)}}</b></td>
                        <td width=8.75% class="right"><b>{{$data['currency']}} {{number_format($room_group['summary']->hotel_charges, 2)}}</b></td>
                        <td width=8.75% class="right"><b>{{$data['currency']}} {{number_format($room_group['summary']->total_charges, 2)}}</b></td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif
</div>
<!---------  End Guest detail By Building Room --------------------->

<!---------  Business Centre detail By Building Department --------------------->
<div style="margin-top: 5px">
    @if(!empty($building_group['business_centre']))
        <p style="margin: 0px"><b>Business Centre - Building :</b> {{$key}}</p>
        @foreach ($building_group['business_centre'] as  $department_key => $department_group)
            <p style="margin: 0px"><b>Department :</b> {{$department_key}}</p>

            <table class="grid" style="width : 100%">
                <thead>
                    <tr style="background-color: #c2dbec;">
                        <th width=8.75%><b>Extension</b></th>
                        <th width=8.75%><b>User</b></th>
                        <th width=8.75%><b>Called No</b></th>
                        @if($data['transfer']=='true')
                            <th width=8.75%><b>Transfer</b></th>
                        @endif
                        <th width=8.75%><b>Call Date</b></th>
                        <th width=10%><b>Time</b></th>
                        <th width=8.75%><b>Duration</b></th>
                        <th width=8.75%><b>Destination</b></th>
                        <th width=8.75%><b>Carrier</b></th>
                        <th width=8.75%><b>Hotel</b></th>
                        <th width=8.75%><b>Total</b></th>
                    </tr>
                </thead>
            </table>
            @foreach ($department_group['detail'] as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                        <tr class="">
                            <td width=8.75%>{{$row->extension}}</td>
                            <td width=8.75%>{{$row->wholename}}</td>
                            <td width=8.75%>{{$row->called_no}}</td>
                            @if($data['transfer']=='true')
                                @if(($row->transfer)==($row->called_no))
                                    <td width=8.75%></td>
                                @else
                                    <td width=8.75%>{{$row->transfer}}</td>
                                @endif
                            @endif
                            <td width=8.75%>{{$row->call_date}}</td>
                            <td width=10%>{{$row->start_time}}</td>
                            <td width=8.75%>{{gmdate("H:i:s", $row->duration)}}</td>
                            <td width=8.75%>{{$row->country}}</td>
                            <td width=8.75% class="right">{{sprintf('%.2f', $row->carrier_charges)}}</td>
                            <td width=8.75% class="right">{{sprintf('%.2f', $row->hotel_charges)}}</td>
                            <td width=8.75% class="right">{{sprintf('%.2f', $row->total_charges)}}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=10%></td>
                        @if($data['transfer']=='true')
                            <td width=8.75%></td>
                        @endif
                        <td width=8.75% class="right"><b>Total</b></td>
                        <td width=8.75% class="right"><b>{{$data['currency']}} {{number_format($department_group['summary']->carrier_charges, 2)}}</b></td>
                        <td width=8.75% class="right"><b>{{$data['currency']}} {{number_format($department_group['summary']->hotel_charges,2)}}</b></td>
                        <td width=8.75% class="right"><b>{{$data['currency']}} {{number_format($department_group['summary']->total_charges,2)}}</b></td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif
</div>
<!---------  End Guest detail By Building Room --------------------->

<!---------   Admin detail By Building Department --------------------->
<div style="margin-top: 5px">
    @if(!empty($building_group['admin']))
        <p style="margin: 0px"><b>Admin - Building :</b> {{$key}}</p>
        @foreach ($building_group['admin'] as  $department_key => $department_group)
            <p style="margin: 0px"><b>Department :</b> {{$department_key}}</p>

            <table class="grid" style="width : 100%">
                <thead>
                    <tr style="background-color: #c2dbec;">
                        <th width=8.75%><b>Extension</b></th>
                        <th width=8.75%><b>User</b></th>
                        <th width=8.75%><b>Called No</b></th>
                        <th width=8.75%><b>Call Type</b></th>
                        @if($data['transfer']=='true')
                            <th width=8.75%><b>Transfer</b></th>
                        @endif
                        <th width=8.75%><b>Call Date</b></th>
                        <th width=10%><b>Time</b></th>
                        <th width=8.75%><b>Duration</b></th>
                        <th width=8.75%><b>Destination</b></th>
                        <th width=8.75%><b>Carrier</b></th>
                    </tr>
                </thead>
            </table>
            @foreach ($department_group['detail'] as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                        <tr class="">
                            <td width=8.75%>{{$row->extension}}</td>
                            @if (!empty($row->wholename))
                                <td width=8.75%>{{$row->wholename}}</td>
                            @elseif (!empty($row->description))
                                <td width=8.75%>{{$row->description}}</td>
                            @else
                                <td width=8.75%></td>
                            @endif
                            <td width=8.75%>{{$row->called_no}}</td>
                            <td width=8.75%>{{$row->call_type}}</td>

                            @if($data['transfer']=='true')
                                @if(($row->transfer)==($row->called_no))
                                    <td width=8.75%></td>
                                @else
                                    <td width=8.75%>{{$row->transfer}}</td>
                                @endif
                            @endif
                            <td width=8.75%>{{$row->call_date}}</td>
                            <td width=10%>{{$row->start_time}}</td>
                            <td width=8.75%>{{gmdate("H:i:s", $row->duration)}}</td>
                            <td width=8.75%>{{$row->country}}</td>
                            <td width=8.75% class="right">{{sprintf('%.2f', $row->carrier_charges)}}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        <td width=8.75%></td>
                        @if($data['transfer']=='true')
                            <td width=10%></td>
                        @endif
                        <td width=8.75%></td>
                        <td width=8.75% class="right"><b>Total</b></td>
                        <td width=8.75% class="right"><b>{{$data['currency']}} {{number_format($department_group['summary']->carrier_charges, 2)}}</b></td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif
</div>
<!---------  End Guest detail By Building Room --------------------->

@endforeach
