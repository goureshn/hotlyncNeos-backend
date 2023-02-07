<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);

?>
<div id="block_container">
    <div id="bloc1" align="center">
        <img src="<?php echo $logo_image_data?>"
             alt=""  width = 150>
    </div>
	<div>
	<table align="center">
			
				<td style="width: 50%; border: 0px;" valign="top">
                    <table class="plain" style="width:70%;border:0px;margin-left:50px;" border="0">
						<tr>
							<td class="plain1"><h6 align="center" style="margin-bottom:0;"><b>MINI BAR</b></h6></td>
						</tr>
						<tr>
							<td class="plain1"><h6 align="center" style="margin-bottom:0;"><b>TAX INVOICE</b></h6></td>
						</tr>
                        <tr>
                            <td class="plain1"><h4 align="center" style="margin-bottom:0;"><b>VAT No :<?php echo $data['vat_no']?></b></h4></td> 
							
                        </tr>
						 
                    </table>
                </td>
		
	</table>
	</div>
    <div>
         <table  style="width:100%;border:0px;" border="0">
             <tr>
                 <!--left--->
                <td style="width: 50%; border: 0px;" valign="top">
                    <table class="plain" style="width:100%;border:0px !important;" border="0">
                        <tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Property Address :</b></td>
                            <td class="plain1"  align="left"><?php echo $data['property']->address?></td>
                        </tr>
                        <tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Property City :</b></td>
                            <td class="plain1" align="left"><?php echo $data['property']->city?></td>
                        </tr>
                        <tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Property Country :</b></td>
                            <td class="plain1" align="left"><?php echo $data['property']->country?></td>
                        </tr>
						
                    </table>
                </td>
				
                 <!--right-->
                <td style="width: 50%;border: 0px;" valign="top">
                    <table class="plain" style="width:100%;border: 0px;margin-left: 100px;" border="0">
						<tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Date Generated :</b></td>
                            <td class="plain1" align="left"><?php echo date('d-M-Y')?></td>
                        </tr>
                        @if(!empty($data['period']))
                            <tr>
                                <td class="plain1" style="width:80px;text-align: right"><b>Period :</b></td>
                                <td class="plain1" align="left"><?php echo $data['period']?></td>
                            </tr>
                        @endif

                        @if(!empty($data['room_tags']) && $data['room_tags'] != null)
                            <tr>
                                <td class="plain1" style="width:80px;text-align: right"><b>Room :</b></td>
                                <td class="plain1" align="left">
                                    <?php
                                    for($i= 0 ; $i < count($data['room_tags']) ; $i ++) {
                                        echo $data['room_tags'][$i].', ';
                                    }
                                    ?>
                                </td>
                            </tr>
                        @endif

                        @if(!empty($data['staff_tags']) && $data['staff_tags'] != null)
                            <tr>
                                <td class="plain1" style="width:80px;text-align: right"><b>Posted By :</b></td>
                                <td class="plain1" align="left">
                                    <?php
                                    for($i= 0 ; $i < count($data['staff_tags']) ; $i ++) {
                                        echo $data['staff_tags'][$i],', ';
                                    }
                                    ?>
                                </td>
                            </tr>
                        @endif

                        @if(!empty($data['item_tags']) && $data['item_tags'] != null)
                            <tr>
                                <td class="plain1" style="width:80px;text-align: right"><b>Service Item :</b></td>
                                <td class="plain1" align="left">
                                    <?php
                                    for($i= 0 ; $i < count($data['item_tags']) ; $i ++) {
                                        echo $data['item_tags'][$i].', ';
                                    }
                                    ?>
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
             </tr>
         </table>
    </div>
    <div>
        <table  style="width:100%;border:0px;" border="0">
            <tr>
                <!--left--->
                <td style="width: 50%; border: 0px;" valign="top">
                    <table class="plain" style="width:100%;border:0px !important;" border="0">
                        <tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Guest Name :</b></td>
                            <td class="plain1"  ><?php echo $data['guest_name']?></td>
                        </tr>
                        <tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Check In :</b></td>
                            <td class="plain1"  align="left"><?php echo date_format(new DateTime($data['checkin']),'d-M-Y') ?></td>
                        </tr>
                    </table>
                </td>
                <!--right-->
                <td style="width: 50%;border: 0px;" valign="top">
                    <table  style="width:100%;border: 0px; margin-left: 100px;" border="0">
						
                        <tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Room :</b></td>
                            <td class="plain1" align="left"><?php echo $data['room']?></td>
                        </tr>
                        <tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Check Out :</b></td>
                            <td class="plain1" align="left"><?php echo date_format(new DateTime($data['checkout']),'d-M-Y')?></td>
                        </tr>
						<tr>
                            <td class="plain1" style="width:80px;text-align: right"><b>Invoice No :</b></td>
                            <td class="plain1" align="left"><?php echo $data['id']?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>
