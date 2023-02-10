<div style="margin-top: 5px">
    <!--<p align="center"; style="font-size:16px;"> Admin Grand Total By Building </p>-->
    <b style="margin-bottom: 5px">Admin Grand Total</b>
    <table class="" style="width : 100%">
        <thead>
        <tr >
            <th width=10%><b>Building</b></th>
            <th width=10%><b>International</b></th>
            <th width=10%><b>Local</b></th>
            <th width=10%><b>Mobile</b></th>
            <th width=10%><b>National</b></th>
            <th width=10%><b>Toll Free</b></th>
            <th width=10%><b>Total</b></th>
        </tr>
        </thead>
    </table>
    @foreach ($data['admin_by_build_data'] as $row)
        <table class="" style="width : 100%">
            <tbody>
                <tr class="">
                    <td width=10%>{{$row->name}}</td>
                    <td width=10% class="right">{{number_format($row->International, 2)}}</td>
                    <td width=10% class="right">{{number_format($row->Local, 2)}}</td>
                    <td width=10% class="right">{{number_format($row->Mobile, 2)}}</td>
                    <td width=10% class="right">{{number_format($row->National, 2)}}</td>
                    <td width=10% class="right">{{number_format($row->Toll, 2)}}</td>
                    <td width=10% class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                </tr>
            </tbody>
        </table>
    @endforeach
    <table class="" style="width : 100%">
        <tbody>
            <tr class="total-amount">
                <td width=10% align="right"><b>Total</b></td>
                <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['admin_total_value']->International, 2)}}</b></td>
                <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['admin_total_value']->Local, 2)}}</b></td>
                <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['admin_total_value']->Mobile, 2)}}</b></td>
                <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['admin_total_value']->National, 2)}}</b></td>
                <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['admin_total_value']->Toll, 2)}}</b></td>
                <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['admin_total_value']->Total_Carrier, 2)}}</b></td>
            </tr>
        </tbody>
    </table>
</div>