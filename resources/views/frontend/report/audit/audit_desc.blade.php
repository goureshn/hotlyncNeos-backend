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
            <tr>
                <th class="plain1" align="right"><b>Property :</b></th>
                <th class="plain1"  align="left"><?php echo $data['property']->name ?></th>
            </tr>
            <tr>
                <th class="plain1" align="right"><b>Audit Report for :</b></th>
                <th class="plain1"  align="left"><?php echo $data['report_type'] ?></th>
            </tr>
            @if($data['report_by'] !='Extension' && $data['report_by'] != 'Guest Rate Charges'  )
            <tr>
                <th class="plain1" align="right"><b>Group By :</b></th>
                <th class="plain1"  align="left">{{$data['report_by']}}</th>
            </tr>
            @endif
        </table>
    </div>
</div>
