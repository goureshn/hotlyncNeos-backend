<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);

?>

<div id="block_container">
    <div id="bloc1" align="left" >
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
                <th class="plain1" align="right"><b>Period :</b></th>
                <th class="plain1" align="left"><?php echo $data['period']?></th>
            </tr>
            @if(!empty($data['status'])  && $data['status'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Status :</b></th>
                    <th class="plain1"  align="left">{{$data['status']}}</th>
                </tr>
            @endif
            @if(!empty($data['category'])  && $data['category'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Category :</b></th>
                    <th class="plain1"  align="left">{{$data['category']}}</th>
                </tr>
            @endif
            @if(!empty($data['ticket_type']) && $data['ticket_type'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Ticket Type :</b></th>
                    <th class="plain1" align="left">{{$data['ticket_type']}}</th>
                </tr>
            @endif
            @if($data['report_by'] != 'Amenities' )
            @if($data['department'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Department :</b></th>
                    <th class="plain1" align="left">
                        <?php
                        for($i= 0 ; $i < count($data['department']) ; $i ++) {
                            echo $data['department'][$i];
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @endif
            @if(!empty($data['location'])&& $data['location'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Location :</b></th>
                    <th class="plain1"  align="left">
                    <?php
                        for($i= 0 ; $i < count($data['location']) ; $i ++) {
                         echo $data['location'][$i]->name;
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['item']) && $data['item'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Item :</b></th>
                    <th class="plain1" align="left">
                        <?php
                        for($i= 0 ; $i < count($data['item']) ; $i ++) {
                            echo $data['item'][$i];
                        }
                        ?>
                    </th>
                </tr>
            @endif

            @if($data['report_by'] != 'Amenities' )
                @if(!empty($data['staff']) && $data['staff'] != null)
                    <tr>
                        <th class="plain1" align="right"><b>Staff :</b></th>
                        <th class="plain1" align="left">
                            <?php
                            for($i= 0 ; $i < count($data['staff']) ; $i ++) {
                                echo $data['staff'][$i]->staffname.", ";
                            }
                            ?>
                        </th>
                    </tr>
                @endif
            @endif
        </table>
    </div>
</div>
