<!--<p align="center"; style="font-size:14px; margin-top: 30px"> Grand Total By Building</p>-->

@if( $data['report_by'] != 'Department' )
    @if(!empty($data['by_build_data']))
    <div style="margin-top: 15px">
        <b style="margin-bottom: 5px">Grand Total By Building</b>
        <table class="" style="width : 100%">
            <thead>
            <tr>
                <th width=10%><b>Building</b></th>
                <th width=10%><b>International</b></th>
                <th width=10%><b>Local</b></th>
                <th width=10%><b>Mobile</b></th>
                <th width=10%><b>National</b></th>
                <th width=10%><b>Toll Free</b></th>
                <th width=10%><b>Total</b></th>
                <th width=10%><b>Carrier</b></th>
                <th width=10%><b>Profit</b></th>
            </tr>
            </thead>
        </table>
        @foreach ($data['by_build_data'] as  $key => $row)
            <table class="" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width=10%>{{$row['name']}}</td>
                        <td width=10% class="right">{{number_format($row['International'], 2)}}</td>
                        <td width=10% class="right">{{number_format($row['Local'], 2)}}</td>
                        <td width=10% class="right">{{number_format($row['Mobile'], 2)}}</td>
                        <td width=10% class="right">{{number_format($row['National'], 2)}}</td>
                        <td width=10% class="right">{{number_format($row['Toll'], 2)}}</td>
                        <td width=10% class="right">{{number_format($row['Total'], 2)}}</td>
                        <td width=10% class="right">{{number_format($row['Total_Carrier'], 2)}}</td>
                        <td width=10% class="right">{{number_format($row['Profit'], 2)}}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
        <table class="" style="width : 100%">
            <tbody>
                <tr class="total-amount">
                    <td width=10% align="right"><b>Total</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['total_value']['International'], 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['total_value']['Local'], 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['total_value']['Mobile'], 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['total_value']['National'], 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['total_value']['Toll'], 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['total_value']['Total'], 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['total_value']['Total_Carrier'], 2)}}</b></td>
                    <td width=10% align="right"><b>{{$data['currency']}} {{number_format($data['total_value']['Profit'], 2)}}</b></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
@endif