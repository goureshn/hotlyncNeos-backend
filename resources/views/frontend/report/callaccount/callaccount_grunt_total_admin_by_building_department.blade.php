@if(!empty($data['admin_by_build_dept_section_data']))
<p align="center"; style="font-size:8px; margin-top: 5px"> Admin Grand Total By Department </p>
@endif
@foreach ($data['admin_by_build_dept_section_data'] as  $key => $data_group)
<p style="margin: 0px"><b>Building :</b> {{$key}}</p>
<div style="margin-top: 5px">  
    <table class="grid" style="width : 100%">
        <thead>
        <tr style="background-color: #c2dbec;">
            <th width=8.75%><b>Department</b></th>
            <th width=8.75%><b>International</b></th>
            <th width=8.75%><b>Local</b></th>
            <th width=10%><b>Mobile</b></th>
            <th width=10%><b>National</b></th>
            <th width=10%><b>Toll Free</b></th>
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
                </tr>
            </tbody>
        </table>
    @endforeach
</div>

@foreach ($data_group['depart'] as  $key => $data_key)
    <div style="margin-top: 5px">
        <p style="margin: 0px"><b>Department :</b> {{$key}}</p>
        
        <table class="grid" style="width : 100%">
            <thead>
                <tr style="background-color: #c2dbec;">
                    <th width=8.75%><b>Section</b></th>
                    <th width=8.75%><b>International</b></th>
                    <th width=8.75%><b>Local</b></th>
                    <th width=10%><b>Mobile</b></th>
                    <th width=10%><b>National</b></th>
                    <th width=10%><b>Toll Free</b></th>
                    <th width=8.75%><b>Total</b></th>
                </tr>
            </thead>
        </table>
        @foreach ($data_key['section'] as $row)
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
                    </tr>
                </tbody>
            </table>
        @endforeach
    </div>
@endforeach

@endforeach
@foreach ($data['admin_by_build_dept_summary_data'] as  $key => $data_group)
<p style="margin: 0px"><b>Building :</b> {{$key}}</p>
    <div style="margin-top: 5px">
        <table class="grid" style="width : 100%">
            <thead>
                <tr style="background-color: #c2dbec;">
                    <th width=8.75%><b>Department</b></th>
                    <th width=8.75%><b>Extension Count</b></th>
                    <th width=8.75%><b>Number of Calls</b></th>
                    <th width=10%><b>Duration</b></th>
                    <th width=10%><b>Cost</b></th>
                    <th width=10%><b>Percentage</b></th>
                </tr>
            </thead>
        </table>
        <?php
            $extension_total = 0;
            $calls = 0;
            $cost = 0;
            $percentage = 0;
            $total_list = $data_group['total'];
            $duration = $data_group['duration'];
        ?>
        @foreach ($data_group['department'] as $row)
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        @if ($total_list > 0)
                            <?php
                            $percent = ($row->percent/$total_list) * 100;
                            ?>
                        @else
                            <?php
                            $percent = 0;
                            ?>
                        @endif

                        <td width=8.75%>{{$row->department}}</td>
                        <td width=8.75% class="right">{{$row->extension_count}}</td>
                        <td width=8.75% class="right">{{$row->total_calls}}</td>
                        <td width=10% class="right">{{$row->duration_total}}</td>
                        <td width=10% class="right">{{number_format($row->cost, 2)}}</td>
                        <td width=10% class="right">{{number_format($percent, 2)}}%</td>
                    </tr>
                </tbody>
            </table>
            <?php
                $extension_total += $row->extension_count;
                $calls += $row->total_calls;
                $cost += $row->cost;
                $percentage += $percent;
            ?>
        @endforeach
        <table class="grid" style="width : 100%">
            <tr class="">
                <td width=8.75% style="text-align:left; background-color:#CFD8DC" class="right"><b>Total</b></td>
                <td width=8.75% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$extension_total}}</b></td>
                <td width=8.75% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$calls}}</b></td>
                <td width=10% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$duration}}</b></td>
                <td width=10% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{number_format($cost, 2)}}</b></td>
                <td width=10% style="text-align:right; background-color:#CFD8DC" class="right"><b>{{number_format($percentage, 2)}}%</b></td>
             </tr>
        </table>
    </div>
@endforeach
