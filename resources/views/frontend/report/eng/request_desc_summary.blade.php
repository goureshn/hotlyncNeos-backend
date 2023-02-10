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
                <th class="plain1"  align="right">
                    <!--                    --><?php //echo date('d-M-Y')?>
                    <b>Date :</b> {{date('d')}}<sup>th</sup> {{date('M Y')}}
                </th>
            </tr>
            <tr>
                <th class="plain1"   align="right">
                    <!--                    --><?php //echo $data['period']?>
                    <b>Period :</b>  {{$data['start_date']}} to {{$data['end_date']}}
                </th>
            </tr>
        </table>
    </div>
</div>
