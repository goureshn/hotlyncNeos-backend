<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
?>
<div id="block_container">
    <div align="left" id="bloc1">
        <img src="<?php echo $logo_image_data?>" alt=""  width = 70>
    </div>
    <div align="right" id="bloc2">
        <table class="plain" style="width:100%">
            <tr>
                <th class="plain1" align="right"><b>Date :</b></th>
                <th class="plain1"  align="left">
                     {{date('d')}}<sup>th</sup> {{date('M Y')}}
                </th>
            </tr>
            <tr>
                <th class="plain1" align="right"><b>Period :</b></th>
                <th class="plain1"   align="left">
                      {{$data['period']}}
                </th>
            </tr>
            @if(!empty($data['location_tags']) && $data['location_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Location :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['location_tags']) ; $i ++) {
                            echo $data['location_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['equip_tags']) && $data['equip_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Equipment :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['equip_tags']) ; $i ++) {
                            echo $data['equip_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['equip_id_tags']) && $data['equip_id_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Equipment ID :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['equip_id_tags']) ; $i ++) {
                            echo $data['equip_id_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['status_tags']) && $data['status_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>WR Status :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['status_tags']) ; $i ++) {
                            echo $data['status_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['staff_tags']) && $data['staff_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Staff :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['staff_tags']) ; $i ++) {
                            echo $data['staff_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['wo_status_tags']) && $data['wo_status_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>WO Status :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['wo_status_tags']) ; $i ++) {
                            echo $data['wo_status_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['category_tags']) && $data['category_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Category :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['category_tags']) ; $i ++) {
                            echo $data['category_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
        </table>
    </div>
</div>
