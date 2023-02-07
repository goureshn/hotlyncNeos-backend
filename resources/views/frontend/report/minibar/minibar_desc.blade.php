<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);

?>
<div id="block_container">
    <div id="bloc1" align="left">
        <img src="<?php echo $logo_image_data?>"
             alt=""  width = 150>
    </div>
    <div id="bloc2" align="right">
        <table class="plain" style="width:100%" align="right">
            <tr>
                <th class="plain1" align="right"><b>Date Generated :</b></th>
                <th class="plain1" align="left"><?php echo date('d-M-Y')?></th>
            </tr>
			@if (($data['report_type'] == 'Detailed' ) || ($data['report_type'] == 'Summary' ))
				@if(!empty($data['period']))
					<tr>
						<th class="plain1" align="right"><b>Period :</b></th>
						<th class="plain1" align="left"><?php echo $data['period']?></th>
					</tr>
				@endif
			
			@endif
            @if(!empty($data['room_tags']) && $data['room_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Room :</b></th>
                    <th class="plain1" align="left">
                        <?php
                        for($i= 0 ; $i < count($data['room_tags']) ; $i ++) {
                            echo $data['room_tags'][$i].', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif

            @if(!empty($data['staff_tags']) && $data['staff_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Posted By :</b></th>
                    <th class="plain1" align="left">
                        <?php
                        for($i= 0 ; $i < count($data['staff_tags']) ; $i ++) {
                            echo $data['staff_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif

            @if(!empty($data['item_tags']) && $data['item_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Service Item :</b></th>
                    <th class="plain1" align="left">
                        <?php
                        for($i= 0 ; $i < count($data['item_tags']) ; $i ++) {
                            echo $data['item_tags'][$i].', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif

        </table>
    </div>
</div>
