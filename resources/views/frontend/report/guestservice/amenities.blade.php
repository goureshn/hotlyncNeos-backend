<br/>
@if(!empty($data['summary_report']))
    <p style="margin: 0px;margin-top :10px" class="table-header"><b>Summary</b></p>
    <div style="margin-top: 5px">
    <table class="grid" style="width : 100%">
        <thead >
        <tr style="background-color: #c2dbec;">
            <th><b>Amenities</b></th>
            <th><b>Quantity</b></th>
            <th><b>Cost</b></th>
            <th><b>Total</b></th>
        </tr>
        </thead>
        <tbody>
            <?php
            $globaltotal = 0;
            ?>
            @foreach ($data['summary_report'] as  $summary)
                <?php
                $total = $summary->cost * $summary->quality;
                $globaltotal += $total;
                ?>
                <tr class="">
                    <td>{{$summary->task_name}}</td>
                    <td style="text-align:center;">{{$summary->quality}}</td>
                    <td style="text-align:right;">{{$summary->cost}}</td>
                    <td style="text-align:right;">{{$total}}</td>
                </tr>
            @endforeach
            <tr class="">
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="font-weight: bold">Grand Total</td>
                <td style="text-align:right;font-weight: bold">{{$globaltotal}}</td>
            </tr>
        </tbody>

    </table>
    </div>
@endif
@if(!empty($data['detail_report']))
    <p style="margin: 0px;margin-top :10px" class="table-header"><b>Details</b></p>
    <div style="margin-top: 5px">

            <?php
            $globaltotal = 0;
            $olddate = '0000-00-00';
            ?>
            @foreach ($data['detail_report'] as  $detail)
                <?php
                $key = date('Y-m-d', strtotime($detail->start_date_time));
                    $tid = $detail->id;
                    switch ($detail->type) {
                        case 1:
                            $detail->ticketno1 = sprintf("G%05d", $tid);
                            break;
                        case 2:
                            $detail->ticketno1 = sprintf("D%05d", $tid);
                            break;
                        case 3:
                            $detail->ticketno1 = sprintf("C%05d", $tid);
                            break;
                        case 4:
                            $detail->ticketno1 = sprintf("M%05d", $tid);
                            break;
                        case 5:
                            $detail->ticketno1 = sprintf("R%05d", $tid);
                            break;
                    }
                ?>
                <?php
                if($key != $olddate){
                    if($olddate != '0000-00-00'){
                        echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td><b>Total</b></td><td>'.$globaltotal.'</td></tr>';
                        $globaltotal = 0;
                        echo '</tbody></table>';
                    }
                ?>
                <table class="grid print-friendly" style="width : 100%">
                    <thead >
                   
                    <tr style="background-color: #c2dbec;">
                        <th style="width : 10%;"><b>ID/Date</b></th>
                        <th style="width : 10%;"><b>Guest/Room</b></th>
                        <th style="width : 20%;"><b>Assigned</b></th>
                        <th style="width : 20%;"><b>Created By</b></th>
                        <th style="width : 30%;"><b>Item/Comment</b></th>
                        <th style="width : 10%;"><b>Cost</b></th>
                    </tr>
                    </thead>
                    <tbody>
                <?php
                    $olddate = $key;
                }
                $total = $detail->cost * $detail->quantity;
                $globaltotal += $total;
                ?>
                    <tr class="">
                        <td>
                        {{ $key }}<br> {{ $detail->ticketno1 }}
                        </td>
                        @if (!empty($detail->room))
                        <td>
                            {{ $detail->guest_name }}<br> {{ $detail->room }}
                        </td>
                        @else
                        <td>
                            Vacant
                        </td>
                        @endif
                        <td>
                            {{ $detail->staffname }}
                        </td>
                        <td>
                            {{ $detail->attendant_name }}
                        </td>
                        <td>
                            {{ $detail->quantity }}x{{ $detail->task_name }} <br> {{ $detail->comment}}
                        </td>
                        <td style="text-align:right;">
                            {{ $detail->cost }}
                        </td>
                    </tr>
                @endforeach
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td><b>Total</b></td>
                        <td style="text-align:right;"><b>{{$globaltotal}}</b></td>
                    </tr>
                </tbody>
        </table>
    </div>
@endif

