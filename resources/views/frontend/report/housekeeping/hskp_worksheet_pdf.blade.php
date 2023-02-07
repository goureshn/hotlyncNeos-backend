<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <style type="text/Css">
        .pagenum:after {
            content: counter(page);
        }
        @media screen {
            div.footer {
                position: fixed;
                bottom: 0;
            }
        }
        table, p, b, span{
            font-family: 'Titillium Web';
            <?php if(empty($data['font-size'])) {?>
            font-size: 12px;
            <?php }else { ?>
            font-size: {{$data['font-size']}};
            <?php } ?>
            -webkit-font-smoothing: antialiased;
            line-height: 1.42857143;
            border-spacing: 0;
        }
        hr{
            line-height: 0.5;
            background:#00a8f3;
        }
        tr, td {
            border: 1px solid #ECEFF1;
            color: #212121;
        }
        .inform {
            border: 0px ;
            text-align: left;
            height:25px;
        }
        td.right {
            text-align: right;
            padding-right: 5px;;
        }

        th {
            align:center;
            vertical-align:middle;
            background-color:#2c3e50 !important;
            color: #fff !important;
        }
        .total-amount {
            background-color:#E0E0E0 !important;
            color:#fff !important;
            font-weight:bold;
        }
        .grid tr:nth-child(even) {
            background-color: #F5F5F5;
        }
        .plain1 {
            border:0 !important;
            <?php if(empty($data['font-size'])) {?>
             font-size: 12px;
            <?php }else { ?>
            font-size: {{$data['font-size']}};
            <?php } ?>
            vertical-align:middle;
            background-color:#fff !important;
            color: #212121 !important;
        }
        #block_container {
            text-align:center;
        }
        #bloc2 {
            display:inline-block;
            width:49%;
            float:right;
            color: #212121 !important;
        }
        li {
            list-style:none;
        }
        #bloc1 {
            display:inline-block;
            width:49%;
        }
        label {
            padding-left: 3px;;
            padding-right: 7px;
            font-size: 13px;
        }
        .yellow {
            color:#f8c77a;
        }
        .red {
            color:#f13d5b;;
        }
        .green {
            color:#30bb29;
        }
        #bloc1 {
            display:inline-block;
            width:49%;
        }
    </style>
</head>
<body>
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

<div style= "margin-top: 20px">
    <table  border="0" style="width : 100%;">
        <thead style="background-color:#ffffff">
            <tr class="plain">                    
                <th align="center"><b>Room</b></th>
                <th align="center"><b>Type</b></th>
                <th align="center"><b>Room Status</b></th>                
                <th align="center"><b>FO Status</b></th>
                <th align="center"><b>Credit</b></th>
                <th align="center"><b>Res Status</b></th>
                <th align="center"><b>Guest Name</b></th>
                <th align="center"><b>VIP</b></th>
                <th align="center"><b>Arrival</b></th>
                <th align="center"><b>Departure</b></th>
                <th align="center"><b>Adult</b></th>
                <th align="center"><b>Child</b></th>

            </tr>   
        </thead>
        <tbody>
        @foreach ($data['room_list'] as $row)
            
                <tr class="plain">                    
                    <td align="center">{{$row->room}}</td>
                    <td align="center">{{$row->type}}</td>
                    <td align="center">{{$row->rm_state}}</td>
                    <td align="center">{{$row->occupancy}}</td>
                    <td align="center">{{$row->credits}}</td>
                    <td align="center">{{$row->fo_state}}</td>
                    <td align="center">{{$row->guest_name}}</td>
                    <td align="center">{{$row->vip_name}}</td>
                    <td align="center">{{$row->arrival}}</td>
                    <td align="center">{{$row->departure}}</td>
                    <td align="center">{{$row->adult}}</td>                 
                    <td align="center">{{$row->chld}}</td>
                </tr>
              
        @endforeach
        </tbody>
    </table>
</div>


</body>
</html>