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
    </style>
</head>
<body>
<?php
$http = 'http://';
if( isset($_SERVER['HTTPS'] ) )
    $http = 'https://';
    $port = $_SERVER['SERVER_PORT'];
    $siteurl = $http . $_SERVER['SERVER_NAME'] . ':' . $port . '/';
?>

<div>
    <span><b>Date:</b> {{$data['report_date']}}</span>
    <span style="float:right"><b>Room:</b> {{$data['room']}}</span>
</div>   

<div>
    <span><b>Attendant:</b> {{$data['attendant_name']}}</span>
    <span style="float:right"><b>Supervisor:</b> {{$data['supervisor_name']}}</span>
</div>   


<div style="text-align: center">
    <b>Room Inspection Checklist</b>
</div>    
<div>
    <table  border="0" style="width : 100%;">
        <thead style="background-color:#ffffff">
            <tr class="plain">                    
                <th align="center"><b>Category</b></th>
                <th align="center"><b>Item</b></th>
                <th align="center"><b>Result</b></th>                
                <th align="center"><b>Comments</b></th>
            </tr>   
        </thead>
        <tbody>
        @foreach ($data['group_list'] as $row)
            @foreach ($row->items as $key => $row1)
                <tr class="plain">                    
                    <td align="center">{{$key == 0 ? $row->name : ''}}</td>
                    <td align="center">{{$row1->item_name}}</td>
                    <td align="center">
                        @if($row1->result == 1)
                            <img style="width:12px;height:12px;" src="{{$data['tick_icon_base64']}}"/>
                        @else
                            <img style="width:12px;height:12px;" src="{{$data['cancel_icon_base64']}}"/>
                        @endif
                    </td>                    
                    <td align="center">{{$row1->comment}}</td>
                </tr>
            @endforeach        
        @endforeach
        </tbody>
    </table>
</div>


</body>
</html>