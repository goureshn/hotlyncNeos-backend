<?php
$all_morning_carrier = 0;
$all_morning_total = 0;
$all_daily_carrier = 0;
$all_daily_total = 0;
$all_night_carrier = 0;
$all_night_total = 0;
$all_carrier = 0;
$all_total = 0;
$count = 0;
?>
<div style="margin-top: 5px">
    <table class="grid" style="width : 100%">
        <thead >
        <tr style="background-color: #c2dbec;">
            <th rowspan="2" width="10%"><b>Group</b></th>
            <th rowspan="2" width="10%"><b>Country Code</b></th>
            <th rowspan="2" width="10%"><b>Country</b></th>
            <th rowspan="2" width="10%"><b>Allowance</b></th>
            <th  width="10%" colspan="2"><b>
                    Morning Off Peak
                    00:00:00 - 06:59:59 </b></th>
            <th  width="10%" colspan="2"><b>
                    Daily Peak
                    07:00:00 - 20:59:59</b></th>
            <th  width="10%" colspan="2"><b>
                    Night Off Peak
                    21:00:00 - 23:59:59</b></th>
            <th  width="10%" colspan="2"><b>
                    All Day Off Peak
                    00:00:00 - 23:59:59</b></th>
        </tr>
        <tr>
            <th width="5%"><b>Carrier</b></th>
            <th width="5%"><b>Total</b></th>
            <th width="5%"><b>Carrier</b></th>
            <th width="5%"><b>Total</b></th>
            <th width="5%"><b>Carrier</b></th>
            <th width="5%"><b>Total</b></th>
            <th width="5%"><b>Carrier</b></th>
            <th width="5%"><b>Total</b></th>
        </tr>
        </thead>
        <tbody>
        <?php $country_name = ''; ?>
            @foreach ($data['data_list'] as  $key => $row)
                <tr class="">
                    <td style="font-size: 10px;">
                        @if($country_name !=$row['group_name'])
                            {{$row['group_name']}}
                            <?php $country_name = $row['group_name'] ;?>
                        @endif
                    </td>
                    <td>{{$row['country_code']}}</td>
                    <td>{{$row['country']}}</td>
                    <td>{{$row['allowance']}}</td>
                    <td class="right">{{$row['morning_carrier']}}</td>
                    <td class="right">{{$row['morning_total']}}</td>
                    <td class="right">{{$row['daily_carrier']}}</td>
                    <td class="right">{{$row['daily_total']}}</td>
                    <td class="right">{{$row['night_carrier']}}</td>
                    <td class="right">{{$row['night_total']}}</td>
                    <td class="right">{{$row['all_carrier']}}</td>
                    <td class="right">{{$row['all_total']}}</td>
                </tr>
                <?php
                $all_morning_carrier += $row['morning_carrier'];
                $all_morning_total += $row['morning_total'];
                $all_daily_carrier += $row['daily_carrier'];
                $all_daily_total += $row['daily_total'];
                $all_night_carrier += $row['night_carrier'];
                $all_night_total += $row['night_total'];
                $all_carrier += $row['all_carrier'];
                $all_total += $row['all_total'];
                $count ++;
                ?>
            @endforeach
            <tr class="">
                <td colspan="4" class="right">Total Count: {{$count}}</td>
                <td class="right">{{round($all_morning_carrier,2)}}</td>
                <td class="right">{{round($all_morning_total,2)}}</td>
                <td class="right">{{round($all_daily_carrier,2)}}</td>
                <td class="right">{{round($all_daily_total,2)}}</td>
                <td class="right">{{round($all_night_carrier,2)}}</td>
                <td class="right">{{round($all_night_total,2)}}</td>
                <td class="right">{{round($all_carrier,2)}}</td>
                <td class="right">{{round($all_total,2)}}</td>
            </tr>
        </tbody>
    </table>
</div>