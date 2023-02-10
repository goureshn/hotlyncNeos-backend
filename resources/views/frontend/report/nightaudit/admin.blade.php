<br/><br/>
<?php

//function getEmptyValue($value) {
//    if( empty($value)|| $value == null )
//        return 0;
//    else
//        return $value;
//}
/*********This is deleted in 3th deployment*************/
//$call_count = 0;
//$total_cost = 0;
//
//
//$call_type_count = array();
//$call_type_count['Internal'] = 0;
//$call_type_count['Mobile'] = 0;
//$call_type_count['International'] = 0;
//$call_type_count['National'] = 0;
//$call_type_count['Local'] = 0;
//$call_type_count['Received'] = 0;
//$call_type_count['Toll Free'] = 0;
//$call_type_count['Total'] = 0;
//
//$call_type_price['Internal'] = 0;
//$call_type_price['Mobile'] = 0;
//$call_type_price['International'] = 0;
//$call_type_price['National'] = 0;
//$call_type_price['Local'] = 0;
//$call_type_price['Received'] = 0;
//$call_type_price['Toll Free'] = 0;
//$call_type_price['Total'] = 0;
?>
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
                <th width=10%><b>Total</b></th>
                <th width=10%><b>Total Carrier</b></th>
                <th width=10%><b>Profit</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data['by_build_data'] as  $key => $row)
                <tr class="">
                    <td>{{$row['name']}}</td>
                    <td class="right">{{number_format($row['International'], 2)}}</td>
                    <td class="right">{{number_format($row['Local'], 2)}}</td>
                    <td class="right">{{number_format($row['Mobile'], 2)}}</td>
                    <td class="right">{{number_format($row['Total'], 2)}}</td>
                    <td class="right">{{number_format($row['Total_Carrier'], 2)}}</td>
                    <td class="right">{{number_format($row['Profit'], 2)}}</td>
                </tr>
            @endforeach
            <tr class="total-amount">
                <td align="right";>Total</td>
                <td align="right";>{{$data['currency']}} {{number_format($data['total_value']['International'], 2)}}</b></td>
                <td align="right";>{{$data['currency']}} {{number_format($data['total_value']['Local'], 2)}}</b></td>
                <td align="right";>{{$data['currency']}} {{number_format($data['total_value']['Mobile'], 2)}}</b></td>
                <td align="right";>{{$data['currency']}} {{number_format($data['total_value']['Total'], 2)}}</b></td>
                <td align="right";>{{$data['currency']}} {{number_format($data['total_value']['Total_Carrier'], 2)}}</b></td>
                <td align="right";>{{$data['currency']}} {{number_format($data['total_value']['Profit'], 2)}}</b></td>
            </tr>
            </tbody>
        </table>
    </div>
@endif

<div style="margin-top: 15px">
    <!--<p align="center"; style="font-size:16px;"> Admin Grand Total By Building </p>-->
    <b style="margin-bottom: 5px">Admin Grand Total</b>
    <table class="" style="width : 100%">
        <thead>
        <tr >
            <th width=10%><b>Building</b></th>
            <th width=10%><b>International</b></th>
            <th width=10%><b>Local</b></th>
            <th width=10%><b>Mobile</b></th>
            <th width=10%><b>Total</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['admin_by_build_data'] as $row)
            <tr class="">
                <td>{{$row->name}}</td>
                <td class="right">{{number_format($row->International, 2)}}</td>
                <td class="right">{{number_format($row->Local, 2)}}</td>
                <td class="right">{{number_format($row->Mobile, 2)}}</td>
                <td class="right">{{number_format($row->Total_Carrier, 2)}}</td>
            </tr>
        @endforeach
        <tr class="total-amount">
            <td align="right";>Total</td>
            <td align="right";>{{number_format($data['admin_total_value']->International, 2)}}</td>
            <td align="right";>{{number_format($data['admin_total_value']->Local, 2)}}</td>
            <td align="right";>{{number_format($data['admin_total_value']->Mobile, 2)}}</td>
            <td align="right";>{{number_format($data['admin_total_value']->Total_Carrier, 2)}}</td>
        </tr>
        </tbody>
    </table>
</div>

@if(!empty($data['admin_by_build_dept_section_data']))
    <p align="center"; style="font-size:14px; margin-top: 30px"> Admin Grand Total By Building Department </p>
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
                <th width=8.75%><b>Total</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data_group['department'] as $row)
                <tr class="">
                    <td>{{$row->department}}</td>
                    <td class="right">{{number_format($row->International, 2)}}</td>
                    <td class="right">{{number_format($row->Local, 2)}}</td>
                    <td class="right">{{number_format($row->Mobile, 2)}}</td>
                    <td class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                </tr>
            @endforeach

            </tbody>
        </table>
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
                <th width=8.75%><b>Total</b></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data_key['section'] as $row)
                <tr class="">
                    <td>{{$row->section, 2}}</td>
                    <td class="right">{{number_format($row->International, 2)}}</td>
                    <td class="right">{{number_format($row->Local, 2)}}</td>
                    <td class="right">{{number_format($row->Mobile, 2)}}</td>
                    <td class="right">{{number_format($row->Total_Carrier, 2)}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
@endforeach

<br/><br/>
@if(!empty($data['admin_call_list']))
    <p align="center"; style="font-size:13px;"> Admin Call </p>
@endif

@foreach ($data['admin_call_list'] as  $key => $data_group)
    <div style="margin-top: 10px">
        <p style="margin: 0px"><b>{{$data['report_by_admin_call']}} :</b> {{$key}}</p>
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="background-color: #c2dbec;">
                @if( $data['report_by'] != 'Call Date')
                    <th><b>Date</b></th>
                @endif
                <th><b>Time</b></th>
                <th><b>Extension</b></th>
                <th><b>User Name</b></th>
                <th><b>Called No</b></th>
                <th><b>Duration</b></th>
                <th><b>Call Type</b></th>
                <th><b>Destination</b></th>
                <th style="text-align:right"><b>Carrier Charges</b></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $total_carrier = 0;
            ?>
            @foreach ($data_group as $row)
                <tr class="">
                    @if( $data['report_by'] != 'Call Date')
                        <td>{{date("d-M-Y",  strtotime($row->call_date))}}</td>
                    @endif
                    <td>{{$row->start_time}}</td>
                    <td>{{$row->extension}}</td>
                    <td>{{$row->wholename}}</td>
                    <td>{{$row->called_no}}</td>
                    <td>{{gmdate("H:i:s", $row->duration)}}</td>
                    <td>{{$row->call_type}}</td>
                    <td>{{$row->country}}</td>
                    <td class="right">{{$row->carrier_charges}}</td>
                </tr>
                <?php
                $total_carrier += $row->carrier_charges;
                ?>
            @endforeach
            <tr class="">
                @if( $data['report_by'] != 'Call Date')
                    <td></td>
                @endif
                <td style="background-color:#fff; border:0;"></td>
                <td style="background-color:#fff; border:0;"></td>
                <td style="background-color:#fff; border:0;"></td>
                <td style="background-color:#fff; border:0;"></td>
                <td style="background-color:#fff; border:0;"></td>
                <td style="background-color:#fff; border:0;"></td>
                <td style="text-align:right; background-color:#CFD8DC"><b>Total</b></td>
                <td style="text-align:right; background-color:#CFD8DC"  class="right"><b>{{$data['currency']}} {{$total_carrier}}</b></td>
            </tr>
            </tbody>
        </table>
    </div>

@endforeach

