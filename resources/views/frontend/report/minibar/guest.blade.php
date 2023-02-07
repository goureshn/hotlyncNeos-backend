<div style="margin-top: 30px">
    <table class=""  style="width : 100%;">
        <thead  >
            <tr>
                <th width=10%><b>Date</b></th>
                <th width=10%><b>Quantity</b></th>
                <th width=10%><b>Item</b></th>
                <th width=10%><b>Price</b></th>
                <th width=10%><b>Total</b></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_mount = 0;
            ?>
            @foreach ($data['datalist'] as  $key => $row)
               <tr class="">
                   <td class="left">{{$row->key_name}}</td>
                   <td class="center">{{$row->quantity}}</td>
                   <td class="left">{{$row->item_name}}</td>
                   <td class="right"><b>
                       {{$row->price}}
                       <?php
                       if($row->price=="Net Amount") $total_mount += $row->mount;
                       ?>
                   </b></td>
                   <td class="right"><b>
                           <?php
                           if($row->price=="Net Amount") {
                           ?>
                               {{$data['currency']}}
                           <?php
                               }
                           ?>
                           {{$row->mount}}</b></td>
                </tr>
				
            @endforeach
        </tbody>
    </table>
	<table class=""  style="width : 100%; border:0px" border="0">
	<tr>
		<td class ="plain" style="width:350px;text-align: right"><b>Municipality Fee @ <?php echo $data['muncip_fee']?>%</b></td>
		<td class ="right" style="width:80px;"><b>
			<?php
			$municip_fee = (($total_mount* $data['muncip_fee'])/100);
			?>{{round($municip_fee,2)}} </b></td>
	</tr>
	<tr>
		<td class ="plain" style="width:350px; text-align: right"><b>Service Charge @ <?php echo $data['ser_chrg']?>%</b></td>
		<td class ="right" style="width:80px;"><b>
			<?php
			$ser_chrg = (($total_mount* $data['ser_chrg'])/100); 
			?> {{round($ser_chrg,2)}}</b></td>
	</tr>
	<tr>
		<td class ="plain" style="width:350px;text-align: right"><b>VAT @ <?php echo $data['vat']?>%</b></td>
		<td class ="right" style="width:80px;"><b>
			<?php
			$vat = (($ser_chrg + $total_mount) * $data['vat'])/100;
			?>{{round($vat,2)}}</b></td>
	</tr>
	<tr>
		<td class ="plain" style="width:350px;text-align: right"><b>Gross Amount</b></td>
		<td class ="right" style="width:80px;"><b>
			<?php
			$gross_amount = $total_mount + $ser_chrg + $municip_fee  + $vat;
			?> {{$data['currency']}} {{round($gross_amount,2)}}</b></td>
   </tr>
   </table>
</div>
<div>
    <p>&nbsp;&nbsp;</p>
    <table class="grid" border="0" style="width : 100%;">
        <tr class ="">
            <td class="right" style="border: 0px;">
                Guest's Signature : ________________________________
            </td>
        </tr>
    </table>
</div>
