<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
if(file_exists($path)) {
    $image_data = file_get_contents($path);
    $logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
}else {
    $logo_image_data = '';
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
                <th class="plain1"  align="right" ><b>Date Generated :</b></th>
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
                            echo $data['status_tags'][$i].', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif

            @if(!empty($data['guest_type_tags']) && $data['guest_type_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Guest Type :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['guest_type_tags']) ; $i ++) {
                            echo $data['guest_type_tags'][$i],', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif

            @if(!empty($data['property_tags']) && $data['property_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Property :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['property_tags']) ; $i ++) {
                            echo $data['property_tags'][$i].', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif

            @if(!empty($data['building_tags']) && $data['building_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Building :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['building_tags']) ; $i ++) {
                            echo $data['building_tags'][$i].', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif


            @if(!empty($data['location_tags']) && $data['location_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Location :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                            if( count($data['location_list']) > 0 )
                            {
                                echo implode(',', array_map(function($row) {
                                    return $row->name;
                                }, $data['location_list']));
                            }
                            else
                                echo 'All';                        
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['dept_list']) && $data['dept_list'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Department :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                            echo $data['dept_list'];                        
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['serverity_tags']) && $data['serverity_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Serverity :</b></th>
                    <th class="plain1"  align="left">
                        <?php
                        for($i= 0 ; $i < count($data['serverity_tags']) ; $i ++) {
                            echo $data['serverity_tags'][$i].', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['main_category_tags']) && $data['main_category_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Main Category :</b></th>
                    <th class="plain1" align="left">
                        <?php
                        for($i= 0 ; $i < count($data['main_category_tags']) ; $i ++) {
                            echo $data['main_category_tags'][$i].', ';
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
                            echo $data['category_tags'][$i].', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
            @if(!empty($data['sub_category_tags']) && $data['sub_category_tags'] != null)
                <tr>
                    <th class="plain1" align="right"><b>Sub Category :</b></th>
                    <th class="plain1" align="left">
                        <?php
                        for($i= 0 ; $i < count($data['sub_category_tags']) ; $i ++) {
                            echo $data['sub_category_tags'][$i].', ';
                        }
                        ?>
                    </th>
                </tr>
            @endif
        </table>
    </div>
</div>
