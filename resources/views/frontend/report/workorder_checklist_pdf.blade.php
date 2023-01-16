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
    $prev_category_name = '';
?>

<div style="text-align: center">
    <b>{{$data['name']}} Checklist</b>
</div>    
<div>
    <table  border="0" style="width : 100%;">
        <thead style="background-color:#ffffff">
            <tr class="plain">                    
                <th align="center"><b>Category</b></th>
                <th align="center"><b>Task</b></th>
                <th align="center"><b>Yes/No</b></th>
                <th align="center"><b>Reading</b></th>   
                <th align="center"><b>Attachments</b></th>                
                <th align="center"><b>Comments</b></th>
            </tr>   
        </thead>
        <tbody>
        @foreach ($data['checklist'] as $row)
            <tr class="plain">                    
                <td align="center">
                    <?php
                        if( $prev_category_name != $row->category_name )
                        {
                            $prev_category_name = $row->category_name;
                            echo $prev_category_name;
                        }
                    ?>
                </td>
                <td align="center">{{$row->item_name}}</td>
                <td align="center">
                    @if($row->item_type == "Yes/No")
                        @if($row->yes_no == 1)
                            <img style="width:12px;height:12px;" src="{{$data['tick_icon_base64']}}"/>
                        @else
                            <img style="width:12px;height:12px;" src="{{$data['cancel_icon_base64']}}"/>
                        @endif
                    @endif    
                </td>    
                <td align="center">
                    @if($row->item_type == "Reading")
                        {{$row->reading}}
                    @endif    
                </td>  
                
                <td width="100">                    
                    @foreach($row->base64_image_list as $row1)                                                          
                        <a href="{{$row1['url']}}"><img style="width:20px;height:20px;" src="{{$row1['base64']}}"/></a>                                
                    @endforeach                    
                </td>     
                                   
                <td align="center">{{$row->comment}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>


</body>
</html>