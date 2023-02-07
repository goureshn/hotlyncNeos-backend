@if(!empty($data['business_centre_by_build_dept_section_data']))
    <p align="center"; style="font-size:8px; margin-top: 5px"> Business Centre Grand Total By Building Department </p>
@endif
@foreach ($data['business_centre_by_build_dept_section_data'] as  $key => $data_group)
    <div style="margin-top: 5px">
        <p style="margin: 0px"><b>Building :</b> {{$key}}</p>
        <table class="grid" style="width : 100%">
            <thead>
            <tr style="background-color: #c2dbec;">
                <th width=8.75%><b>Department</b></th>
                <th width=8.75%><b>International</b></th>
                <th width=8.75%><b>Local</b></th>
                <th width=10%><b>Mobile</b></th>
                <th width=10%><b>National</b></th>
                <th width=10%><b>Toll Free</b></th>
                <th width=8.75%><b>Carrier</b></th>
                <th width=8.75%><b>Hotel</b></th>
                <th width=8.75%><b>Total</b></th>
            </tr>
            </thead>
        </table>
        @foreach ($data_group['department'] as $row)
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width=8.75%>{{$row->department}}</td>
                        <td width=8.75% class="right">{{number_format($row->International, 2)}}</td>
                        <td width=8.75% class="right">{{number_format($row->Local, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Mobile, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->National, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Toll, 2)}}</td>
                        <td width=8.75% class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                        <td width=8.75% class="right">{{number_format($row->Total_Hotel, 2)}}</td>
                        <td width=8.75% class="right">{{number_format($row->Total, 2)}}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
        <table class="grid" style="width : 100%">
            <thead>
            <tr style="background-color: #c2dbec;">
                <th width=8.75%><b>Section</b></th>
                <th width=8.75%><b>International</b></th>
                <th width=8.75%><b>Local</b></th>
                <th width=10%><b>Mobile</b></th>
                <th width=10%><b>National</b></th>
                <th width=10%><b>Toll Free</b></th>
                <th width=8.75%><b>Carrier</b></th>
                <th width=8.75%><b>Hotel</b></th>
                <th width=8.75%><b>Total</b></th>
            </tr>
            </thead>
        </table>
        @foreach ($data_group['section'] as $row)
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width=8.75%>{{$row->section, 2}}</td>
                        <td width=8.75% class="right">{{number_format($row->International, 2)}}</td>
                        <td width=8.75% class="right">{{number_format($row->Local, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Mobile, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->National, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Toll, 2)}}</td>
                        <td width=8.75% class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                        <td width=8.75% class="right">{{number_format($row->Total_Hotel, 2)}}</td>
                        <td width=8.75% class="right">{{number_format($row->Total, 2)}}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    </div>
@endforeach