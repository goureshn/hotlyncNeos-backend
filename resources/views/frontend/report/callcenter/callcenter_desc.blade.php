<?php
$path = $_SERVER['DOCUMENT_ROOT'] . '/frontpage/img/hotlync_.png';
$type = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$hotlync_mark_image_data = 'data:image/' . $type . ';base64,' . base64_encode($image_data);

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
    <div id="bloc1" align="left" valign="top">
        <img src="<?php echo $logo_image_data?>"
             alt=""  width = 150>
    </div>
    <div id="bloc2"  align="right">
        <table class="plain" style="width:100%;" align="right">
            <tr>
                <th class="plain1" align="right"><b>Date Period :</b></th>
                <th class="plain1"  align="left">{{converDate($data['start_date'])}} to {{converDate($data['end_date'])}}</th>
            </tr>
            <tr>
                <th class="plain1" align = "right"><b>Agent :</b></th>
                <th class="plain1"  align="left">{{$data['agentlist']}}</th>
            </tr>
            <tr>
                <th class="plain1" align = "right"><b>Time :</b></th>
                <th class="plain1"  align="left">{{$data['start_time']}} - {{$data['end_time']}}</th>
            </tr>
        </table>
    </div>
</div>
