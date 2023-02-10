<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
?>
<div id="block_container">
    <div id="bloc1" align="left">
        <img src="{{$logo_image_data}}"
             alt=""  width = 150>
    </div>

    <div id="bloc2" >
        <table class="plain" style="width:100%;" align="right">
            <tr>
                <th class="plain1" align="right" ><b>Date:</b></th>
                <th class="plain1" style="padding-left:25px;" width="260px" align="left">{{$data['generate_date']}} </th>
            </tr>
            <tr>
                <th class="plain1" align="right" ><b>Period:</b></th>
                <th class="plain1" style="padding-left:25px;" width="260px" align="left">{{$data['period']}} </th>
            </tr>
            <tr>
                <th class="plain1" align="right"><b>Type:</b></th>
                <th class="plain1" style="padding-left:25px;" width="200px" align="left">Detail</th>
            </tr>
            {{--<tr>--}}
                {{--<th class="plain1" align="right" style="height:10px"><b>Extension :</b></th>--}}
                {{--<th class="plain1" style="padding-left:25px;height:13px" width="220px" align="left">{{$data['extension']}}</th>--}}
            {{--</tr>--}}
            {{--<tr>--}}
                {{--<th class="plain1" align="right" style="height:13px"><b>Call Type :</b></th>--}}
                {{--<th class="plain1" style="padding-left:25px;height:13px" width="220px" align="left">{{$data['call_type']}}</th>--}}
            {{--</tr>--}}
        </table>
    </div>
</div>
