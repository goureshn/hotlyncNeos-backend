<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
?>
<div id="block_container">
    <div id="bloc1" align="left" valign="top">
        <img src="<?php echo $logo_image_data?>"
             alt=""  width = 150>
    </div>
    <div id="bloc2" align = "right">
        <table class="plain" style="width:100%" align = "right">
            <tr>
                <th class="plain1"  align="right"><b>Date Generated :</b></th>
                <th class="plain1"  align="left"><?php echo date('d-M-Y')?></th>
            </tr>
            <tr>
                <th class="plain1" align="right"><b>Period :</b></th>
                <th class="plain1"   align="left"><?php echo $data['period']?></th>
            </tr>
            <tr>
                <th class="plain1" align="right"><b>Property :</b></th>
                <th class="plain1"  align="left"><?php echo $data['property']->name ?></th>
            </tr>
            @if($data['building'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Building :</b></th>
                <th class="plain1"   align="left"><?php echo $data['building'] ?></th>
            </tr>
            @endif
            @if($data['extenstion_type'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Extension Type :</b></th>
                <th class="plain1"   align="left">{{$data['extenstion_type']}}</th>
            </tr>
            @endif
            @if($data['department'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Department :</b></th>
                <th class="plain1"   align="left">{{$data['department']}}</th>
            </tr>
            @endif
            @if($data['room'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Room :</b></th>
                <th class="plain1"   align="left">{{$data['room']}}</th>
            </tr>
            @endif
            @if($data['extension'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Extension :</b></th>
                <th class="plain1"   align="left">{{$data['extension']}}</th>
            </tr>
            @endif
            @if($data['destination'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Destination :</b></th>
                <th class="plain1"  align="left">{{$data['destination']}}</th>
            </tr>
            @endif
            @if($data['access_code'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Access Code :</b></th>
                <th class="plain1"  align="left">{{$data['access_code']}}</th>
            </tr>
            @endif

            @if($data['called_no'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Called Number :</b></th>
                <th class="plain1"  align="left">{{$data['called_no']}}</th>
            </tr>
            @endif
        </table>
    </div>
</div>
