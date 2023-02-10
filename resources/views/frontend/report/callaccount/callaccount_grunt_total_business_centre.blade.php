<div style="margin-top: 5px">
    <!--<p align="center"; style="font-size:16px;"> Guest Grand Total By Building </p>-->
    @if (!empty($data['business_centre_by_build_data']))
        <b style="margin-bottom: 5px">Business Centre Grand Total By Building</b>
        <table class="" style="width : 100%">
            <thead>
            <tr>
                <th width=10%><b>Building</b></th>
                <th width=10%><b>International</b></th>
                <th width=10%><b>Local</b></th>
                <th width=10%><b>Mobile</b></th>
                <th width=10%><b>National</b></th>
                <th width=10%><b>Total Carrier</b></th>
                <th width=10%><b>Total Hotel</b></th>
                <th width=10%><b>Total</b></th>
            </tr>
            </thead>
        </table>
        @foreach ($data['business_centre_by_build_data'] as $row)
            <table class="" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width=10%>{{$row->name}}</td>
                        <td width=10% class="right">{{number_format($row->International, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Local, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Mobile, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->National, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Total_Hotel, 2)}}</td>
                        <td width=10% class="right">{{number_format($row->Total, 2)}}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
        <table class="" width="100%">
            <tbody>
                <tr class="total-amount">
                    <td width=10% align="right"><b>Total</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['business_centre_total_value']->International, 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['business_centre_total_value']->Local, 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['business_centre_total_value']->Mobile, 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['business_centre_total_value']->National, 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['business_centre_total_value']->Total_Carrier, 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['business_centre_total_value']->Total_Hotel, 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['business_centre_total_value']->Total, 2)}}</b></td>
                </tr>
            </tbody>
        </table>
    @endif
</div>