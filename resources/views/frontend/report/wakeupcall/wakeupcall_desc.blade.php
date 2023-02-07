<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
function converDate($val) {
        $date_val = date_format(new DateTime($val),'d-M-Y');
        return  $date_val;
    }
?>
<div id="block_container">
      
<div id="bloc1" align="left">
        <img src="<?php echo $logo_image_data?>" alt=""  width = 150>
    </div>
    <div id="bloc2" align="right">
        <table class="plain" style="width:100%" align="right">
            <tr>
                <th class="plain1" align="right" ><b>Date Generated :</b></th>
                <th class="plain1"  align="left"><?php echo date('d-M-Y')?></th>
            </tr>
            @if(!empty($data['period']))
                <tr>
                    <th class="plain1" align="right"><b>Period :</b></th>
                    <th class="plain1"  align="left"><?php echo $data['period']?></th>
                </tr>
            @endif

            @if(!empty($data['status_tags']) && $data['status_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Status :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['status_tags']) ; $i ++) {
                            echo $data['status_tags'][$i].' ';
                        }
                        ?>
                    </th>
                </tr>
            @endif

            @if(!empty($data['room_tags']) && $data['room_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Room :</b></th>
                    <th class="plain1" align="left">
                        <?php
                        for($i= 0 ; $i < count($data['room_tags']) ; $i ++) {
                            echo $data['room_tags'][$i],' ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
        </table>
     </div>
</div>


