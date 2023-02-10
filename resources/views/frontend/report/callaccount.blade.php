<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <style>

		.pagenum:after {
			content: counter(page);
		}
		@media screen {
			div.footer {
			position: fixed;
			bottom: 0;
			}
		}
    body {
			font-family: 'Titillium Web';
        <?php if(empty($data['font-size'])) {?>
        font-size: 12px;
        <?php }else { ?>
        font-size: {{$data['font-size']}};
        <?php } ?>
        -webkit-font-smoothing: antialiased;
			line-height: 1.42857143;
			color: #212121;
		}
    table th {
      padding-left: 2px;
      padding-right: 2px;
			text-align: left;
    }
		table thead {
			text-align: left;
			background-color: #c2dbec;
		}
    table {
      border-spacing: 0;
      border-collapse: collapse;
			width:100%;
    }
    .grid th, .grid td {
      border-spacing: 0;
      border: 1px solid #d4d4d4;
    }
    td.right {
      text-align: right;
      padding-right: 5px;;
    }
    
		</style>
</head>
<body>
	<div class="footer">Page: <span class="pagenum"></span>
		<img src="frontpage/img/ets_footer.png" alt=""  style="width: 80px; height: auto; padding-left: 950px; margin-top: -57px;" align="left">
	</div>
	<table style="width:100%">
		<tr>
			<th rowspan="4">
				<img src="frontpage/img/goldensands.png" alt=""  style="width: 150px; height: auto; left:0;">
			</th>
			<td style="padding-left: 550px">Date Generated : <?php echo date('d-M-Y') ?></td>
		</tr>
		<tr>
			<td style="padding-left: 550px">Property : <?php echo $data['property']->name ?></td>
		</tr>
        <tr>
            <td style="padding-left: 550px">Building : <?php echo $data['building'] ?></td>
        </tr>
		<tr>
			<td style="padding-left: 550px">Extension Type : {{$data['extenstion_type']}}</td>
		</tr>
      	<tr>
            <td></td>
            <td style="padding-left: 550px">{{$data['call_type']}}</td>
        </tr>
	</table>

	@if( $data['call_sort'] == 'All' || $data['call_sort'] == 'Guest Call' )
    <h3 style="text-align:center"> Guest Call </h3>

    @foreach ($data['guest_call_list'] as  $key => $data_group)
        <div style="margin-top: 30px">
            <p style="margin: 0px"><b>Report Date :<b/> {{$key}}</p>
            <table class="grid">
                <thead >
                    <tr >
                        @if ($data['report_type'] == 'Detailed')
                        <th><b>Time</b></th>
                        <th><b>Extension</b></th>
                        <th><b>Room</b></th>
                        <th><b>Called No</b></th>
                        <th><b>Duration</b></th>
                        <th><b>Call Type</b></th>
                        <th><b>Destination</b></th>
                        @else
                        <th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
                        @endif
                        <th><b>Carrier</b></th>
                        <th><b>Hotel</b></th>
                        <th><b>Tax</b></th>
                        <th><b>Total</b></th>
                    </tr>
				</thead>
                <tbody>
                <?php
                    $total_carrier = 0;
                    $total_hotel = 0;
                    $total_tax = 0;
                    $total_total = 0;
                ?>
                @foreach ($data_group as $row)
                    @if ($data['report_type'] == 'Detailed')
                        <tr class="">
                            <td>{{$row->start_time}}</td>
                            <td>{{$row->extension}}</td>
                            <td>{{$row->room}}</td>
                            <td>{{$row->called_no}}</td>
                            <td>{{$row->duration}}</td>
                            <td>{{$row->call_type}}</td>
                            <td>{{$row->country}}</td>
                            <td  class="right">{{$row->carrier_charges}}</td>
                            <td  class="right">{{$row->hotel_charges}}</td>
                            <td  class="right">{{$row->tax}}</td>
                            <td  class="right">{{$row->total_charges}}</td>
                        </tr>
                    @else
                        <tr style="display: none"><td colspan="11"></td></tr>
                    @endif
                    <?php
                        $total_carrier += $row->carrier_charges;
                        $total_hotel += $row->hotel_charges;
                        $total_tax += $row->tax;
                        $total_total += $row->total_charges;
                    ?>
					@endforeach
                    <tr class="">
                        @if ($data['report_type'] == 'Detailed')
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="text-align:right"><b>Total</b></td>
                        @else
                        <td style="text-align:right"><b>Total</b></td>
                        @endif
                        <td style="text-align:right"  class="right"><b>{{$total_carrier}}<b></td>
                        <td style="text-align:right"  class="right"><b>{{$total_hotel}}</b></td>
                        <td style="text-align:right"  class="right"><b>{{$total_tax}}</b></td>
                        <td style="text-align:right"  class="right"><b>{{$total_total}}</b></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
    @endif

    @if( $data['call_sort'] == 'All' || $data['call_sort'] == 'Admin Call' )
		<br/><br/>
    <h3 style="text-align:center"> Admin Call </h3>

    @foreach ($data['admin_call_list'] as  $key => $data_group)
        <div style="margin-top: 30px">
            <p style="margin: 0px">{{$key}}</p>
            <table class="grid">
                <thead >
					<tr >
						@if ($data['report_type'] == 'Detailed')
							<th><b>Time</b></th>
							<th><b>Extension</b></th>
							<th><b>User Name</b></th>
							<th><b>Called No</b></th>
							<th><b>Duration</b></th>
							<th><b>Call Type</b></th>
							<th><b>Destination</b></th>
						@else
							<th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
						@endif
						<th style="text-align:right"><b>Carrier</b></th>
					</tr>
				</thead>
                <tbody>
                <?php
                $total_carrier = 0;
                ?>
                @foreach ($data_group as $row)
                    @if ($data['report_type'] == 'Detailed')
                        <tr class="">
                            <td>{{$row->start_time}}</td>
                            <td>{{$row->extension}}</td>
                            <td>{{$row->wholename}}</td>
                            <td>{{$row->called_no}}</td>
                            <td>{{$row->duration}}</td>
                            <td>{{$row->call_type}}</td>
                            <td>{{$row->country}}</td>
                            <td>{{$row->carrier_charges}}</td>
                        </tr>
                    @else
                        <tr style="display: none"><td colspan="8"></td></tr>
                    @endif
                    <?php
                    $total_carrier += $row->carrier_charges;
                    ?>
                @endforeach
                <tr class="">
                    @if ($data['report_type'] == 'Detailed')
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="text-align:right"><b>Total</b></td>
                    @else
                        <td style="text-align:right"><b>Total</b></td>
                    @endif
                    <td style="text-align:right"  class="right"><b>{{$total_carrier}}</b></td>
                </tr>
                </tbody>
            </table>
        </div>

    @endforeach
    @endif

</body>
</html>