
@if(!empty($data['admin_call_list']))
<p align="center"; style="font-size:8px;"> Admin Call </p>
@endif
@foreach ($data['admin_call_list'] as  $building => $data_key)
@foreach ($data_key['admin'] as  $key => $data_group)
    <div style="margin-top: 5px">
        <p style="margin: 0px">
        <b>Building :</b> {{$building}}
        @if( $data['report_by'] != 'Access Code') 
            <b>{{$data['report_by_admin_call']}} :</b> {{$key}}
        @else
            <b>{{$data['report_by_admin_call']}} :</b> {{'****'}}<?php echo substr($key,-2);?> - {{$data_group['detail'][0]->wholename}}
         @endif</p>
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="background-color: #c2dbec;">
            @if( $data['report_by'] != 'Call Date')
                <th width="10%"><b>Date</b></th>
            @endif
                <th width="10%"><b>Time</b></th>
                <th width="10%"><b>Extension</b></th>
               @if( $data['report_by'] != 'Access Code')
                 <th width="10%"><b>User Name</b></th>
                @endif 
                @if( $data['report_by'] != 'Called Number')
                    <th width="10%"><b>Called No</b></th>
                @endif
                @if($data['transfer']=='true')
                    <th width=8.75%><b>Transfer</b></th>
                    @endif
                <th width="10%"><b>Duration</b></th>
                @if($data['report_by'] != 'Called Number')
                <th width="10%"><b>Call Type</b></th>
              
                <th width="10%"><b>Destination</b></th>
                @endif
                <th width="10%" style="text-align:right"><b>Carrier</b></th>
            </tr>
            </thead>
        </table>
            <?php
            $total_carrier = 0;
            ?>
            @foreach ($data_group['detail'] as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                    <tr class="">
                        @if( $data['report_by'] != 'Call Date')
                            <td width="10%">{{date("d-M-Y",  strtotime($row->call_date))}}</td>
                        @endif
                        <td width="10%">{{$row->start_time}}</td>
                        <td width="10%">{{$row->extension}}</td>
                        @if( $data['report_by'] != 'Access Code')
                            @if(!empty($row->wholename))
                                <td width="10%">{{$row->wholename}}</td>
                            @else
                                <td width="10%">{{$row->description}}</td>
                            @endif
                        @endif
                        @if($data['report_by'] != 'Called Number')
                            <td width="10%">{{$row->called_no}}</td>
                        @endif
                        @if($data['transfer']=='true')
                            @if(($row->transfer)==($row->called_no))
                                <td width="8.75%"></td>

                            @else
                                <td width="8.75%">{{$row->transfer}}</td>

                            @endif
                        @endif
                        <td width="10%">{{gmdate("H:i:s", $row->duration)}}</td>
                        @if($data['report_by'] != 'Called Number')
                            <td width="10%">{{$row->call_type}}</td>

                            <td width="10%">{{$row->country}}</td>
                        @endif
                        <td width="10%" class="right">{{sprintf('%.2f', $row->carrier_charges)}}</td>
                    </tr>
                    <?php
                    $total_carrier += $row->carrier_charges;
                    ?>
                    </tbody>
                </table>
            @endforeach
        <table class="grid" style="width : 100%">
            <tr class="">
                @if( $data['report_by'] != 'Call Date')
                    <td width="10%"></td>
                @endif
                <td width="10%" style="background-color:#fff; border:0;"></td>
                <td width="10%" style="background-color:#fff; border:0;"></td>
                @if($data['report_by'] != 'Called Number')
                    <td width="10%" style="background-color:#fff; border:0;"></td>
                @endif
                @if( $data['report_by'] != 'Access Code')  <td width="10%" style="background-color:#fff; border:0;"></td>@endif
                @if (($data['transfer']=='true')) <td width="8.75%" style="background-color:#fff; border:0;"></td>@endif
                @if($data['report_by'] != 'Called Number')
                    <td width="10%" style="background-color:#fff; border:0;"></td>
                    <td width="10%" style="background-color:#fff; border:0;"></td>
                @endif
                <td width="10%" style="text-align:right; background-color:#CFD8DC" class="right"><b>Total</b></td>
                <td width="10%" style="text-align:right; background-color:#CFD8DC" class="right"><b>{{$data['currency']}} {{sprintf('%.2f', $total_carrier)}}</b></td>
            </tr>
        </table>
    </div>
@endforeach
@endforeach