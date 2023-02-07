@if(!empty($data['summary_by_building_calldate_data']))
<p align="center"; style="font-size:8px; margin-top: 5px"> Grand Total By Building Call Date </p>
@endif
@foreach ($data['summary_by_building_calldate_data'] as  $key => $data_group)
    <div style="margin-top: 10px">
        @if( !empty($data_group['guest']) )
            <p style="margin: 0px"><b>Building :</b> {{$key}} - Guest</p>
            <table class="grid" style="width : 100%">
                <thead>
                <tr style="background-color: #c2dbec;">
                    <th width=8.75%><b>Date</b></th>
                    <th width=8.75%><b>International</b></th>
                    <th width=8.75%><b>Local</b></th>
                    <th width=8.75%><b>National</b></th>
                    <th width=10%><b>Mobile</b></th>
                    <th width=8.75%><b>Total</b></th>
                    <th width=8.75%><b>Carrier</b></th>
                    <th width=8.75%><b>Total Hotel</b></th>
                </tr>
                </thead>
            </table>
            @foreach ($data_group['guest'] as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                        <tr class="">
                            <td width=8.75%>{{date("d-M-Y",  strtotime($row->call_date))}}</td>
                            <td width=8.75% class="right">{{number_format($row->International, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Local, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->National, 2)}}</td>
                            <td width=10% class="right">{{number_format($row->Mobile, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Total, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Total_Hotel, 2)}}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @endif
        @if( !empty($data_group['business_centre']) )
            <p style="margin: 0px"><b>Building :</b> {{$key}} - Business Centre</p>
            <table class="grid" style="width : 100%">
                <thead>
                    <tr style="background-color: #c2dbec;">
                        <th width=8.75%><b>Date</b></th>
                        <th width=8.75%><b>International</b></th>
                        <th width=8.75%><b>Local</b></th>
                        <th width=8.75%><b>National</b></th>
                        <th width=10%><b>Mobile</b></th>
                        <th width=8.75%><b>Carrier</b></th>
                        <th width=8.75%><b>Hotel</b></th>
                        <th width=8.75%><b>Total</b></th>
                    </tr>
                </thead>
            </table>
            @foreach ($data_group['business_centre'] as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                        <tr class="">
                            <td width=8.75%>{{date("d-M-Y",  strtotime($row->call_date))}}</td>
                            <td width=8.75% class="right">{{number_format($row->International, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Local, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->National, 2)}}</td>
                            <td width=10% class="right">{{number_format($row->Mobile, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Total_Hotel, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Total, 2)}}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @endif
        @if( !empty($data_group['admin']) )
            <p style="margin: 0px"><b>Building :</b> {{$key}} - Admin</p>
            <table class="grid" style="width : 100%">
                <thead>
                    <tr style="background-color: #c2dbec;">
                        <th width=8.75%><b>Date</b></th>
                        <th width=8.75%><b>International</b></th>
                        <th width=8.75%><b>Local</b></th>
                        <th width=8.75%><b>National</b></th>
                        <th width=10%><b>Mobile</b></th>
                        <th width=8.75%><b>Carrier</b></th>
                    </tr>
                </thead>
            </table>
            @foreach ($data_group['admin'] as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                        <tr class="">
                            <td width=8.75%>{{date("d-M-Y",  strtotime($row->call_date))}}</td>
                            <td width=8.75% class="right">{{number_format($row->International, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Local, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->National, 2)}}</td>
                            <td width=10% class="right">{{number_format($row->Mobile, 2)}}</td>
                            <td width=8.75% class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @endif
    </div>
@endforeach