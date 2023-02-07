<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
function converDate($val) {
        $date_val1 = date_format(new DateTime($val),'d-M-Y');
        return  $date_val1;
    }
?>
<div id="block_container">
    <div id="bloc1" align="left">
        <img src="<?php echo $logo_image_data?>"
             alt=""  width = 150>
    </div>
    <div id="bloc2" align="right">
        <table class="plain" style="width:100%" align="right">
            <tr>
                <th class="plain1" align="right"><b>Report Type :</b></th>
                <th class="plain1" align="left"><?php echo $data['report_type']?></th>
            </tr>
            <tr>
                <th class="plain1" align="right"><b>Report By :</b></th>
                <th class="plain1" align="left"><?php echo $data['report_by'] ?></th>
            </tr>
            @if ($data['report_by'] != 'Comparison')
            @if ($data['call_type'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Call Type :</b></th>
                <th class="plain1" align="left"><?php echo $data['call_type'] ?></th>
            </tr>
            @endif
            @if ($data['classify'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Call Classification :</b></th>
                <th class="plain1"  align="left"><?php echo $data['classify'] ?></th>
            </tr>
            @endif
            @if ($data['approval'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Approval :</b></th>
                <th class="plain1"  align="left"><?php echo $data['approval'] ?></th>
            </tr>
            @endif
            @if (($data['report_by'] == 'Cost Comparison') || ($data['report_by'] == 'Call Status') || ($data['report_by'] == 'Summary Cost Comparison'))
            @if($data['call_sort'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Call Sort :</b></th>
                <th class="plain1"   align="left">{{$data['call_sort'] }}</th>
            </tr>
            @endif
            @endif
            @else
            @if ($data['filterby'] != 'All')
            <tr>
                <th class="plain1" align="right"><b>Comparison by :</b></th>
                <th class="plain1"  align="left"><?php echo $data['filterby'] ?></th>
            </tr>
            @endif
            @endif
            <tr>
                <th class="plain1" align="right"><b>Date :</b></th>
                <th class="plain1" align="left"><?php echo $data['period'] ?></th>
            </tr>
        </table>
    </div>
</div>
