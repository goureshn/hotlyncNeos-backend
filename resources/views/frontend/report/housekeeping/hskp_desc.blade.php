<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
?>
<div id="block_container">
    <div align="left" id="bloc1">
        <img src="<?php echo $logo_image_data?>" alt=""  width = 150>
    </div>
    <div align="right" id="bloc2">
    <table class="plain" style="width:100%" align="right">
            <tr>
                <th class="plain1" align="right" ><b>Date Generated :</b></th>
                <th class="plain1" align="left"  ><?php echo date('d-M-Y')?></th>
            </tr>
            @if(!empty($data['period']) && $data['report_type'] != 'Task Sheet')
                <tr>
                    <th class="plain1" align="right"><b>Period :</b></th>
                    <th class="plain1"align="left" ><?php echo $data['period']?></th>
                </tr>
            @endif
        </table>
    </div>
</div>
